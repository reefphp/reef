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
	
	Field.componentName = 'reef:checklist';
	
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
		var values = {};
		this.$field.find('input').each(function() {
			var $input = $(this);
			values[$input.attr('data-name')] = $input.prop('checked');
		});
		return values;
	};
	
	Field.prototype.setValue = function(values) {
		this.$field.find('input').each(function() {
			var $input = $(this);
			var name = $input.attr('data-name');
			if(values == 'default') {
				$input.prop('checked', !!$input.attr('data-default'));
			}
			else {
				$input.prop('checked', (typeof values[name] !== 'undefined' && values[name]) ? true : false);
			}
		}).change();
	};
	
	Field.prototype.toDefault = function() {
		this.setValue('default');
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		return true;
	};
	
	Field.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].setError(message_key);
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].removeErrors();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].addError(message);
		}
	};
	
	Field.getConditionOperators = function() {
		return [
			'has checked',
			'has not checked',
			'at least checked',
			'at most checked',
			'at least unchecked',
			'at most unchecked'
		];
	};
	
	Field.prototype.getConditionOperandInput = function(operator, layout) {
		var self = this;
		
		var $input = null;
		
		if(['has checked', 'has not checked'].indexOf(operator) > -1) {
			$input = $('<select>');
			
			this.$field.find('input').each(function() {
				$input.append($('<option>').val($(this).attr('data-name')).text(self.$field.find('[for="'+$(this).attr('id')+'"]').text()));
			});
		}
		
		if(['at least checked', 'at most checked', 'at least unchecked', 'at most unchecked'].indexOf(operator) > -1) {
			$input = $('<input type="number" min="0" step="1" />');
		}
		
		if($input == null) {
			return null;
		}
		
		if(this.layouts[layout]) {
			this.layouts[layout].styleConditionOperandInput($input);
		}
		
		return $input;
	};
	
	Field.prototype.validateConditionOperation = function(operator, operand) {
		
		if(['has checked', 'has not checked'].indexOf(operator) > -1) {
			var found = false;
			this.$field.find('input').each(function() {
				if($(this).attr('data-name') === operand) {
					found = true;
					return false;
				}
			});
			if(!found) {
				throw ('Invalid operand "'+operand+'"');
			}
		}
		
		if(['at least checked', 'at most checked', 'at least unchecked', 'at most unchecked'].indexOf(operator) > -1) {
			if(!$.isNumeric(operand)) {
				throw 'Check counting requires a numeric operand';
			}
		}
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var values = this.getValue();
		var valueArray = [];
		
		if(['at least checked', 'at most checked', 'at least unchecked', 'at most unchecked'].indexOf(operator) > -1) {
			for(var i in values) {
				valueArray.push(values[i]);
			}
		}
		
		switch(operator) {
			case 'has checked':
				return values[operand];
				
			case 'has not checked':
				return !values[operand];
				
			case 'at least checked':
				return valueArray.filter(function(b) { return b; }).length >= operand;
				
			case 'at most checked':
				return valueArray.filter(function(b) { return b; }).length <= operand;
				
			case 'at least unchecked':
				return valueArray.filter(function(b) { return !b; }).length >= operand;
				
			case 'at most unchecked':
				return valueArray.filter(function(b) { return !b; }).length <= operand;
		};
	};
	
	return Field;
})());
