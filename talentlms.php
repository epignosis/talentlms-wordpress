<?php
/*
 Plugin Name: TalentLMS
 Plugin URI: http://wordpress.org/extend/plugins/talentlms/
 Description: This plugin integrates Talentlms with Wordpress. Promote your TalentLMS content through your WordPress site.
 Version: 6.6.9.5
 Author: Epignosis LLC
 Author URI: www.epignosishq.com
 License: GPL2
 */

/**
 * Require once the Composer Autoload
 */
if(file_exists(plugin_dir_path( __FILE__ ) . 'vendor/autoload.php')){
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

/**
 * Define constants
 */
define('TLMS_BASEPATH', dirname(__FILE__));
define('TLMS_BASEURL', plugin_dir_url(__FILE__));
define('TLMS_VERSION', '6.6.9.5');
define('TLMS_UPLOAD_DIR', 'talentlmswpplugin');
/**
 * The code that runs during plugin activation
 */
function activate(): void{
	TalentlmsIntegration\Activate::tlms_activate();
}
register_activation_hook(__FILE__, 'activate');


register_uninstall_hook(__FILE__, 'tlms_uninstall');

if(class_exists('TalentlmsIntegration\Plugin')){
	TalentlmsIntegration\Plugin::register_services();
}


//register_activation_hook(__FILE__, 'tlms_install');
//register_uninstall_hook(__FILE__, 'tlms_uninstall');

require_once (TLMS_BASEPATH . '/TalentLMSLib/lib/TalentLMS.php');
//require_once (TLMS_BASEPATH . '/warnings.php');

require_once (TLMS_BASEPATH . '/utils/utils.php');
//require_once (TLMS_BASEPATH . '/utils/db.php');
//require_once (TLMS_BASEPATH . '/utils/install.php');
//require_once (TLMS_BASEPATH . '/admin/admin.php');
require_once (TLMS_BASEPATH . '/shortcodes/reg_shortcodes.php');
require_once (TLMS_BASEPATH . '/integrations/woocommerce.php');
require_once (TLMS_BASEPATH . '/widgets/reg_widgets.php');

function tlms_isWoocommerceActive() {
	if ( is_plugin_active('woocommerce/woocommerce.php') ) {
		update_option('tlms-woocommerce-active', 1);
	} else {
		update_option('tlms-woocommerce-active', 0);
	}
    if( empty(get_option('tlms-enroll-user-to-courses')) ){
        update_option('tlms-enroll-user-to-courses', 'submission');    
    }
}
add_action( 'admin_init', 'tlms_isWoocommerceActive' );
