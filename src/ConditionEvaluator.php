<?php

namespace Reef;

use \Reef\Form\Form;
use \Reef\Submission\Submission;
use \Reef\Exception\ConditionException;

/**
 * Condition evaluation class
 * 
 * This class provides functionality for evaluating conditions. It has a counterpart
 * written in JavaScript, which at all times should be kept identical to this implementation.
 * 
 * Terminology:
 * 
 * A condition is a statement of the form
 *   `sub-condition [or/and sub-condition] [...]`
 * 
 * Here, a sub-condition is defined to be either of:
 *   - `(condition)`
 *   - a clause
 * 
 * A clause is either a boolean value (yes/no, true/false, 1/0) or a single field check operation, defined as
 *   `fieldname operator operand`
 * Which operators and operands are allowed in a field check operation depends on the
 * component that `fieldname` belongs to. In any case, the following holds:
 *   - fieldname is an ordinary field name, subject to regex \Reef::NAME_REGEXP
 *   - operator is one of the operators defined by field::getConditionOperators()
 *   - operand is a valid JSON expression
 */

class ConditionEvaluator {
	
	const WHITESPACE = " \t\r\n";
	
	/**
	 * The form this ConditionEvaluator is used on
	 * @type Form
	 */
	private $Form;
	
	/**
	 * The value fields of the form, cached for performance, index by field name
	 * @type Field[]
	 */
	private $a_fields;
	
	/**
	 * Token stack, holding any tokens that were read prematurely and should be saved for later processing
	 * @type string[]
	 */
	private $a_tokenStack;
	
	/**
	 * Cached empty submission for $Form
	 * @type Submission
	 */
	private $EmptySubmission;
	
	/**
	 * During evaluation, the submission we are currently working upon
	 * @type Submission
	 */
	private $Submission;
	
	/**
	 * During evaluation, the current condition
	 * @type string
	 */
	private $s_condition;
	
	/**
	 * During evaluation, the current cursor position
	 * @type int
	 */
	private $i_cursor;
	
	/**
	 * During evaluation, the length of the condition
	 * @type int
	 */
	private $i_length;
	
	/**
	 * During evaluation, whether to perform operand validation
	 * @type bool
	 */
	private $b_validate;
	
	/**
	 * Constructor
	 * @param Form $Form The form this evaluator is for
	 */
	public function __construct(\Reef\Form\Form $Form) {
		$this->Form = $Form;
		$this->a_fields = $this->Form->getValueFieldsByName();
	}
	
	/**
	 * Validate a condition
	 * 
	 * @param string|bool $s_condition The condition
	 * @param array &$a_errors (Out) Optional, the errors
	 * 
	 * @return bool True if the condition is valid, false otherwise
	 */
	public function validate($s_condition, array &$a_errors = null) : bool {
		if($this->EmptySubmission == null) {
			$this->EmptySubmission = $this->Form->newSubmission();
			$this->EmptySubmission->emptySubmission();
		}
		
		try {
			$this->evaluate($this->EmptySubmission, $s_condition, true);
			return true;
		}
		catch(ConditionException $e) {
			$a_errors[] = $e->getMessage();
			return false;
		}
	}
	
	/**
	 * Evaluate an entire condition
	 * 
	 * @param Submission $Submission The submission to evaluate against
	 * @param string|bool $s_condition The condition
	 * @param bool $b_validate Whether to perform operand validation, defaults to false
	 * 
	 * @return ?bool The boolean result, or null if the input condition was empty
	 * 
	 * @throws BadMethodCallException If called with a submission not belonging to the form this evaluator is initialized with
	 * @throws ConditionException If the input condition is invalid
	 */
	public function evaluate(Submission $Submission, $s_condition, bool $b_validate = false) : ?bool {
		if(is_bool($s_condition)) {
			return (bool)$s_condition;
		}
		$s_condition = (string)$s_condition;
		
		if($Submission->getForm() !== $this->Form) {
			throw new \Reef\Exception\BadMethodCallException("Caught non-related form and submission");
		}
		
		if(trim($s_condition) == '') {
			return null;
		}
		
		$this->Submission = $Submission;
		$this->s_condition = $s_condition;
		$this->i_length = strlen($this->s_condition);
		$this->i_cursor = 0;
		$this->a_tokenStack = [];
		$this->b_validate = $b_validate;
		
		$b_result = $this->condition();
		
		if($this->getToken() !== '') {
			throw new ConditionException('Caught invalid condition');
		}
		
		return $b_result;
	}
	
	/**
	 * Evaluate the (sub)condition at the current cursor position
	 * @return bool The result of the (sub)condition
	 */
	private function condition() : bool {
		$b_result = null;
		$a_ands = [];
		
		while($this->i_cursor < $this->i_length) {
			
			$s_token = $this->getToken();
			
			if($s_token == '(') {
				$b_clause = $this->condition();
				
				if($this->getToken() !== ')') {
					throw new ConditionException("Caught runaway argument");
				}
			}
			else {
				$this->giveBackToken($s_token);
				$b_clause = $this->clause();
			}
			
			$a_ands[] = $b_clause;
			
			$s_token = $this->getToken();
			
			if($s_token != ')' && $s_token != 'and' && $s_token != 'or' && $s_token != '') {
				throw new ConditionException("Unexpected token '" . $s_token."'");
			}
			
			if($s_token != 'and') {
				$b_result = $b_result || (count(array_filter($a_ands)) == count($a_ands));
				$a_ands = [];
				
				if($s_token == ')') {
					$this->giveBackToken($s_token);
					break;
				}
			}
		}
		
		if($b_result === null) throw new ConditionException("Caught empty result");
		
		return $b_result;
	}
	
