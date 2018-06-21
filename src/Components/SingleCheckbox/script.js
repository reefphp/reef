Reef.addComponent((function() {
	
	'use strict';
	
	var Component = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Component.componentName = 'reef:single_checkbox';
	
	Component.prototype.attach = function() {
		var self = this;
		
		this.$field.find('input').on('change blur keyup', function(evt) {
			self.validate();
		});
	};
	
	Component.prototype.validate = function() {
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
	
	Component.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout.name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
		}
	};
	
	Component.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout.name == 'bootstrap4') {
			this.$field.find('input').removeClass('is-invalid');
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	return Component;
})());
