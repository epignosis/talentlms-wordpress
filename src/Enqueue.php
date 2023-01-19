<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

use TalentlmsIntegration\Services\PluginService;

class Enqueue implements PluginService{

	public function register(){
		add_action('admin_enqueue_scripts', array($this, 'tlms_enqueueAdminScripts'));
		add_action('wp_enqueue_scripts', array($this, 'tlms_enqueueFrontScripts'));
	}

	private function tlms_commonLibs(){
		wp_register_style('tlms-widget', TLMS_BASEURL . '/assets/css/talentlms-widget.css', false, TLMS_VERSION);

		wp_register_script('bootstrap-js', TLMS_BASEURL . '/assets/js/bootstrap.min.js', ['jquery'], false, true);
		wp_register_script('tlms-font-awesome', TLMS_BASEURL . '/assets/js/font-awesome.min.js', false, TLMS_VERSION);

		wp_enqueue_style('tlms-widget');
		wp_enqueue_script('bootstrap-js');
		wp_enqueue_script('tlms-font-awesome');
	}
	function tlms_enqueueAdminScripts() {
		$this->tlms_commonLibs();

		//Register styles
		wp_register_style('tlms-admin', TLMS_BASEURL . '/assets/css/tlms-admin.css', false, TLMS_VERSION);

		//Register scripts
		wp_register_script('tlms-admin', TLMS_BASEURL . '/assets/js/tlms-admin.js', false, TLMS_VERSION);

		$translations_array = array(
			'progress_message' => __('Please wait while syncing..', 'talentlms'),
			'success_message' => __('The operation completed successfully', 'talentlms'),
			'select_all_message' => __('Select all', 'talentlms'),
			'unselect_all_message' => __('Unselect all', 'talentlms'),
		);

		//Enqueue styles
		wp_enqueue_style('tlms-admin');
		wp_enqueue_style('tlms-datatables-css', TLMS_BASEURL . '/resources/DataTables-1.10.15/media/css/jquery.dataTables.css');

		//Enqueue scripts
		wp_localize_script('tlms-admin', 'translations', $translations_array );
		wp_enqueue_script('tlms-admin');
		wp_enqueue_script('tlms-datatables-js', TLMS_BASEURL . '/resources/DataTables-1.10.15/media/js/jquery.dataTables.js');
	}

	function tlms_enqueueFrontScripts() {
		$this->tlms_commonLibs();

		//Register styles
		wp_register_style('tlms-front', TLMS_BASEURL . '/assets/css/talentlms.css', false, TLMS_VERSION);

		wp_enqueue_style('tlms-datatables-css', TLMS_BASEURL . '/resources/DataTables-1.10.15/media/css/jquery.dataTables.css');

		wp_enqueue_script('tlms-datatables-js', TLMS_BASEURL . '/resources/DataTables-1.10.15/media/js/jquery.dataTables.js');
	}
}
