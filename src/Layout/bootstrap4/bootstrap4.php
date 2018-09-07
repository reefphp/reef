<?php

namespace Reef\Layout\bootstrap4;

class bootstrap4 implements \Reef\Layout\Layout {
	
	private $a_config;
	
	public function __construct($a_config = []) {
		$this->a_config = array_merge([
			'break' => ['md' => 3],
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
	public static function getDir() : string {
		return __DIR__.'/';
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
	public function view(array $a_config = []) : array {
		$a_config = array_merge($this->a_config, $a_config);
		
		$a_break = $a_config['break'];
		
		$i_previous = 12;
		$a_bps_left = ['col-12'];
		$a_bps_right = ['col-12'];
		
		foreach(['xs', 'sm', 'md', 'lg', 'xl'] as $s_bp) {
			if(!isset($a_break[$s_bp]) || $a_break[$s_bp] == $i_previous) {
				continue;
			}
			
			$i_left = (int)$a_break[$s_bp];
			
			$a_bps_left[] = 'col-'.$s_bp.'-'.$i_left;
			$a_bps_right[] = 'col-'.$s_bp.'-'.(12-$i_left);
			
			$i_previous = $i_left;
		}
		
		return [
			'col_left' => implode(' ', $a_bps_left),
			'col_right' => implode(' ', $a_bps_right),
		];
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
