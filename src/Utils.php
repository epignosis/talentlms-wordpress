<?php
/**
 * @package talentlms-wordpress
 */
namespace TalentlmsIntegration;

use DateTime;
use Exception;
use http\Exception\InvalidArgumentException;
use TalentLMS_Category;
use TalentLMS_Course;
use TalentLMS_Siteinfo;
use TalentLMS_User;
use TalentlmsIntegration\Validations\TLMSEmail;
use TalentlmsIntegration\Validations\TLMSFloat;
use TalentlmsIntegration\Validations\TLMSInteger;
use TalentlmsIntegration\Validations\TLMSPositiveInteger;
use TalentlmsIntegration\Validations\TLMSUrl;

class Utils {

	public static function tlms_pr($var) {
		echo "<pre>";
		print_r($var);
		echo "</pre>";
	}

	public static function tlms_pre($var) {
		echo "<pre>";
		print_r($var);
		echo "</pre>";
		exit;
	}

	public static function tlms_vd($var) {
		echo "<pre>";
		var_dump($var);
		echo "</pre>";
	}

	public static function tlms_limitWords($string, $limit) {
		if($limit){
			$words = explode(" ", $string);

			return implode(" ", array_splice($words, 0, $limit));
		}
		else{
			return $string;
		}
	}

	public static function tlms_limitSentence($string, $limit){
		$sentences = explode(".", $string);

		return implode(".", array_splice($sentences, 0, $limit));
	}

	public static function tlms_isValidDomain($domain){
		return preg_match("/^[a-z0-9-\.]{1,100}\w+$/", $domain) AND (strpos($domain, 'talentlms.com') !== false);
	}

	public static function tlms_isApiKey($apiKey){
		if(strlen($apiKey) == 30){
			return true;
		}

		return false;
	}

	public static function tlms_parseDate($format, $date){
		$isPM = (stripos($date, 'PM') !== false);
		$parsedDate = str_replace(array('AM', 'PM'), '', $date);
		$is12hourFormat = ($parsedDate !== $date);
		$parsedDate = DateTime::createFromFormat(trim($format), trim($parsedDate));

		if($is12hourFormat){
			if($isPM && $parsedDate->format('H') !== '12'){
				$parsedDate->modify('+12 hours');
			}
			else if(!$isPM && $parsedDate->format('H') === '12'){
				$parsedDate->modify('-12 hours');
			}
		}

		return $parsedDate;
	}

	public static function tlms_getDateFormat($no_sec = false){ // used in reg_shortcodes
		// TODO: Store the site info in the database instead of hitting the API everytime we want to get it.
		$site_info = self::tlms_getTalentLMSSiteInfo();
		$date_format = $site_info instanceof Exception ? '' : $site_info['date_format'];

		switch($date_format){
			case 'DDMMYYYY':
				if($no_sec){
					$format = 'd/m/Y';
				}
				else{
					$format = 'd/m/Y, H:i:s';
				}
				break;
			case 'MMDDYYYY':
				if($no_sec){
					$format = 'm/d/Y';
				}
				else{
					$format = 'm/d/Y, H:i:s';
				}
				break;
			case 'YYYYMMDD':
			default:
				if($no_sec){
					$format = 'Y/m/d';
				}
				else{
					$format = 'Y/m/d, H:i:s';
				}
				break;
		}

		return $format;
	}

