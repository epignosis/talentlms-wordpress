<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

use TalentlmsIntegration\Services\PluginService;

/**
 * Owns the WP-user -> TalentLMS-user id binding lifecycle that backs the secure
 * password sync in src/Woocommerce.php:
 *
 *  - bind-on-login   : links an existing account-holder on their next login.
 *  - one-time backfill: links every existing account-holder after an upgrade,
 *                       via a batched WP-Cron job (bind-on-creation lives in
 *                       Utils::tlms_enrollUserToCoursesByOrderId).
 *
 * The backfill is version-gated so it runs once per version and never on every
 * page load, and it only *binds* accounts that already exist in TalentLMS — it
 * never creates any.
 */
class UserSync implements PluginService
{
    const DB_VERSION_OPTION      = 'tlms-db-version';
    const BACKFILL_STATUS_OPTION = 'tlms-backfill-status';
    const BACKFILL_LOCK_OPTION   = 'tlms-backfill-lock';
    const BACKFILL_STALL_OPTION  = 'tlms-backfill-stalls';
    const BACKFILL_HOOK          = 'tlms_run_user_backfill';

    const STATUS_PENDING  = 'pending';
    const STATUS_RUNNING  = 'running';
    const STATUS_COMPLETE = 'complete';

    // Conservative batch to respect the TalentLMS API rate limits.
    const BATCH_SIZE = 25;
    // Give up automatic passes after this many consecutive no-progress batches
    // (e.g. a sustained API outage); the remaining users bind lazily on login.
    const MAX_STALLS = 5;

    public function register(): void
    {
        // The cron worker must be registered on every load so WP-Cron can fire it
        // regardless of admin context. Registering a callback is cheap.
        add_action(self::BACKFILL_HOOK, array($this, 'tlms_runBackfillBatch'));

        // Link an existing account-holder the next time they log in.
        add_action('wp_login', array($this, 'tlms_bindOnLogin'), 10, 2);

        // Admin-only: schedule the backfill and surface progress / a manual trigger.
        add_action('admin_init', array($this, 'tlms_maybeScheduleBackfill'));
        add_action('admin_notices', array($this, 'tlms_backfillAdminNotice'));
        add_action('admin_post_' . self::BACKFILL_HOOK, array($this, 'tlms_handleRunNow'));
    }

    /**
     * Runs on admin_init (a cheap couple of option reads). On a version change it
     * flags that existing users need linking; while a backfill is outstanding and
     * the API is configured it makes sure a single cron event is queued.
     */
    public function tlms_maybeScheduleBackfill(): void
    {
        if (get_option(self::DB_VERSION_OPTION) !== TLMS_VERSION) {
            if (get_option(self::BACKFILL_STATUS_OPTION) !== self::STATUS_COMPLETE) {
                update_option(self::BACKFILL_STATUS_OPTION, self::STATUS_PENDING);
            }
            update_option(self::DB_VERSION_OPTION, TLMS_VERSION);
        }

        if ($this->tlms_backfillOutstanding() && self::tlms_apiConfigured()) {
            self::tlms_ensureScheduled();
        }
    }

    /**
     * Starts the backfill once the API becomes usable (called from the settings
     * save in Pages\Admin, which is the moment credentials are first stored).
     */
    public static function tlms_startBackfill(): void
    {
        if (get_option(self::BACKFILL_STATUS_OPTION) !== self::STATUS_COMPLETE) {
            update_option(self::BACKFILL_STATUS_OPTION, self::STATUS_PENDING);
        }
        if (self::tlms_apiConfigured()) {
            self::tlms_ensureScheduled();
        }
    }

