if(typeof Reef === 'undefined') {
	var CSSPRFX = 'JS_INSERT_CSS_PREFIX';
	var EVTPRFX = 'JS_INSERT_EVENT_PREFIX';

	var Reef = (function() {
		'use strict';
		
		var Reef = function(selector, options) {
			var self = this;
			
			this.$wrapper = $(selector);
			this.fields = {};
			this.initTime = Date.now();
			
			// Validate wrapper
			if(this.$wrapper.length == 0) {
				throw "Cannot initialize Reef with an empty selector set";
			}
			
			// Parse options
			this.options = options || {};
			
			this.builder = this.options.builder || null; // Only used when this is the preview reef instance in the builder
			
			if(typeof this.options.submit_url === 'undefined') {
				this.options.submit_url = false;
			}
			else {
				if(typeof this.options.submit_form === 'undefined') {
					this.options.submit_form = this.$wrapper;
				}
				
				if(this.options.submit_form) {
					this.options.submit_form = $(this.options.submit_form);
					
					if(!this.options.submit_form.is('form')) {
						console.log("Submit form must be a <form> element for automatic AJAX submission to work");
						this.options.submit_form = false;
					}
				}
			}
			
			this.options.submit_invalid = this.options.submit_invalid || $.noop;
			this.options.submit_before = this.options.submit_before || $.noop;
			this.options.submit_success = this.options.submit_success || $.noop;
			this.options.submit_error = this.options.submit_error || $.noop;
			this.options.submit_after = this.options.submit_after || $.noop;
			
			// Initialize all fields
			this.$wrapper.find('.'+CSSPRFX+'field').each(function() {
				var name = $(this).data(CSSPRFX+'name');
				var type = $(this).data(CSSPRFX+'type');
				if(Reef.components[type]) {
					self.fields[name] = self.newField(type, $(this));
					self.fields[name].attach();
				}
			});
			
			// Set config
			var config = this.$wrapper.find('.'+CSSPRFX+'main-config').data('config');
			this.config = (typeof config !== 'undefined') ? JSON.parse(atob(config)) : {};
			
			// Attach to submit, if required
			if(this.options.submit_form) {
				this.options.submit_form.on('submit', function(evt) {
					evt.preventDefault();
					self.submit();
				});
			}
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
		
		Reef.prototype.addField = function(name, field) {
			if(!this.hasField(name)) {
				this.fields[name] = field;
			}
		};
		
		Reef.prototype.removeField = function(name, field) {
			if(this.hasField(name) && this.getField(name) === field) {
				delete this.fields[name];
			}
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
		
		Reef.prototype.addErrors = function(errors) {
			var name;
			
			for(name in errors) {
				if(typeof(this.fields[name].addError) !== 'undefined') {
					this.fields[name].addError(errors[name]);
				}
				else {
					alert(errors[name]);
				}
			}
		};
		
		Reef.prototype.assetHelper = function() {
			var self = this;
			
			return function(text, render) {
				return self.config.assets_url.replace('[[assets_hash]]', render(text)+'@'+self.initTime);
			};
		};
		
		Reef.prototype.submit = function(options) {
			var self = this;
			
			options = options || {};
			options.submit_invalid = options.submit_invalid || $.noop;
			options.submit_before = options.submit_before || $.noop;
			options.submit_success = options.submit_success || $.noop;
			options.submit_error = options.submit_error || $.noop;
			options.submit_after = options.submit_after || $.noop;
			
			// Validate
			if(!self.validate()) {
				this.options.submit_invalid();
				options.submit_invalid();
				return;
			}
			
			var ajaxParams = {
				url: this.options.submit_url,
				method: 'post',
				data: $(this.options.submit_form).serializeArray(),
				dataType : 'json',
				success: function(response) {
					if(typeof(response.errors) != 'undefined') {
						// Errors
						self.addErrors(response.errors);
						
						self.options.submit_error();
						options.submit_error();
					}
					else {
						// Success
						self.options.submit_success(response);
						options.submit_success(response);
					}
					
					self.options.submit_after(response);
					options.submit_after(response);
				}
			};
			
			// Callback
			this.options.submit_before(ajaxParams);
			options.submit_before(ajaxParams);
			
			// Submit
			$.ajax(ajaxParams);
		};
		
		/**
		 * Get builder. Only to be used by builder-only components
		 */
		Reef.prototype.getBuilder = function() {
			return this.builder;
		};
		
		return Reef;
	})();
}
