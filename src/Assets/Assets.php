<?php

namespace Reef\Assets;

use \Reef\Reef;
use \Reef\Exception\InvalidArgumentException;

/**
 * This Assets class contains general functionality for asset management
 */
abstract class Assets {
	
	/**
	 * Array of custom assets
	 * @type array
	 */
	private $customAssets = ['CSS' => [], 'JS' => []];
	
	/**
	 * Retrieve the current Reef object
	 * @type Reef
	 */
	abstract public function getReef() : Reef;
	
	/**
	 * Get all components for which we should build the assets
	 * @type Component[]
	 */
	abstract protected function getComponents() : array;
	
	/**
	 * Build HTML for the CSS assets
	 * @param array $a_options, to choose from:
	 *  - builder   bool  True to include builder assets. Defaults to false
	 *  - exclude   array Assets to exclude, e.g. because you include them already in your own code
	 */
	public function getCSSHTML($a_options = []) {
		$a_assets = $this->getCSS($a_options);
		$s_html = '';
		foreach($a_assets as $a_asset) {
			if($a_asset['type'] == 'remote') {
				if(isset($a_asset['integrity'])) {
					$s_html .= '<link href="'.$a_asset['path'].'" rel="stylesheet" integrity="'.$a_asset['integrity'].'" crossorigin="anonymous">'.PHP_EOL;
				}
				else {
					$s_html .= '<link href="'.$a_asset['path'].'" rel="stylesheet">'.PHP_EOL;
				}
			}
			else if($a_asset['type'] == 'local') {
				$s_html .= '<link href="'.str_replace('[[request_hash]]', $a_asset['hash'], $this->getReef()->getOption('internal_request_url')).'" rel="stylesheet">'.PHP_EOL;
			}
		}
		return $s_html;
	}
	
	/**
	 * Build HTML for the JS assets
	 * @param array $a_options, to choose from:
	 *  - builder   bool  True to include builder assets. Defaults to false
	 *  - exclude   array Assets to exclude, e.g. because you include them already in your own code
	 */
	public function getJSHTML($a_options = []) {
		$a_assets = $this->getJS($a_options);
		$s_html = '';
		foreach($a_assets as $a_asset) {
			if($a_asset['type'] == 'remote') {
				if(isset($a_asset['integrity'])) {
					$s_html .= '<script src="'.$a_asset['path'].'" integrity="'.$a_asset['integrity'].'" crossorigin="anonymous"></script>'.PHP_EOL;
				}
				else {
					$s_html .= '<script src="'.$a_asset['path'].'"></script>'.PHP_EOL;
				}
			}
			else if($a_asset['type'] == 'local') {
				$s_html .= '<script src="'.str_replace('[[request_hash]]', $a_asset['hash'], $this->getReef()->getOption('internal_request_url')).'"></script>'.PHP_EOL;
			}
		}
		return $s_html;
	}
	
	/**
	 * Get all CSS assets in an array
	 * @param array $a_options @see getCSSHTML
	 * @return array
	 */
	public function getCSS($a_options = []) {
		return $this->getAssets('CSS', $a_options);
	}
	
	/**
	 * Get all JS assets in an array
	 * @param array $a_options @see getJSHTML
	 * @return array
	 */
	public function getJS($a_options = []) {
		return $this->getAssets('JS', $a_options);
	}
	
	/**
	 * Add a custom CSS asset
	 * @param string $s_path The asset path relative to the Reef root directory
	 */
	public function addLocalCSS($s_path) {
		$this->customAssets['CSS'][] = Reef::getDir() . $s_path;
	}
	
	/**
	 * Add a custom JS asset
	 * @param string $s_path The asset path relative to the Reef root directory
	 */
	public function addLocalJS($s_path) {
		$this->customAssets['JS'][] = Reef::getDir() . $s_path;
	}
	
	/**
	 * Get all CSS/JS assets in an array
	 * @param string $s_type Either 'JS' or 'CSS'
	 * @param array $a_options
	 * @return array
	 */
	private function getAssets($s_type, $a_options) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$a_remoteAssets = $this->getReef()->getSetup()->getLayout()->$s_assetFnc();
		
