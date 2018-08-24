/**
 * Condition evaluation class
 * 
 * @see PHP class \Reef\ConditionEvaluator
 * 
 * This JS implementation should be kept identical to the PHP implementation
 */

var ReefConditionEvaluator = (function() {
	
	'use strict';
	
	var ConditionEvaluator = function() {
		this.WHITESPACE = " \t\r\n";
		
		this.reef = null;
		this.s_condition = null;
		this.i_cursor = null;
		this.i_length = null;
		this.a_tokenStack = null;
	};
	
	/**
	 * Evaluate an entire condition
	 * 
	 * @param Reef reef The reef object
	 * @param string s_condition The condition
	 * @param bool b_validate Whether to perform operand validation, defaults to false
	 * 
	 * @return ?bool The boolean result, or null if the input condition was empty
	 * 
	 * @throws Exception If the input condition is invalid
	 */
	ConditionEvaluator.prototype.evaluate = function(reef, s_condition, b_validate) {
		
		var b_result;
		
		if(s_condition.trim() == '') {
			return null;
		}
		
		this.reef = reef;
		this.s_condition = s_condition;
		this.i_length = this.s_condition.length;
		this.i_cursor = 0;
		this.a_tokenStack = [];
		this.b_validate = !!b_validate;
		
		b_result = this.condition();
		
		if(this.getToken() !== '') {
			throw ('Caught invalid condition');
		}
		
		return b_result;
	}
	
	/**
	 * Evaluate the (sub)condition at the current cursor position
	 * @return bool The result of the (sub)condition
	 */
	ConditionEvaluator.prototype.condition = function() {
		
		var b_result, a_ands, s_token, b_clause;
		
		b_result = null;
		a_ands = [];
		
		while(this.i_cursor < this.i_length) {
			
			s_token = this.getToken();
			
			if(s_token == '(') {
				b_clause = this.condition();
				
				if(this.getToken() !== ')') {
					throw ("Caught runaway argument");
				}
			}
			else {
				this.giveBackToken(s_token);
				b_clause = this.clause();
			}
			
			a_ands.push(b_clause);
			
			s_token = this.getToken();
			
			if(s_token != ')' && s_token != 'and' && s_token != 'or' && s_token != '') {
				throw ("Unexpected token '" + s_token + "'");
			}
			
			if(s_token != 'and') {
				b_result = b_result || (a_ands.filter(function(b) { return b; }).length == a_ands.length);
				a_ands = [];
				
				if(s_token == ')') {
					this.giveBackToken(s_token);
					break;
				}
			}
		}
		
		if(b_result === null) throw ("Caught empty result");
		
		return b_result;
	}
	
	/**
	 * Evaluate the clause at the current cursor position
	 * @return bool The result of the clause
	 */
	ConditionEvaluator.prototype.clause = function() {
		
		var s_token;
		
		s_token = this.getToken();
		
		if(s_token == '') {
			throw ("Unexpected end of line");
		}
		if(['true', 'yes', '1'].indexOf(s_token) > -1) {
			return true;
		}
		else if(['false', 'no', '0'].indexOf(s_token) > -1) {
			return false;
		}
		
		this.giveBackToken(s_token);
		return this.fieldOperation();
	}
	
	/**
	 * Evaluate the field operation at the current cursor position
	 * @return bool The result of the field operation
	 */
	ConditionEvaluator.prototype.fieldOperation = function() {
		var operation = this.getFieldOperation();
		
		if(this.b_validate) {
			this.reef.getField(operation[0]).validateConditionOperation(operation[1], operation[2]);
		}
		
		return this.reef.getField(operation[0]).evaluateConditionOperation(operation[1], operation[2]);
	}
	
	/**
	 * Get the clause at the current cursor position
	 * @return array [fieldname, operator, operand]
	 */
	ConditionEvaluator.prototype.getFieldOperation = function() {
		
		var s_fieldName, Field, a_operators, i_maxWords, s_opKey, a_tokens, i, s_token, s_operator, j, s_operand, m_operand;
		
		s_fieldName = this.getToken();
		if(!this.reef.hasField(s_fieldName)) {
			throw ("Invalid field name '"+s_fieldName+"'");
		}
		
		Field = this.reef.getField(s_fieldName);
		if(typeof Field.constructor.getConditionOperators === 'undefined' || typeof Field.evaluateConditionOperation === 'undefined') {
			throw ("Field '"+s_fieldName+"' does not support conditions");
		}
		
		a_operators = Field.constructor.getConditionOperators();
		if(typeof a_operators === 'undefined' || a_operators.length == 0) {
			throw ("Field '"+s_fieldName+"' does not support conditions");
		}
		
		i_maxWords = 0;
		for(s_opKey in a_operators) {
			i_maxWords = Math.max(i_maxWords, a_operators[s_opKey].split(' ').length);
		}
		
		a_tokens = [];
		for(i=0; i<i_maxWords; i++) {
			s_token = this.getToken();
			
			if(s_token == '') {
				break;
			}
			
			a_tokens.push(s_token);
		}
		
		s_operator = null;
		for(i=a_tokens.length; i>0; i--) {
			if(-1 < (j = a_operators.indexOf(a_tokens.join(' ')))) {
				s_operator = a_operators[j];
				break;
			}
			
			this.giveBackToken(a_tokens.pop());
		}
		
		if(s_operator === null) {
			throw ("Invalid operator");
		}
		
		s_operand = '';
		while(true) {
			s_token = this.getToken();
			
			if(['', 'and', 'or', ')'].indexOf(s_token) > -1) {
				if(s_token !== '') {
					this.giveBackToken(s_token);
				}
				break;
			}
			
			s_operand += ((s_operand == '') ? '' : ' ') + s_token;
		}
		
		if(s_operand == '') {
			m_operand = s_operand;
		}
		else {
			try {
				m_operand = JSON.parse(s_operand);
			}
			catch(e) {
				throw ('Invalid operand "'+s_operand+'"');
			}
		}
		
		return [s_fieldName, s_operator, m_operand];
	}
	
	/**
	 * Give back a token to be processed later by another method
	 * @param string s_token The token
	 */
	ConditionEvaluator.prototype.giveBackToken = function(s_token) {
		this.a_tokenStack.push(s_token);
	}
	
	/**
	 * Get the next token at the current cursor position, or the last one on the stack if present
	 * @return string The next token
	 */
	ConditionEvaluator.prototype.getToken = function() {
		if(this.a_tokenStack.length > 0) {
			return this.a_tokenStack.pop();
		}
		
		var s_token, s_find, i, i_numBackslashes;
		
		s_token = '';
		for(; this.i_cursor < this.i_length && this.WHITESPACE.indexOf(this.s_condition[this.i_cursor]) > -1; this.i_cursor++);
		
		if(this.i_cursor >= this.i_length) {
			return s_token;
		}
		
		if(this.s_condition[this.i_cursor] == '(' || this.s_condition[this.i_cursor] == ')') {
			return this.s_condition[this.i_cursor++];
		}
		
		s_find = null;
		if(this.s_condition[this.i_cursor] == '"' || this.s_condition[this.i_cursor] == "'") {
			s_find = this.s_condition[this.i_cursor];
			s_token += s_find;
			this.i_cursor++;
		}
		
		for(; this.i_cursor < this.i_length && (s_find !== null || (this.WHITESPACE+')').indexOf(this.s_condition[this.i_cursor]) == -1); this.i_cursor++) {
			s_token += this.s_condition[this.i_cursor];
			
			if(s_find !== null && this.s_condition[this.i_cursor] == s_find) {
				for(i=s_token.length-2; i>=0; i--) {
					if(s_token.substr(i, 1) != '\\') {
						break;
					}
				}
				
				i_numBackslashes = s_token.length-2 - i;
				if(i_numBackslashes % 2 == 0) {
					this.i_cursor++;
					break;
				}
			}
		}
		return s_token;
	}
	
	/**
	 * Parse a condition into an array to be used by the builder
	 * 
	 * @param Reef reef The reef object
	 * @param string s_condition The condition; only first-level (linear) conditions can be used, i.e. no parentheses.
	 *                           Furthermore, the condition may not contain any boolean value, except if the boolean value forms the entire condition.
	 * 
	 * @return array The parsed condition
	 */
	ConditionEvaluator.prototype.conditionToArray = function(reef, s_condition) {
		
		var a_result, s_token, a_clause;
		
		if(s_condition.trim() == '') {
			return true;
		}
		if(['true', 'yes', '1'].indexOf(s_condition) > -1) {
			return true;
		}
		else if(['false', 'no', '0'].indexOf(s_condition) > -1) {
			return false;
		}
		
		this.reef = reef;
		this.s_condition = s_condition;
		this.i_length = this.s_condition.length;
		this.i_cursor = 0;
		this.a_tokenStack = [];
		
		a_result = [[]];
		
		while(this.i_cursor < this.i_length) {
			
			s_token = this.getToken();
			
			if(s_token == '(') {
				throw ("Caught parentheses");
			}
			
			this.giveBackToken(s_token);
			a_clause = this.conditionToArray_clause();
			
			a_result[a_result.length-1].push(a_clause);
			
			s_token = this.getToken();
			
			if(s_token == ')') {
				throw ("Caught parentheses");
			}
			
			if(s_token != 'and' && s_token != 'or' && s_token != '') {
				throw ("Unexpected token '" + s_token + "'");
			}
			
			if(s_token == 'or') {
				a_result.push([]);
			}
		}
		
		if(a_result[0].length == 0) throw ("Caught empty result");
		
		return a_result;
	}
	
	/**
	 * Parse the clause at the current cursor position
	 * @return array The clause
	 */
	ConditionEvaluator.prototype.conditionToArray_clause = function() {
		
		var s_token;
		
		s_token = this.getToken();
		
		if(s_token == '') {
			throw ("Unexpected end of line");
		}
		if(['true', 'yes', '1'].indexOf(s_token) > -1) {
			throw ("Caught boolean '"+s_token+"'");
		}
		else if(['false', 'no', '0'].indexOf(s_token) > -1) {
			throw ("Caught boolean '"+s_token+"'");
		}
		
		this.giveBackToken(s_token);
		return this.getFieldOperation();
	}
	
	/**
	 * Find all fields in a condition
	 * 
	 * @param Reef reef The reef object
	 * @param string s_condition The condition
	 * 
	 * @return array The field names
	 * 
	 * @throws Exception If the input condition is invalid
	 */
	ConditionEvaluator.prototype.fetchFieldNames = function(reef, s_condition) {
		this.conditionRename(reef, s_condition, null, null);
		
		var a_fieldNames = [];
		
		for(var s_fieldName in this.o_usedFieldNames) {
			a_fieldNames.push(s_fieldName);
		}
		
		return a_fieldNames;
	}
	
	/**
	 * Rename a field in a condition (or find all fields in a condition, as used by fetchFieldNames())
	 * 
	 * @param Reef reef The reef object
	 * @param string s_condition The condition
	 * @param string old_name The old name
	 * @param string new_name The new name
	 * 
	 * @return string The new condition
	 * 
	 * @throws Exception If the input condition is invalid
	 */
	ConditionEvaluator.prototype.conditionRename = function(reef, s_condition, old_name, new_name) {
		
		var new_condition;
		
		if(s_condition.trim() == '') {
			return null;
		}
		
		this.reef = reef;
		this.s_condition = s_condition;
		this.i_length = this.s_condition.length;
		this.i_cursor = 0;
		this.a_tokenStack = [];
		this.o_usedFieldNames = {};
		
		new_condition = this.conditionRename_condition(old_name, new_name);
		
		if(this.getToken() !== '') {
			throw ('Caught invalid condition');
		}
		
		return new_condition;
	}
	
	/**
	 * Evaluate the (sub)condition at the current cursor position
	 * @param string old_name The old name
	 * @param string new_name The new name
	 * @return bool The result of the (sub)condition
	 */
	ConditionEvaluator.prototype.conditionRename_condition = function(old_name, new_name) {
		
		var new_condition, s_token;
		
		new_condition = '';
		
		while(this.i_cursor < this.i_length) {
			
			s_token = this.getToken();
			
			if(s_token == '(') {
				new_condition += this.conditionRename_condition(old_name, new_name);
				
				if(this.getToken() !== ')') {
					throw ("Caught runaway argument");
				}
			}
			else {
				this.giveBackToken(s_token);
				new_condition += this.conditionRename_clause(old_name, new_name);
			}
			
			s_token = this.getToken();
			
			if(s_token != ')' && s_token != 'and' && s_token != 'or' && s_token != '') {
				throw ("Unexpected token '" + s_token + "'");
			}
			
			if(s_token == ')') {
				this.giveBackToken(s_token);
				break;
			}
			
			if(s_token == 'and' || s_token == 'or') {
				new_condition += ' ' + s_token + ' ';
			}
		}
		
		if(new_condition === '') throw ("Caught empty result");
		
		return new_condition;
	}
	
	/**
	 * Evaluate the clause at the current cursor position
	 * @param string old_name The old name
	 * @param string new_name The new name
	 * @return bool The result of the clause
	 */
	ConditionEvaluator.prototype.conditionRename_clause = function(old_name, new_name) {
		
		var s_token;
		
		s_token = this.getToken();
		
		if(s_token == '') {
			throw ("Unexpected end of line");
		}
		if(['true', 'yes', '1'].indexOf(s_token) > -1) {
			return s_token;
		}
		else if(['false', 'no', '0'].indexOf(s_token) > -1) {
			return s_token;
		}
		
		this.giveBackToken(s_token);
		
		var operation = this.getFieldOperation();
		
		if(operation[0] == old_name) {
			operation[0] = new_name;
		}
		if(old_name == null && new_name == null) {
			this.o_usedFieldNames[operation[0]] = 1;
		}
		
		return operation[0] + ' ' + operation[1] + ' ' + JSON.stringify(operation[2]);
	}
	
	return new ConditionEvaluator();
})();
