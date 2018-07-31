// https://stackoverflow.com/questions/105034/create-guid-uuid-in-javascript
function unique_id() {
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000)
		.toString(16)
		.substring(1);
	}
	return s4() + s4() + s4() + s4()+ s4() + s4() + s4() + s4();
}

// https://stackoverflow.com/questions/3446170/escape-string-for-use-in-javascript-regex
function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}

// NOTE: Currently not used
var ReefDialog = (function() {
	'use strict';
	
	var ReefDialog = function($dialog, $anchor) {
		var self = this;
		
		this.$dialog = $dialog;
		this.$anchor = $anchor;
		
		$anchor.on('click', function() {
			self.show();
		});
	};
	
	ReefDialog.prototype.toggle = function() {
		if(this.$dialog.is(':visible')) {
			this.hide();
		}
		else {
			this.show();
		}
	};
	
	ReefDialog.prototype.hide = function() {
		this.$dialog.hide();
	};
	
	ReefDialog.prototype.show = function() {
		var self = this;
		var i, left;
		
		// Open dialog & possibly reposition for appearing scroll bars
		for(i=0; i<2; i++) {
			left = this.$anchor.position().left;
			
			left = Math.min(
				left,
				this.$anchor.offsetParent().width() - this.$dialog.outerWidth() - 3
			);
			
			left = Math.max(0, left);
			
			this.$dialog.css({
				left: left,
				top: this.$anchor.position().top + this.$anchor.outerHeight()
			});
			
			if(i == 0) {
				this.$dialog.show();
			}
		}
		
		// Focus first input
		this.$dialog.find('input').filter(':visible').first().focus();
		
		// Close on mousedown outside dialog or on ESC press
		setTimeout(function() {
			var namespace = '.'+CSSPRFX+'builder-dialog-'+unique_id();
			$(document).on('mousedown'+namespace, function(evt) {
				var $target = $(evt.target);
				if(!$target.is(self.$dialog) && !$target.is(self.$anchor) && $target.closest(self.$dialog).length == 0) {
					self.hide();
					$(document).off('mousedown'+namespace);
					$(document).off('keydown'+namespace);
				}
			});
			
			$(document).on('keydown'+namespace, function(evt) {
				if(evt.which === 27) {
					self.hide();
					evt.preventDefault();
					$(document).off('mousedown'+namespace);
					$(document).off('keydown'+namespace);
				}
			});
		}, 0);
		
	};
	
	return ReefDialog;
})();