		$a_assetSources = array_merge($this->getComponents(), $this->getReef()->getExtensionCollection()->getExtensionMapping());
		foreach($a_assetSources as $assetSource) {
			$a_assets = $assetSource->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] == 'remote') {
					$a_remoteAssets[$a_asset['name']] = $a_asset;
				}
			}
		}
		
		if($s_type == 'JS' && !isset($a_remoteAssets['jquery'])) {
			$a_remoteAssets = array_merge(['jquery' => [
				'name' => 'jquery',
				'type' => 'remote',
				'path' => 'https://code.jquery.com/jquery-3.2.1.min.js',
				'integrity' => 'sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=',
			]], $a_remoteAssets);
		}
		
		if($s_type == 'JS' && !empty($a_options['builder'])) {
			$a_remoteAssets[] = [
				'name' => 'sortable',
				'type' => 'remote',
				'path' => 'https://cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js',
				'integrity' => 'sha256-+BvLlLgWJALRwV4lbCh0i4zqHhDqxR8FKUJmIl/u/vQ=',
			];
			
			$a_remoteAssets[] = [
				'name' => 'mustache',
				'type' => 'remote',
				'path' => 'https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js',
				'integrity' => 'sha256-iaqfO5ue0VbSGcEiQn+OeXxnxAMK2+QgHXIDA5bWtGI=',
			];
		}
		
		if(isset($a_options['exclude'])) {
			foreach($a_remoteAssets as $i => $a_remoteAsset) {
				if(in_array($a_remoteAsset['name'], $a_options['exclude'])) {
					unset($a_remoteAssets[$i]);
				}
			}
		}
		
		$this->checkLocalAsset($s_type, $s_assetsHash);
		
		$a_assets = $a_remoteAssets;
		
		$a_assets[] = [
			'type' => 'local',
			'hash' => $s_assetsHash,
		];
		
		return $a_assets;
	}
	
	/**
	 * Combine all local CSS/JS assets into a single file, or fetch its asset hash if it is in cache already
	 * @param string $s_type Either 'JS' or 'CSS'
	 * @param string &$s_assetsHash (Out) The asset hash to use to refer to this asset file
	 */
	private function checkLocalAsset($s_type, &$s_assetsHash = null) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$Cache = $this->getReef()->getCache();
		$a_localAssets = [];
		
		if($s_type == 'JS') {
			$s_mainFile = Reef::getDir() . 'assets/script.js';
			
			$a_localAssets[Reef::getDir() . 'assets/ConditionEvaluator.js'] = filemtime(Reef::getDir() . 'assets/ConditionEvaluator.js');
		}
		else if($s_type == 'CSS') {
			$s_mainFile = Reef::getDir() . 'assets/style.css';
		}
		$a_localAssets[$s_mainFile] = filemtime($s_mainFile);
		
		foreach($this->customAssets[$s_type] as $s_assetPath) {
			$a_localAssets[$s_assetPath] = filemtime($s_assetPath);
		}
		
		$a_assetSources = array_merge($this->getComponents(), $this->getReef()->getExtensionCollection()->getExtensionMapping());
		foreach($a_assetSources as $assetSource) {
			$a_assets = $assetSource->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] == 'local') {
					$s_path = $assetSource::getDir() . '/' . $a_asset['path'];
					$a_localAssets[$s_path] = filemtime($s_path);
				}
			}
		}
		
		$a_localAssetsSorted = $a_localAssets;
		ksort($a_localAssetsSorted);
		$s_assetsHash = sha1(implode(',', array_keys($a_localAssetsSorted)));
		
		$s_cacheKey = 'asset.'.$s_assetsHash.'.'.strtolower($s_type);
		
		$b_compile = false;
		if(!$Cache->has($s_cacheKey)) {
			$b_compile = true;
		}
		else {
			$a_cache = $Cache->get($s_cacheKey);
			
			if($a_cache['created'] < max($a_localAssets)) {
				$b_compile = true;
			}
		}
		
		if($b_compile) {
			$s_content = '';
			
			foreach($a_localAssets as $s_path => $i_time) {
				$s_content .= file_get_contents($s_path);
			}
			
			if($s_type == 'CSS') {
				$s_content = str_replace('CSSPRFX', $this->getReef()->getOption('css_prefix'), $s_content);
			}
			else if($s_type == 'JS') {
				$s_content = str_replace('JS_INSERT_CSS_PREFIX', $this->getReef()->getOption('css_prefix'), $s_content);
				$s_content = str_replace('JS_INSERT_EVENT_PREFIX', $this->getReef()->getOption('js_event_prefix'), $s_content);
			}
			
			$a_cache = [
				'created' => time(),
				'content' => $s_content,
			];
			
			$Cache->set($s_cacheKey, $a_cache);
		}
		
		$s_assetsHash = 'asset:'.strtolower($s_type).':'.$s_assetsHash . ':' . $a_cache['created'];
	}
}
