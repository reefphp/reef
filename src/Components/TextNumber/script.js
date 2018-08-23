Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:text_number';
	
	Field.getLanguageReplacements = function(declaration) {
		var replacements = {};
		if(typeof declaration.min !== 'undefined') {
			replacements.min = declaration.min;
		}
		if(typeof declaration.max !== 'undefined') {
			replacements.max = declaration.max;
		}
		return replacements;
	};
	
	Field.viewVars = function(declaration) {
		declaration.hasMin = (declaration.min !== '');
		declaration.hasMax = (declaration.max !== '');
		return declaration;
	};
	
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
		var valid = true;
		
		this.removeErrors();
		
		var $input = this.$field.find('input');
		var val = $input.val();
		
		if($input.prop('required')) {
			if($.trim(val) == '') {
				valid = false;
				this.setError('error-required-empty');
			}
		}
		
		if($input[0].validity && $input[0].validity.badInput) {
			valid = false;
			this.setError('error-not-a-number');
		}
		else if($.trim(val) != '' && (isNaN(parseFloat(val)) || !isFinite(val))) {
			valid = false;
			this.setError('error-not-a-number');
		}
		else {
			val = parseFloat(val);
			
			if($input.is('[min]') && val < $input.attr('min')) {
				valid = false;
				this.setError($input.is('[max]') ? 'error-number-min-max' : 'error-number-min');
			}
			else if($input.is('[max]') && val > $input.attr('max')) {
				valid = false;
				this.setError($input.is('[min]') ? 'error-number-min-max' : 'error-number-max');
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
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('input').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	return Field;
})());
