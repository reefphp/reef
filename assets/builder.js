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
		
		var $sidetab = this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field');
		if($sidetab.children().length > 0) {
			alert("Corrupt state (#1)!");
			throw "Corrupt state (#1)!";
		}
		
		field.$declarationForm.appendTo($sidetab).show();
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
		
		this.selectedField.$declarationForm.hide().appendTo(fieldTemplates);
		
		this.selectedField.$fieldWrapper.removeClass(CSSPRFX+'active');
		
		this.selectedField = null;
	};
	
	ReefBuilder.prototype.openSideTab = function(tab) {
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab').hide().filter('.'+CSSPRFX+'builder-sidetab-'+tab).show();
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-tab').removeClass(CSSPRFX+'builder-tab-active').filter('.'+CSSPRFX+'builder-tab-'+tab).addClass(CSSPRFX+'builder-tab-active');
		
		if(tab === 'field' && this.selectedField === null && this.fields.length > 0) {
			this.selectField(this.fields[0]);
		}
	};
	
	ReefBuilder.prototype.submit = function() {
		var self = this;
		
		var i;
		
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
		
		for(i in this.fields) {
			fields.push(this.fields[i].getDeclaration());
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
		this.componentForm = null;
		this.localeForms = null;
		this.$fieldWrapper = null;
		this.field = null;
		this.$field = null;
		this.$declarationForm = null;
	};
	
	ReefBuilderField.prototype.initFromItem = function($item) {
		var self = this;
		
		var existingField = $item.is('.'+CSSPRFX+'builder-existing-field');
		
		var $fieldWrapper = $('<div class="'+CSSPRFX+'builder-field"><div class="'+CSSPRFX+'builder-field-preview"></div><div class="'+CSSPRFX+'builder-field-actions"><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-drag-handle">&#8661;</div><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-component-delete">&times;</div></div><div class="'+CSSPRFX+'builder-field-templates"><div class="'+CSSPRFX+'builder-declaration-form" style="display: none;"></div></div></div>');
		
		var templates = [CSSPRFX+'builder-component-form', CSSPRFX+'builder-locale-forms'];
		var $template;
		for(var i in templates) {
			$template = $item.find('.'+CSSPRFX+'builder-template.'+templates[i]).clone().removeClass(CSSPRFX+'builder-template');
			$template = $.parseHTML($template[0].outerHTML.replace(new RegExp(escapeRegExp($template.find('.'+CSSPRFX+'main-config').data('form-idpfx')), 'g'), unique_id()));
			
			$fieldWrapper.find('.'+CSSPRFX+'builder-declaration-form').append($template);
		}
		
		$fieldWrapper.on('click', function() {
			self.reefBuilder.selectField(self);
		});
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-form input, .'+CSSPRFX+'builder-locale-forms input').on('change', function() {
			self.updateField($fieldWrapper);
		});
		
		this.componentForm = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-component-form'));
		this.localeForms = {};
		
		var $localeForms = $fieldWrapper.find('.'+CSSPRFX+'builder-locale-form');
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
			self.localeForms[$localeForm.data('locale')] = new Reef($localeForm);
		});
		
		if(this.componentForm.hasField('name') && !existingField) {
			this.componentForm.getField('name').setValue('field_'+unique_id().substr(0, 16));
		}
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-delete').on('click', function() {
			self.deleteField();
		});
		
		$fieldWrapper.attr('data-component-name', $item.data('component-name'));
		
		$item.replaceWith($fieldWrapper);
		
		this.$fieldWrapper = $fieldWrapper;
		this.$declarationForm = $fieldWrapper.find('.'+CSSPRFX+'builder-declaration-form');
		
		this.updateField();
	};
	
	ReefBuilderField.prototype.deleteField = function() {
		this.reefBuilder.removeField(this);
		this.$fieldWrapper.remove();
	};
	
	ReefBuilderField.prototype.updateField = function() {
		var componentName = this.$fieldWrapper.data('component-name');
		
		var template = atob(this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-components .'+CSSPRFX+'builder-component[data-component-name="'+componentName+'"]').data('html'));
		
		var fieldConfig = this.componentForm.getData();
		var $localeForms = this.$declarationForm.find('.'+CSSPRFX+'builder-locale-form');
		var $locale = $localeForms.filter(':visible');
		if($locale.length == 0) {
			$locale = $localeForms.first();
		}
		var locale = $locale.attr('data-locale');
		if(typeof this.localeForms[locale] !== 'undefined') {
			fieldConfig.locale = this.localeForms[locale].getData();
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
		if(!this.componentForm.validate()) {
			this.reefBuilder.selectField(this);
			return false;
		}
		for(var locale in this.localeForms) {
			if(!this.localeForms[locale].validate()) {
				this.reefBuilder.selectField(this);
				this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field .'+CSSPRFX+'builder-locale-form').hide().find('[data-locale="'+locale+'"]').show();
				return false;
			}
		}
		return true;
	};
	
	ReefBuilderField.prototype.getDeclaration = function() {
		var locales = {};
		
		for(var locale in this.localeForms) {
			locales[locale] = this.localeForms[locale].getData();
		}
		
		return {
			component: this.$fieldWrapper.data('component-name'),
			config: this.componentForm.getData(),
			locale: locales
		};
	};
	
	return ReefBuilderField;
})();
