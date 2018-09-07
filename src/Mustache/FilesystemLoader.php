<?php

namespace Reef\Mustache;

use \Reef\Reef;

/**
 * Child class of the Mustache FilesystemLoader, to allow template preprocessing
 */
class FilesystemLoader extends \Mustache_Loader_FilesystemLoader {
	
	/**
	 * The Reef object
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * The base directory
	 * @type string
	 */
	private $s_baseDir;
	
	/**
	 * Array of options
	 * @type array
	 */
	private $a_options;
	
	/**
	 * Constructor
	 * @param Reef   $Reef      The Reef object
	 * @param string $s_baseDir Base directory containing Mustache template files
	 * @param array  $a_options Array of Loader options (default: [])
	 */
	public function __construct(Reef $Reef, string $s_baseDir, array $a_options = []) {
		parent::__construct($s_baseDir, array_diff_key($a_options, ['default_sub_dir']));
		$this->Reef = $Reef;
		$this->s_baseDir = $s_baseDir;
		$this->a_options = \Reef\array_subset($a_options, [
			'default_sub_dir',
		]);
		
		if(isset($this->a_options['default_sub_dir'])) {
			$this->a_options['default_sub_dir'] = rtrim($this->a_options['default_sub_dir'], '/') . '/';
		}
	}
	
	/**
	 * Load a Mustache file by name
	 * @param string $s_name
	 * @return string The template
	 */
	protected function loadFile($s_name) {
		return $this->getTemplateData('template', $s_name);
	}
	
	/**
	 * Get meta data about a Mustache file
	 * @param string $s_name
	 * @return array The meta data
	 */
	public function getMeta($s_name) {
		return $this->getTemplateData('templateMeta', $s_name);
	}
	
	/**
	 * Get template data
	 * @param string $s_type The type of data to get, either template or templateMeta
	 * @param string $s_name
	 * @return mixed
	 */
	protected function getTemplateData($s_type, $s_name) {
		if(!in_array($s_type, ['template', 'templateMeta'])) {
			throw new \Reef\Exception\LogicException('Invalid type "'.$s_type.'"');
		}
		
		if(!empty($this->a_options['default_sub_dir']) && strpos($s_name, '/') === false) {
			$s_name = $this->a_options['default_sub_dir'] . $s_name;
		}
		
		$ExtensionCollection = $this->Reef->getExtensionCollection();
		
		$s_cacheKey = md5($this->s_baseDir.'/'.$s_name.';'.$ExtensionCollection->getCollectionHash());
		
		$Cache = $this->Reef->getCache();
		
		if($Cache->has($s_type.'.'.$s_cacheKey)) {
			return $Cache->get($s_type.'.'.$s_cacheKey);
		}
		else {
			[$s_template, $a_meta] = $this->preprocessTemplate(parent::loadFile($s_name));
			
			$Cache->set('template.'.$s_cacheKey, $s_template);
			$Cache->set('templateMeta.'.$s_cacheKey, $a_meta);
			
			return ($s_type == 'template') ? $s_template : $a_meta;
		}
	}
	
	/**
	 * Preprocess a template. Expands [[hook]] tags
	 * @param string $s_template
	 * @return array [(string) The preprocessed template, (array) Meta data]
	 */
	protected function preprocessTemplate(string $s_template) {
		$ExtensionCollection = $this->Reef->getExtensionCollection();
		$i_cursor = 0;
		$a_meta = [
			'hookNames' => [],
		];
		
		while(false !== ($i_cursor = strpos($s_template, '[[', $i_cursor))) {
			$i_closeCursor = strpos($s_template, ']]', $i_cursor);
			if($i_closeCursor === false) {
				break;
			}
			
			$s_hookName = substr($s_template, $i_cursor+2, $i_closeCursor - ($i_cursor+2));
			if(!preg_match('/'.str_replace('/', '\\/', \Reef\Reef::NAME_REGEXP).'/', $s_hookName)) {
				$i_cursor = $i_closeCursor+2;
				continue;
			}
			
			$a_meta['hookNames'][] = $s_hookName;
			
			$s_hookTemplate = $ExtensionCollection->getHookTemplate($s_hookName);
			
			$s_template = substr($s_template, 0, $i_cursor) . $s_hookTemplate . substr($s_template, $i_closeCursor+2);
			$i_cursor += strlen($s_hookTemplate);
		}
		
		return [$s_template, $a_meta];
	}
	
}
