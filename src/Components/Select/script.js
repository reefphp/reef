Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
	};
	
	Field.componentName = 'reef:select';
	
	Field.viewVars = function(declaration) {
		var i, j;
		
		for(i in declaration.options) {
			for(j in declaration.options[i].locale) {
				declaration.options[i].title = declaration.options[i].locale[j];
				break;
			}
		}
		
		return declaration;
	};
	
	Field.prototype.attach = function() {
		var self = this;
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('select').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('select').val(value);
	};
	
	Field.prototype.validate = function() {
		return true;
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
