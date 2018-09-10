Reef.getComponent('reef:upload').addLayout((function() {
	
	'use strict';
	
	var Layout = function(Field) {
		this.Field = Field;
		this.$field = Field.$field;
		this.$upload = Field.$upload;
	};
	
	Layout.layoutName = 'bootstrap4';
	
	Layout.prototype.setError = function(message_key) {
		this.$upload.addClass('is-invalid');
		this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
	};
	
	Layout.prototype.removeErrors = function() {
		this.$upload.removeClass('is-invalid');
		this.$field.find('.invalid-feedback').hide();
	};
	
	Layout.prototype.addError = function(message) {
		this.$upload.addClass('is-invalid');
		this.$upload.parent().append($('<div class="invalid-feedback"></div>').text(message));
	};
	
	return Layout;
})());
