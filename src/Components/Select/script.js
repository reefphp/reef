Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:select';
	
	Field.viewVars = function(declaration) {
		var i, l;
		
		for(i in declaration.options) {
			for(l in declaration.options[i].locale) {
				declaration.options[i].title = declaration.options[i].locale[l];
				break;
			}
		}
		
		return declaration;
	};
	
	Field.prototype.attach = function() {
		
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('select').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('select').val(value).change();
	};
	
	Field.prototype.toDefault = function() {
		this.setValue(this.$field.find('select').attr('data-default'));
	};
	
	Field.prototype.validate = function() {
		return true;
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('select').addClass('is-invalid');
			this.$field.find('select').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	Field.getConditionOperators = function() {
		return [
			'equals',
			'does not equal',
			'is empty',
			'is not empty'
		];
	};
	
	Field.prototype.getConditionOperandInput = function(operator, layout) {
		var self = this;
		
		var classes = '';
		if(layout == 'bootstrap4') {
			classes += ' form-control';
		}
		
		if(operator.indexOf('equal') > -1) {
			var $select = $('<select class="'+classes+'">');
			
			this.$field.find('select option').each(function() {
				$select.append($('<option>').val($(this).attr('value')).text($(this).text()));
			});
			
			return $select;
		}
		
		return null;
	};
	
	Field.prototype.validateConditionOperation = function(operator, operand) {
		if(['is empty', 'is not empty'].indexOf(operator) > -1) {
			if(operand != '') {
				throw 'Empty does not take an operand';
			}
		}
		
		if(['equals', 'does not equal'].indexOf(operator) > -1) {
			var found = false;
			this.$field.find('select option').each(function() {
				if($(this).attr('value') === operand) {
					found = true;
					return false;
				}
			});
			if(!found) {
				throw ('Invalid operand "'+operand+'"');
			}
		}
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var value = this.getValue();
		
		switch(operator) {
			case 'equals':
				return value == operand;
			case 'does not equal':
				return value != operand;
			case 'is empty':
				return value == '';
			case 'is not empty':
				return value != '';
		};
	};
	
	return Field;
})());