    /**
     * Batched worker: resolves a slice of not-yet-linked users and reschedules
     * itself until none remain. Mirrors WordPress core's _wp_batch_update_comment_type
     * lock/schedule/complete pattern.
     */
    public function tlms_runBackfillBatch(): void
    {
        if (! self::tlms_apiConfigured()) {
            return; // cannot resolve without credentials; re-kicked once configured
        }

        if (! $this->tlms_acquireLock()) {
            return; // another pass holds a fresh lock
        }

        update_option(self::BACKFILL_STATUS_OPTION, self::STATUS_RUNNING);

        $userIds = get_users(array(
            'fields'     => 'ID',
            'number'     => self::BATCH_SIZE,
            'orderby'    => 'ID',
            'order'      => 'ASC',
            'meta_query' => array(
                array(
                    'key'     => Utils::TLMS_USER_ID_META,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ));

        if (empty($userIds)) {
            $this->tlms_finish();
            return;
        }

        $resolved = 0;
        foreach ($userIds as $userId) {
            $wpUser = get_userdata((int) $userId);
            if (! $wpUser) {
                // No account to read an email from — stop selecting this user.
                Utils::tlms_markNoLmsAccount((int) $userId);
                $resolved++;
                continue;
            }
            if (Utils::tlms_resolveAndBindByEmail((int) $userId, $wpUser->user_email)) {
                $resolved++;
            }
        }

        // Stall detection: a whole batch with no progress means the API is likely
        // down. Back off, and after MAX_STALLS give up the automatic pass so cron
        // does not churn forever — remaining users still bind on their next login.
        if ($resolved === 0) {
            $stalls = (int) get_option(self::BACKFILL_STALL_OPTION, 0) + 1;
            update_option(self::BACKFILL_STALL_OPTION, $stalls);
            if ($stalls >= self::MAX_STALLS) {
                Utils::tlms_recordLog(
                    'TalentLMS user backfill halted after repeated API failures; '
                    . 'remaining users will be linked on their next login.'
                );
                $this->tlms_finish();
                return;
            }
        } else {
            delete_option(self::BACKFILL_STALL_OPTION);
        }

        $this->tlms_releaseLock();

        // More users remain — queue the next batch.
        wp_schedule_single_event(time() + (2 * MINUTE_IN_SECONDS), self::BACKFILL_HOOK);
    }

    /**
     * Links a returning user on login, but only if they have never been processed
     * (absent meta). Bound users and "no account" sentinels are skipped, so there
     * are no per-login API calls once a user has been resolved.
     */
    public function tlms_bindOnLogin($user_login, $user): void
    {
        if (! ($user instanceof \WP_User)) {
            return;
        }
        if (get_user_meta($user->ID, Utils::TLMS_USER_ID_META, true) !== '') {
            return; // already bound or sentinel-marked
        }
        if (! self::tlms_apiConfigured()) {
            return;
        }
        Utils::tlms_resolveAndBindByEmail((int) $user->ID, $user->user_email);
    }

    /**
     * Progress notice on the TalentLMS admin screens, with a "Run now" button.
     */
    public function tlms_backfillAdminNotice(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        if (! $this->tlms_backfillOutstanding()) {
            return;
        }
        $remaining = $this->tlms_countUnprocessed();
        if ($remaining === 0) {
            $this->tlms_finish();
            return;
        }
        if (! function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if (! $screen || strpos($screen->id, 'talentlms') === false) {
            return; // only on our own pages
        }

        $runNowUrl = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::BACKFILL_HOOK),
            self::BACKFILL_HOOK
        );

        printf(
            '<div class="notice notice-warning"><p>%s</p><p><a class="button button-primary" href="%s">%s</a></p></div>',
            esc_html(sprintf(
                /* translators: %d: number of WordPress users still to be linked */
                __(
                    'TalentLMS: linking existing users to their TalentLMS accounts (%d remaining). '
                    . 'Password changes for not-yet-linked users are not synced until this finishes.',
                    'talentlms'
                ),
                $remaining
            )),
            esc_url($runNowUrl),
            esc_html__('Run now', 'talentlms')
        );
    }

    /**
     * Manual trigger: runs one batch synchronously for immediate feedback; the
     * batch reschedules the remainder via cron.
     */
    public function tlms_handleRunNow(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'talentlms'));
        }
        check_admin_referer(self::BACKFILL_HOOK);

        // Drop any stale lock so the manual run is not blocked, then run a batch.
        $this->tlms_releaseLock();
        $this->tlms_runBackfillBatch();

        wp_safe_redirect(wp_get_referer() ?: admin_url('admin.php?page=talentlms'));
        exit;
    }

    private function tlms_finish(): void
    {
        update_option(self::BACKFILL_STATUS_OPTION, self::STATUS_COMPLETE);
        delete_option(self::BACKFILL_STALL_OPTION);
        $this->tlms_releaseLock();
        wp_clear_scheduled_hook(self::BACKFILL_HOOK);
    }

    private function tlms_backfillOutstanding(): bool
    {
        $status = get_option(self::BACKFILL_STATUS_OPTION);
        return $status === self::STATUS_PENDING || $status === self::STATUS_RUNNING;
    }

    private static function tlms_apiConfigured(): bool
    {
        return ! empty(get_option('tlms-domain')) && ! empty(get_option('tlms-apikey'));
    }

    private static function tlms_ensureScheduled(): void
    {
        if (! wp_next_scheduled(self::BACKFILL_HOOK)) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS, self::BACKFILL_HOOK);
        }
    }

    private function tlms_countUnprocessed(): int
    {
        $query = new \WP_User_Query(array(
            'fields'      => 'ID',
            'number'      => 1,
            'count_total' => true,
            'meta_query'  => array(
                array(
                    'key'     => Utils::TLMS_USER_ID_META,
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ));

        return (int) $query->get_total();
    }

    /**
     * Short-lived DB lock (INSERT IGNORE, mirroring WP core) so overlapping cron
     * fires do not double-process a batch.
     */
    private function tlms_acquireLock(): bool
    {
        global $wpdb;
        $now = time();

        $acquired = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) "
            . "VALUES (%s, %s, 'no')",
            self::BACKFILL_LOCK_OPTION,
            $now
        ));

        if ($acquired) {
            return true;
        }

        // A lock row exists — honour it only while it is fresh.
        $lockTime = (int) get_option(self::BACKFILL_LOCK_OPTION);
        if ($lockTime && $lockTime > ($now - HOUR_IN_SECONDS)) {
            return false;
        }

        // Stale lock: take it over.
        update_option(self::BACKFILL_LOCK_OPTION, $now);
        return true;
    }

    private function tlms_releaseLock(): void
    {
        delete_option(self::BACKFILL_LOCK_OPTION);
    }
}
