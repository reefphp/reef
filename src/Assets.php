<?php

namespace Reef;

use \Reef\Exception\InvalidArgumentException;

abstract class Assets {
	
	private $customAssets = ['CSS' => [], 'JS' => []];
	
	abstract public function getReef() : Reef;
	abstract protected function getComponents() : array;
	
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
				$s_html .= '<link href="'.str_replace('[[assets_hash]]', $a_asset['hash'], $this->getReef()->getOption('assets_url')).'" rel="stylesheet">'.PHP_EOL;
			}
		}
		return $s_html;
	}
	
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
				$s_html .= '<script src="'.str_replace('[[assets_hash]]', $a_asset['hash'], $this->getReef()->getOption('assets_url')).'"></script>'.PHP_EOL;
			}
		}
		return $s_html;
	}
	
	public function getCSS($a_options = []) {
		return $this->getAssets('CSS', $a_options);
	}
	
	public function getJS($a_options = []) {
		return $this->getAssets('JS', $a_options);
	}
	
	public function addLocalCSS($s_path) {
		$this->customAssets['CSS'][] = __DIR__ . '/../'.$s_path;
	}
	
	public function addLocalJS($s_path) {
		$this->customAssets['JS'][] = __DIR__ . '/../'.$s_path;
	}
	
	private function getAssets($s_type, $a_options) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$a_remoteAssets = $this->getReef()->getSetup()->getLayout()->$s_assetFnc();
		
		foreach($this->getComponents() as $Component) {
			$a_assets = $Component->$s_assetFnc();
			
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
	
	private function checkLocalAsset($s_type, &$s_assetsHash = null) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new InvalidArgumentException("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$Cache = $this->getReef()->getCache();
		$a_localAssets = [];
		
		if($s_type == 'JS') {
			$s_mainFile = __DIR__ . '/../assets/script.js';
			
			$a_localAssets[__DIR__ . '/../assets/ConditionEvaluator.js'] = filemtime(__DIR__ . '/../assets/ConditionEvaluator.js');
		}
		else if($s_type == 'CSS') {
			$s_mainFile = __DIR__ . '/../assets/style.css';
		}
		$a_localAssets[$s_mainFile] = filemtime($s_mainFile);
		
		foreach($this->customAssets[$s_type] as $s_assetPath) {
			$a_localAssets[$s_assetPath] = filemtime($s_assetPath);
		}
		
		foreach($this->getComponents() as $Component) {
			$a_assets = $Component->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] == 'local') {
					$s_path = $a_asset['path'];
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
		
		$s_assetsHash = strtolower($s_type).':'.$s_assetsHash . '/' . $a_cache['created'];
	}
}
