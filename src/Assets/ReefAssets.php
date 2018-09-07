<?php

namespace Reef\Assets;

use \Reef\Reef;
use \Reef\Exception\DomainException;
use \Reef\Exception\InvalidArgumentException;

/**
 * ReefAssets can be used to load all assets for all components that are
 * loaded into the Reef setup
 */
class ReefAssets extends Assets {
	
	/**
	 * The Reef object
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * Constructor
	 * @param Reef $Reef
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
	}
	
	/**
	 * @inherit
	 */
	public function getReef() : Reef {
		return $this->Reef;
	}
	
	/**
	 * @inherit
	 */
	protected function getComponents() : array {
		return array_values($this->Reef->getSetup()->getComponentMapping());
	}
	
	/**
	 * Write an asset file to the browser, identified by the asset hash.
	 * This is a specific kind of internal request.
	 * This function exists the script.
	 * @param string $s_assetsHash The asset hash to write the asset of
	 */
	public function writeAssetByHash($s_assetsHash) {
		
		header_remove('Pragma');
		header('Cache-Control: public, max-age=31536000');
		
		if(substr($s_assetsHash, 0, 3) == 'js:' || substr($s_assetsHash, 0, 4) == 'css:') {
			$this->writeVarAsset($s_assetsHash);
		}
		
		[$s_assetType, $s_subName, $s_assetName] = $this->parseAssetHash($s_assetsHash);
		
		if($s_assetType == 'reef') {
			$this->writeStaticAsset($s_assetName, Reef::getDir(), $this->Reef->getAssets());
		}
		
		if($s_assetType == 'component') {
			$Component = $this->Reef->getSetup()->getComponent($s_subName);
			$this->writeStaticAsset($s_assetName, $Component::getDir(), $Component->getAssets());
		}
		
		if($s_assetType == 'extension') {
			$Extension = $this->Reef->getExtensionCollection()->getExtension($s_subName);
			$this->writeStaticAsset($s_assetName, $Extension::getDir(), $Extension->getAssets());
		}
		
	}
	
	/**
	 * Write a JS or CSS asset file to the browser, identified by the asset hash.
	 * This function exists the script.
	 * @param string $s_assetsHash The asset hash to write the asset of
	 */
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
	
	/**
	 * Write a static asset file to the browser, identified by the asset hash.
	 * This function exists the script.
	 * @param string $s_assetName The asset name as returned by parseAssetHash()
	 * @param string $s_dir The directory to search the asset in
	 * @param string[] $a_assets Array of available asset files
	 */
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
	
	/**
	 * Parse an asset hash (not a JS or CSS asset), determining whether it is a Reef asset or a Component asset
	 * @param string $s_assetsHash The asset hash to parse
	 * @return string[] [type ('reef'/'component'), subname (component name), asset name]
	 */
	private function parseAssetHash($s_assetHash) {
		$a_assetHash = explode(':', $s_assetHash);
		
		if($a_assetHash[0] == 'asset') {
			array_shift($a_assetHash);
		}
		
		if(count($a_assetHash) == 1) {
			throw new InvalidArgumentException("Illegal asset name");
		}
		
		$s_assetType = $a_assetHash[0];
		
		if(!in_array($s_assetType, ['reef', 'component', 'extension'])) {
			throw new InvalidArgumentException("Illegal asset type");
		}
		
		// reef:asset_name:12345
		if($s_assetType == 'reef') {
			$s_subName = null;
			$s_assetName = $a_assetHash[1];
		}
		
		// component:vendor:name:asset_name:12345, extension:vendor:name:asset_name:12345
		if($s_assetType == 'component' || $s_assetType == 'extension') {
			$s_subName = $a_assetHash[1] . ':' . $a_assetHash[2];
			$s_assetName = $a_assetHash[3];
		}
		
		return [$s_assetType, $s_subName, $s_assetName];
	}
	
	/**
	 * Append the file time to an asset hash, to facilitate efficient browser caching
	 * @param string $s_assetsHash The asset hash to append the file time to
	 * @return string The new asset hash
	 */
	public function appendFiletime($s_assetHash) {
		
		[$s_assetType, $s_subName, $s_assetName] = $this->parseAssetHash($s_assetHash);
		
		$s_newAssetHash = '';
		
		if($s_assetType == 'reef') {
			$s_newAssetHash = 'reef:'.$s_assetName.':'.filemtime(Reef::getDir() . $this->Reef->getAssets()[$s_assetName]);
		}
		
		if($s_assetType == 'component') {
			$Component = $this->Reef->getSetup()->getComponent($s_subName);
			$s_newAssetHash = 'component:'.$s_subName.':'.$s_assetName.':'.filemtime($Component::getDir().$Component->getAssets()[$s_assetName]);
		}
		
		if($s_assetType == 'extension') {
			$Asset = $this->Reef->getExtensionCollection()->getExtension($s_subName);
			$s_newAssetHash = 'extension:'.$s_subName.':'.$s_assetName.':'.filemtime($Asset::getDir().$Asset->getAssets()[$s_assetName]);
		}
		
		return 'asset:'.$s_newAssetHash;
	}
	
}
