<?php

namespace Reef;

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

class Condition {
	
	const WHITESPACE = " \t\r\n";
	
	private $Submission;
	private $a_fields;
	
	private $s_condition;
	private $i_cursor;
	private $i_length;
	
	public function __construct(\Reef\Submission $Submission) {
		$this->Submission = $Submission;
		$this->a_fields = $this->Submission->getForm()->getValueFieldsByName();
	}
	
	/**
	 * Evaluate an entire condition
	 * @param string $s_condition The condition
	 * @return bool
	 */
	public function evaluate(string $s_condition) : bool {
		
		if(trim($s_condition) == '') {
			return true;
		}
		
		$this->s_condition = $s_condition;
		$this->i_length = strlen($this->s_condition);
		$this->i_cursor = 0;
		
		return $this->condition();
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
			}
			else {
				$this->giveBackToken($s_token);
				$b_clause = $this->getClause();
			}
			
			if($b_clause === null) {
				throw new ConditionException("Unexpected end of line");
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
					break;
				}
			}
		}
		
		if($b_result === null) {
			throw new ConditionException("Caught empty (sub)condition");
		}
		
		return $b_result;
	}
	
	/**
	 * Evaluate the clause at the current cursor position
	 * @return bool The result of the clause, or null if the clause is empty
	 */
	private function getClause() : ?bool {
		$a_sentence = [];
		
		$s_token = $this->getToken();
		
		if($s_token == '') {
			return null;
		}
		if(in_array($s_token, ['true', 'yes', '1'])) {
			return true;
		}
		else if(in_array($s_token, ['false', 'no', '0'])) {
			return false;
		}
		
		$s_fieldName = $s_token;
		if(!isset($this->a_fields[$s_fieldName])) {
			throw new ConditionException("Invalid field name '".$s_fieldName."'");
		}
		
		$Field = $this->a_fields[$s_fieldName];
		$a_operators = $Field->getConditionOperators();
		
		if(empty($a_operators)) {
			throw new ConditionException("Field '".$s_fieldName."' uses does not support conditions");
		}
		
		$i_maxWords = 0;
		foreach($a_operators as $s_operator) {
			$i_maxWords = max($i_maxWords, substr_count($s_operator, ' ')+1);
		}
		
		$a_tokens = [];
		for($i=0; $i<$i_maxWords; $i++) {
			$s_token = $this->getToken();
			
			if($s_token == '') {
				$i_maxWords = $i+1;
				break;
			}
			
			$a_tokens[] = $s_token;
		}
		
		$s_operator = null;
		for($i=$i_maxWords; $i>0; $i--) {
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
		
		return (bool)$this->Submission->getFieldValue($s_fieldName)->evaluateCondition($s_operator, json_decode($s_operand, true));
	}
	
	protected $a_tokenStack = [];
	
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
		
		$s_token = '';
		for(; $this->i_cursor < $this->i_length && strpos(self::WHITESPACE, $this->s_condition[$this->i_cursor]) !== false; $this->i_cursor++);
		
		if($this->i_cursor >= $this->i_length) {
			return $s_token;
		}
		
		if($this->s_condition[$this->i_cursor] == '(' || $this->s_condition[$this->i_cursor] == ')') {
			return $this->s_condition[$this->i_cursor++];
		}
		
		$s_find = null;
		if($this->s_condition[$this->i_cursor] == '"' || $this->s_condition[$this->i_cursor] == "'") {
			$s_find = $this->s_condition[$this->i_cursor];
			$s_token .= $s_find;
			$this->i_cursor++;
		}
		
		for(; $this->i_cursor < $this->i_length && strpos(self::WHITESPACE.')', $this->s_condition[$this->i_cursor]) === false; $this->i_cursor++) {
			$s_token .= $this->s_condition[$this->i_cursor];
			
			if($s_find !== null && $this->s_condition[$this->i_cursor] == $s_find && substr($s_token, -2, 1) != '\\') {
				// TODO: Should the backslash be removed?
				break;
			}
		}
		return $s_token;
	}
	
}
