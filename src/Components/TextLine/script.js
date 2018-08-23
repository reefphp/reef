Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:text_line';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('input').on('change blur keyup', function(evt) {
			
			// Only perform validation on-key-up if there were errors previously
			if(evt.type == 'keyup' && !self.$field.hasClass(CSSPRFX+'invalid')) {
				return;
			}
			
			self.validate();
		});
		
		this.Reef.listenRequired(this, this.$field.find('input'));
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('input').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('input').val(value);
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		var $input = this.$field.find('input');
		
		if($input.prop('required')) {
			if($.trim($input.val()) == '') {
				this.setError('error-required-empty');
				return false;
			}
		}
		
		if($input.attr('maxlength') && $input.attr('maxlength') > 0 && $input.val().length > $input.attr('maxlength')) {
			this.setError('error-value-too-long');
			return false;
		}
		
		if($input.attr('pattern')) {
			if(!$input.val().match(new RegExp($input.attr('pattern')))) {
				this.setError('error-regexp');
				return false;
			}
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
			this.$field.find('input').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	Field.getConditionOperators = function() {
		return [
			'equals',
			'does not equal',
			'matches',
			'does not match',
			'is empty',
			'is not empty',
			'is longer than',
			'is shorter than'
		];
	};
	
	Field.getConditionOperandInput = function(operator, layout) {
		var classes = '';
		if(layout == 'bootstrap4') {
			classes += ' form-control';
		}
		
		if(operator.indexOf('equal') > -1 || operator.indexOf('match') > -1) {
			return $('<input type="text" class="'+classes+'" />');
		}
		else if(['is longer than', 'is shorter than'].indexOf(operator) > -1) {
			return $('<input type="number" class="'+classes+'" min="0" step="1" />');
		}
		
		return null;
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var value = this.getValue();
		
		switch(operator) {
			case 'equals':
				return value == operand;
			case 'does not equal':
				return value != operand;
			case 'matches':
				return ReefUtil.matcherToRegExp(operand).test(value);
			case 'does not match':
				return !ReefUtil.matcherToRegExp(operand).test(value);
			case 'is empty':
				return $.trim(value) == '';
			case 'is not empty':
				return $.trim(value) != '';
			case 'is longer than':
				return value.length > operand;
			case 'is shorter than':
				return value.length < operand;
		};
	};
	
	return Field;
})());
