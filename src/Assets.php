<?php

namespace Reef;

abstract class Assets {
	
	private $customAssets = ['CSS' => [], 'JS' => []];
	
	abstract public function getReef() : Reef;
	abstract protected function getComponents() : array;
	
	public function getCSSHTML() {
		$a_assets = $this->getCSS();
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
	
	public function getJSHTML() {
		$a_assets = $this->getJS();
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
	
	public function getCSS() {
		return $this->getAssets('CSS');
	}
	
	public function getJS() {
		return $this->getAssets('JS');
	}
	
	public function addLocalCSS($s_path) {
		$this->customAssets['CSS'][] = __DIR__ . '/../'.$s_path;
	}
	
	public function addLocalJS($s_path) {
		$this->customAssets['JS'][] = __DIR__ . '/../'.$s_path;
	}
	
	private function getAssets($s_type) {
		if($s_type != 'JS' && $s_type != 'CSS') {
			throw new \Exception("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$a_remoteAssets = [];
		
		foreach($this->getComponents() as $Component) {
			$a_assets = $Component->$s_assetFnc();
			
			foreach($a_assets as $a_asset) {
				if($a_asset['type'] == 'remote') {
					$a_remoteAssets[$a_asset['name']] = $a_asset;
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
			throw new \Exception("Invalid asset type '".$s_type."'.");
		}
		$s_assetFnc = 'get'.$s_type;
		
		$Cache = $this->getReef()->getCache();
		$a_localAssets = [];
		
		if($s_type == 'JS') {
			$s_mainFile = __DIR__ . '/../assets/script.js';
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
