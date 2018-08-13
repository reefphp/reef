<?php

namespace Reef;

use \Reef\Locale\Trait_ReefLocale;
use \Reef\Storage\DataStore;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\Storage;
use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Yaml\Yaml;

require(__DIR__ . '/functions.php');

class Reef {
	
	use Trait_ReefLocale;
	
	const NAME_REGEXP = '^[a-zA-Z](((?!__)[a-zA-Z0-9_])*[a-zA-Z0-9])?$';
	
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
		$this->a_options['locales'] = $a_options['locales'] ?? ['-'];
		$this->a_options['default_locale'] = $a_options['default_locale'] ?? 'en_US';
		$this->a_options['assets_url'] = $a_options['assets_url'] ?? './reef.php?hash=[[assets_hash]]';
		
		$this->ReefSetup = $ReefSetup;
		$this->ReefSetup->checkSetup($this);
		
		$this->DataStore = new DataStore($this->ReefSetup->getStorageFactory(), ['prefix' => $a_options['db_prefix'] ?? 'reef_']);
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
			throw new BadMethodCallException("Cannot create stored form using NoStorage");
		}
		
		return new StoredForm($this);
	}
	
	public function newTempForm() : TempForm {
		return new TempForm($this);
	}
	
	public function getSetup() : ReefSetup {
		return $this->ReefSetup;
	}
	
	public function writeAsset($s_assetsHash) {
		$this->getReefAssets()->writeAssetByHash($s_assetsHash);
	}
	
	public function getAssets() {
		return [
			'builder-tick' => 'assets/img/builder-tick.svg',
			'builder-error' => 'assets/img/builder-error.svg',
			'builder-spinner' => 'assets/img/builder-spinner.svg',
			'builder-question' => 'assets/img/builder-question.svg',
		];
	}
	
	public function newMustache() : \Mustache_Engine {
		$a_helpers = [];
		
		$a_helpers['CSSPRFX'] = $this->getOption('css_prefix');
		
		$a_helpers['asset'] = function($s_assetHash, $Mustache) {
			return $this->getReefAssets()->assetHelper($Mustache->render($s_assetHash));
		};
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
			'cache' => $this->getOption('cache_dir').'mustache/',
		]);
		
		return $Mustache;
	}
	
	public function getReefAssets() {
		if($this->ReefAssets == null) {
			$this->ReefAssets = new ReefAssets($this);
		}
		
		return $this->ReefAssets;
	}
	
	public function checkDefinition(array $a_definition) {
		$a_unknown = array_diff(array_keys($a_definition), ['storage_name', 'fields', 'locale', 'locales', 'layout']);
		if(count($a_unknown) > 0) {
			throw new ValidationException([
				-1 => ['Unknown form values '.implode(', ', $a_unknown).''],
			]);
		}
		
		$a_names = [];
		foreach($a_definition['fields']??[] as $i_index => $a_fieldDecl) {
			
			// Check for duplicates
			if(isset($a_fieldDecl['name'])) {
				if(isset($a_names[$a_fieldDecl['name']])) {
					throw new ValidationException([
						-1 => ['Duplicate name found: '.$a_fieldDecl['name'].''],
					]);
				}
				$a_names[$a_fieldDecl['name']] = true;
			}
			
			try {
				// Check field declaration
				$this->checkDeclaration($a_fieldDecl);
			}
			catch(ValidationException $e) {
				throw new ValidationException([
					$i_index => $e->getErrors(),
				]);
			}
		}
	}
	
	public function checkDeclaration(array $a_declaration) {
		foreach(['component'] as $s_key) {
			if(!array_key_exists($s_key, $a_declaration)) {
				throw new ValidationException([
					-1 => ['Field value for '.$s_key.' not present'],
				]);
			}
		}
		
		if(!$this->ReefSetup->hasComponent($a_declaration['component'])) {
			throw new ValidationException([
				-1 => ['Invalid component name "'.$a_declaration['component'].'"'],
			]);
		}
		
		$Component = $this->ReefSetup->getComponent($a_declaration['component']);
		$DeclarationForm = $Component->generateCombinedDeclarationForm();
		
		$DeclarationSubmission = $DeclarationForm->newSubmission();
		$DeclarationSubmission->fromStructured($a_declaration);
		
		if(!$DeclarationSubmission->validate()) {
			$a_errors = $DeclarationSubmission->getErrors();
			
			throw new ValidationException([
				-1 => $a_errors,
			]);
		}
		
		if(!$Component->validateDeclaration($a_declaration, $a_errors)) {
			throw new ValidationException([
				-1 => $a_errors,
			]);
		}
		
	}
	
}
