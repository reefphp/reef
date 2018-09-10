Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.layouts = {};
		for(var layoutName in Field.layoutPrototypes) {
			this.layouts[layoutName] = new Field.layoutPrototypes[layoutName](this);
		}
		
		this.auto_inc = 1;
		this.active_locale = null;
	};
	
	Field.componentName = 'reef:option_list';
	
	Field.layoutPrototypes = {};
	Field.addLayout = function(layout) {
		Field.layoutPrototypes[layout.layoutName] = layout;
	};
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.openLocale(this.$field.find('div.'+CSSPRFX+'ol-locale').first().attr('data-locale'));
		
		this.$field.find('.'+CSSPRFX+'ol-add').on('click', function() {
			self.newDefaultOption();
		});
		
		var options = this.$field.find('table tbody').data('options');
		options = (typeof options !== 'undefined' && options != '') ? JSON.parse(atob(options)) : {};
		if(options.length > 0) {
			var option;
			for(var i in options) {
				option = options[i];
				
				var $option = this.newOption();
				$option.find('.'+CSSPRFX+'ol-default').prop('checked', option.default);
				$option.find('.'+CSSPRFX+'ol-name').val(option.name);
				$option.find('.'+CSSPRFX+'ol-old-name').val(option.name);
				
				for(var l in option.locale) {
					$option.find('div.'+CSSPRFX+'ol-locale[data-locale="'+l+'"] input').val(option.locale[l]);
				}
			}
		}
		else {
			for(var i=0; i<this.$field.attr('data-num_opt_def'); i++) {
				this.newDefaultOption();
			}
		}
		
		Sortable.create(this.$field.find('tbody')[0], {
				sort: true,
				handle: '.'+CSSPRFX+'ol-drag-handle',
				animation: 150,
				onUpdate: function(evt) {
					self.$field.trigger(EVTPRFX+'change');
				}
			}
		);
		
		this.initLocaleNavigation();
	};
	
	Field.prototype.newDefaultOption = function() {
		var name = 'option_'+this.auto_inc;
		var $option = this.newOption();
		$option.find('.'+CSSPRFX+'ol-name').val(name);
		$option.find('.'+CSSPRFX+'ol-old-name').val('__new__');
		$option.find('div.'+CSSPRFX+'ol-locale input').filter(':visible').first().focus();
		return $option;
	};
	
	Field.prototype.newOption = function() {
		var self = this;
		
		var $option = this.$field.find('table thead tr.'+CSSPRFX+'ol-option.'+CSSPRFX+'template').clone().removeClass(CSSPRFX+'template');
		
		this.$field.find('tbody').append($option);
		
		if(this.$field.attr('data-max_checked_def') == 1) {
			$option.find('.'+CSSPRFX+'ol-default').on('click', function() {
				if($(this).prop('checked')) {
					self.$field.find('.'+CSSPRFX+'ol-default').not($(this)).prop('checked', false);
				}
			});
		}
		
		$.each(['.'+CSSPRFX+'ol-name', '.'+CSSPRFX+'ol-locale input'], function(i, selector) {
			$option.find(selector).on('keydown', function(evt) {
				if(evt.which == 13 || evt.which == 38 || evt.which == 40) {
					// Enter, up, down
					var $focusOption = (evt.which == 38) ? $option.prev() : $option.next();
					
					if($focusOption.length == 0 && evt.which == 13) {
						$focusOption = self.newDefaultOption();
					}
					
					if($focusOption.length == 1) {
						$focusOption.find(selector).filter(':visible').first().focus();
					}
				}
				
				if(evt.which == 9 && i == 1) {
					// Tab
					var $localeHeads = self.$field.find('table thead th .'+CSSPRFX+'ol-locale');
					if($localeHeads.length > 1) {
						var $localeHead = $localeHeads.filter(':visible');
						var $newLocaleHead;
						if(evt.shiftKey) {
							$newLocaleHead = $localeHead.prev();
							if($newLocaleHead.length > 0) {
								evt.preventDefault();
							}
						}
						else {
							$newLocaleHead = $localeHead.next();
							if($newLocaleHead.length > 0) {
								evt.preventDefault();
							}
							else {
								$newLocaleHead = $localeHead.siblings().first();
							}
						}
						if($newLocaleHead.length == 1) {
							self.openLocale($newLocaleHead.attr('data-locale'));
						}
					}
				}
			});
		});
		
		$option.find('.'+CSSPRFX+'ol-default, .'+CSSPRFX+'ol-name, .'+CSSPRFX+'ol-locale input').on('change', function() {
			self.$field.trigger(EVTPRFX+'change');
		});
		
		$option.find('.'+CSSPRFX+'ol-remove-option').on('click', function() {
			self.removeOption($option);
		});
		
		this.auto_inc++;
		
		return $option;
	};
	
	Field.prototype.removeOption = function($option) {
		var self = this;
		
		var $lang = this.$field.find('.'+CSSPRFX+'ol-lang');
		
		var $deleteConfirm = $('<td class="'+CSSPRFX+'ol-delete-confirm" colspan="5">');
		var $deleteConfirmDiv = $('<div class="'+CSSPRFX+'ol-delete-confirm-div">').appendTo($deleteConfirm);
		$deleteConfirmDiv.append($('<div>').text($lang.data('delete_option_confirm')));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'ol-btn">').text($lang.data('yes')).on('click', function() {
			$deleteConfirm.remove();
			
			$option.remove();
			self.$field.trigger(EVTPRFX+'change');
		}));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'ol-btn">').text($lang.data('no')).on('click', function() {
			$deleteConfirm.remove();
			
			$option.removeClass(CSSPRFX+'ol-deleting');
		}));
		
		$option.addClass(CSSPRFX+'ol-deleting').append($deleteConfirm);
	};
	
	Field.prototype.openLocale = function(locale) {
		var $activeLocaleInput = this.$field.find('.'+CSSPRFX+'ol-locale > input').filter(':focus');
		this.active_locale = locale;
		this.$field.find('div.'+CSSPRFX+'ol-locale').hide().filter('[data-locale="'+this.active_locale+'"]').show();
		
		if($activeLocaleInput.length > 0) {
			$activeLocaleInput.parent().siblings('[data-locale="'+this.active_locale+'"]').children('input').focus();
		}
		
		this.$field.trigger(EVTPRFX+'change');
	};
	
	Field.prototype.initLocaleNavigation = function() {
		var self = this;
		var $localeHeads = this.$field.find('table thead th .'+CSSPRFX+'ol-locale');
		
		if($localeHeads.length > 1) {
			$localeHeads.each(function(index) {
				var $localeHead = $(this);
				if(index === 0) {
					$localeHead.find('.'+CSSPRFX+'ol-locale-prev').addClass(''+CSSPRFX+'ol-locale-end');
				}
				if(index === $localeHeads.length-1) {
					$localeHead.find('.'+CSSPRFX+'ol-locale-next').addClass(''+CSSPRFX+'ol-locale-end');
				}
			});
			
			$localeHeads.find('.'+CSSPRFX+'ol-locale-prev').not('.'+CSSPRFX+'ol-locale-end').on('click', function() {
				var $localeHead = $(this).closest('.'+CSSPRFX+'ol-locale');
				self.openLocale($localeHead.prev().attr('data-locale'));
			});
			$localeHeads.find('.'+CSSPRFX+'ol-locale-next').not('.'+CSSPRFX+'ol-locale-end').on('click', function() {
				var $localeHead = $(this).closest('.'+CSSPRFX+'ol-locale');
				self.openLocale($localeHead.next().attr('data-locale'));
			});
		}
	};
	
	Field.prototype.getValue = function() {
		var options = [];
		var $table = this.$field.find('table');
		
		var locales = {};
		$table.find('thead th .'+CSSPRFX+'ol-locale').each(function() {
			locales[$(this).attr('data-locale')] = '';
		});
		
		$table.find('tbody tr').each(function() {
			var $this = $(this);
			var locale = Object.assign({}, locales);
			
			$this.find('.'+CSSPRFX+'ol-locale').each(function() {
				var $this = $(this);
				locale[$this.attr('data-locale')] = $this.children('input').val();
			});
			
			var option = {
				default: $this.find('.'+CSSPRFX+'ol-default').prop('checked'),
				name: $this.find('.'+CSSPRFX+'ol-name').val(),
				locale: locale
			};
			
			var old_name = $this.find('.'+CSSPRFX+'ol-old-name').val();
			if(old_name != '__new__' && old_name != option.name) {
				option.old_name = old_name;
			}
			
			options.push(option);
		});
		
		return options;
	};
	
	Field.prototype.validate = function() {
		var self = this;
		
		this.removeErrors();
		
		var $trs = this.$field.find('table tbody tr');
		
		if(this.$field.attr('data-num_opt_min') > 0 && $trs.length < this.$field.attr('data-num_opt_min')) {
			this.setError('error-min-options');
			return false;
		}
		
		if(this.$field.attr('data-num_opt_max') > 0 && $trs.length > this.$field.attr('data-num_opt_max')) {
			this.setError('error-max-options');
			return false;
		}
		
		if(this.$field.attr('data-max_checked_def') > 0 && $trs.find('.'+CSSPRFX+'ol-default').filter(':checked').length > this.$field.attr('data-max_checked_def')) {
			this.setError('error-max-checked-def');
			return false;
		}
		
		var valid = true;
		var uniqueCheck = {};
		$trs.find('.'+CSSPRFX+'ol-name').each(function() {
			var $input = $(this);
			var name = $input.val();
			
			if(name == '' || !name.match(new RegExp($input.attr('pattern')))) {
				self.setError('error-regexp');
				valid = false;
			}
			
			if(typeof(uniqueCheck[name]) !== 'undefined') {
				self.setError('error-duplicate');
				valid = false;
			}
			
			uniqueCheck[name] = true;
		});
		if(!valid) {
			return false;
		}
		
		return true;
	};
	
	Field.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].setError(message_key);
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].removeErrors();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.layouts[this.Reef.config.layout_name]) {
			this.layouts[this.Reef.config.layout_name].addError(message);
		}
	};
	
	return Field;
})());