var ReefBuilder = (function() {
	'use strict';
	
	var ReefBuilder = function($builderWrapper, options) {
		var self = this;
		
		options = options || {};
		this.options = {};
		this.options.success = options.success || $.noop;
		
		this.selectedField = null;
		
		this.$builderWrapper = $($builderWrapper);
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-tab').on('click', function() {
			self.openSideTab($(this).data('tab'));
		});
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-components').each(function() {
			Sortable.create(this, {
					sort: false,
					group: {
						name: 'component-select',
						pull: 'clone',
						put: false
					},
					ghostClass: CSSPRFX+'builder-add-ghost',
					animation: 150
				}
			);
		});
		
		Sortable.create(this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace .'+CSSPRFX+'fields')[0], {
				sort: true,
				group: {
					name: 'component-select',
					pull: false,
					put: true
				},
				handle: '.'+CSSPRFX+'builder-drag-handle',
				animation: 150,
				onAdd: function(evt) {
					var $item = $(evt.item);
					self.addField($item, evt.newIndex);
				},
				onUpdate: function(evt) {
					self.moveField(evt.oldIndex, evt.newIndex);
				}
			}
		);
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-submit').on('click', function() {
			self.submit();
		});
		
		this.fields = [];
		
		this.reef = new Reef(this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace'));
		
		this.definitionForm = new Reef(this.$builderWrapper.find('.'+CSSPRFX+'builder-definition-form'));
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-existing-field').each(function(index) {
			self.addField($(this), index);
			$(this).remove();
		});
		this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace .'+CSSPRFX+'fields').append(this.$builderWrapper.find('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-field'));
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-tab').on('click', function() {
			self.openFieldTab($(this).data('type'));
		});
	};
	
	ReefBuilder.prototype.getReef = function() {
		return this.reef;
	};
	
	ReefBuilder.prototype.addField = function($item, newIndex) {
		var field = new ReefBuilderField(this);
		field.initFromItem($item);
		this.fields.splice(newIndex, 0, field);
	};
	
	ReefBuilder.prototype.moveField = function(oldIndex, newIndex) {
		if(typeof this.fields[oldIndex] == 'undefined') {
			return;
		}
		
		this.fields.splice(newIndex, 0, this.fields.splice(oldIndex, 1)[0]);
	};
	
	ReefBuilder.prototype.removeField = function(field) {
		this.deselectField();
		var index = this.fields.indexOf(field);
		if(index > -1) {
			this.fields.splice(index, 1);
		}
	};
	
	ReefBuilder.prototype.selectField = function(field) {
		if(this.selectedField === field) {
			return;
		}
		
		if(this.selectedField !== null) {
			this.deselectField();
		}
		
		var $sidetab = this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-content');
		if($sidetab.children().length > 0) {
			alert("Corrupt state (#1)!");
			throw "Corrupt state (#1)!";
		}
		
		field.$declarationForms.appendTo($sidetab).show();
		field.$fieldWrapper.addClass(CSSPRFX+'active');
		
		this.selectedField = field;
		
		this.openSideTab('field');
	};
	
	ReefBuilder.prototype.deselectField = function() {
		if(this.selectedField === null) {
			return;
		}
		
		var fieldTemplates = this.selectedField.$fieldWrapper.find('.'+CSSPRFX+'builder-field-templates');
		
		if(fieldTemplates.children().length > 0) {
			alert("Corrupt state (#2)!");
			throw "Corrupt state (#2)!";
		}
		
		this.selectedField.$declarationForms.hide().appendTo(fieldTemplates);
		
		this.selectedField.$fieldWrapper.removeClass(CSSPRFX+'active');
		
		this.selectedField = null;
		
		if($('.'+CSSPRFX+'builder-tab-active').data('tab') == 'field') {
			this.openSideTab('components');
		}
	};
	
	ReefBuilder.prototype.openSideTab = function(tab) {
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab').hide().filter('.'+CSSPRFX+'builder-sidetab-'+tab).show();
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-tab').removeClass(CSSPRFX+'builder-tab-active').filter('.'+CSSPRFX+'builder-tab-'+tab).addClass(CSSPRFX+'builder-tab-active');
		
		if(tab === 'field') {
			if(this.selectedField === null && this.fields.length > 0) {
				this.selectField(this.fields[0]);
			}
			
			if(this.selectedField !== null) {
				this.openFieldTab('basic');
				this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-'+tab).find(':input').filter(':visible').first().focus();
			}
		}
		else {
			this.deselectField();
		}
	};
	
	ReefBuilder.prototype.openFieldTab = function(tab) {
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-content .'+CSSPRFX+'builder-declaration-forms')
			.children().hide()
			.filter('.'+CSSPRFX+'builder-'+tab+'-declaration-form, .'+CSSPRFX+'builder-'+tab+'-locale-forms').show();
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-tab').removeClass(CSSPRFX+'builder-sidetab-field-tab-active')
			.filter('[data-type="'+tab+'"]').addClass(CSSPRFX+'builder-sidetab-field-tab-active');
	};
	
	ReefBuilder.prototype.submit = function(callback) {
		var self = this;
		
		var i, declaration;
		
		this.deselectField();
		
		if(!this.definitionForm.validate()) {
			this.openSideTab('form');
			return false;
		}
		
		for(i in this.fields) {
			if(!this.fields[i].validate()) {
				return false;
			}
		}
		
		// All is valid, gather fields
		var fields = [];
		var names = [], name;
		
		for(i in this.fields) {
			declaration = this.fields[i].getDeclaration();
			if(typeof declaration.declaration.basic.name !== 'undefined') {
				name = declaration.declaration.basic.name;
				if(typeof names[name] !== 'undefined') {
					alert("Found duplicate name: "+name);
					return;
				}
				names[name] = true;
			}
			
			fields.push(declaration);
		}
		
		// Gather all data
		var form_data = {
			'form_id' : this.$builderWrapper.find('.'+CSSPRFX+'builder').data('form_id'),
			'definition' : this.definitionForm.getData(),
			'fields' : fields
		};
		
		(function(fn_apply) {
			// Check data loss
			$.ajax({
				url: self.$builderWrapper.find('.'+CSSPRFX+'builder-submit').data('action'),
				method: 'POST',
				data: {
					form_data : form_data,
					mode : 'check'
				},
				dataType: 'json',
				success: function(response) {
					if(typeof response == 'object') {
						if(typeof response.errors !== 'undefined') {
							alert(response.errors.join("\n"));
							return;
						}
						
						var definite = [], potential = [];
						
						for(var field_name in response.dataloss) {
							if(response.dataloss[field_name] == 'definite') {
								definite.push(field_name);
							}
							else if(response.dataloss[field_name] == 'potential') {
								potential.push(field_name);
							}
						}
						
						if(definite.length == 0 && potential.length == 0) {
							fn_apply();
							return;
						}
						
						var txt = 'Potential data loss in '+potential.join(', ')+'; definite data loss in '+definite.join(', ')+'. Continue?';
						if(confirm(txt)) {
							fn_apply();
						}
					}
				}
			});
		})(function() {
			// Save form
			$.ajax({
				url: self.$builderWrapper.find('.'+CSSPRFX+'builder-submit').data('action'),
				method: 'POST',
				data: {
					form_data : form_data,
					mode : 'apply'
				},
				dataType: 'json',
				success: function(response) {
					if(typeof response == 'object') {
						if(typeof response.result !== 'undefined') {
							
							
						}
						if(typeof response.redirect !== 'undefined') {
							window.location = response.redirect;
						}
						
						self.options.success(response);
						
						if(typeof callback !== 'undefined') {
							callback(response);
						}
					}
				}
			});
		});
	};
	
	return ReefBuilder;
})();

