Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.layouts = {};
		for(var layoutName in Field.layoutPrototypes) {
			this.layouts[layoutName] = new Field.layoutPrototypes[layoutName](this);
		}
	};
	
	Field.componentName = 'reef:select';
	
	Field.layoutPrototypes = {};
	Field.addLayout = function(layout) {
		Field.layoutPrototypes[layout.layoutName] = layout;
	};
	
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
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].addError(message);
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
		
		if(operator.indexOf('equal') > -1) {
			var $select = $('<select>');
			
			this.$field.find('select option').each(function() {
				$select.append($('<option>').val($(this).attr('value')).text($(this).text()));
			});
			
			if(this.layouts[layout]) {
				this.layouts[layout].styleConditionOperandInput($select);
			}
			
			return $select;
		}
		else {
			return null;
		}
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
