<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration\Pages;

use TalentLMS_ApiError;
use TalentlmsIntegration\Helpers\TalentLMSApiIntegrationHelper;
use TalentlmsIntegration\Services\PluginService;
use TalentlmsIntegration\Utils;
use TalentlmsIntegration\Validations\TLMSPositiveInteger;

class Admin implements PluginService
{
    use TalentLMSApiIntegrationHelper;

    /**
     * @throws TalentLMS_ApiError
     */
    public function register(): void
    {
        add_action('admin_menu', array( $this, 'tlms_registerAdministrationPages' ));
        $this->enableTalentLMSLib();
    }

    public function tlms_registerAdministrationPages(): void
    {
        add_menu_page(
            esc_html_e('TalentLMS', 'talentlms'),
            esc_html_e('TalentLMS', 'talentlms'),
            'manage_options',
            'talentlms',
            array( $this, 'tlms_adminPanel' )
        );
        add_submenu_page(
            'talentlms',
            esc_html_e('Dashboard', 'talentlms'),
            esc_html_e('Dashboard', 'talentlms'),
            'manage_options',
            'talentlms',
            array( $this, 'tlms_adminPanel' )
        );
        add_submenu_page(
            'talentlms',
            esc_html_e('Setup', 'talentlms'),
            esc_html_e('Setup', 'talentlms'),
            'manage_options',
            'talentlms-setup',
            array( $this, 'tlms_setupPage' )
        );
        add_submenu_page(
            'talentlms',
            esc_html_e('Integrations', 'talentlms'),
            esc_html_e('Integrations', 'talentlms'),
            'manage_options',
            'talentlms-integrations',
            array( $this, 'tlms_integrationsPage' )
        );
        add_submenu_page(
            'talentlms',
            esc_html_e('CSS', 'talentlms'),
            esc_html_e('CSS', 'talentlms'),
            'manage_options',
            'talentlms-css',
            array( $this, 'tlms_cssPage' )
        );
    }

    public function tlms_adminPanel(): void
    {
        require_once TLMS_BASEPATH . '/templates/dashboard.php';
    }

    public function tlms_setupPage(): void
    {
        $action_status = $action_message = $api_validation = $domain_validation = '';
        if (isset($_POST['action']) && $_POST['action'] == 'tlms-setup') {
            if (isset($_POST['tlms-domain'], $_POST['tlms-apikey'], $_POST['tlms-enroll-user-to-courses'])
            ) {
                // we accept the domain only, without the protocol
                if (stripos(strtolower($_POST['tlms-domain']), 'http') === 0
                    || stripos(strtolower($_POST['tlms-domain']), 'https') === 0
                ) {
                    $action_status     = 'error';
                    $domain_validation = 'form-invalid';
                    $action_message    = __('Invalid TalentLMS Domain', 'talentlms') . '<br />';
                } elseif (strlen($_POST['tlms-apikey']) !== 30) { // TalentLMS API key is exactly 30 characters
                    $action_status  = 'error';
                    $api_validation = 'form-invalid';
                    $action_message = __('Invalid TalentLMS API key', 'talentlms') . '<br />';
                } else {
                    update_option('tlms-domain', esc_url_raw($_POST['tlms-domain']));
                    update_option('tlms-apikey', sanitize_text_field($_POST['tlms-apikey']));
                    update_option(
                        'tlms-enroll-user-to-courses',
                        sanitize_text_field($_POST['tlms-enroll-user-to-courses'])
                    );

                    if (isset($_POST['tlms-automtically-complete-orders'])) {
                        update_option(
                            'tlms-automtically-complete-orders',
                            sanitize_text_field($_POST['tlms-automtically-complete-orders'])
                        );
                    }

                    $action_status  = 'updated';
                    $action_message = __('Details edited successfully', 'talentlms');
                }
            } else {
                $action_status = 'error';

                if (! $_POST['tlms-domain']) {
                    $domain_validation = 'form-invalid';
                    $action_message    = __('TalentLMS Domain required', 'talentlms') . '<br />';
                    update_option('tlms-domain', '');
                }

                if (! $_POST['tlms-apikey']) {
                    $api_validation = 'form-invalid';
                    $action_message = __('TalentLMS API key required', 'talentlms') . '<br />';
                    update_option('tlms-apikey', '');
                }
            }
        }

        require_once TLMS_BASEPATH . '/templates/setup.php';
    }

    public function tlms_integrationsPage(): void
    {
        $courses = Utils::tlms_selectCourses();

        if (isset($_POST['tlms_products']) && $_POST['tlms_products']) {
            Utils::tlms_addProductCategories();

            foreach ($_POST['tlms_products'] as $course_id) {
                $course_id = ( new TLMSPositiveInteger($course_id) )->getValue();
                if (! Utils::tlms_productExists($course_id)) {
                    Utils::tlms_addProduct($course_id, $courses);
                }
            }

            $action_status  = 'updated';
            $action_message = __('Operation completed successfuly', 'talentlms');
        }

        if (isset($_POST['action']) && $_POST['action'] == 'tlms-fetch-courses') {// refresh courses
            Utils::tlms_getCourses(true);
            wp_redirect(admin_url('admin.php?page=talentlms-integrations'));
        }

        require_once TLMS_BASEPATH . '/templates/integrations.php';
    }

    public function tlms_cssPage(): void
    {
        global $wp_filesystem;
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();

        $upload_dir = wp_upload_dir();
        $dir        = trailingslashit($upload_dir['basedir']) . TLMS_UPLOAD_DIR;

        $customCssFileName    = $dir . '/css/talentlms-style.css';
        $customCssFileContent = null;

        if ($_POST['action'] == 'edit-css') {
            // Create main folder within upload if not exist
            if (! $wp_filesystem->is_dir($dir)) {
                $wp_filesystem->mkdir($dir);
            }

            // Create a subfolder in my new folder if not exist
            if (! $wp_filesystem->is_dir($dir . '/css')) {
                $wp_filesystem->mkdir($dir . '/css');
            }

            if ($wp_filesystem->exists($customCssFileName)) {
                unlink($customCssFileName);
            }

            // Save file and set permission to 0644
            $wp_filesystem->put_contents($customCssFileName, stripslashes(strip_tags($_POST['tl-edit-css'])), 0644);

            $action_status  = 'updated';
            $action_message = __('Details edited successfully', 'talentlms');
        }

        if ($wp_filesystem->exists($customCssFileName)) {
            $customCssFileContent = $wp_filesystem->get_contents($customCssFileName);
        }

        $presentCssFileName = pathinfo($customCssFileName)['filename'];
        require_once TLMS_BASEPATH . '/templates/css.php';
    }

    public static function getCustomCssFilePath(): string
    {
        $upload_dir = wp_upload_dir();
        $dir        = trailingslashit($upload_dir['baseurl']) . TLMS_UPLOAD_DIR;

        return $dir . '/css/talentlms-style.css';
    }
}
