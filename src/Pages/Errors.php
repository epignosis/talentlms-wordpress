<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration\Pages;

use Exception;
use TalentLMS;
use TalentLMS_ApiError;
use TalentlmsIntegration\Services\PluginService;
use TalentlmsIntegration\Utils;

class Errors implements PluginService
{
	public $talentlmsAdminErrors = array();  // Stores all the errors that need to be displayed to the admin.
    public $screen_id;

    public function register(): void
    {
        add_action(
            'admin_notices',
            array( $this, 'tlms_showWarnings' )
        );
    }

    /**
     * Logs the error and stores it, so it can be displayed to the admin.
     *
     * @param string $message
     */
    public function tlms_logError(string $message): void
    {
        $this->talentlmsAdminErrors[] = esc_html($message);
        Utils::tlms_recordLog($message);
    }

    /**
     * Used to display the stored errors to the admin.
     *
     * @return void
     */
    public function tlms_showWarnings(): void
    {
        if (( defined('DOING_AJAX') && DOING_AJAX )
            || ! is_admin()
        ) {
            die();
        }

        $screen_id = get_current_screen()->id;

        if ($screen_id === 'toplevel_page_talentlms'
            || $screen_id == 'talentlms_page_talentlms-setup'
            || $screen_id == 'talentlms_page_talentlms-integrations'
        ) {
            $this->tlms_displayErrors();
        }

        if (! empty($this->talentlmsAdminErrors)) {
            foreach ($this->talentlmsAdminErrors as $message) {
                echo '<div class="error notice is-dismissible">' . $message . '</div>';
            }
        }
    }

    public function tlms_displayErrors(): void
    {
        if ((
                empty($_POST['tlms-domain'])
                && empty($_POST['tlms-apikey'])
            )
            &&
            (
                ! get_option('tlms-domain')
                && ! get_option('tlms-apikey')
            )
        ) {
            $this->tlms_logError(
                '<p><strong>'
                . esc_html_e('You need to specify a TalentLMS domain and a TalentLMS API key.', 'talentlms')
                . '</strong>'
                . sprintf(
                    esc_html_e('You must <a href="%1$s">enter your domain and API key</a> for it to work.', 'talentlms'),
                    admin_url('admin.php?page=talentlms-setup')
                )
                . '</p>'
            );
        } else {
            try {
                TalentLMS::setDomain(esc_html(get_option('tlms-domain')));
                TalentLMS::setApiKey(esc_html(get_option('tlms-apikey')));

                if (is_admin() && ! wp_doing_ajax()) {
                    Utils::tlms_getCourses();
                    Utils::tlms_getCategories();
                }
            } catch (Exception $e) {
                if ($e instanceof TalentLMS_ApiError) {
                    $this->tlms_logError($e->getMessage());
                }
            }
        }
    }
}
