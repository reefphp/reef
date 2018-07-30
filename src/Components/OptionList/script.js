Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.auto_inc = 1;
		this.active_locale = null;
	};
	
	Field.componentName = 'reef:option_list';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.openLocale(this.$field.find('div.'+CSSPRFX+'ol-locale').first().attr('data-locale'));
		
		this.$field.find('.'+CSSPRFX+'ol-add').on('click', function() {
			self.newDefaultOption();
		});
		
		if(false) {
			
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
			$option.remove();
			self.$field.trigger(EVTPRFX+'change');
		});
		
		this.auto_inc++;
		
		return $option;
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
			
			options.push({
				default: $this.find('.'+CSSPRFX+'ol-default').prop('checked'),
				name: $this.find('.'+CSSPRFX+'ol-name').val(),
				locale: locale
			});
		});
		
		return options;
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		var $trs = this.$field.find('table tbody tr');
		
		if(this.$field.attr('data-num_opt_min') > 0 && $trs.length < this.$field.attr('data-num_opt_min')) {
			this.setError('error-min-options');
			return false;
		}
		
		if(this.$field.attr('data-max_checked_def') > 0 && $trs.find('.'+CSSPRFX+'ol-default').filter(':checked').length > this.$field.attr('data-max_checked_def')) {
			this.setError('error-max-checked-def');
			return false;
		}
		
		return true;
	};
	
	Field.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').removeClass('is-invalid');
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').addClass('is-invalid');
			this.$field.find('input').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	return Field;
})());
