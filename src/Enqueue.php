<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

use TalentlmsIntegration\Pages\Admin;

class Enqueue {

	public function register(){
		add_action('admin_enqueue_scripts', array($this, 'tlms_enqueueAdminScripts'));
		add_action('tlms_enqueue_styles', array($this, 'tlms_enqueueStyles'));
	}

	public function tlms_enqueueAdminScripts() {

		//Register styles
		wp_register_style('tlms-admin', TLMS_BASEURL. 'assets/css/tlms-admin.css', false, TLMS_VERSION);
		wp_register_style('bootstrap-css', TLMS_BASEURL. 'assets/css/bootstrap.min.css', false);
//		wp_register_style('tlms-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css');

		//Register scripts
		wp_register_script('tlms-admin', TLMS_BASEURL . 'assets/js/tlms-admin.js', false, TLMS_VERSION);
		wp_register_script('bootstrap-js', TLMS_BASEURL. 'assets/js/bootstrap.min.js', ['jquery'], false, true);
		wp_register_script('tlms-font-awesome', '//kit.fontawesome.com/71863a7e02.js', false);

		$translations_array = array(
			'progress_message' => __('Please wait while syncing..', 'talentlms'),
			'success_message' => __('The operation completed successfully', 'talentlms'),
			'select_all_message' => __('Select all', 'talentlms'),
			'unselect_all_message' => __('Unselect all', 'talentlms'),
		);

		//Enqueue styles
		wp_enqueue_style('tlms-admin');
//		wp_enqueue_style('tlms-font-awesome');
		wp_enqueue_style('tlms-datatables-css', TLMS_BASEURL . 'resources/DataTables-1.10.15/media/css/jquery.dataTables.css');
		wp_enqueue_style('bootstrap-css');

		//Enqueue scripts
		wp_localize_script('tlms-admin', 'translations', $translations_array );
		wp_enqueue_script('tlms-admin');
		wp_enqueue_script('tlms-datatables-js', TLMS_BASEURL. 'resources/DataTables-1.10.15/media/js/jquery.dataTables.js');
		wp_enqueue_script('bootstrap-js');
		wp_enqueue_script('tlms-font-awesome');
	}


	public function tlms_enqueueStyles() {
		wp_register_style('tlms-custom-css', Admin::getCustomCssFilePath(), false, TLMS_VERSION);
		wp_enqueue_style('tlms-custom-css');
	}
}
