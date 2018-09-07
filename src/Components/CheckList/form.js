Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:checklist';
	
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
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').removeClass('is-invalid');
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('input').last().parent().append($('<div class="invalid-feedback"></div>').text(message));
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
		
		var classes = '';
		if(layout == 'bootstrap4') {
			classes += ' form-control';
		}
		
		if(['has checked', 'has not checked'].indexOf(operator) > -1) {
			var $select = $('<select class="'+classes+'">');
			
			this.$field.find('input').each(function() {
				$select.append($('<option>').val($(this).attr('data-name')).text(self.$field.find('[for="'+$(this).attr('id')+'"]').text()));
			});
			
			return $select;
		}
		
		if(['at least checked', 'at most checked', 'at least unchecked', 'at most unchecked'].indexOf(operator) > -1) {
			return $('<input type="number" class="'+classes+'" min="0" step="1" />');
		}
		
		return null;
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
