<?php

namespace Reef\Session;

/**
 * This is the session class used by Reef, providing functionality to get and set session
 * values based on specified contexts. It uses the session implementation submitted during
 * the initialization of Reef.
 */
class ContextSession {
	
	/**
	 * The session implementation to use
	 * @type SessionInterface
	 */
	protected $SessionObject;
	
	/**
	 * Constructor
	 * @param SessionInterface $SessionObject The session implementation to use
	 */
	public function __construct(SessionInterface $SessionObject) {
		$this->SessionObject = $SessionObject;
	}
	
	/**
	 * Determine the context string of a context object
	 * @param mixed $context The context to determine the context string of
	 * @return string The identifying context string
	 */
	protected function resolveContext($context) : string {
		if($context instanceof \Reef\Components\Component) {
			return 'component:'.$context::COMPONENT_NAME.':';
		}
		
		if($context instanceof \Reef\Components\Field) {
			$s_formContext = ($context->getForm() instanceof \Reef\Form\AbstractStorableForm) ? $context->getForm()->getUUID() : 'tmp';
			return 'field:'.$context->getComponent()::COMPONENT_NAME.':'.$s_formContext.':';
		}
		
		if($context instanceof \Reef\Components\FieldValue) {
			$s_submissionContext = ($context->getSubmission() instanceof \Reef\Form\AbstractStoredSubmission) ? $context->getSubmission()->getUUID() : 'tmp';
			return 'value:'.$context->getField()->getComponent()::COMPONENT_NAME.':'.$s_submissionContext.':';
		}
		
		if($context instanceof \Reef\Submission\AbstractStoredSubmission) {
			return 'submission:'.$context->getForm()->getUUID().':'.$context->getUUID().':';
		}
		
		if($context instanceof \Reef\Submission\TempSubmission) {
			return 'submission:tmp:';
		}
		
		if($context instanceof \Reef\Form\AbstractStorableForm) {
			return 'form:'.$context->getUUID().':';
		}
		
		if($context instanceof \Reef\Form\TempForm) {
			return 'form:tmp:';
		}
		
		throw new \Reef\Exception\BadMethodCallException('Invalid context');
	}
	
	/**
	 * Set a session value
	 * @param mixed $context The context to place the value in
	 * @param string $s_name The value name
	 * @param mixed $m_value The value
	 */
	public function set($context, string $s_name, $m_value) {
		$this->SessionObject->set($this->resolveContext($context) . $s_name, $m_value);
	}
	
	/**
	 * Determine whether a session value exists
	 * @param mixed $context The context to place the value in
	 * @param string $s_name The value name
	 * @return bool True if it exists
	 */
	public function has($context, string $s_name) : bool {
		return $this->SessionObject->has($this->resolveContext($context) . $s_name);
	}
	
	/**
	 * Get a session value
	 * @param mixed $context The context to place the value in
	 * @param string $s_name The value name
	 * @param mixed $m_default A default value to return if the value does not exist, null by default
	 * @return mixed The value
	 */
	public function get($context, string $s_name, $m_default = null) {
		return $this->SessionObject->get($this->resolveContext($context) . $s_name, $m_default);
	}
	
	/**
	 * Remove a session value
	 * @param mixed $context The context to place the value in
	 * @param string $s_name The value name
	 * @return mixed The removed value
	 */
	public function remove($context, string $s_name) {
		return $this->SessionObject->remove($this->resolveContext($context) . $s_name);
	}
}
