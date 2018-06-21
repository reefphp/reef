<?php

namespace Reef;

use \Reef\Storage\Storage;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Yaml\Yaml;

require(__DIR__ . '/functions.php');

class Reef {
	
	/**
	 * Place where the forms are stored
	 * @type Storage
	 */
	private $FormStorage;
	
	/**
	 * Mapping from component name to component class path
	 * @type ComponentMapper
	 */
	private $ComponentMapper;
	
	/**
	 * Options
	 * @type array
	 */
	private $a_options;
	
	/**
	 * Cache object
	 * @type FilesystemCache
	 */
	private $Cache;
	
	/**
	 * Constructor
	 */
	public function __construct(Storage $FormStorage, $a_options = []) {
		$this->FormStorage = $FormStorage;
		$this->ComponentMapper = new ComponentMapper($this);
		
		$this->a_options = [];
		if(isset($a_options['cache_dir'])) {
			$this->a_options['cache_dir'] = $a_options['cache_dir'];
		}
		else {
			$this->a_options['cache_dir'] = '/tmp/reef/';
			if(!is_dir($this->a_options['cache_dir'])) {
				mkdir($this->a_options['cache_dir'], 0644);
			}
		}
		
		$this->a_options['css_prefix'] = $a_options['css_prefix'] ?? 'rf-';
		$this->a_options['js_event_prefix'] = $a_options['js_event_prefix'] ?? 'reef:';
	}
	
	public static function getDir() : string {
		return __DIR__.'/../';
	}
	
	public function getCache() : FilesystemCache {
		if($this->Cache == null) {
			$this->Cache = new FilesystemCache('reef', 0, $this->a_options['cache_dir']);
		}
		return $this->Cache;
	}
	
	public function getOption($s_name) {
		return $this->a_options[$s_name];
	}
	
	public function getFormStorage() : Storage {
		return $this->FormStorage;
	}
	
	public function getStorage($a_storageDeclaration) {
		switch(strtolower($a_storageDeclaration['type']??'')) {
			case 'json':
				return new \Reef\Storage\JSONStorage($a_storageDeclaration['path']);
			break;
			
			case 'none':
				return new \Reef\Storage\NoStorage();
			break;
		}
		
		throw new \Exception('Invalid storage.');
	}
	
	public function getForm(int $i_formId) : Form {
		$Form = $this->newForm();
		
		$Form->load($i_formId);
		
		return $Form;
	}
	
	public function newForm() : Form {
		return new Form($this);
		
	}
	
	public function getComponentMapper() : ComponentMapper {
		return $this->ComponentMapper;
	}
	
	public function getAsset($s_type, $s_assetsHash) {
		return FormAssets::getAssetByHash($this, $s_type, $s_assetsHash);
	}
	
	/**
	 * Get locale array
	 * @param array|string $a_locales The locale to fetch, or null for default locale. If you provide multiple locales, the first available locale will be fetched
	 * @return array The locale data
	 */
	public function getLocale($a_locales = null) {
		if(!is_array($a_locales)) {
			$a_locales = [$a_locales];
		}
		
		$a_locales[] = 'en_US';
		$a_locales = array_unique(array_filter($a_locales));
		
		foreach($a_locales as $s_loc) {
			if(file_exists(static::getDir().'locale/'.$s_loc.'.yml')) {
				return Yaml::parseFile(static::getDir().'locale/'.$s_loc.'.yml')??[];
			}
		}
	}
	
	public function trans($s_key, $a_locales = null) {
		return $this->getLocale($a_locales)[$s_key]??null;
	}
	
}
