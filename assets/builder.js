// https://stackoverflow.com/questions/105034/create-guid-uuid-in-javascript
function unique_id() {
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000)
		.toString(16)
		.substring(1);
	}
	return s4() + s4() + s4() + s4()+ s4() + s4() + s4() + s4();
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
		
		this.options = options || {};
		this.options.submit_before = this.options.submit_before || $.noop;
		this.options.submit_success = this.options.submit_success || $.noop;
		
		this.selectedField = null;
		this.submitting = false;
		
		this.$builderWrapper = $($builderWrapper);
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-tab').on('click', function() {
			self.openSideTab($(this).data('tab'));
		});
		
		// Add fields on drag
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
		
		Sortable.create(this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace > .'+CSSPRFX+'fields')[0], {
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
					var field = self.addField($item, evt.newIndex);
					self.selectField(field);
				},
				onUpdate: function(evt) {
					self.moveField(evt.oldIndex, evt.newIndex);
				}
			}
		);
		
		// Add fields on click
		this.$builderWrapper.find('.'+CSSPRFX+'builder-component').on('click', function() {
			var $item = $(this).clone();
			self.$builderWrapper.find('.'+CSSPRFX+'builder-workspace > .'+CSSPRFX+'fields').append($item);
			var field = self.addField($item, self.fields.length);
			self.selectField(field);
		});
		
		// Submit button
		this.$builderWrapper.find('.'+CSSPRFX+'builder-submit').on('click', function() {
			self.submit();
		});
		
		// Often used elements
		this.fields = [];
		
		this.reef = new Reef(this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace'));
		
		this.definitionForm = new Reef(this.$builderWrapper.find('.'+CSSPRFX+'builder-definition-form'));
		
		// Add existing fields
		this.$builderWrapper.find('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-existing-field').each(function(index) {
			self.addField($(this), index);
			$(this).remove();
		});
		this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace > .'+CSSPRFX+'fields').append(this.$builderWrapper.find('.'+CSSPRFX+'builder-existing-fields .'+CSSPRFX+'builder-field'));
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-tab').on('click', function() {
			self.openFieldTab($(this).data('type'));
		});
	};
	
	ReefBuilder.prototype.getReef = function() {
		return this.reef;
	};
	
	ReefBuilder.prototype.getFields = function() {
		return this.fields;
	};
	
	ReefBuilder.prototype.getConditionFieldsByName = function() {
		
		var fields = {};
		
		for(var i=0; i<this.fields.length; i++) {
			if(this.fields[i].declarationForm.basic.hasField('name')) {
				fields[this.fields[i].declarationForm.basic.getField('name').getValue()] = this.fields[i];
			}
		}
		
		return fields;
	};
	
	ReefBuilder.prototype.addField = function($item, newIndex) {
		var field = new ReefBuilderField(this);
		field.initFromItem($item);
		this.fields.splice(newIndex, 0, field);
		return field;
	};
	
	ReefBuilder.prototype.moveField = function(oldIndex, newIndex) {
		this.interruptSubmit();
		
		if(typeof this.fields[oldIndex] == 'undefined') {
			return;
		}
		
		this.fields.splice(newIndex, 0, this.fields.splice(oldIndex, 1)[0]);
	};
	
	ReefBuilder.prototype.removeField = function(rbfield) {
		this.interruptSubmit();
		this.deselectField();
		var index = this.fields.indexOf(rbfield);
		if(index > -1) {
			this.fields.splice(index, 1);
		}
		this.getReef().removeField(rbfield.current_name, rbfield.field);
	};
	
	ReefBuilder.prototype.selectField = function(field) {
		if(this.selectedField === field) {
			return;
		}
		
		// Deselect field before selecting new one
		if(this.selectedField !== null) {
			this.deselectField();
		}
		
		// Check that sidetab not still contains content
		var $sidetab = this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-content');
		if($sidetab.children().length > 0) {
			alert("Corrupt state (#1)!");
			throw "Corrupt state (#1)!";
		}
		
		// Move forms to side tab and show it
		field.$declarationForms.appendTo($sidetab).show();
		field.$fieldWrapper.addClass(CSSPRFX+'active');
		
		// Display component name
		var componentName = field.$fieldWrapper.attr('data-component-name');
		var componentTitle = this.$builderWrapper.find('.'+CSSPRFX+'builder-component[data-component-name="'+componentName+'"] span.'+CSSPRFX+'builder-component-title').text();
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-component-name').text(componentTitle);
		
		// Register selected field
		this.selectedField = field;
		
		// Show/hide basic/advanced tabs depending on content
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-tabs').toggle(
			this.$builderWrapper
				.find('.'+CSSPRFX+'builder-sidetab-field-content .'+CSSPRFX+'builder-declaration-forms')
				.find('.'+CSSPRFX+'builder-advanced-declaration-form, .'+CSSPRFX+'builder-advanced-locale-forms')
				.find('.'+CSSPRFX+'field')
				.length > 0
		);
		
		// Make sure we open the field side tab
		this.openSideTab('field');
	};
	
	ReefBuilder.prototype.deselectField = function() {
		if(this.selectedField === null) {
			return;
		}
		
		// Check that the field forms container is empty
		var fieldTemplates = this.selectedField.$fieldWrapper.find('.'+CSSPRFX+'builder-field-templates');
		
		if(fieldTemplates.children().length > 0) {
			alert("Corrupt state (#2)!");
			throw "Corrupt state (#2)!";
		}
		
		// Move forms back to the forms container
		this.selectedField.$declarationForms.hide().appendTo(fieldTemplates);
		
		this.selectedField.$fieldWrapper.removeClass(CSSPRFX+'active');
		this.selectedField.checkValid();
		
		// Remove component name
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-component-name').text('');
		
		// Register (no) selected field
		this.selectedField = null;
		
		// Make sure we switch to another side tab
		if($('.'+CSSPRFX+'builder-tab-active').data('tab') == 'field') {
			this.openSideTab('form');
		}
	};
	
	ReefBuilder.prototype.openSideTab = function(tab) {
		this.interruptSubmit();
		
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
			.filter('.'+CSSPRFX+'builder-'+tab+'-declaration-form, .'+CSSPRFX+'builder-'+tab+'-locale-forms').show().trigger(EVTPRFX+'builder-open');
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-field-tab').removeClass(CSSPRFX+'builder-sidetab-field-tab-active')
			.filter('[data-type="'+tab+'"]').addClass(CSSPRFX+'builder-sidetab-field-tab-active');
	};
	
	ReefBuilder.prototype.submit = function(options) {
		var self = this;
		
		if(this.submitting) {
			return;
		}
		
		options = options || {};
		options.submit_before = options.submit_before || $.noop;
		options.submit_success = options.submit_success || $.noop;
		
		self.clearGeneralErrors();
		
		var i, declaration;
		
		this.deselectField();
		
		this.saveProgressIcon('validate');
		this.submitting = true;
		
		if(!this.definitionForm.validate()) {
			this.openSideTab('form');
			this.saveProgressIcon('validate', 'error');
			this.submitting = false;
			return false;
		}
		
		for(i in this.fields) {
			if(!this.fields[i].validate()) {
				this.saveProgressIcon('validate', 'error');
				this.submitting = false;
				return false;
			}
		}
		
		// All is valid, gather fields
		this.$builderWrapper.find('.'+CSSPRFX+'builder-workspace .'+CSSPRFX+'builder-field.'+CSSPRFX+'builder-field-error').removeClass(CSSPRFX+'builder-field-error');
		
		var fields = [];
		var names = [], name;
		
		for(i in this.fields) {
			declaration = this.fields[i].getDeclaration();
			if(typeof declaration.declaration.basic.name !== 'undefined') {
				name = declaration.declaration.basic.name;
				if(typeof names[name] !== 'undefined') {
					self.generalErrors(["Found duplicate name: "+name]);
					this.saveProgressIcon('validate', 'error');
					this.submitting = false;
					return;
				}
				names[name] = true;
			}
			
			fields.push(declaration);
		}
		
		this.saveProgressIcon('validate', 'tick');
		this.saveProgressIcon('save');
		
		// Gather all data
		var builder_data = {
			'definition' : this.definitionForm.getData(),
			'fields' : fields,
			'allow_dataloss' : 'no'
		};
		
		var fn_submit = function() {
			// Check for data loss & apply
			
			var ajaxParams = {
				url: self.$builderWrapper.find('.'+CSSPRFX+'builder-submit').data('action'),
				method: 'POST',
				data: {
					builder_data : builder_data
				},
				dataType: 'json',
				success: function(response) {
					if(typeof response == 'object') {
						if(typeof response.errors !== 'undefined' || typeof response.result === 'undefined') {
							if(typeof response.errors !== 'undefined') {
								self.addErrors(response.errors);
							}
							self.saveProgressIcon('save', 'error');
							self.submitting = false;
							return;
						}
						
						var hasDataloss = self.displayDataLoss(response.dataloss, function() {
							// Dataloss option 'yes'
							self.saveProgressIcon('save');
							builder_data.allow_dataloss = 'yes';
							fn_submit();
						}, function() {
							// Dataloss option 'no'
							self.saveProgressIcon('data', 'error');
							self.submitting = false;
						});
						
						if(hasDataloss) {
							// Data loss, so the yes/no question has been asked
							self.saveProgressIcon('data', 'question');
							return;
						}
						
						// 'Some' error?
						if(!response.result) {
							self.saveProgressIcon('save', 'error');
							self.submitting = false;
							return;
						}
						
						// A positive result and no data loss
						self.saveProgressIcon('save', 'tick');
						
						if(typeof response.redirect !== 'undefined') {
							window.location = response.redirect;
						}
						
						// Callback
						self.options.submit_success(response);
						options.submit_success(response);
						
						self.submitting = false;
					}
				}
			};
			
			// Callback
			self.options.submit_before(ajaxParams);
			options.submit_before(ajaxParams);
			
			$.ajax(ajaxParams);
		};
		
		fn_submit();
	};
	
	ReefBuilder.prototype.interruptSubmit = function() {
		if(this.hideDataLoss()) {
			// The user has navigated away from the form tab or performed an action, without choosing 'yes' or 'no' first
			// Hence the value of submitting is probably still true
			// Set it to false to be able to submit the form again
			this.submitting = false;
		}
	};
	
	ReefBuilder.prototype.addErrors = function(errors) {
		var pos, fieldErrors;
		
		this.clearGeneralErrors();
		
		for(pos in errors) {
			fieldErrors = errors[pos];
			
			if(typeof this.fields[pos] === 'undefined') {
				this.generalErrors(fieldErrors);
				continue;
			}
			
			this.fields[pos].addErrors(fieldErrors);
		}
	};
	
	ReefBuilder.prototype.clearGeneralErrors = function() {
		this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-form-errors').html('');
	};
	
	ReefBuilder.prototype.generalErrors = function(errors) {
		for(var name in errors) {
			this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-form-errors').append($('<div class="'+CSSPRFX+'builder-error"></div>').text(errors[name]));
		}
		
		this.openSideTab('form');
	};
	
	ReefBuilder.prototype.saveProgressIcon = function(stage, status) {
		var self = this;
		
		var stages = ['validate', 'data', 'save'];
		var stageIdx = stages.indexOf(stage);
		if(stageIdx == -1) {
			return;
		}
		
		var $fn_icon = function(status) {
			return self.$builderWrapper.find('.'+CSSPRFX+'builder-save-icons .'+CSSPRFX+'builder-'+status).children(':first-child').clone();
		};
		
		if(arguments.length < 2) {
			status = 'spinner';
		}
		
		this.$builderWrapper
			.find('.'+CSSPRFX+'builder-save-'+stage)
				.show()
			.children('.'+CSSPRFX+'builder-save-icon')
				.html('')
				.append($fn_icon(status));
		
		for(var i=stageIdx+1; i<stages.length; i++) {
			this.$builderWrapper
				.find('.'+CSSPRFX+'builder-save-'+stages[i])
					.hide()
				.children('.'+CSSPRFX+'builder-save-icon')
					.html('');
		}
		
	};
	
	ReefBuilder.prototype.hideDataLoss = function() {
		var was_visible = this.$builderWrapper.find('.'+CSSPRFX+'builder-save-dataloss').is(':visible');
		
		this.$builderWrapper
			.find('.'+CSSPRFX+'builder-save-dataloss')
				.hide()
			.find('.'+CSSPRFX+'builder-save-dataloss-definite, .'+CSSPRFX+'builder-save-dataloss-potential')
				.hide()
			.find('ul.'+CSSPRFX+'builder-save-dataloss-fields')
				.html('');
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-dataloss-no, .'+CSSPRFX+'builder-dataloss-yes').off('click.dataloss');
		
		return was_visible;
	};
	
	ReefBuilder.prototype.displayDataLoss = function(dataloss, callback_yes, callback_no) {
		var self = this;
		
		// Reset
		this.hideDataLoss();
		
		if(typeof dataloss === 'undefined') {
			return false;
		}
		
		// Build mapping from field names to field index
		var field_name2idx = {};
		for(var i=0; i<this.fields.length; i++) {
			if(this.fields[i].declarationForm.basic.hasField('name')) {
				field_name2idx[this.fields[i].declarationForm.basic.getField('name').getValue()] = i;
			}
		}
		
		// Show data loss fields
		var hasDataloss = false;
		for(var field_name in dataloss) {
			if(dataloss[field_name] != 'definite' && dataloss[field_name] != 'potential') {
				continue;
			}
			
			hasDataloss = true;
			
			// Append field name to data loss list
			var li;
			if(typeof(field_name2idx[field_name]) !== 'undefined') {
				li = $('<li>').append(
					$('<a href="#">').text(field_name).data('field_name', field_name).on('click', function(evt) {
						evt.preventDefault();
						self.selectField(self.fields[field_name2idx[$(this).data('field_name')]]);
					})
				);
			}
			else {
				// Should not happen, but do this as a fallback
				li = $('<li>').text(field_name);
			}
			
			this.$builderWrapper
				.find('.'+CSSPRFX+'builder-save-dataloss-'+dataloss[field_name])
					.show()
				.find('ul')
					.append(li);
		}
		
		// Only show builder & attach events if there really is dataloss to be mentioned
		if(!hasDataloss) {
			return false;
		}
		else {
			this.$builderWrapper
				.find('.'+CSSPRFX+'builder-save-dataloss')
					.show();
		}
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-dataloss-yes').off('click.dataloss').one('click.dataloss', function() {
			self.hideDataLoss();
			callback_yes();
		});
		
		this.$builderWrapper.find('.'+CSSPRFX+'builder-dataloss-no').off('click.dataloss').one('click.dataloss', function() {
			self.hideDataLoss();
			callback_no();
		});
		
		return true;
	};
	
	ReefBuilder.prototype.createField = function(reef, $container, fieldConfig, options) {
		options = options || {};
		
		var componentName = fieldConfig.component;
		
		var template = atob(this.$builderWrapper.find('.'+CSSPRFX+'builder-sidetab-components .'+CSSPRFX+'builder-component[data-component-name="'+componentName+'"]').data('html'));
		
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
		
		delete fieldConfig.visible;
		if(typeof fieldConfig.required !== 'undefined') {
			try {
				// Only try to set the 'required-if' data attribute if it is a valid condition
				ReefConditionEvaluator.evaluate(reef, fieldConfig.required);
				fieldConfig.required = 'data-required-if="'+(fieldConfig.required.replace(/&/g, '&amp;').replace(/"/g, '&quot;'))+'"';
			}
			catch(e) {
				// Otherwise, neglect the required setting for this preview
				delete fieldConfig.required;
			}
		}
		
		var vars = JSON.parse(atob(this.$builderWrapper.find('.'+CSSPRFX+'builder').attr('data-form_config')));
		vars.form_idpfx = unique_id();
		vars.CSSPRFX = CSSPRFX+'';
		vars.main_var = 'preview';
		vars.field = fieldConfig;
		vars.internalRequest = function() {
			return reef.internalRequestHelper();
		};
		vars.nl2br = function() {
			return function(template, render) {
				return render(template).replace(/(\r\n|\n\r|\r|\n)/g, '<br />$1');
			};
		};
		
		if(typeof options.beforeRender !== 'undefined') {
			options.beforeRender(vars);
		}
		
		var html = Mustache.render(template, vars);
		
		$container.html(html);
		var field = null;
		var $field = $container.find('.'+CSSPRFX+'field');
		
		if(Reef.hasComponent(componentName)) {
			field = reef.newField($field.data(CSSPRFX+'type'), $field);
			field.attach();
		}
		
		for(var extensionName in reef.extensionInstances) {
			reef.extensionInstances[extensionName].attach($field);
		}
		
		return field;
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
		this.current_name = null;
	};
	
	ReefBuilderField.prototype.initFromItem = function($item) {
		var self = this;
		
		var existingField = $item.is('.'+CSSPRFX+'builder-existing-field');
		
		var $fieldWrapper = this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-template.'+CSSPRFX+'builder-field').clone().removeClass(CSSPRFX+'builder-template');
		
		var templates = [
			CSSPRFX+'builder-basic-locale-forms',
			CSSPRFX+'builder-basic-declaration-form',
			CSSPRFX+'builder-advanced-declaration-form',
			CSSPRFX+'builder-advanced-locale-forms'
		];
		
		var $template;
		for(var i in templates) {
			$template = $item.find('.'+CSSPRFX+'builder-template.'+templates[i]).clone().removeClass(CSSPRFX+'builder-template');
			$template = $.parseHTML($template[0].outerHTML.replace(new RegExp(ReefUtil.escapeRegExp($template.find('.'+CSSPRFX+'main-config').data('form-idpfx')), 'g'), unique_id()));
			$template = $($template);
			
			$fieldWrapper.find('.'+CSSPRFX+'builder-declaration-forms').append($template);
		}
		
		$fieldWrapper.on('click', function(evt) {
			if($(evt.target).closest('.'+CSSPRFX+'builder-component-delete, .'+CSSPRFX+'builder-field-delete-confirm').length > 0) {
				return;
			}
			
			self.reefBuilder.selectField(self);
		});
		
		this.declarationForm.basic = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-basic-declaration-form'), {'builder': this.reefBuilder});
		this.declarationForm.advanced = new Reef($fieldWrapper.find('.'+CSSPRFX+'builder-advanced-declaration-form'), {'builder': this.reefBuilder});
		
		this.initLocaleWidget($fieldWrapper, 'basic');
		this.initLocaleWidget($fieldWrapper, 'advanced');
		
		if(this.declarationForm.basic.hasField('name')) {
			var nameField = this.declarationForm.basic.getField('name');
			
			if(!existingField) {
				nameField.setValue('field_'+unique_id().substr(0, 16));
			}
			
			this.current_name = nameField.getValue();
			
			nameField.$field.find('input').on('change', function() {
				var name = $(this).val();
				if(name != self.current_name && self.reefBuilder.getReef().hasField(name)) {
					var i = 1;
					while(self.reefBuilder.getReef().hasField(name + '_' + (++i)));
					
					$(this).val(name + '_' + i);
				}
				
				$fieldWrapper.trigger(EVTPRFX+'name_change', [self.current_name, $(this).val()]);
			});
		}
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-declaration-forms :input').on('change', function() {
			self.updateField();
		});
		
		$fieldWrapper.find('.'+CSSPRFX+'builder-component-delete').on('click', function() {
			var state = {
				prevent : false
			};
			$fieldWrapper.trigger(EVTPRFX+'delete_field_before', [self, state]);
			
			if(state.prevent) {
				return;
			}
			
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
		var self = this;
		
		var $lang = this.reefBuilder.$builderWrapper.find('.'+CSSPRFX+'builder-lang');
		
		var $deleteConfirm = $('<div class="'+CSSPRFX+'builder-field-delete-confirm">');
		$deleteConfirm.append($('<div>').text($lang.data('builder_delete_field_confirm')));
		
		$deleteConfirm.append($('<div class="'+CSSPRFX+'builder-btn">').text($lang.data('yes')).on('click', function() {
			$deleteConfirm.remove();
			
			self.reefBuilder.removeField(self);
			self.$fieldWrapper.remove();
		}));
		
		$deleteConfirm.append($('<div class="'+CSSPRFX+'builder-btn">').text($lang.data('no')).on('click', function() {
			$deleteConfirm.remove();
			
			self.$fieldWrapper.removeClass(CSSPRFX+'builder-field-deleting');
		}));
		
		this.$fieldWrapper.addClass(CSSPRFX+'builder-field-deleting').append($deleteConfirm);
	};
	
	ReefBuilderField.prototype.updateField = function() {
		var self = this;
		
		var fieldConfig = Object.assign({},
			this.declarationForm.basic.getData(),
			this.declarationForm.advanced.getData(),
			{
				'component' : this.$fieldWrapper.data('component-name'),
				'locale' : {}
			}
		);
		
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
		
		if(typeof fieldConfig.name !== 'undefined') {
			this.reefBuilder.getReef().removeField(this.current_name, this.field);
		}
		
		this.field = this.reefBuilder.createField(
			this.reefBuilder.getReef(),
			this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview'),
			fieldConfig
		);
		
		if(typeof fieldConfig.name !== 'undefined') {
			this.current_name = fieldConfig.name;
			this.reefBuilder.getReef().addField(this.current_name, this.field);
		}
		
		this.$field = this.$fieldWrapper.find('.'+CSSPRFX+'builder-field-preview .'+CSSPRFX+'field');
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
	
	ReefBuilderField.prototype.checkValid = function() {
		this.$fieldWrapper.toggleClass(CSSPRFX+'builder-field-error', this.$fieldWrapper.find('.'+CSSPRFX+'builder-declaration-forms .'+CSSPRFX+'invalid').length > 0);
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
	
	ReefBuilderField.prototype.addErrors = function(errors) {
		var errorSection, errorSubSection;
		
		for(errorSection in errors) {
			if(errorSection == '-1') {
				for(var name in errors[errorSection]) {
					var tmpError = {};
					tmpError[name] = errors[errorSection][name];
					if(this.declarationForm.basic.hasField(name)) {
						this.declarationForm.basic.addErrors(tmpError);
						delete errors[errorSection][name];
					}
					else if(this.declarationForm.advanced.hasField(name)) {
						this.declarationForm.advanced.addErrors(tmpError);
						delete errors[errorSection][name];
					}
				}
				
				this.reefBuilder.generalErrors(errors[errorSection]);
			}
			
			if(errorSection == 'declaration') {
				for(errorSubSection in errors[errorSection]) {
					this.declarationForm[errorSubSection].addErrors(errors[errorSection][errorSubSection]);
				}
			}
		}
		
		this.checkValid();
	};
	
	return ReefBuilderField;
})();
