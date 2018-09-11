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
			
			if(empty($s_hookName)) {
				$i_cursor = $i_closeCursor+2;
				continue;
			}
			
			// Determine modifier
			$s_modifier = substr($s_hookName, 0, 1);
			if(in_array($s_modifier, ['?', '+'])) {
				$s_hookName = substr($s_hookName, 1);
				$a_hookNames = explode($s_modifier, $s_hookName);
			}
			else {
				$s_modifier = '';
				$a_hookNames = [$s_hookName];
			}
			
			// Validate hook name(s)
			$b_valid = true;
			foreach($a_hookNames as $s_subHookName) {
				if(!preg_match('/'.str_replace('/', '\\/', \Reef\Reef::NAME_REGEXP).'/', $s_subHookName)) {
					$b_valid = false;
					break;
				}
			}
			if(!$b_valid) {
				$i_cursor = $i_closeCursor+2;
				continue;
			}
			
			if(in_array($s_modifier, ['?', '+'])) {
				$s_closeTag = '[[/'.$s_hookName.']]';
				$i_closeTagCursor = strpos($s_template, $s_closeTag, $i_cursor);
				if($i_closeTagCursor === false) {
					throw new \Reef\Exception\LogicException('Could not find close tag "'.$s_closeTag.'"');
				}
			}
			
			// Register hook names
			foreach($a_hookNames as $s_subHookName) {
				$a_meta['hookNames'][] = $s_subHookName;
			}
			
			// Get template
			$s_hookTemplate = '';
			foreach($a_hookNames as $s_subHookName) {
				$b_returnFirst = ($s_modifier == '?');
				$s_hookTemplate .= $ExtensionCollection->getHookTemplate($s_subHookName, $b_returnFirst);
				
				if($s_modifier == '') {
					break;
				}
				else if($s_modifier == '?' && !empty($s_hookTemplate)) {
					break;
				}
			};
			
			// Insert
			if($s_modifier == '') {
				// Replace tag
				$s_template = substr($s_template, 0, $i_cursor) . $s_hookTemplate . substr($s_template, $i_closeCursor+2);
				$i_cursor += strlen($s_hookTemplate);
			}
			else {
				if(!empty($s_hookTemplate)) {
					// Replace start through end tag
					$s_template = substr($s_template, 0, $i_cursor) . $s_hookTemplate . substr($s_template, $i_closeTagCursor+strlen($s_closeTag));
					$i_cursor += strlen($s_hookTemplate);
				}
				else {
					// Replace start and end tag only
					$i_openTagEnd = $i_cursor + strlen($s_modifier) + strlen($s_hookName) + 4;
					$s_hookTemplate = substr($s_template, $i_openTagEnd, $i_closeTagCursor - $i_openTagEnd);
					$s_template = substr($s_template, 0, $i_cursor) . $s_hookTemplate . substr($s_template, $i_closeTagCursor+strlen($s_closeTag));
					// $i_cursor remains the same
				}
			}
		}
		
		return [$s_template, $a_meta];
	}
	
}
