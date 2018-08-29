<?php

namespace Reef;

use \Reef\Exception\DomainException;
use \Reef\Exception\InvalidArgumentException;

class ReefAssets extends Assets {
	
	private $Reef;
	
	/**
	 * Constructor
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	public function getReef() : Reef {
		return $this->Reef;
	}
	
	protected function getComponents() : array {
		return array_values($this->Reef->getSetup()->getComponentMapping());
	}
	
	public function writeAssetByHash($s_assetsHash) {
		
		header_remove('Pragma');
		header('Cache-Control: public, max-age=31536000');
		
		if(substr($s_assetsHash, 0, 3) == 'js:' || substr($s_assetsHash, 0, 4) == 'css:') {
			$this->writeVarAsset($s_assetsHash);
		}
		
		[$s_assetType, $s_subName, $s_assetName] = $this->parseAssetHash($s_assetsHash);
		
		if($s_assetType == 'reef') {
			$this->writeStaticAsset($s_assetName, __DIR__.'/../', $this->Reef->getAssets());
		}
		
		if($s_assetType == 'component') {
			$Component = $this->Reef->getSetup()->getComponent($s_subName);
			$this->writeStaticAsset($s_assetName, $Component::getDir(), $Component->getAssets());
		}
		
	}
	
	private function writeVarAsset($s_assetsHash) {
		$s_type = (substr($s_assetsHash, 0, 3) == 'js:') ? 'js' : 'css';
		$s_assetsHash = substr($s_assetsHash, ($s_type == 'js') ? 3 : 4);
		
		if(false !== ($i_colPos = strpos($s_assetsHash, ':'))) {
			// Remove the timestamp part
			$s_assetsHash = substr($s_assetsHash, 0, $i_colPos);
		}
		
		$s_cacheKey = 'asset.'.$s_assetsHash.'.'.strtolower($s_type);
		
		if(!$this->Reef->getCache()->has($s_cacheKey)) {
			throw new DomainException("Invalid assets hash");
		}
		
		$a_cache = $this->Reef->getCache()->get($s_cacheKey);
		
		if($s_type == 'js') {
			header('Content-type: text/javascript');
		}
		else if($s_type == 'css') {
			header('Content-type: text/css');
		}
		
		echo $a_cache['content'];
		die();
	}
	
	private function writeStaticAsset($s_assetName, $s_dir, $a_assets) {
		if(!isset($a_assets[$s_assetName])) {
			throw new DomainException("Unknown asset name");
		}
		
		$s_filename = $s_dir . $a_assets[$s_assetName];
		
		if(!file_exists($s_filename)) {
			throw new DomainException("Unknown asset");
		}
		
		header('Content-type: '.mime_content_type($s_filename));
		echo file_get_contents($s_filename);
		die();
	}
	
	private function parseAssetHash($s_assetHash) {
		$a_assetHash = explode(':', $s_assetHash);
		
		if($a_assetHash[0] == 'asset') {
			array_shift($a_assetHash);
		}
		
		if(count($a_assetHash) == 1) {
			throw new InvalidArgumentException("Illegal asset name");
		}
		
		$s_assetType = $a_assetHash[0];
		
		if(!in_array($s_assetType, ['reef', 'component'])) {
			throw new InvalidArgumentException("Illegal asset type");
		}
		
		// reef:asset_name:12345
		if($s_assetType == 'reef') {
			$s_subName = null;
			$s_assetName = $a_assetHash[1];
		}
		
		// component:vendor:name:asset_name:12345
		if($s_assetType == 'component') {
			$s_subName = $a_assetHash[1] . ':' . $a_assetHash[2];
			$s_assetName = $a_assetHash[3];
		}
		
		return [$s_assetType, $s_subName, $s_assetName];
	}
	
	public function appendFiletime($s_assetHash) {
		
		[$s_assetType, $s_subName, $s_assetName] = $this->parseAssetHash($s_assetHash);
		
		$s_newAssetHash = '';
		
		if($s_assetType == 'reef') {
			$s_newAssetHash = 'reef:'.$s_assetName.':'.filemtime(__DIR__.'/../'.$this->Reef->getAssets()[$s_assetName]);
		}
		
		if($s_assetType == 'component') {
			$Component = $this->Reef->getSetup()->getComponent($s_subName);
			$s_newAssetHash = 'component:'.$s_subName.':'.$s_assetName.':'.filemtime($Component::getDir().$Component->getAssets()[$s_assetName]);
		}
		
		return 'asset:'.$s_newAssetHash;
	}
	
}
