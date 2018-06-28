if(typeof Reef === 'undefined') {
	var CSSPRFX = 'JS_INSERT_CSS_PREFIX';
	var EVTPRFX = 'JS_INSERT_EVENT_PREFIX';

	var Reef = (function() {
		'use strict';
		
		var Reef = function(selector) {
			var self = this;
			
			this.$wrapper = $(selector);
			this.fields = {};
			
			if(this.$wrapper.length == 0) {
				throw "Cannot initialize Reef with an empty selector set";
			}
			
			this.$wrapper.find('.'+CSSPRFX+'field').each(function() {
				var name = $(this).data(CSSPRFX+'name');
				var type = $(this).data(CSSPRFX+'type');
				if(Reef.components[type]) {
					self.fields[name] = self.newField(type, $(this));
					self.fields[name].attach();
				}
			});
			
			this.config = JSON.parse(atob(this.$wrapper.find('.'+CSSPRFX+'main-config').data('config')));
		};
		
		Reef.components = {};
		
		Reef.addComponent = function(component) {
			Reef.components[component.componentName] = component;
		};
		
		Reef.prototype.newField = function(type, $el) {
			return new Reef.components[type](this, $el);
		};
		
		Reef.prototype.validate = function() {
			var valid = true;
			
			for(var name in this.fields) {
				valid = this.fields[name].validate() && valid;
			}
			
			return (valid && this.$wrapper.find('.'+CSSPRFX+'invalid').length == 0);
		};
		
		return Reef;
	})();
}
