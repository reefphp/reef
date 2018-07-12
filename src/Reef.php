<?php

namespace Reef;

use \Reef\Trait_Locale;
use \Reef\Storage\DataStore;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\Storage;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Yaml\Yaml;

require(__DIR__ . '/functions.php');

class Reef {
	
	use Trait_Locale;
	
	/**
	 * All setup that cannot change after Reef is initialized
	 * @type ReefSetup
	 */
	private $ReefSetup;
	
	/**
	 * Place where the forms and submissions are stored
	 * @type DataStore
	 */
	private $DataStore;
	
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
	 * Reef assets object
	 * @type ReefAssets
	 */
	private $ReefAssets;
	
	/**
	 * Constructor
	 */
	public function __construct(ReefSetup $ReefSetup, $a_options = []) {
		
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
		
		$this->ReefSetup = $ReefSetup;
		$this->ReefSetup->checkSetup($this);
		
		$this->DataStore = new DataStore($this->ReefSetup->getStorageFactory());
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
	
	public function cache($s_cacheKey, $fn_val) {
		$Cache = $this->getCache();
		$s_cacheKey = str_replace(['{','}','(',')','/','\\','@',':'], '_', $s_cacheKey);
		
		if($Cache->has($s_cacheKey)) {
			return $Cache->get($s_cacheKey);
		}
		
		$m_val = $fn_val();
		$Cache->set($s_cacheKey, $m_val);
		
		return $m_val;
	}
	
	public function getOption($s_name) {
		return $this->a_options[$s_name];
	}
	
	public function getFormStorage() : Storage {
		return $this->DataStore->getFormStorage();
	}
	
	public function getDataStore() : DataStore {
		return $this->DataStore;
	}
	
	public function getFormIds() {
		return $this->getFormStorage()->list();
	}
	
	public function getSubmissionStorage($Form) {
		return $this->DataStore->getSubmissionStorage($Form);
	}
	
	public function getBuilder() : Builder {
		return new Builder($this);
	}
	
	public function getForm(int $i_formId) : Form {
		$Form = $this->newStoredForm();
		
		$Form->load($i_formId);
		
		return $Form;
	}
	
	public function newStoredForm() : StoredForm {
		if($this->ReefSetup->getStorageFactory() instanceof \Reef\Storage\NoStorageFactory) {
			throw new \Exception("Cannot create stored form using NoStorage");
		}
		
		return new StoredForm($this);
	}
	
	public function newTempForm() : TempForm {
		return new TempForm($this);
	}
	
	public function getSetup() : ReefSetup {
		return $this->ReefSetup;
	}
	
	public function getAsset($s_type, $s_assetsHash) {
		return FormAssets::getAssetByHash($this, $s_type, $s_assetsHash);
	}
	
	protected function fetchBaseLocale($s_locale) {
		return $this->cache('locale.reef.base.'.$s_locale, function() use($s_locale) {
			
			if(file_exists(static::getDir().'locale/'.$s_locale.'.yml')) {
				return Yaml::parseFile(static::getDir().'locale/'.$s_locale.'.yml')??[];
			}
			
			return [];
		});
	}
	
	public function getReefAssets() {
		if($this->ReefAssets == null) {
			$this->ReefAssets = new ReefAssets($this);
		}
		
		return $this->ReefAssets;
	}
	
}
