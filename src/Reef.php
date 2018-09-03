<?php

namespace Reef;

use \Reef\Locale\Trait_ReefLocale;
use \Reef\Storage\DataStore;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\Storage;
use \Reef\Form\StoredFormFactory;
use \Reef\Form\StoredForm;
use \Reef\Form\TempStoredFormFactory;
use \Reef\Form\TempStoredForm;
use \Reef\Form\TempFormFactory;
use \Reef\Form\TempForm;
use \Reef\Session\ContextSession;
use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Yaml\Yaml;

require(__DIR__ . '/functions.php');

class Reef {
	
	use Trait_ReefLocale;
	
	const NAME_REGEXP = '^[a-z](((?!__)[a-z0-9_])*[a-z0-9])?$';
	
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
	 * Stored forms factory
	 * @type StoredFormFactory
	 */
	private $StoredFormFactory;
	
	/**
	 * Temporarily stored forms factory
	 * @type TempStoredFormFactory
	 */
	private $TempStoredFormFactory;
	
	/**
	 * Temporary form factory
	 * @type TempFormFactory
	 */
	private $TempFormFactory;
	
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
	 * Reef context session object
	 * @type ContextSession
	 */
	private $Session;
	
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
		$this->a_options['db_prefix'] = $a_options['db_prefix'] ?? 'reef_';
		$this->a_options['reef_name'] = $a_options['reef_name'] ?? 'reef';
		$this->a_options['locales'] = $a_options['locales'] ?? ['_no_locale'];
		$this->a_options['default_locale'] = $a_options['default_locale'] ?? reset($this->a_options['locales']) ?? 'en_US';
		$this->a_options['internal_request_url'] = $a_options['internal_request_url'] ?? './reef.php?hash=[[request_hash]]';
		$this->a_options['max_upload_size'] = $a_options['max_upload_size'] ?? 0;
		$this->a_options['files_dir'] = $a_options['files_dir'] ?? null;
		$this->a_options['byte_base'] = $a_options['byte_base'] ?? 1024;
		if(!in_array($this->a_options['byte_base'], [1000, 1024]) && $this->a_options['byte_base'] !== null) {
			$this->a_options['byte_base'] = 1024;
		}
		
		if($this->a_options['files_dir'] !== null) {
			if(!is_dir($this->a_options['files_dir']) || !is_writable($this->a_options['files_dir'])) {
				throw new \Reef\Exception\InvalidArgumentException("Cannot write to files dir '".$this->a_options['files_dir']."'");
			}
		}
		
		$this->ReefSetup = $ReefSetup;
		$this->DataStore = new DataStore($this);
		$this->ReefSetup->checkSetup($this);
		
		$SessionObject = $this->ReefSetup->getSessionObject();
		if($SessionObject instanceof \Reef\Session\PhpSession) {
			$SessionObject->setReef($this);
		}
		$this->Session = new ContextSession($SessionObject);
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
	
	public function getSession() {
		return $this->Session;
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
	
	public function getStoredFormFactory() : StoredFormFactory {
		if($this->ReefSetup->getStorageFactory() instanceof \Reef\Storage\NoStorageFactory) {
			throw new BadMethodCallException("Cannot get StoredFormFactory using NoStorage");
		}
		
		if($this->StoredFormFactory == null) {
			$this->StoredFormFactory = new \Reef\Form\StoredFormFactory($this);
		}
		return $this->StoredFormFactory;
	}
	
	public function getTempStoredFormFactory() : TempStoredFormFactory {
		if($this->ReefSetup->getStorageFactory() instanceof \Reef\Storage\NoStorageFactory) {
			throw new BadMethodCallException("Cannot get TempStoredFormFactory using NoStorage");
		}
		
		if($this->TempStoredFormFactory == null) {
			$this->TempStoredFormFactory = new \Reef\Form\TempStoredFormFactory($this);
		}
		return $this->TempStoredFormFactory;
	}
	
	public function getTempFormFactory() : TempFormFactory {
		if($this->TempFormFactory == null) {
			$this->TempFormFactory = new \Reef\Form\TempFormFactory($this);
		}
		return $this->TempFormFactory;
	}
	
	public function getForm(int $i_formId) : StoredForm {
		return $this->getStoredFormFactory()->load($i_formId);
	}
	
	public function getFormByUUID(string $s_formUUID) : StoredForm {
		return $this->getStoredFormFactory()->loadByUUID($s_formUUID);
	}
	
	public function newTempStoredForm(array $a_definition = []) : TempStoredForm {
		return $this->getTempStoredFormFactory()->createFromArray($a_definition);
	}
	
	public function newStoredForm(array $a_definition = []) : StoredForm {
		return $this->getStoredFormFactory()->createFromArray($a_definition);
	}
	
	public function newValidTempForm(array $a_definition = []) : TempForm {
		return $this->getTempFormFactory()->createFromValidatedArray($a_definition);
	}
	
	public function newTempForm(array $a_definition = []) : TempForm {
		return $this->getTempFormFactory()->createFromArray($a_definition);
	}
	
	public function getSetup() : ReefSetup {
		return $this->ReefSetup;
	}
	
	public function internalRequest(string $s_requestHash, array $a_options = []) {
		$a_requestHash = explode(':', $s_requestHash);
		if(count($a_requestHash) == 1) {
			throw new \Reef\Exception\InvalidArgumentException("Illegal request hash");
		}
		
		if($a_requestHash[0] == 'asset') {
			array_shift($a_requestHash);
			
			return $this->getReefAssets()->writeAssetByHash(implode(':', $a_requestHash));
		}
		
		if($a_requestHash[0] == 'form') {
			if($a_requestHash[1] == 'temp') {
				$Form = $a_options['form'] ?? $this->newTempForm();
			}
			else {
				$Form = $this->getFormByUUID($a_requestHash[1]);
				if(isset($a_options['form_check'])) {
					$a_options['form_check']($Form);
				}
			}
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Form->internalRequest(implode(':', $a_requestHash));
		}
		
		if($a_requestHash[0] == 'component') {
			$Component = $this->ReefSetup->getComponent($a_requestHash[1] . ':' . $a_requestHash[2]);
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Component->internalRequest(implode(':', $a_requestHash));
		}
		
		throw new \Reef\Exception\InvalidArgumentException('Invalid request hash');
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
		
		$a_helpers['internalRequest'] = function($s_requestHash, $Mustache) {
			return $this->internalRequestHelper($Mustache->render($s_requestHash));
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
	
	public function internalRequestHelper($s_requestHash) {
		if(substr($s_requestHash, 0, 6) == 'asset:') {
			$s_requestHash = $this->getReefAssets()->appendFiletime($s_requestHash);
		}
		
		return str_replace('[[request_hash]]', $s_requestHash, $this->getOption('internal_request_url'));
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
		
		$Form = $this->newValidTempForm($a_definition);
		foreach($Form->getFields() as $i_index => $Field) {
			if(!$Field->validateDeclaration($a_errors)) {
				throw new ValidationException([
					$i_index => [
						-1 => $a_errors,
					],
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
