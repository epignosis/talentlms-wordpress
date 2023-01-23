<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;
use TalentLMS_Siteinfo;
use TalentlmsIntegration\Utils;

class Ajax {

	public function register(){
		add_action('wp_ajax_tlms_resynch', 'tlms_resyncCourse');
	}

	function tlms_resyncCourse() {
		global $wpdb;

		$limit = TalentLMS_Siteinfo::getRateLimit();

		if (!empty($_POST['course_id']) && (empty($limit['remaining']) || $limit['remaining'] > 4)) { // we are gonna make at least 3 api calls, so we have to be prepared.
			$product_ID = $wpdb->get_var("SELECT product_id FROM ".TLMS_PRODUCTS_TABLE." WHERE course_id = ".$_POST['course_id']);

			if($product_ID) {
				$wpdb->query("DELETE FROM " . TLMS_PRODUCTS_TABLE . " WHERE course_id = " . $_POST['course_id']);
				$wpdb->query("DELETE FROM " . TLMS_COURSES_TABLE . " WHERE id = " . $_POST['course_id']);
				$wpdb->query("DELETE FROM " . WP_POSTS_TABLE . " WHERE ID = ".$product_ID);

				Utils::tlms_getCourses(true);
				Utils::tlms_getCategories(true);
				Utils::tlms_addProduct($_POST['course_id'], Utils::tlms_selectCourses());
			}
			echo json_encode(array('api_limitation' => 'none'));
		}
		else{
			echo json_encode(array('api_limitation' => 'Api usage resets at '.$limit['formatted_reset']));
		}

		wp_die();
	}
}
