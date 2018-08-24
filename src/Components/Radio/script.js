Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:radio';
	
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
		this.Reef.listenRequired(this, this.$field.find('input'));
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('input:checked').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('input').filter('[value="'+value+'"]').prop('checked', true);
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		if(this.$field.find('input[required]').length > 0 && this.$field.find('input:checked').length == 0) {
			this.setError('error-required-empty');
			return false;
		}
		
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
			
			this.$field.find('input').each(function() {
				$select.append($('<option>').val($(this).attr('value')).text(self.$field.find('[for="'+$(this).attr('id')+'"]').text()));
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
			this.$field.find('input').each(function() {
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