	/**
	 * Evaluate the clause at the current cursor position
	 * @return bool The result of the clause
	 */
	private function clause() : bool {
		$s_token = $this->getToken();
		
		if($s_token == '') {
			throw new ConditionException("Unexpected end of line");
		}
		if(in_array($s_token, ['true', 'yes', '1'])) {
			return true;
		}
		else if(in_array($s_token, ['false', 'no', '0'])) {
			return false;
		}
		
		$this->giveBackToken($s_token);
		return $this->fieldOperation();
	}
	
	/**
	 * Evaluate the field operation at the current cursor position
	 * @return bool The result of the field operation
	 */
	private function fieldOperation() : bool {
		[$s_fieldName, $s_operator, $m_operand] = $this->getFieldOperation();
		
		if($this->b_validate) {
			$this->Submission->getFieldValue($s_fieldName)->validateConditionOperation($s_operator, $m_operand);
		}
		
		return (bool)$this->Submission->getFieldValue($s_fieldName)->evaluateConditionOperation($s_operator, $m_operand);
	}
	
	/**
	 * Get the clause at the current cursor position
	 * @return array [fieldname, operator, operand]
	 */
	private function getFieldOperation() : array {
		$s_fieldName = $this->getToken();
		if(!isset($this->a_fields[$s_fieldName])) {
			throw new ConditionException("Invalid field name '".$s_fieldName."'");
		}
		
		$Field = $this->a_fields[$s_fieldName];
		$a_operators = $Field->getComponent()->getConditionOperators();
		
		if(empty($a_operators)) {
			throw new ConditionException("Field '".$s_fieldName."' does not support conditions");
		}
		
		$i_maxWords = 0;
		foreach($a_operators as $s_operator) {
			$i_maxWords = max($i_maxWords, substr_count($s_operator, ' ')+1);
		}
		
		$a_tokens = [];
		for($i=0; $i<$i_maxWords; $i++) {
			$s_token = $this->getToken();
			
			if($s_token == '') {
				break;
			}
			
			$a_tokens[] = $s_token;
		}
		
		$s_operator = null;
		for($i=count($a_tokens); $i>0; $i--) {
			if(false !== ($j = array_search(implode(' ', $a_tokens), $a_operators))) {
				$s_operator = $a_operators[$j];
				break;
			}
			
			$this->giveBackToken(array_pop($a_tokens));
		}
		
		if($s_operator === null) {
			throw new ConditionException("Invalid operator");
		}
		
		$s_operand = '';
		while(true) {
			$s_token = $this->getToken();
			
			if(in_array($s_token, ['', 'and', 'or', ')'])) {
				if($s_token !== '') {
					$this->giveBackToken($s_token);
				}
				break;
			}
			
			$s_operand .= (($s_operand == '') ? '' : ' ') . $s_token;
		}
		
		if($s_operand == '') {
			$m_operand = $s_operand;
		}
		else {
			$m_operand = json_decode($s_operand, true);
			
			if($m_operand === null && strtolower($s_operand) != 'null') {
				throw new ConditionException('Invalid operand "'.$s_operand.'"');
			}
		}
		
		return [$s_fieldName, $s_operator, $m_operand];
	}
	
	/**
	 * Give back a token to be processed later by another method
	 * @param string $s_token The token
	 */
	private function giveBackToken(string $s_token) : void {
		$this->a_tokenStack[] = $s_token;
	}
	
	/**
	 * Get the next token at the current cursor position, or the last one on the stack if present
	 * @return string The next token
	 */
	private function getToken() : string {
		if(!empty($this->a_tokenStack)) {
			return array_pop($this->a_tokenStack);
		}
		
		// Skip whitespace
		$s_token = '';
		for(; $this->i_cursor < $this->i_length && strpos(self::WHITESPACE, $this->s_condition[$this->i_cursor]) !== false; $this->i_cursor++);
		
		// Return empty string if we reached the end (the end consisted of whitespace)
		if($this->i_cursor >= $this->i_length) {
			return $s_token;
		}
		
		// Parentheses are tokens, return them irrespective of possible whitespace after them
		$s_char = $this->s_condition[$this->i_cursor];
		if($s_char == '(' || $s_char == ')') {
			$this->i_cursor++;
			return $s_char;
		}
		
		// Detect whether the current token is delimited by quotes
		$s_find = null;
		if($s_char == '"' || $s_char == "'") {
			$s_find = $s_char;
			$s_token .= $s_char;
			$this->i_cursor++;
		}
		
		// Collect the token, consisting of all characters until we arrive at whitespace or the closing parentheses, or the $s_find character if applicable
		while($this->i_cursor < $this->i_length && ($s_find !== null || strpos(self::WHITESPACE.')', $this->s_condition[$this->i_cursor]) === false)) {
			
			// Obtain the next character. Note we use mb_substr() to find the next UTF-8 character instead of the next byte
			$s_char = mb_substr(substr($this->s_condition, $this->i_cursor, 6), 0, 1);
			$s_token .= $s_char;
			$this->i_cursor += strlen($s_char);
			
			// If we found the $s_find character, determine whether it is escaped. Stop if not escaped
			if($s_find !== null && $s_char == $s_find) {
				$i_numBackslashes = 0;
				for($i=mb_strlen($s_token)-2; $i>=0; $i--) {
					if(mb_substr($s_token, $i, 1) != '\\') {
						break;
					}
					$i_numBackslashes++;
				}
				
				if($i_numBackslashes % 2 == 0) {
					break;
				}
			}
		}
		return $s_token;
	}
	
}
