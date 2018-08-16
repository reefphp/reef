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

var ConditionEvaluator = (function() {
	
	'use strict';
	
	var ConditionEvaluator = function(form) {
		this.WHITESPACE = " \t\r\n";
		
		this.Form = form;
		this.a_fields;
		
		this.Submission;
		this.s_condition;
		this.i_cursor;
		this.i_length;
		this.a_tokenStack = [];
	};
	
	/**
	 * Evaluate an entire condition
	 * 
	 * @param \Reef\Submission $Submission The submission to evaluate against
	 * @param string $s_condition The condition
	 * 
	 * @return bool
	 * 
	 * @throws BadMethodCallException If called with a submission not belonging to the form this evaluator is initialized with
	 * @throws ConditionException If the input condition is invalid
	 */
	ConditionEvaluator.prototype.evaluate = function(\Reef\Submission $Submission, s_condition) {
		
		var $b_result;
		
		if(s_condition.trim() == '') {
			return true;
		}
		
		this.Submission = $Submission;
		this.s_condition = s_condition;
		this.i_length = this.s_condition.length;
		this.i_cursor = 0;
		
		$b_result = this.condition();
		
		if(this.getToken() !== '') {
			throw ('Caught invalid condition');
		}
		
		return $b_result;
	}
	
	/**
	 * Evaluate the (sub)condition at the current cursor position
	 * @return bool The result of the (sub)condition
	 */
	ConditionEvaluator.prototype.condition = function() {
		
		var $b_result, $a_ands, $s_token, $b_clause;
		
		$b_result = null;
		$a_ands = [];
		
		while(this.i_cursor < this.i_length) {
			
			$s_token = this.getToken();
			
			if($s_token == '(') {
				$b_clause = this.condition();
				
				if(this.getToken() !== ')') {
					throw ("Caught runaway argument");
				}
			}
			else {
				this.giveBackToken($s_token);
				$b_clause = this.clause();
			}
			
			$a_ands.push($b_clause);
			
			$s_token = this.getToken();
			
			if($s_token != ')' && $s_token != 'and' && $s_token != 'or' && $s_token != '') {
				throw ("Unexpected token '" . $s_token."'");
			}
			
			if($s_token != 'and') {
				$b_result = $b_result || ($a_ands.filter(function(b) { return b; }).length == $a_ands.length);
				$a_ands = [];
				
				if($s_token == ')') {
					this.giveBackToken($s_token);
					break;
				}
			}
		}
		
		if($b_result === null) throw ("Caught empty result");
		
		return $b_result;
	}
	
	/**
	 * Evaluate the clause at the current cursor position
	 * @return bool The result of the clause
	 */
	ConditionEvaluator.prototype.clause = function() {
		
		var $s_token;
		
		$s_token = this.getToken();
		
		if($s_token == '') {
			throw ("Unexpected end of line");
		}
		if(['true', 'yes', '1'].indexOf($s_token) > -1) {
			return true;
		}
		else if(['false', 'no', '0'].indexOf($s_token) > -1) {
			return false;
		}
		
		this.giveBackToken($s_token);
		return this.fieldOperation();
	}
	
	/**
	 * Evaluate the field operation at the current cursor position
	 * @return bool The result of the field operation
	 */
	ConditionEvaluator.prototype.fieldOperation = function() {
		[$s_fieldName, $s_operator, $m_operand] = this.getFieldOperation();
		
		return (bool)this.Submission->getFieldValue($s_fieldName)->evaluateCondition($s_operator, $m_operand);
	}
	
	/**
	 * Get the clause at the current cursor position
	 * @return array [fieldname, operator, operand]
	 */
	ConditionEvaluator.prototype.getFieldOperation = function() {
		
		var $s_fieldName, $Field, $a_operators, $i_maxWords, $s_operator, $a_tokens, $s_token, $j, $s_operand, $m_operand;
		
		$s_fieldName = this.getToken();
		if(!isset(this.a_fields[$s_fieldName])) {
			throw ("Invalid field name '".$s_fieldName."'");
		}
		
		$Field = this.a_fields[$s_fieldName];
		$a_operators = $Field->getComponent()->getConditionOperators();
		
		if(empty($a_operators)) {
			throw ("Field '".$s_fieldName."' does not support conditions");
		}
		
		$i_maxWords = 0;
		foreach($a_operators as $s_operator) {
			$i_maxWords = max($i_maxWords, substr_count($s_operator, ' ')+1);
		}
		
		$a_tokens = [];
		for($i=0; $i<$i_maxWords; $i++) {
			$s_token = this.getToken();
			
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
			
			this.giveBackToken(array_pop($a_tokens));
		}
		
		if($s_operator === null) {
			throw ("Invalid operator");
		}
		
		$s_operand = '';
		while(true) {
			$s_token = this.getToken();
			
			if(in_array($s_token, ['', 'and', 'or', ')'])) {
				if($s_token !== '') {
					this.giveBackToken($s_token);
				}
				break;
			}
			
			$s_operand .= (($s_operand == '') ? '' : ' ') . $s_token;
		}
		
		$m_operand = json_decode($s_operand, true);
		
		if($m_operand === null && strtolower($s_operand) != 'null') {
			throw ('Invalid operand "'.$s_operand.'"');
		}
		
		return [$s_fieldName, $s_operator, $m_operand];
	}
	
	/**
	 * Give back a token to be processed later by another method
	 * @param string $s_token The token
	 */
	ConditionEvaluator.prototype.giveBackToken = function($s_token) {
		this.a_tokenStack.push($s_token);
	}
	
	/**
	 * Get the next token at the current cursor position, or the last one on the stack if present
	 * @return string The next token
	 */
	ConditionEvaluator.prototype.getToken = function() {
		if(this.a_tokenStack.length > 0) {
			return this.a_tokenStack.pop();
		}
		
		var $s_token, $s_find, $i_numBackslashes;
		
		$s_token = '';
		for(; this.i_cursor < this.i_length && strpos(this.WHITESPACE, this.s_condition[this.i_cursor]) !== false; this.i_cursor++);
		
		if(this.i_cursor >= this.i_length) {
			return $s_token;
		}
		
		if(this.s_condition[this.i_cursor] == '(' || this.s_condition[this.i_cursor] == ')') {
			return this.s_condition[this.i_cursor++];
		}
		
		$s_find = null;
		if(this.s_condition[this.i_cursor] == '"' || this.s_condition[this.i_cursor] == "'") {
			$s_find = this.s_condition[this.i_cursor];
			$s_token .= $s_find;
			this.i_cursor++;
		}
		
		for(; this.i_cursor < this.i_length && ($s_find !== null || strpos(this.WHITESPACE+')', this.s_condition[this.i_cursor]) === false); this.i_cursor++) {
			$s_token .= this.s_condition[this.i_cursor];
			
			if($s_find !== null && this.s_condition[this.i_cursor] == $s_find) {
				for($i=strlen($s_token)-2; $i>=0; $i--) {
					if(substr($s_token, $i, 1) != '\\') {
						break;
					}
				}
				
				$i_numBackslashes = strlen($s_token)-2 - $i;
				if($i_numBackslashes % 2 == 0) {
					this.i_cursor++;
					break;
				}
			}
		}
		return $s_token;
	}
	
	return ConditionEvaluator;
})();