	public static function tlms_getCourses($force = false){
		global $wpdb;
		if($force){
			$wpdb->query('TRUNCATE TABLE '.TLMS_COURSES_TABLE);
		}

		$result = $wpdb->get_var("SELECT COUNT(*) FROM ".TLMS_COURSES_TABLE);
		if(empty($result)){
			$apiCourses = TalentLMS_Course::all();
			$format = self::tlms_getDateFormat();

			foreach($apiCourses as $course){
				$wpdb->insert(TLMS_COURSES_TABLE, array(
					'id' => (new TLMSPositiveInteger($course['id']))->getValue(),
					'name' => esc_sql($course['name']),
					'course_code' => esc_sql($course['code']),
					'category_id' => (new TLMSPositiveInteger($course['category_id']))->getValue(),
					'description' => esc_sql($course['description']),
					'price' => esc_sql(filter_var(html_entity_decode($course['price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
					'status' => esc_sql($course['status']),
					'creation_date' => self::tlms_parseDate($format, $course['creation_date'])->getTimestamp(),
					'last_update_on' => self::tlms_parseDate($format, $course['last_update_on'])->getTimestamp(),
					'hide_catalog' => (new TLMSInteger($course['hide_from_catalog']))->getValue(),
					'shared' => (new TLMSInteger($course['shared']))->getValue(),
					'shared_url' => (new TLMSUrl($course['shared_url']))->getValue(),
					'avatar' => esc_sql($course['avatar']),
					'big_avatar' => esc_sql($course['big_avatar']),
					'certification' => esc_sql($course['certification']),
					'certification_duration' => esc_sql($course['certification_duration'])
				));
			}
		}
	}

	public static function tlms_getCourse($course_id){
		$apiCourse = TalentLMS_Course::retrieve((new TLMSPositiveInteger($course_id))->getValue());

		return $apiCourse;
	}

	public static function tlms_getCategories($force = false){
		global $wpdb;

		if($force){
			$wpdb->query("TRUNCATE TABLE ".TLMS_CATEGORIES_TABLE);
		}

		$result = $wpdb->get_var("SELECT COUNT(*) FROM ".TLMS_CATEGORIES_TABLE);
		if(empty($result)){
			$apiCategories = TalentLMS_Category::all();
			foreach($apiCategories as $category){
				$wpdb->insert(TLMS_CATEGORIES_TABLE, array(
					'id' => (new TLMSPositiveInteger($category['id']))->getValue(),
					'name' => esc_sql($category['name']),
					'price' => (new TLMSFloat($category['price']))->getValue(),
					'parent_id' => (!empty($category['parent_id'])) ? (new TLMSPositiveInteger($category['parent_id']))->getValue() : ''
				));
			}
		}
	}

	public static function tlms_selectCourses(){
		global $wpdb;

		$courses = [];
		// snom 5
		$sql = "SELECT c.*, cat.name as category_name FROM ".TLMS_COURSES_TABLE." c LEFT JOIN ".TLMS_CATEGORIES_TABLE
			." cat ON c.category_id=cat.id WHERE c.status = 'active' AND c.hide_catalog = '0'";
		$results = $wpdb->get_results($sql);
		foreach($results as $res){
			$courses[$res->id] = $res;
		}

		return $courses;
	}

	public static function tlms_selectCourse($course_id){
		global $wpdb;
		$results = $wpdb->get_row("SELECT * FROM ".TLMS_COURSES_TABLE." WHERE id = ".(new TLMSPositiveInteger($course_id))->getValue());

		return $results;
	}

	public static function tlms_selectCategories($where = false, $order = false){
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM ".TLMS_CATEGORIES_TABLE);

		return $results;
	}

	public static function tlms_selectProductCategories(){
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM ".TLMS_PRODUCTS_CATEGORIES_TABLE);

		return $results;
	}

	public static function tlms_addProduct($course_id, $courses){
		global $wpdb;

		if(!is_array($courses)){
			throw new InvalidArgumentException('$courses is not an array');
		}

		$course_id = (new TLMSPositiveInteger($course_id))->getValue();

		$categories = self::tlms_selectProductCategories();

		$post = array(
			'post_author' => wp_get_current_user()->ID,
			'post_content' => esc_sql($courses[$course_id]->description),
			'post_status' => "publish",
			'post_title' => esc_sql($courses[$course_id]->name),
			'post_parent' => '',
			'post_type' => "product",
		);

		$product_id = wp_insert_post($post);

		wp_set_object_terms($product_id, esc_sql($courses[$course_id]->category_name), 'product_cat');
		wp_set_object_terms($product_id, 'simple', 'product_type');

		$price = filter_var(html_entity_decode($courses[$course_id]->price), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

		update_post_meta($product_id, '_visibility', 'visible');
		update_post_meta($product_id, '_stock_status', 'instock');
		update_post_meta($product_id, 'total_sales', '0');
		update_post_meta($product_id, '_downloadable', 'no');
		update_post_meta($product_id, '_virtual', 'yes');
		update_post_meta($product_id, '_purchase_note', "");
		update_post_meta($product_id, '_featured', "no");
		update_post_meta($product_id, '_weight', "");
		update_post_meta($product_id, '_length', "");
		update_post_meta($product_id, '_width', "");
		update_post_meta($product_id, '_height', "");
		update_post_meta($product_id, '_sku', "");
		update_post_meta($product_id, '_product_attributes', array());
		update_post_meta($product_id, '_sale_price_dates_from', "");
		update_post_meta($product_id, '_sale_price_dates_to', "");
		update_post_meta($product_id, '_price', $price);
		update_post_meta($product_id, '_regular_price', $price);
		update_post_meta($product_id, '_sale_price', $price);
		update_post_meta($product_id, '_sold_individually', "");
		update_post_meta($product_id, '_manage_stock', "no");
		update_post_meta($product_id, '_backorders', "no");
		update_post_meta($product_id, '_stock', "");
		update_post_meta($product_id, '_talentlms_course_id', $course_id);

		require_once(ABSPATH.'wp-admin/includes/file.php');
		require_once(ABSPATH.'wp-admin/includes/media.php');
		require_once(ABSPATH.'wp-admin/includes/image.php');

		$thumbs_url = esc_sql($courses[$course_id]->big_avatar);

		$tmp = download_url($thumbs_url);

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumbs_url, $matches);
		$file_array = [];

		if(count($matches)){
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $tmp;

			if(is_wp_error($tmp)){
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
				//$logtxt .= "Error: download_url error - $tmp\n";
			}
			else{
				//$logtxt .= "download_url: $tmp\n";
			}

			$thumbid = media_handle_sideload($file_array, $product_id, esc_sql($courses[$course_id]->name));
			if(is_wp_error($thumbid)){
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}
		}

		set_post_thumbnail($product_id, $thumbid);

		$wpdb->insert(TLMS_PRODUCTS_TABLE, array(
			'product_id' => $product_id,
			'course_id' => $course_id
		));
	}

	public static function tlms_deleteProduct($product_id){
		global $wpdb;
		$wpdb->delete(TLMS_PRODUCTS_TABLE, array('product_id' => (new TLMSPositiveInteger($product_id))->getValue()));
	}

	public static function tlms_productExists($course_id){
		global $wpdb;
		$result = $wpdb->get_row("SELECT * FROM ".TLMS_PRODUCTS_TABLE." WHERE course_id = ".(new TLMSPositiveInteger($course_id))->getValue());
		if(!empty($result)){
			return true;
		}

		return false;
	}

	public static function tlms_addProductCategories(){
		global $wpdb;

		$categories = self::tlms_selectCategories();

		foreach($categories as $category){
			if(!self::tlms_productCategoryExists($category->id)){
				$wp_category_id = wp_insert_category(array(
														 'cat_name' => esc_sql($category->name),
														 'category_nicename' => strtolower(esc_sql($category->name)),
														 'taxonomy' => 'product_cat'));

				$wpdb->insert(TLMS_PRODUCTS_CATEGORIES_TABLE, array(
					'tlms_categories_ID' => (new TLMSPositiveInteger($category->id))->getValue(),
					'woo_categories_ID' => $wp_category_id
				));
			}
		}
	}

	public static function tlms_productCategoryExists($category_id){
		global $wpdb;
		$result = $wpdb->get_row("SELECT * FROM ".TLMS_PRODUCTS_CATEGORIES_TABLE." WHERE tlms_categories_ID = ".(new TLMSPositiveInteger($category_id))->getValue());
		if(!empty($result)){
			return true;
		}

		return false;
	}

	public static function tlms_getTalentLMSSiteInfo(){
		try{
			$site_info = TalentLMS_Siteinfo::get();
		}
		catch(Exception $e){
			self::tlms_recordLog($e->getMessage());

			return $e;
		}

		return $site_info;
	}

	public static function tlms_getCustomFields(){
		try{
			$custom_fields = TalentLMS_User::getCustomRegistrationFields();
		}
		catch(Exception $e){
			self::tlms_recordLog($e->getMessage());

			return $e;
		}

		return $custom_fields;
	}

	public static function tlms_getTalentLMSURL($url){
		if(get_option('tlms-domain-map')){
			return str_replace(get_option('tlms-domain'), get_option('tlms-domain-map'), $url);
		}
		else{
			return $url;
		}
	}

	public static function tlms_getLoginKey($url){
		$arr = explode('key:', $url);
		$login_key = ',key:'.$arr[1];

		return $login_key;
	}

	public static function tlms_currentPageURL(){
		$pageURL = 'http';
		if(isset($_SERVER["HTTPS"])){
			if($_SERVER["HTTPS"] == "on"){
				$pageURL .= "s";
			}
		}
		$pageURL .= "://";
		if($_SERVER["SERVER_PORT"] != "80"){
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		}
		else{
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}

		return $pageURL;
	}

	public static function tlms_getUnitIconClass($unit_type){
		$iconClass = '';
		switch($unit_type){
			case 'Unit':
				$iconClass = 'fa fa-check';
				break;
			case 'Document':
				$iconClass = 'fa fa-desktop';
				break;
			case 'Video':
				$iconClass = 'fa fa-film';
				break;
			case 'Scorm':
				$iconClass = 'fa fa-book';
				break;
			case 'Webpage':
				$iconClass = 'fa fa-bookmark-o';
				break;
			case 'Test':
				$iconClass = 'fa fa-edit';
				break;
			case 'Survey':
				break;
			case 'Audio':
				$iconClass = 'fa fa-file-audio-o';
				break;
			case 'Flash':
				$iconClass = 'fa fa-asterisk';
				break;
			case 'IFrame' :
				$iconClass = 'fa fa-bookmark';
				break;
			case 'Assignment':
				$iconClass = 'fa fa-calendar-o';
				break;
			case 'Section':
				break;
			case 'Content':
				$iconClass = 'fa fa-bookmark-o';
				break;
			case 'SCORM | TinCan':
				$iconClass = 'fa fa-book';
				break;
		}

		return $iconClass;
	}

	public static function tlms_orderHasLatePaymentMethod($order_id){

		$order = wc_get_order((new TLMSPositiveInteger($order_id))->getValue()); //tlms_recordLog('payment_method: ' . $order->get_payment_method());

		return in_array($order->get_payment_method(), array('bacs', 'cheque', 'cod'));
	}

	public static function tlms_orderHasTalentLMSCourseItem($order_id){

		$order = wc_get_order((new TLMSPositiveInteger($order_id))->getValue());
		$order_items = $order->get_items();
		if($order_items){
			foreach($order_items as $item){
				if(!empty(get_post_meta($item['product_id'], '_talentlms_course_id'))){

					return true;
				}
			}
		}

		return false;
	}

	public static function tlms_isTalentLMSCourseInCart(){
		global $woocommerce;

		$items = $woocommerce->cart->get_cart();
		$tmls_courses = array();
		foreach($items as $item => $values){
			$tlms_course_id = get_post_meta($values['product_id'], '_talentlms_course_id', true);
			if(!empty($tlms_course_id)){
				$tmls_courses[] = $tlms_course_id;
			}
		}

		return (empty($tmls_courses)) ? false : true;
	}

	public static function tlms_enrollUserToCoursesByOrderId($order_id){

		$order = wc_get_order((new TLMSPositiveInteger($order_id))->getValue());
		$user = self::tlms_getUserByOrder($order);

		try{
			$retrieved_user = TalentLMS_User::retrieve(array('email' => (new TLMSEmail($user->user_email))->getValue()));
			$retrieved_user_exists = true;
		}
		catch(Exception $e){
			self::tlms_recordLog($e->getMessage());
			$retrieved_user_exists = false;
		}

		if(!$retrieved_user_exists){
			try{
				TalentLMS_User::signup(self::tlms_buildSignUpArgumentsByUser($user));
			}
			catch (Exception $e) {
				self::tlms_recordLog($e->getMessage());
			}
		}

		try{
			foreach($order->get_items() as $item){

				if(!empty($product_tlms_course = get_post_meta($item['product_id'], '_talentlms_course_id'))){ // isTalentLMSCourseInCart

					$enrolled_course = TalentLMS_Course::addUser(array('course_id' => $product_tlms_course[0], 'user_email' => $user->user_email));
					wc_add_order_item_meta($item->get_id(), 'tlms_go-to-course', TalentLMS_Course::gotoCourse(array('course_id' => $product_tlms_course[0], 'user_id' => $enrolled_course[0]['user_id'])));
				}
			}
		}
		catch(Exception $e){
			self::tlms_recordLog($e->getMessage());
		}
	}

	public static function tlms_buildSignUpArgumentsByUser($user){

		$signup_arguments = array();
		$signup_arguments['first_name'] = sanitize_text_field($user->user_firstname);
		$signup_arguments['last_name'] = sanitize_text_field($user->user_lastname);
		$signup_arguments['email'] = sanitize_email($user->user_email);
		$signup_arguments['login'] = sanitize_user(preg_replace('/\s+/', '', $user->user_login));
		$signup_arguments['password'] = $user->user_password;

		try{
			if(!empty($custom_fields = TalentLMS_User::getCustomRegistrationFields())){
				foreach($custom_fields as $custom_field){
					if($custom_field['mandatory'] == 'yes'){
						switch($custom_field['type']){
							case 'text':
								$signup_arguments[$custom_field['key']] = " ";
								break;
							case 'dropdown':
								$options = explode(';', $custom_field['dropdown_values']);
								$signup_arguments[$custom_field['key']] = $options[0];
								break;
							case 'date':
								$signup_arguments[$custom_field['key']] = " ";
								break;
						}
					}
				}
			}
		}
		catch(Exception $e){
			self::tlms_recordLog($e->getMessage());
		}

		return $signup_arguments;
	}

	public static function tlms_getUserByOrder($order){

		$user = new stdClass();
		$user->user_firstname = $order->get_billing_first_name();
		$user->user_lastname = $order->get_billing_last_name();

		if( $existing_user = $order->get_user() ){ //existing or just created

			$user->user_email = $existing_user->data->user_email;
			$user->user_login = $existing_user->data->user_login;
			$user->user_password = !empty($_POST['account_password']) ? substr($_POST['account_password'], 0, 20) : self::tlms_passgen();
		}
		else { //guest user

			$user->user_email = $order->get_billing_email();
			$user->user_login = $user->user_firstname.'.'.$user->user_lastname;
			$user->user_password = self::tlms_passgen();
		}

		return $user;
	}

	public static function tlms_recordLog($message){
		$logFile = TLMS_BASEPATH.'/errorLog.txt';

		$time = date("F jS Y, H:i", time() + 25200);
		$logOutput = "#$time: $message\r\n";

		$fp = fopen($logFile, "a");
		$write = fputs($fp, $logOutput);
		fclose($fp);
	}

	public static function tlms_passgen($length = 8){
		$uppercases = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$lowercases = "abcdefghijklmnopqrstuvwxyz";
		$digits = "1234567890";

		$length = max($length, 8);
		$password = wp_generate_password($length) . $uppercases[rand(0, strlen($uppercases) - 1)] . $lowercases[rand(0, strlen($lowercases) - 1)] . $digits[rand(0, strlen($digits) - 1)];

		return str_shuffle($password);
	}

	public static function tlms_getCourseIdByProduct($product_id){

		if(empty($product_id)){
			return;
		}

		global $wpdb;

		$products_courses = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT * FROM ".TLMS_PRODUCTS_TABLE."
				WHERE `product_id` = %d
				",
				$product_id
			),
			ARRAY_A
		);

		return $products_courses[0]['course_id'];
	}

	public static function tlms_isOrderCompletedInPast($order_id){

		if(empty($order_id)){
			return;
		}

		global $wpdb;

		$completed_statuses_in_past = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT * FROM ".$wpdb->comments."
				WHERE `comment_post_ID` = %d
				AND `comment_content` LIKE %s
				",
				$order_id,
				"%to Completed."
			),
			ARRAY_A
		);

		return (empty($completed_statuses_in_past)) ? false : true;
	}

