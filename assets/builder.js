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

$(function() {
	$('.'+CSSPRFX+'builder-category').on('click', function() {
		$('.'+CSSPRFX+'builder-components').hide().filter('[data-category="'+$(this).data('category')+'"]').show();
		$('.'+CSSPRFX+'builder-category').removeClass(CSSPRFX+'active');
		$(this).addClass(CSSPRFX+'active');
	});
	
	$('.'+CSSPRFX+'builder-components').each(function() {
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
	
	Sortable.create($('.'+CSSPRFX+'builder-workspace .'+CSSPRFX+'fields')[0], {
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
				ReefBuilder.addField($item, evt.newIndex);
			},
			onUpdate: function(evt) {
				ReefBuilder.moveField(evt.oldIndex, evt.newIndex);
			}
		}
	);
	
	$('.'+CSSPRFX+'builder-toolbar-submit').on('click', function() {
		ReefBuilder.submit();
	});
	
});

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
	
	var ReefBuilder = function() {
		var self = this;
		
		this.fields = [];
		this.reef = null;
		
		$(function() {
			self.reef = new Reef($('.'+CSSPRFX+'builder-workspace'));
			
			self.configForm = new Reef($('.'+CSSPRFX+'builder-form-config'));
			
			self.configDialog = new ReefDialog($('.'+CSSPRFX+'builder-dialog.'+CSSPRFX+'builder-form-config'), $('.'+CSSPRFX+'builder-toolbar-config'));
			
			$('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-existing-field').each(function(index) {
				self.addField($(this), index);
				$(this).remove();
			});
			$('.'+CSSPRFX+'builder-workspace .'+CSSPRFX+'fields').append($('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-field'));
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
		var index = this.fields.indexOf(field);
		if(index > -1) {
			this.fields.splice(index, 1);
		}
	};
	
	ReefBuilder.prototype.submit = function() {
		var i;
		
		if(!this.configForm.validate()) {
			this.configDialog.show();
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
			fields.push(this.fields[i].getDefinition());
		}
		
		// Gather all data
		var form_data = {
			'form_id' : $('.'+CSSPRFX+'builder').data('form_id'),
			'form_config' : this.configForm.getData(),
			'fields' : fields
		};
		
		// Save form
		$.ajax({
			url: $('.'+CSSPRFX+'builder-toolbar-submit').data('action'),
			method: 'POST',
			data: {
				form_data : form_data
			},
			dataType: 'json',
			success: function(response) {
				if(typeof response == 'object') {
					if(typeof response.result !== 'undefined') {
						
						
					}
					if(typeof response.redirect !== 'undefined') {
						window.location = response.redirect;
					}
				}
			}
		});
	};
	
	return new ReefBuilder();
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
	};
	
	ReefBuilderField.prototype.initFromItem = function($item) {
		var self = this;
		
		var existingField = $item.is('.'+CSSPRFX+'builder-existing-field');
		
		var $fieldWrapper = $('<div class="'+CSSPRFX+'builder-field"><div class="'+CSSPRFX+'builder-field-preview"></div><div class="'+CSSPRFX+'builder-field-actions"><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-drag-handle">&#8661;</div><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-component-config">&#9881;</div><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-component-locale">&#128483;</div><div class="'+CSSPRFX+'builder-btn '+CSSPRFX+'builder-component-delete">&times;</div></div><div class="'+CSSPRFX+'builder-field-templates"></div></div>');
		
		var templates = [CSSPRFX+'builder-component-form', CSSPRFX+'builder-locale-forms'];
		var $template;
		for(var i in templates) {
			$template = $item.find('.'+CSSPRFX+'builder-template.'+templates[i]).clone().hide().removeClass(CSSPRFX+'builder-template').addClass(CSSPRFX+'builder-dialog');
			$template = $.parseHTML($template[0].outerHTML.replace(new RegExp(escapeRegExp($template.find('.'+CSSPRFX+'main-config').data('form-idpfx')), 'g'), unique_id()));
			
			$fieldWrapper.find('.'+CSSPRFX+'builder-field-templates').append($template);
		}
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-form input, .'+CSSPRFX+'builder-locale-forms input').on('change', function() {
			self.updateField($fieldWrapper);
		});
		
		this.componentForm = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-component-form'));
		this.localeForms = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-locale-forms'));
		
		this.componentDialog = new ReefDialog($fieldWrapper.find('.'+CSSPRFX+'builder-component-form'), $fieldWrapper.find('.'+CSSPRFX+'builder-component-config'));
		this.localeDialog = new ReefDialog($fieldWrapper.find('.'+CSSPRFX+'builder-locale-forms'), $fieldWrapper.find('.'+CSSPRFX+'builder-component-locale'));
		
		if(this.componentForm.hasField('name') && !existingField) {
			this.componentForm.getField('name').setValue('field_'+unique_id().substr(0, 16));
		}
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-delete').on('click', function() {
			self.deleteField();
		});
		
		$fieldWrapper.attr('data-component-name', $item.data('component-name'));
		
		$item.replaceWith($fieldWrapper);
		
		this.$fieldWrapper = $fieldWrapper;
		
		this.updateField();
	};
	
	ReefBuilderField.prototype.deleteField = function() {
		this.reefBuilder.removeField(this);
		this.$fieldWrapper.remove();
	};
	
	ReefBuilderField.prototype.updateField = function() {
		var componentName = this.$fieldWrapper.data('component-name');
		
		var template = atob($('.'+CSSPRFX+'builder-select .'+CSSPRFX+'builder-component[data-component-name="'+componentName+'"]').data('html'));
		
		var fieldConfig = this.componentForm.getData();
		fieldConfig.locale = this.localeForms.getData();
		
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
		
		var vars = JSON.parse(atob($('.'+CSSPRFX+'builder').attr('data-form_config')));
		vars.form_idpfx = unique_id();
		vars.CSSPRFX = CSSPRFX+'';
		vars.main_var = 'preview';
		vars.field = fieldConfig;
		
		var html = Mustache.render(template, vars);
		
		this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview').html(html);
		this.$field = this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview .'+CSSPRFX+'field');
		
		if(Reef.hasComponent(componentName)) {
			this.field = ReefBuilder.getReef().newField(this.$field.data(CSSPRFX+'type'), this.$field);
			this.field.attach();
		}
	};
	
	ReefBuilderField.prototype.validate = function() {
		if(!this.componentForm.validate()) {
			this.componentDialog.show();
			return false;
		}
		if(!this.localeForms.validate()) {
			this.localeDialog.show();
			return false;
		}
		return true;
	};
	
	ReefBuilderField.prototype.getDefinition = function() {
		return {
			component: this.$fieldWrapper.data('component-name'),
			config: this.componentForm.getData(),
			locale: this.localeForms.getData()
		};
	};
	
	return ReefBuilderField;
})();
