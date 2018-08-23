Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:textarea';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('textarea').on('change blur keyup', function(evt) {
			
			// Only perform validation on-key-up if there were errors previously
			if(evt.type == 'keyup' && !self.$field.hasClass(CSSPRFX+'invalid')) {
				return;
			}
			
			self.validate();
		});
		
		this.Reef.listenRequired(this, this.$field.find('textarea'));
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('textarea').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('textarea').val(value);
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		var $textarea = this.$field.find('textarea');
		
		if($textarea.prop('required')) {
			if($.trim($textarea.val()) == '') {
				this.setError('error-required-empty');
				return false;
			}
		}
		
		if($textarea.attr('maxlength') && $textarea.attr('maxlength') > 0 && $textarea.val().length > $textarea.attr('maxlength')) {
			this.setError('error-value-too-long');
			return false;
		}
		
		return true;
	};
	
	Field.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('textarea').addClass('is-invalid');
			this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('textarea').removeClass('is-invalid');
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('textarea').addClass('is-invalid');
			this.$field.find('textarea').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	Field.getConditionOperators = function() {
		return [
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
		
		if(['is longer than', 'is shorter than'].indexOf(operator) > -1) {
			return $('<input type="number" class="'+classes+'" min="0" step="1" />');
		}
		
		return null;
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var value = this.getValue();
		
		switch(operator) {
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
