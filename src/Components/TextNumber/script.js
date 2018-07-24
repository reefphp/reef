Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.parent = this.Reef.newField('reef:single_line_text', $field);
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
	};
	
	Field.prototype.getValue = function() {
		return this.parent.getValue();
	};
	
	Field.prototype.setValue = function(value) {
		this.parent.setValue(parseFloat(value));
	};
	
	Field.prototype.validate = function() {
		var valid = this.parent.validate();
		
		var $input = this.$field.find('input');
		var val = $input.val();
		
		if($.trim(val) != '' && (isNaN(parseFloat(val)) || !isFinite(val))) {
			valid = false;
			this.parent.setError('error-not-a-number');
		}
		else {
			val = parseFloat(val);
			
			if($input.is('[min]') && val < $input.attr('min')) {
				valid = false;
				this.parent.setError($input.is('[max]') ? 'error-number-min-max' : 'error-number-min');
			}
			else if($input.is('[max]') && val > $input.attr('max')) {
				valid = false;
				this.parent.setError($input.is('[min]') ? 'error-number-min-max' : 'error-number-max');
			}
		}
		
		return valid;
	};
	
	return Field;
})());
