<?php
/**
 * @package talentlms-wordpress
 */
namespace TalentlmsIntegration;
use TalentlmsIntegration\Database;

class Activate {

	public static function tlms_activate() {
		Database::tlms_createDB();
		Activate::tlms_addOptions();
		Activate::tlms_installAndUpdateOptions();
		Activate::tlms_setupWPPages();
	}

	public static function tlms_addOptions() {
		update_option('tlms-domain', '');
		update_option('tlms-apikey', '');
		update_option('tlms-woocommerce-active', 0);
	}
	public static function tlms_installAndUpdateOptions() {

		update_option('tlms-enroll-user-to-courses', 'submission');
	}


	public static function tlms_setupWPPages() {
		Activate::tlms_addCoursesPage();
		//Activate::tlms_addSignupPage(); //deprecated
	}

	public static function tlms_addCoursesPage() {
		global $wpdb;

		$the_page_title = 'Courses';
		$the_page_name = 'courses';

		delete_option("tlms_courses_page_title");
		add_option("tlms_courses_page_title", $the_page_title, '', 'yes');
		delete_option("tlms_courses_page_name");
		add_option("tlms_courses_page_name", $the_page_name, '', 'yes');
		delete_option("tlms_courses_page_id");
		add_option("tlms_courses_page_id", '0', '', 'yes');

		$the_page = get_page_by_title($the_page_title);
		if (!$the_page) {
			$_p = array();
			$_p['post_title'] = $the_page_title;
			$_p['post_content'] = "[talentlms-courses]";
			$_p['post_status'] = 'publish';
			$_p['post_type'] = 'page';
			$_p['comment_status'] = 'closed';
			$_p['ping_status'] = 'closed';
			$_p['post_category'] = array(1);
			$the_page_id = wp_insert_post($_p);
		} else {
			$the_page_id = $the_page -> ID;
			$the_page -> post_status = 'publish';
			$the_page_id = wp_update_post($the_page);
		}
		delete_option('tlms_courses_page_id');
		add_option('tlms_courses_page_id', $the_page_id);
	}

	public static function tlms_addSignupPage() {
		global $wpdb;

		$the_page_title = 'Signup';
		$the_page_name = 'signup';

		delete_option("tlms_signup_page_title");
		add_option("tlms_signup_page_title", $the_page_title, '', 'yes');
		delete_option("tlms_signup_page_name");
		add_option("tlms_signup_page_name", $the_page_name, '', 'yes');
		delete_option("tlms_signup_page_id");
		add_option("tlms_signup_page_id", '1', '', 'yes');

		$the_page = get_page_by_title($the_page_title);
		if (!$the_page) {
			$_p = array();
			$_p['post_title'] = $the_page_title;
			$_p['post_content'] = "[talentlms-signup]";
			$_p['post_status'] = 'publish';
			$_p['post_type'] = 'page';
			$_p['comment_status'] = 'closed';
			$_p['ping_status'] = 'closed';
			$_p['post_category'] = array(1);
			$the_page_id = wp_insert_post($_p);
		} else {
			$the_page_id = $the_page -> ID;
			$the_page -> post_status = 'publish';
			$the_page_id = wp_update_post($the_page);
		}
		delete_option('tlms_signup_page_id');
		add_option('tlms_signup_page_id', $the_page_id);
	}
}