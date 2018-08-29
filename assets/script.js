if(typeof Reef === 'undefined') {
	var CSSPRFX = 'JS_INSERT_CSS_PREFIX';
	var EVTPRFX = 'JS_INSERT_EVENT_PREFIX';
	
	var ReefUtil = {
		// https://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
		escapeRegExp : function(str) {
			return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
		},
		
		/**
		 * Convert a matcher to a regular expression.
		 * `*`, `?` and `_` are mapped to `.*`, `.?` and `.`, respectively
		 * This function has an equivalent in php
		 * @param string matcher The matcher string
		 * @return RegExp The regular expression
		 */
		matcherToRegExp : function(matcher) {
			var regexp = ReefUtil.escapeRegExp(matcher);
			
			regexp = regexp.replace(new RegExp('((?:\\\\)*)\\\\\\*', 'g'), function(match, slashes) {
				return slashes.substr(0, slashes.length/2) + ((slashes.length % 4 == 0) ? '.*' : '*');
			});
			
			regexp = regexp.replace(new RegExp('((?:\\\\)*)\\\\\\?', 'g'), function(match, slashes) {
				return slashes.substr(0, slashes.length/2) + ((slashes.length % 4 == 0) ? '.?' : '?');
			});
			
			regexp = regexp.replace(new RegExp('((?:\\\\)*)_', 'g'), function(match, slashes) {
				return slashes.substr(0, Math.floor(slashes.length/4)*2) + ((slashes.length % 4 == 0) ? '.' : '_');
			});
			
			return new RegExp('^'+regexp+'$');
		}
	};

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
					self.listenVisible($(this), self.fields[name]);
				}
				else {
					self.listenVisible($(this));
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
		
		Reef.prototype.getFormUUID = function() {
			return this.config.form_uuid;
		};
		
		Reef.prototype.internalRequestURL = function(request_hash) {
			return this.config.internal_request_url.replace('[[request_hash]]', request_hash);
		};
		
		Reef.prototype.internalRequestHelper = function() {
			var self = this;
			
			return function(text, render) {
				return self.config.internal_request_url.replace('[[request_hash]]', render(text)+'@'+self.initTime);
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
		
		Reef.prototype.onConditionChange = function(condition, callback) {
			var self = this;
			
			var fieldNames = ReefConditionEvaluator.fetchFieldNames(this, condition);
			
			for(var i in fieldNames) {
				var fieldName = fieldNames[i];
				this.fields[fieldName].$field.on('change '+EVTPRFX+'change', function() {
					callback(ReefConditionEvaluator.evaluate(self, condition));
				});
			}
		};
		
		Reef.prototype.listenRequired = function(field, $input) {
			var conditions = [];
			
			if($input.attr('data-required-if') && $input.attr('data-required-if').length > 0) {
				conditions.push(' ('+$input.attr('data-required-if')+') ');
			}
			else {
				conditions.push(' ('+($input.prop('required') ? 'true' : 'false')+') ');
			}
			if(field.$field.attr('data-visible-if') && field.$field.attr('data-visible-if').length > 0) {
				conditions.push(' ('+field.$field.attr('data-visible-if')+') ');
			}
			
			if(conditions.length == 0) {
				return;
			}
			
			var condition = conditions.join(' and ');
			
			this.onConditionChange(condition, function(should_be_required) {
				if(should_be_required != $input.prop('required')) {
					$input.prop('required', should_be_required);
					field.validate();
				}
			});
		}
		
		Reef.prototype.listenVisible = function($field, field) {
			if($field.attr('data-visible-if')) {
				this.onConditionChange($field.attr('data-visible-if'), function(should_be_visible) {
					var should_be_hidden = !should_be_visible;
					if(should_be_hidden != $field.attr('data-'+CSSPRFX+'hidable-hidden')) {
						if(should_be_hidden) {
							$field.attr('data-'+CSSPRFX+'hidable-hidden', '1');
							
							if(typeof field !== 'undefined' && typeof field.toDefault !== 'undefined') {
								field.toDefault();
							}
						}
						else {
							$field.removeAttr('data-'+CSSPRFX+'hidable-hidden');
						}
					}
				});
			}
		}
		
		return Reef;
	})();
}
