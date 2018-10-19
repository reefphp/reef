<?php

namespace Reef;

use \Reef\Assets\ReefAssets;
use \Reef\Locale\Trait_ReefLocale;
use \Reef\Extension\ExtensionCollection;
use \Reef\Storage\DataStore;
use \Reef\Storage\StorageFactory;
use \Reef\Storage\Storage;
use \Reef\Form\StoredFormFactory;
use \Reef\Form\StoredForm;
use \Reef\Form\NonpersistableStoredForm;
use \Reef\Form\TempStorableFormFactory;
use \Reef\Form\TempStorableForm;
use \Reef\Form\TempFormFactory;
use \Reef\Form\TempForm;
use \Reef\Session\ContextSession;
use \Reef\Exception\BadMethodCallException;
use \Reef\Exception\ValidationException;
use Symfony\Component\Cache\Simple\FilesystemCache;
use Symfony\Component\Yaml\Yaml;

require_once(__DIR__ . '/functions.php');

/**
 * The Reef class is the main API class, providing ways to reach
 * all functionality of the library.
 */
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
	 * The extension collection
	 * @type ExtensionCollection
	 */
	private $ExtensionCollection;
	
	/**
	 * Stored forms factory
	 * @type StoredFormFactory
	 */
	private $StoredFormFactory;
	
	/**
	 * Temporarily stored forms factory
	 * @type TempStorableFormFactory
	 */
	private $TempStorableFormFactory;
	
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
	 * @param ReefSetup A reef setup, defining a well-defined basis on which this Reef object can operate
	 * @param array $a_options Options array, to choose from:
	 * NAMING:
	 *  - css_prefix              string   The CSS prefix to use, defaults to 'rf-'
	 *  - js_event_prefix         string   The JS prefix to use for events, defaults to 'reef:'
	 *  - db_prefix               string   The prefix to use for database tables, defaults to 'reef_'
	 *  - reef_name               string   The name of this reef. Make sure to use different names for different
	 *                                     reef instances on your website, if you are using more than one instance
	 * 
	 * CACHING:
	 *  - cache_dir               string   The directory to use for caching. Defaults to '/tmp/reef'
	 * 
	 * LOCALE:
	 *  - locales                 string[] The locales to use
	 *  - default_locale          string   The default locale, defaults to the first locale in `locales` if set, or
	 *                                     'en_US' otherwise
	 * 
	 * FILES:
	 *  - files_dir               string   The directory to store files in, when using a component that requires this
	 *  - byte_base               int      The default byte base; use 1024 for MiB notation, 1000 for MB notation
	 *  - max_upload_size         int      Maximum allowed upload file size. In addition, Reef also auto-detects
	 *                                     other maxima induced by the environment
	 * 
	 * OTHER:
	 *  - internal_request_url    string   URL to call for internal requests. Required for a well-behaving application.
	 *                                     Defaults to './reef.php?hash=[[request_hash]]', but you will probably want to
	 *                                     set a custom URL. Must contain `[[request_hash]]`
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
		$this->ExtensionCollection = new ExtensionCollection($this);
		$this->ReefSetup->checkSetup($this);
		
		$SessionObject = $this->ReefSetup->getSessionObject();
		if($SessionObject instanceof \Reef\Session\PhpSession) {
			$SessionObject->setReef($this);
		}
		$this->Session = new ContextSession($SessionObject);
	}
	
	/**
	 * Get the Reef root directory. As this file is situated in src/, the root is one
	 * level above.
	 * @return string The Reef root directory
	 */
	public static function getDir() : string {
		return realpath(__DIR__.'/../') . '/';
	}
	
	/**
	 * Get the cache object for this Reef instance
	 * @return FilesystemCache The cache object
	 */
	public function getCache() : FilesystemCache {
		if($this->Cache == null) {
			$this->Cache = new FilesystemCache('reef', 0, $this->a_options['cache_dir']);
		}
		return $this->Cache;
	}
	
	/**
	 * Get a cache value defined by the callback. Returns the cached value if present, or executes
	 * the callback and stores & returns that value instead otherwise
	 * @param string $s_cacheKey The key identifying the desired value
	 * @param callable $fn_val Callback to a function yielding the value that should be cached
	 * @return mixed The (cached) value
	 */
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
	
	/**
	 * Get the session object of this Reef
	 * @return ContextSession
	 */
	public function getSession() {
		return $this->Session;
	}
	
	/**
	 * Get the value of the given option
	 * @param string $s_name The option name
	 * @return mixed The value
	 */
	public function getOption($s_name) {
		return $this->a_options[$s_name];
	}
	
	/**
	 * Get the form storage
	 * @return Storage The form storage
	 */
	public function getFormStorage() : Storage {
		return $this->DataStore->getFormStorage();
	}
	
	/**
	 * Get the data store
	 * @return DataStore The data store
	 */
	public function getDataStore() : DataStore {
		return $this->DataStore;
	}
	
	/**
	 * Get the extension collection
	 * @return ExtensionCollection The extension collection
	 */
	public function getExtensionCollection() : ExtensionCollection {
		return $this->ExtensionCollection;
	}
	
	/**
	 * Get all stored form ids
	 * @return int[] The form ids
	 */
	public function getFormIds() {
		return $this->getFormStorage()->list();
	}
	
	/**
	 * Get the submission storage of a form
	 * @param Form $Form The form to obtain the submission storage for
	 * @return Storage The submission storage
	 */
	public function getSubmissionStorage($Form) {
		return $this->DataStore->getSubmissionStorage($Form);
	}
	
	/**
	 * Return a new Builder object for this Reef
	 * @return Builder
	 */
	public function getBuilder() : Builder {
		return new Builder($this);
	}
	
	/**
	 * Switch to an other layout. Must be one of the layouts initialized
	 * in the reef setup.
	 * @param ?string $s_layoutName The layout name, or null for the default layout
	 */
	public function setLayout(?string $s_layoutName) {
		$this->ReefSetup->setLayout($s_layoutName);
	}
	
	/**
	 * Retrieve the StoredForm factory
	 * @return StoredFormFactory
	 * @throws BadMethodCallException When there is no storage attached
	 */
	public function getStoredFormFactory() : StoredFormFactory {
		if($this->ReefSetup->getStorageFactory() instanceof \Reef\Storage\NoStorageFactory) {
			throw new BadMethodCallException("Cannot get StoredFormFactory using NoStorage");
		}
		
		if($this->StoredFormFactory == null) {
			$this->StoredFormFactory = new \Reef\Form\StoredFormFactory($this);
		}
		return $this->StoredFormFactory;
	}
	
	/**
	 * Retrieve the TempStorableForm factory
	 * @return TempStorableFormFactory
	 * @throws BadMethodCallException When there is no storage attached
	 */
	public function getTempStorableFormFactory() : TempStorableFormFactory {
		if($this->ReefSetup->getStorageFactory() instanceof \Reef\Storage\NoStorageFactory) {
			throw new BadMethodCallException("Cannot get TempStorableFormFactory using NoStorage");
		}
		
		if($this->TempStorableFormFactory == null) {
			$this->TempStorableFormFactory = new \Reef\Form\TempStorableFormFactory($this);
		}
		return $this->TempStorableFormFactory;
	}
	
	/**
	 * Retrieve the TempForm factory
	 * @return TempFormFactory
	 */
	public function getTempFormFactory() : TempFormFactory {
		if($this->TempFormFactory == null) {
			$this->TempFormFactory = new \Reef\Form\TempFormFactory($this);
		}
		return $this->TempFormFactory;
	}
	
	/**
	 * Get a form by its id
	 * @param int $i_formId The form id
	 * @return StoredForm
	 */
	public function getForm(int $i_formId) : StoredForm {
		return $this->getStoredFormFactory()->load($i_formId);
	}
	
	/**
	 * Get a form by its id
	 * @param int $i_formId The form id
	 * @return NonpersistableStoredForm
	 */
	public function getNonpersistableForm(int $i_formId) : NonpersistableStoredForm {
		return $this->getStoredFormFactory()->loadNonpersistable($i_formId);
	}
	
	/**
	 * Get a form by its uuid
	 * @param string $s_formUUID The form uuid
	 * @return StoredForm
	 */
	public function getFormByUUID(string $s_formUUID) : StoredForm {
		return $this->getStoredFormFactory()->loadByUUID($s_formUUID);
	}
	
	/**
	 * Get a form by its uuid
	 * @param string $s_formUUID The form uuid
	 * @return NonpersistableStoredForm
	 */
	public function getNonpersistableFormByUUID(string $s_formUUID) : NonpersistableStoredForm {
		return $this->getStoredFormFactory()->loadNonpersistableByUUID($s_formUUID);
	}
	
	/**
	 * Create a new TempStorableForm
	 * @param array $a_definition The form definition (optional)
	 * @return TempStorableForm
	 */
	public function newTempStorableForm(array $a_definition = []) : TempStorableForm {
		return $this->getTempStorableFormFactory()->createFromArray($a_definition);
	}
	
	/**
	 * Create a new StoredForm. In most cases, you'll likely want to use newTempStorableForm() instead
	 * @param array $a_definition The form definition (optional)
	 * @return StoredForm
	 */
	public function newStoredForm(array $a_definition = []) : StoredForm {
		return $this->getStoredFormFactory()->createFromArray($a_definition);
	}
	
	/**
	 * Create a new TempForm without performing any definition validation
	 * @param array $a_definition The form definition (optional)
	 * @return TempForm
	 */
	public function newValidTempForm(array $a_definition = []) : TempForm {
		return $this->getTempFormFactory()->createFromValidatedArray($a_definition);
	}
	
	/**
	 * Create a new TempForm
	 * @param array $a_definition The form definition (optional)
	 * @return TempForm
	 */
	public function newTempForm(array $a_definition = []) : TempForm {
		return $this->getTempFormFactory()->createFromArray($a_definition);
	}
	
	/**
	 * Get the ReefSetup object
	 * @return ReefSetup
	 */
	public function getSetup() : ReefSetup {
		return $this->ReefSetup;
	}
	
	/**
	 * Entry-point for performin internal requests. A call to the `internal_request_url` URL
	 * should be redirected to this function
	 * 
	 * @param string $s_requestHash The used request hash
	 * @param array $a_options Array of options that you can pass yourself. To choose from:
	 *  - form_check        function  Callback to e.g. check the current user may access the
	 *                                requested form. Receives the Form as first parameter
	 *  - submission_check  function  Callback to e.g. check the current user may access the
	 *                                requested submission. Receives the Submission as first parameter
	 * 
	 * @throws \Reef\Exception\InvalidArgumentException When the request hash is invalid
	 */
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
			
			return $Form->internalRequest(implode(':', $a_requestHash), $a_options);
		}
		
		if($a_requestHash[0] == 'component') {
			$Component = $this->ReefSetup->getComponent($a_requestHash[1] . ':' . $a_requestHash[2]);
			
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			array_shift($a_requestHash);
			
			return $Component->internalRequest(implode(':', $a_requestHash), $a_options);
		}
		
		throw new \Reef\Exception\InvalidArgumentException('Invalid request hash');
	}
	
	/**
	 * Return array of general assets used by Reef
	 * @return string[]
	 */
	public function getAssets() {
		return [
			'builder-tick' => 'assets/img/builder-tick.svg',
			'builder-error' => 'assets/img/builder-error.svg',
			'builder-spinner' => 'assets/img/builder-spinner.svg',
			'builder-question' => 'assets/img/builder-question.svg',
		];
	}
	
	/**
	 * Obtain a new Mustache_Engine instance, with some general helpers already added
	 * @return \Mustache_Engine
	 */
	public function newMustache() : \Mustache_Engine {
		$a_helpers = [];
		
		$a_helpers['CSSPRFX'] = $this->getOption('css_prefix');
		
		$a_helpers['internalRequest'] = function($s_requestHash, $Mustache) {
			return $this->internalRequestHelper($Mustache->render($s_requestHash));
		};
		
		$a_helpers['nl2br'] = function($s_template, $Mustache) {
			return nl2br($Mustache->render($s_template));
		};
		
		$Mustache = new \Mustache_Engine([
			'helpers' => $a_helpers,
			'cache' => $this->getOption('cache_dir').'mustache/',
		]);
		
		return $Mustache;
	}
	
	/**
	 * Get the ReefAssets object
	 * @return ReefAssets
	 */
	public function getReefAssets() {
		if($this->ReefAssets == null) {
			$this->ReefAssets = new ReefAssets($this);
		}
		
		return $this->ReefAssets;
	}
	
	/**
	 * InternalRequest helper for Mustache
	 * @param string $s_requestHash The request hash
	 * @return string The resulting internal request URL
	 */
	public function internalRequestHelper($s_requestHash) {
		if(substr($s_requestHash, 0, 6) == 'asset:') {
			$s_requestHash = $this->getReefAssets()->appendFiletime($s_requestHash);
		}
		
		return str_replace('[[request_hash]]', $s_requestHash, $this->getOption('internal_request_url'));
	}
	
	/**
	 * Validate a form definition
	 * @param array $a_definition The form definition to check
	 * @throws ValidationException If the form definition is invalid
	 */
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
	
	/**
	 * Validate a field declaration
	 * @param array $a_definition The field declaration to check
	 * @throws ValidationException If the field declaration is invalid
	 */
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
