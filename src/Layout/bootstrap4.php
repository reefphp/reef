<?php

namespace Reef\Layout;

class bootstrap4 implements Layout {
	
	private $a_config;
	
	public function __construct($a_config = []) {
		$this->a_config = array_merge([
			'col_left' => 'col-12 col-md-3',
			'col_right' => 'col-12 col-md-9',
		], $a_config);
	}
	
	/**
	 * @inherit
	 */
	public static function getName() : string {
		return 'bootstrap4';
	}
	
	/**
	 * @inherit
	 */
	public function getConfig() : array {
		return $this->a_config;
	}
	
	/**
	 * @inherit
	 */
	public function getMergedConfig(array $a_config) : array {
		return array_merge($this->getConfig(), $a_config);
	}
	
	/**
	 * @inherit
	 */
	public function getJS() : array {
		return [
			[
				'name' => 'popper',
				'type' => 'remote',
				'path' => 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js',
				'integrity' => 'sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q',
			],
			[
				'name' => 'bootstrap4',
				'type' => 'remote',
				'path' => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js',
				'integrity' => 'sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl',
			],
		];
	}
	
	/**
	 * @inherit
	 */
	public function getCSS() : array {
		return [
			[
				'name' => 'bootstrap4',
				'type' => 'remote',
				'path' => 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css',
				'integrity' => 'sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm',
			],
		];
	}
	
}