	public static function tlms_getCourseUrl($course_id){

		$course_url = '';
		$tlms_domain = get_option('tlms-domain');
		if(!empty($tlms_domain)){

			$course_url = '//'.$tlms_domain.'/learner/courseinfo/id:'.$course_id;
		}

		return $course_url;
	}

	public static function tlms_deleteWoocomerceProducts(){

		global $wpdb;
		$products = $wpdb->get_results("SELECT * FROM ".TLMS_PRODUCTS_TABLE);
		if(!empty($products)){
			foreach($products as $product){
				self::tlms_deleteWoocomerceProduct($product->product_id, false);
			}
		}

		return false;
	}

	public static function tlms_deleteWoocomerceProduct($id, $force = FALSE){
		$id = (new TLMSPositiveInteger($id))->getValue();

		$product = wc_get_product($id);

		if(empty($product)){
			return new WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));
		}

		if($force){
			if($product->is_type('variable')){
				foreach($product->get_children() as $child_id){
					$child = wc_get_product($child_id);
					$child->delete(true);
				}
			}
			else if($product->is_type('grouped')){
				foreach($product->get_children() as $child_id){
					$child = wc_get_product($child_id);
					$child->set_parent_id(0);
					$child->save();
				}
			}

			$product->delete(true);
			$result = $product->get_id() > 0 ? false : true;
		}
		else{
			$product->delete();
			$result = 'trash' === $product->get_status();
		}

		if(!$result){
			return new WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
		}

		// Delete parent product transients.
		if($parent_id = wp_get_post_parent_id($id)){
			wc_delete_product_transients($parent_id);
		}

		return true;
	}
}
