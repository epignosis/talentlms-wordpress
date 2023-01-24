<?php
/**
 * Fix for Hearders already sent
 * checkout: https://tommcfarlin.com/wp_redirect-headers-already-sent/
 */
function app_output_buffer() {
	ob_start();
}
add_action('init', 'app_output_buffer');

function talentlms_course_list($atts) {

	wp_enqueue_style('tlms-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css');
	wp_enqueue_style('tlms-datatables-css', TLMS_BASEURL . '/resources/DataTables-1.10.15/media/css/jquery.dataTables.css');
	wp_enqueue_style('talentlms', TLMS_BASEURL . 'css/talentlms.css', false, '1.0');

	wp_enqueue_script('jquery');
	wp_enqueue_script('tlms-datatables-js', TLMS_BASEURL. '/resources/DataTables-1.10.15/media/js/jquery.dataTables.js');


	$categories = tlms_selectCategories();
	$courses = tlms_selectCourses();
	$dateFormat = tlms_getDateFormat(true);

	ob_start();
	include(TLMS_BASEPATH.'/shortcodes/talentlms_courses.php');
	$output = ob_get_clean();

	return $output;
}

add_shortcode('talentlms-courses', 'talentlms_course_list');

