<?php

namespace Reef\Extension;

use \Reef\Reef;
use \Reef\Exception\DomainException;

/**
 * Class for manipulating extensions and calling their methods when necessary.
 */
class ExtensionCollection {
	
	/**
	 * The Reef object
	 * @type Reef
	 */
	private $Reef;
	
	/**
	 * The extensions
	 * @type Extension[]
	 */
	private $a_extensionMapping;
	
	/**
	 * The collection hash
	 * @type string
	 */
	private $s_collectionHash;
	
	/**
	 * Array of event listeners
	 * @type array
	 */
	private $a_eventListeners;
	
	/**
	 * Constructor
	 * @param Reef $Reef The Reef object
	 */
	public function __construct(Reef $Reef) {
		$this->Reef = $Reef;
		$this->a_extensionMapping = $Reef->getSetup()->getExtensionMapping();
		
		foreach($this->a_extensionMapping as $Extension) {
			$Extension->setExtensionCollection($this);
		}
		
		$a_extMapCopy = $this->a_extensionMapping;
		uasort($a_extMapCopy, function(Extension $Extension1, Extension $Extension2) {
			return $Extension1::getName() <=> $Extension2::getName();
		});
		
		$this->s_collectionHash = md5(implode(';', array_map(function(Extension $Extension) {
			return $Extension::getName();
		}, $a_extMapCopy)));
		
		$this->processEventListeners();
	}
	
	/**
	 * Return a Reef object
	 * @return Reef
	 */
	public function getReef() {
		return $this->Reef;
	}
	
	/**
	 * Return a hash that uniquely defines the collection of extensions in use
	 * @return string The hash
	 */
	public function getCollectionHash() {
		return $this->s_collectionHash;
	}
	
	/**
	 * Get the array of extensions
	 * @return Extension[]
	 */
	public function getExtensionMapping() {
		return $this->a_extensionMapping;
	}
	
	/**
	 * Get the requested extension
	 * @return Extension
	 */
	public function getExtension(string $s_extensionName) {
		if(!isset($this->a_extensionMapping[$s_extensionName])) {
			throw new DomainException("Extension not loaded: ".$s_extensionName);
		}
		
		return $this->a_extensionMapping[$s_extensionName];
	}
	
	/**
	 * Get combined extension template of a template hook
	 * @param string $s_hookName The hook name
	 * @param bool $b_returnFirst Whether to return on the first nonempty result. Defaults to false
	 * @return string The template
	 */
	public function getHookTemplate(string $s_hookName, bool $b_returnFirst = false) {
		$s_template = '';
		foreach($this->a_extensionMapping as $Extension) {
			$s_template .= $Extension->getHookTemplate($s_hookName);
			
			if($b_returnFirst && !empty(trim($s_template))) {
				break;
			}
		}
		return $s_template;
	}
	
	/**
	 * Register the event listeners of all extensions
	 */
	public function processEventListeners() {
		$this->a_eventListeners = [];
		
		foreach($this->a_extensionMapping as $Extension) {
			$a_listeners = $Extension::getSubscribedEvents();
			
			foreach($a_listeners as $s_eventName => $callable) {
				$this->a_eventListeners[$s_eventName][] = ($callable instanceof \Closure) ? $callable : \Closure::fromCallable([$Extension, $callable]);
			}
		}
	}
	
	/**
	 * Trigger an extension event
	 * @param string $s_eventName The event name to trigger
	 * @param ?array &$a_vars The variables to pass
	 */
	public function event($s_eventName, ?array $a_vars = []) {
		foreach($this->a_eventListeners[$s_eventName]??[] as $closure) {
			$closure($a_vars);
		}
	}
}
