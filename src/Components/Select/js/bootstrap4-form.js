Reef.getComponent('reef:select').addLayout((function() {
	
	'use strict';
	
	var Layout = function(Field) {
		this.Field = Field;
		this.$field = Field.$field;
	};
	
	Layout.layoutName = 'bootstrap4';
	
	Layout.prototype.addError = function(message) {
		this.$field.find('select').addClass('is-invalid');
		this.$field.find('select').parent().append($('<div class="invalid-feedback"></div>').text(message));
	};
	
	Layout.prototype.styleConditionOperandInput = function($input) {
		$input.addClass('form-control');
	};
	
	return Layout;
})());
