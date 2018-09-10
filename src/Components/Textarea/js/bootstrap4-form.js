Reef.getComponent('reef:textarea').addLayout((function() {
	
	'use strict';
	
	var Layout = function(Field) {
		this.Field = Field;
		this.$field = Field.$field;
	};
	
	Layout.layoutName = 'bootstrap4';
	
	Layout.prototype.setError = function(message_key) {
		this.$field.find('textarea').addClass('is-invalid');
		this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
	};
	
	Layout.prototype.removeErrors = function() {
		this.$field.find('textarea').removeClass('is-invalid');
		this.$field.find('.invalid-feedback').hide();
	};
	
	Layout.prototype.addError = function(message) {
		this.$field.find('textarea').addClass('is-invalid');
		this.$field.find('textarea').parent().append($('<div class="invalid-feedback"></div>').text(message));
	};
	
	Layout.prototype.styleConditionOperandInput = function($input) {
		$input.addClass('form-control');
	};
	
	return Layout;
})());
