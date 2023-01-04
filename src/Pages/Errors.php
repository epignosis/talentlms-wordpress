<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration\Pages;
use Exception;
use TalentLMS;
use TalentLMS_ApiError;

class Errors {

	public array $talentlmsAdminErrors = array();  // Stores all the errors that need to be displayed to the admin.
	public string $screen_id;

	public function register(){
		add_action( 'admin_notices', array($this, 'tlms_showWarnings'));
	}

	/**
	 * Logs the error and stores it, so it can be displayed to the admin.
	 *
	 * @param string $message
	 */
	function tlms_logError(string $message){
		$this->talentlmsAdminErrors[] = $message;
		tlms_recordLog($message);
	}

	/**
	 * Used to display the stored errors to the admin.
	 *
	 * @return false|void
	 */
	function tlms_showWarnings(){

		if(!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)){
			return false;
		}

		$screen_id = get_current_screen()->id;
		if ($screen_id === 'toplevel_page_talentlms' || $screen_id == 'talentlms_page_talentlms-setup' || $screen_id == 'talentlms_page_talentlms-integrations') {
			$this->tlms_displayErrors();
		}

		if(!empty($this->talentlmsAdminErrors)){
			foreach($this->talentlmsAdminErrors as $message){
				echo '<div class="error notice is-dismissible">'.$message.'</div>';
			}
		}
	}

	function tlms_displayErrors(){
		if((!get_option('tlms-domain') && !get_option('tlms-apikey')) && (empty($_POST['tlms-domain']) && empty($_POST['tlms-apikey']))){
			$this->tlms_logError('<p><strong>'.__('You need to specify a TalentLMS domain and a TalentLMS API key.', 'talentlms').'</strong>'.sprintf(__('You must <a href="%1$s">enter your domain and API key</a> for it to work.', 'talentlms'), 'admin.php?page=talentlms-setup').'</p>');
		}
		else{
			try{
				TalentLMS::setDomain(get_option('tlms-domain'));
				TalentLMS::setApiKey(get_option('tlms-apikey'));

				if(is_admin() && !wp_doing_ajax()){
					tlms_getCourses();
					tlms_getCategories();
				}
			}
			catch(Exception $e){
				if ($e instanceof TalentLMS_ApiError) {
					tlms_logError($e->getMessage());
				}
			}
		}
	}
}