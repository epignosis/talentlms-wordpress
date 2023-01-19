<?php

namespace TalentlmsIntegration;

class ShortCodes implements Services\PluginService{

	public function register(){
		ob_start();
		add_shortcode('talentlms-courses', array($this, 'talentlms_course_list'));
	}

	function talentlms_course_list(){

		$categories = Utils::tlms_selectCategories();
		$courses = Utils::tlms_selectCourses();
		$dateFormat = Utils::tlms_getDateFormat(true);

		ob_start();
		require_once TLMS_BASEPATH . '/templates/talentlms_courses.php';
		return ob_get_clean();
	}
}