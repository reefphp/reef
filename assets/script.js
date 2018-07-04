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
		
		Reef.hasComponent = function(type) {
			return (typeof Reef.components[type] !== 'undefined');
		};
		
		Reef.getComponent = function(type) {
			if(!Reef.hasComponent(type)) {
				throw "Unknown component "+type+".";
			}
			
			return Reef.components[type];
		};
		
		Reef.prototype.newField = function(type, $el) {
			return new Reef.components[type](this, $el);
		};
		
		Reef.prototype.hasField = function(name) {
			return (typeof this.fields[name] !== 'undefined');
		};
		
		Reef.prototype.getField = function(name) {
			return this.fields[name];
		};
		
		Reef.prototype.getData = function() {
			var data = {};
			for(name in this.fields) {
				data[name] = this.fields[name].getValue();
			}
			return data;
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
