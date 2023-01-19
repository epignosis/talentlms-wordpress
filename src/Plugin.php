<?php
/**
 * Plugin Class.
 *
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;

use TalentlmsIntegration\Services\PluginService;

final class Plugin {

	private array $services = [
			Pages\Admin::class,
			Pages\Errors::class,
			Pages\Help::class,
			Ajax::class,
			Database::class,
			Enqueue::class,
			Woocommerce::class,
	];

	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public function get_services(): array{
		return $this->services;
	}

	/**
	 * Loop through the classes, initialize them,
	 * and call the register() method if it exists
	 * @return void
	 */
	public function register_services(): self{
		foreach($this->get_services() as $class){
			$service = new $class;
			if(!$service instanceof PluginService){
				throw new \RuntimeException("A plugin must conform PluginService contract");
			}
			$service->register();
		}

		return $this;
	}

	public static function init(): self {
		return (new self())->register_services();
	}
}

