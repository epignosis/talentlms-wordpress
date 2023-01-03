<?php
/**
 * Plugin Class.
 *
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

final class Plugin {

	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public static function get_services(): array{
		return [
			Pages\Admin::class,
			Pages\Help::class,
//			Pages\Errors::class,
			Enqueue::class,
			Widget::class
		];
	}

	/**
	 * Loop through the classes, initialize them,
	 * and call the register() method if it exists
	 * @return void
	 */
	public static function register_services(){
		foreach(self::get_services() as $class){
			$service = self::init($class);
			if(method_exists($service, 'register')){
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param $class
	 */
	private static function init($class) {
		return new $class();

//		register_activation_hook(__FILE__, 'activate');
//		register_deactivation_hook(__FILE__, 'deactivate');
	}
}

