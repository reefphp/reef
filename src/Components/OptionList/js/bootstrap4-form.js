Reef.getComponent('reef:option_list').addLayout((function() {
	
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
		this.$field.find('.'+CSSPRFX+'ol-add').after($('<div class="invalid-feedback" style="display: block;"></div>').text(message));
	};
	
	return Layout;
})());
