Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:checkbox';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('input').on('change blur keyup', function(evt) {
			self.validate();
		});
		
		this.Reef.listenRequired(this, this.$field.find('input'));
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('input').prop('checked');
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('input').prop('checked', !!value);
	};
	
	Field.prototype.validate = function() {
		var valid = true;
		
		this.removeErrors();
		
		var $input = this.$field.find('input');
		
		if($input.prop('required')) {
			if(!$input.prop('checked')) {
				valid = false;
				this.setError('error-required-empty');
			}
		}
		
		return valid;
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
	
	Field.getConditionOperators = function() {
		return [
			'is checked',
			'is not checked'
		];
	};
	
	Field.prototype.getConditionOperandInput = function(operator, layout) {
		return null;
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var value = this.getValue();
		
		switch(operator) {
			case 'is checked':
				return value;
			case 'is not checked':
				return !value;
		};
	};
	
	return Field;
})());
