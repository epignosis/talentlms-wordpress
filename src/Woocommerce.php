<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

use TalentLMS_User;
use TalentlmsIntegration\Services\PluginService;
use TalentlmsIntegration\Validations\TLMSEmail;
use TalentlmsIntegration\Validations\TLMSPositiveInteger;

class Woocommerce implements PluginService{

	public function register(): void{
		if(get_option('tlms-woocommerce-active')){
			add_action('woocommerce_checkout_order_processed',  array($this, 'tlms_processExistingCustomer'), 1, 1);
			add_action('woocommerce_payment_complete',  array($this, 'tlms_woocommerce_payment_complete'));
			add_action('woocommerce_order_status_completed',  array($this, 'tlms_processWooComOrder'), 10, 1);
			add_action('woocommerce_save_account_details',  array($this, 'tmls_customerChangedPassword'), 10, 1);
			add_action('password_reset',  array($this, 'tmls_customerResetPassword'), 10, 2);
			add_action('before_delete_post',  array($this, 'tlms_wooCommerceProductDeleted'));
			add_action('woocommerce_order_item_meta_end',  array($this, 'action_woocommerce_order_item_meta_end'), 10, 4);
			add_filter('woocommerce_is_sold_individually',  array($this, 'filter_woocommerce_is_sold_individually'), 10, 2);
		}
	}

	function tlms_processExistingCustomer($order_id){ //tlms_recordLog('enter_woocommerce_checkout_order_processed');
		$order_id = (new TLMSPositiveInteger($order_id))->getValue();
		$enroll_user_to_courses = get_option('tlms-enroll-user-to-courses');
		if(!Utils::tlms_isOrderCompletedInPast($order_id) && Utils::tlms_orderHasTalentLMSCourseItem($order_id) && Utils::tlms_orderHasLatePaymentMethod($order_id) && !empty($enroll_user_to_courses) && $enroll_user_to_courses == 'submission') { //tlms_recordLog('execute_woocommerce_checkout_order_processed');

			Utils::tlms_enrollUserToCoursesByOrderId($order_id);
		}
	}

	// enroll user to courses: setup is "upon submission" and order's payment option is "payment gateway (stripe, paypal, etc)" and transaction returned "success"
	function tlms_woocommerce_payment_complete($order_id){ //tlms_recordLog('enter_woocommerce_payment_complete');
		$order_id = (new TLMSPositiveInteger($order_id))->getValue();
		$enroll_user_to_courses = get_option('tlms-enroll-user-to-courses');
		if(!Utils::tlms_isOrderCompletedInPast($order_id) && Utils::tlms_orderHasTalentLMSCourseItem($order_id) && !empty($enroll_user_to_courses) && $enroll_user_to_courses == 'submission') { //tlms_recordLog('excecute_woocommerce_payment_complete');

			Utils::tlms_enrollUserToCoursesByOrderId($order_id);
		}
	}

	// enroll user to courses: setup is "upon completion" and order's status changed to "completed" (in most cases manually by eshop manager)
	function tlms_processWooComOrder($order_id){ //tlms_recordLog('enter_woocommerce_order_status_completed');
		$order_id = (new TLMSPositiveInteger($order_id))->getValue();
		$enroll_user_to_courses = get_option('tlms-enroll-user-to-courses');
		if(!Utils::tlms_isOrderCompletedInPast($order_id) && Utils::tlms_orderHasTalentLMSCourseItem($order_id) && !empty($enroll_user_to_courses) && $enroll_user_to_courses == 'completion') { //tlms_recordLog('execute_woocommerce_order_status_completed');

			Utils::tlms_enrollUserToCoursesByOrderId($order_id);
		}
	}

	// for when a user changes his password
	function tmls_customerChangedPassword($user){
		//die(print_r($_POST, true));
		try{
			$userEmail = (new TLMSEmail($_POST['account_email']))->getValue();
			$tlmsUser = TalentLMS_User::retrieve(array('email' => $userEmail));
			$userId = (new TLMSPositiveInteger($tlmsUser['id']))->getValue();
			TalentLMS_User::edit(array('user_id' => $userId, 'password' => esc_sql($_POST['password_1'])));
		}
		catch(Exception $e){
			Utils::tlms_recordLog($e->getMessage());
			wc_add_notice(__($e->getMessage()), 'error');
		}
	}

	function tmls_customerResetPassword($user, $pass){
		//die(print_r($_POST, true));
		try{
			$userEmail = (new TLMSEmail($user->data->user_email))->getValue();
			$tlmsUser = TalentLMS_User::retrieve(array('email' => $userEmail));
			$userId = (new TLMSPositiveInteger($tlmsUser['id']))->getValue();
			TalentLMS_User::edit(array('user_id' => $userId, 'password' => esc_sql($_POST['password_1'])));
		}
		catch(Exception $e){
			Utils::tlms_recordLog($e->getMessage());
			wc_add_notice(__($e->getMessage()), 'error');
		}
	}

	// for when deleting a product from woocommerce
	function tlms_wooCommerceProductDeleted($post_id){
		global $post_type;
		if($post_type != 'product'){
			return;
		}

		$post_id = (new TLMSPositiveInteger($post_id))->getValue();
		Utils::tlms_deleteProduct($post_id);
	}

	function action_woocommerce_order_item_meta_end($item_id, $item, $order, $flag){

		$tlms_gotocourse = wc_get_order_item_meta($item_id, 'tlms_go-to-course', $single = true);
		if(!empty($tlms_gotocourse)){

			echo '<br/><a href="'.$tlms_gotocourse['goto_url'].'" class="button" target="_blank" >'.__('Start course', 'talentlms').'</a>';
		}
	}

	function filter_woocommerce_is_sold_individually($sold_individually, $product){

		return ( !empty(get_post_meta($product->get_id(), '_talentlms_course_id')) ) ? true : $sold_individually;
	}
}