var ReefBuilderField = (function() {
	'use strict';
	
	var ReefBuilderField = function(reefBuilder) {
		this.reefBuilder = reefBuilder;
		this.declarationForm = {};
		this.localeForms = {};
		this.$fieldWrapper = null;
		this.field = null;
		this.$field = null;
		this.$declarationForms = null;
	};
	
	ReefBuilderField.prototype.initFromItem = function($item) {
		var self = this;
		
		var existingField = $item.is('.'+CSSPRFX+'builder-existing-field');
		
		var $fieldWrapper = $('<div class="'+CSSPRFX+'builder-field"><div class="'+CSSPRFX+'builder-field-preview"></div><div class="'+CSSPRFX+'builder-field-actions"><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-drag-handle">&#8661;</div><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-component-delete">&times;</div></div><div class="'+CSSPRFX+'builder-field-templates"><div class="'+CSSPRFX+'builder-declaration-forms" style="display: none;"></div></div></div>');
		
		var templates = [
			CSSPRFX+'builder-basic-locale-forms',
			CSSPRFX+'builder-basic-declaration-form',
			CSSPRFX+'builder-advanced-declaration-form',
			CSSPRFX+'builder-advanced-locale-forms'
		];
		
		var $template;
		for(var i in templates) {
			$template = $item.find('.'+CSSPRFX+'builder-template.'+templates[i]).clone().removeClass(CSSPRFX+'builder-template');
			$template = $.parseHTML($template[0].outerHTML.replace(new RegExp(escapeRegExp($template.find('.'+CSSPRFX+'main-config').data('form-idpfx')), 'g'), unique_id()));
			$template = $($template);
			
			$template.find(':input').on('change', function() {
				self.updateField();
			});
			
			$fieldWrapper.find('.'+CSSPRFX+'builder-declaration-forms').append($template);
		}
		
		$fieldWrapper.on('click', function() {
			self.reefBuilder.selectField(self);
		});
		
		this.declarationForm.basic = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-basic-declaration-form'));
		this.declarationForm.advanced = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-advanced-declaration-form'));
		
		this.initLocaleWidget($fieldWrapper, 'basic');
		this.initLocaleWidget($fieldWrapper, 'advanced');
		
		if(this.declarationForm.basic.hasField('name') && !existingField) {
			this.declarationForm.basic.getField('name').setValue('field_'+unique_id().substr(0, 16));
		}
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-delete').on('click', function() {
			self.deleteField();
		});
		
		$fieldWrapper.attr('data-component-name', $item.data('component-name'));
		
		$item.replaceWith($fieldWrapper);
		
		this.$fieldWrapper = $fieldWrapper;
		this.$declarationForms = $fieldWrapper.find('.'+CSSPRFX+'builder-declaration-forms');
		
		this.$declarationForms.find('.'+CSSPRFX+'field').on(EVTPRFX+'change', function() {
			self.updateField();
		});
		
		this.updateField();
	};
	
	ReefBuilderField.prototype.initLocaleWidget = function($fieldWrapper, type) {
		var self = this;
		var $localeForms = $fieldWrapper.find('.'+CSSPRFX+'builder-'+type+'-locale-forms .'+CSSPRFX+'builder-locale-form');
		this.localeForms[type] = {};
		
		$localeForms.hide().first().show();
		if($localeForms.length > 1) {
			$localeForms.each(function(index) {
				var $localeForm = $(this);
				if(index === 0) {
					$localeForm.find('.'+CSSPRFX+'builder-locale-prev').addClass(''+CSSPRFX+'builder-locale-end');
				}
				if(index === $localeForms.length-1) {
					$localeForm.find('.'+CSSPRFX+'builder-locale-next').addClass(''+CSSPRFX+'builder-locale-end');
				}
			});
			
			$localeForms.find('.'+CSSPRFX+'builder-locale-prev').not('.'+CSSPRFX+'builder-locale-end').on('click', function() {
				var $localeForm = $(this).closest('.'+CSSPRFX+'builder-locale-form');
				$localeForm.hide().prev().show();
				self.updateField();
			});
			$localeForms.find('.'+CSSPRFX+'builder-locale-next').not('.'+CSSPRFX+'builder-locale-end').on('click', function() {
				var $localeForm = $(this).closest('.'+CSSPRFX+'builder-locale-form');
				$localeForm.hide().next().show();
				self.updateField();
			});
		}
		$localeForms.each(function() {
			var $localeForm = $(this);
			self.localeForms[type][$localeForm.data('locale')] = new Reef($localeForm);
		});
	};
	
	ReefBuilderField.prototype.deleteField = function() {
		this.reefBuilder.removeField(this);
		this.$fieldWrapper.remove();
	};
	
	ReefBuilderField.prototype.updateField = function() {
		var componentName = this.$fieldWrapper.data('component-name');
		
		var template = atob(this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-components .'+CSSPRFX+'builder-component[data-component-name="'+componentName+'"]').data('html'));
		
		var fieldConfig = Object.assign({}, this.declarationForm.basic.getData(), this.declarationForm.advanced.getData(), {'locale' : {}});
		
		// Determine used locale
		var $localeForms = this.$declarationForms.find('.'+CSSPRFX+'builder-locale-form');
		var $locale = $localeForms.filter(':visible');
		if($locale.length == 0) {
			$locale = $localeForms.first();
		}
		var locale = $locale.attr('data-locale');
		
		// Fetch locale data
		if(typeof this.localeForms.basic[locale] !== 'undefined') {
			Object.assign(fieldConfig.locale, this.localeForms.basic[locale].getData());
		}
		if(typeof this.localeForms.advanced[locale] !== 'undefined') {
			Object.assign(fieldConfig.locale, this.localeForms.advanced[locale].getData());
		}
		
		var component;
		if(Reef.hasComponent(componentName)) {
			component = Reef.getComponent(componentName);
		}
		else {
			component = function(){};
		}
		
		if(component.viewVars) {
			fieldConfig = component.viewVars(fieldConfig);
		}
		
		if(component.getLanguageReplacements) {
			var replacements = component.getLanguageReplacements(fieldConfig);
			for(var i in fieldConfig.locale) {
				fieldConfig.locale[i] = fieldConfig.locale[i].replace(/\[\[([^\[\]]+)\]\]/g, function(match, key) {
					var parts = key.split('.');
					var repl = replacements;
					for(var j in parts) {
						if(typeof repl !== 'object' || typeof repl[parts[j]] === 'undefined') {
							return '';
						}
						repl = repl[parts[j]];
					}
					
					// Most likely, `repl` has now become a string...
					return (typeof repl !== 'object') ? repl : '';
				});
			}
		}
		
		var vars = JSON.parse(atob(this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder').attr('data-form_config')));
		vars.form_idpfx = unique_id();
		vars.CSSPRFX = CSSPRFX+'';
		vars.main_var = 'preview';
		vars.field = fieldConfig;
		
		var html = Mustache.render(template, vars);
		
		this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview').html(html);
		this.$field = this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview .'+CSSPRFX+'field');
		
		if(Reef.hasComponent(componentName)) {
			this.field = this.reefBuilder.getReef().newField(this.$field.data(CSSPRFX+'type'), this.$field);
			this.field.attach();
		}
	};
	
	ReefBuilderField.prototype.validate = function() {
		var types = ['basic', 'advanced'], i, type;
		for(i in types) {
			type = types[i];
				
			if(!this.declarationForm[type].validate()) {
				this.reefBuilder.selectField(this);
				this.reefBuilder.openFieldTab(type);
				return false;
			}
			for(var locale in this.localeForms[type]) {
				if(!this.localeForms[type][locale].validate()) {
					this.reefBuilder.selectField(this);
					this.reefBuilder.openFieldTab(type);
					this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field .'+CSSPRFX+'builder-locale-form').hide().find('[data-locale="'+locale+'"]').show();
					return false;
				}
			}
		}
		return true;
	};
	
	ReefBuilderField.prototype.getDeclaration = function() {
		var locales = {}, declaration = {};
		
		var types = ['basic', 'advanced'], i, type;
		for(i in types) {
			type = types[i];
			
			locales[type] = {};
			for(var locale in this.localeForms[type]) {
				locales[type][locale] = this.localeForms[type][locale].getData();
			}
			
			declaration[type] = this.declarationForm[type].getData();
		}
		
		return {
			component: this.$fieldWrapper.data('component-name'),
			declaration: declaration,
			locale: locales
		};
	};
	
	return ReefBuilderField;
})();
