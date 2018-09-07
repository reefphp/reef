<?php

namespace Reef\Assets;

use \Reef\Reef;
use \Reef\Exception\InvalidArgumentException;

/**
 * This Assets class contains general functionality for asset management
 */
abstract class Assets {
	
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
	 * @param string $s_view Either 'form', 'submission', 'builder' or 'all'
	 * @param array $a_options, to choose from:
	 *  - exclude   array Assets to exclude, e.g. because you include them already in your own code
	 */
	public function getCSSHTML($s_view, $a_options = []) {
		$a_assets = $this->getCSS($s_view, $a_options);
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
	 * @param string $s_view Either 'form', 'submission', 'builder' or 'all'
	 * @param array $a_options, to choose from:
	 *  - exclude   array Assets to exclude, e.g. because you include them already in your own code
	 */
	public function getJSHTML($s_view, $a_options = []) {
		$a_assets = $this->getJS($s_view, $a_options);
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
	 * @param string $s_view @see getCSSHTML
	 * @param array $a_options @see getCSSHTML
	 * @return array
	 */
	public function getCSS($s_view, $a_options = []) {
		return $this->getAssets('CSS', $s_view, $a_options);
	}
	
	/**
	 * Get all JS assets in an array
	 * @param string $s_view @see getJSHTML
	 * @param array $a_options @see getJSHTML
	 * @return array
	 */
	public function getJS($s_view, $a_options = []) {
		return $this->getAssets('JS', $s_view, $a_options);
	}
	
	/**
	 * Get all CSS/JS assets in an array
	 * @param string $s_type Either 'JS' or 'CSS'
	 * @param string $s_view Either 'form', 'submission', 'builder' or 'all'
	 * @param array $a_options
	 * @return array
	 */
	private function getAssets($s_type, $s_view, $a_options) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		if(!in_array($s_view, ['form', 'submission', 'builder', 'all'])) {
			throw new InvalidArgumentException("Invalid view type '".$s_view."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		// Get main files
		$a_remoteAssets = [];
		if($s_type == 'JS') {
			$a_remoteAssets['jquery'] = [
				'name' => 'jquery',
				'type' => 'remote',
				'path' => 'https://code.jquery.com/jquery-3.2.1.min.js',
				'integrity' => 'sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=',
			];
		}
		
		if($s_type == 'JS' && ($s_view == 'builder' || $s_view == 'all')) {
			$a_remoteAssets['sortable'] = [
				'name' => 'sortable',
				'type' => 'remote',
				'path' => 'https://cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js',
				'integrity' => 'sha256-+BvLlLgWJALRwV4lbCh0i4zqHhDqxR8FKUJmIl/u/vQ=',
			];
			
			$a_remoteAssets['mustache'] = [
				'name' => 'mustache',
				'type' => 'remote',
				'path' => 'https://cdnjs.cloudflare.com/ajax/libs/mustache.js/2.3.0/mustache.min.js',
				'integrity' => 'sha256-iaqfO5ue0VbSGcEiQn+OeXxnxAMK2+QgHXIDA5bWtGI=',
			];
		}
		
		// Merge layout, component and extension assets
		$a_assetSources = array_merge(
			[$this->getReef()->getSetup()->getLayout()],
			$this->getComponents(),
			$this->getReef()->getExtensionCollection()->getExtensionMapping()
		);
		foreach($a_assetSources as $assetSource) {
			$a_assets = $assetSource->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] == 'remote') {
					$a_remoteAssets[$a_asset['name']] = $a_asset;
				}
			}
		}
		
		// Exclude files where necessary
		if(isset($a_options['exclude'])) {
			$a_remoteAssets = array_diff_key($a_remoteAssets, $a_options['exclude']);
		}
		
		foreach($a_remoteAssets as $s_name => $a_asset) {
			if(!isset($a_asset['view'])) {
				continue;
			}
			
			$a_asset['view'] = is_array($a_asset['view']) ? $a_asset['view'] : explode(',', $a_asset['view']);
			if($s_view != 'all' && !in_array($s_view, $a_asset['view']) && !($s_view == 'builder' && in_array('form', $a_asset['view']))) {
				unset($a_remoteAssets[$s_name]);
			}
		}
		
		$this->checkLocalAsset($s_type, $s_view, $s_assetsHash);
		
		$a_assets = array_values($a_remoteAssets);
		
		$a_assets[] = [
			'type' => 'local',
			'hash' => $s_assetsHash,
		];
		
		return $a_assets;
	}
	
	/**
	 * Combine all local CSS/JS assets into a single file, or fetch its asset hash if it is in cache already
	 * @param string $s_type Either 'JS' or 'CSS'
	 * @param string $s_view @see getAssets()
	 * @param string &$s_assetsHash (Out) The asset hash to use to refer to this asset file
	 */
	private function checkLocalAsset($s_type, $s_view, &$s_assetsHash = null) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		// Get main files
		$a_mainFiles = [];
		if($s_type == 'JS') {
			$a_mainFiles[] = Reef::getDir() . 'assets/script.js';
			
			if($s_view == 'form' || $s_view == 'builder' || $s_view == 'all') {
				$a_mainFiles[] = Reef::getDir() . 'assets/ConditionEvaluator.js';
			}
			
			if($s_view == 'builder' || $s_view == 'all') {
				$a_mainFiles[] = Reef::getDir() . 'assets/builder.js';
			}
		}
		else if($s_type == 'CSS') {
			$a_mainFiles[] = Reef::getDir() . 'assets/style.css';
			
			if($s_view == 'builder' || $s_view == 'all') {
				$a_mainFiles[] = Reef::getDir() . 'assets/builder.css';
			}
		}
		
		$a_localAssets = [];
		foreach($a_mainFiles as $s_mainFile) {
			$a_localAssets[$s_mainFile] = filemtime($s_mainFile);
		}
		
		// Merge layout, component and extension assets
		$a_assetSources = array_merge(
			[$this->getReef()->getSetup()->getLayout()],
			$this->getComponents(),
			$this->getReef()->getExtensionCollection()->getExtensionMapping()
		);
		foreach($a_assetSources as $assetSource) {
			$a_assets = $assetSource->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] != 'local') {
					continue;
				}
				
				if(isset($a_asset['view'])) {
					$a_asset['view'] = is_array($a_asset['view']) ? $a_asset['view'] : explode(',', $a_asset['view']);
					if($s_view != 'all' && !in_array($s_view, $a_asset['view']) && !($s_view == 'builder' && in_array('form', $a_asset['view']))) {
						continue;
					}
				}
				
				$s_path = $assetSource::getDir() . '/' . $a_asset['path'];
				$a_localAssets[$s_path] = filemtime($s_path);
			}
		}
		
		// Compute assets hash
		$a_localAssetsSorted = $a_localAssets;
		ksort($a_localAssetsSorted);
		$s_assetsHash = sha1(implode(',', array_keys($a_localAssetsSorted)));
		
		$s_cacheKey = 'asset.'.$s_assetsHash.'.'.strtolower($s_type);
		
		// Check cache
		$Cache = $this->getReef()->getCache();
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
		
		// Compile if necessary
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
