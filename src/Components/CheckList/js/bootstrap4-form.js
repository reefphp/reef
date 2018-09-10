Reef.getComponent('reef:checklist').addLayout((function() {
	
	'use strict';
	
	var Layout = function(Field) {
		this.Field = Field;
		this.$field = Field.$field;
	};
	
	Layout.layoutName = 'bootstrap4';
	
	Layout.prototype.setError = function(message_key) {
		this.$field.find('input').addClass('is-invalid');
		this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
	};
	
	Layout.prototype.removeErrors = function() {
		this.$field.find('input').removeClass('is-invalid');
		this.$field.find('.invalid-feedback').hide();
	};
	
	Layout.prototype.addError = function(message) {
		this.$field.find('input').addClass('is-invalid');
		this.$field.find('input').last().parent().append($('<div class="invalid-feedback"></div>').text(message));
	};
	
	Layout.prototype.styleConditionOperandInput = function($input) {
		$input.addClass('form-control');
	};
	
	return Layout;
})());
