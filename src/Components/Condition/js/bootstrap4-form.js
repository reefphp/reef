Reef.getComponent('reef:condition').addLayout((function() {
	
	'use strict';
	
	var Layout = function(Field) {
		this.Field = Field;
		this.$field = Field.$field;
	};
	
	Layout.layoutName = 'bootstrap4';
	
	Layout.prototype.setError = function(message) {
		this.$field.find('.'+CSSPRFX+'cond-feedback').text(message).show();
	};
	
	Layout.prototype.removeErrors = function() {
		this.$field.find('.invalid-feedback').hide();
	};
	
	Layout.prototype.addError = function(message) {
		this.$field.find('input').parent().append($('<div class="invalid-feedback"></div>').text(message));
	};
	
	return Layout;
})());
