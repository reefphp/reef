Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.auto_inc = 1;
	};
	
	Field.componentName = 'reef:condition';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('tr.'+CSSPRFX+'cond-add-or').on('click', function() {
			self.addOr($(this));
		});
		
		var options = this.$field.find('table tbody').data('options');
		options = (typeof options !== 'undefined' && options != '') ? JSON.parse(atob(options)) : {};
		if(options.length > 0) {
			var option;
			for(var i in options) {
				option = options[i];
				
				var $option = this.newOption();
				$option.find('.'+CSSPRFX+'cond-default').prop('checked', option.default);
				$option.find('.'+CSSPRFX+'cond-name').val(option.name);
				$option.find('.'+CSSPRFX+'cond-old-name').val(option.name);
				
				for(var l in option.locale) {
					$option.find('div.'+CSSPRFX+'cond-locale[data-locale="'+l+'"] input').val(option.locale[l]);
				}
			}
		}
		else {
			for(var i=0; i<this.$field.attr('data-num_opt_def'); i++) {
				this.newDefaultOption();
			}
		}
		
		this.initLocaleNavigation();
	};
	
	Field.prototype.addOr = function($anchorTr) {
		var self = this;
		
		var $tbody = this.$field.find('table tbody.'+CSSPRFX+'template').clone().removeClass(CSSPRFX+'template');
		
		$anchorTr.parent().prepend($tbody);
		
		$tbody.find('tr.'+CSSPRFX+'cond-add-and').on('click', function() {
			self.addAnd($(this));
		});
		
		return $tbody;
	};
	
	Field.prototype.addAnd = function($anchorTr) {
		var self = this;
		
		var $tr = this.$field.find('table thead tr.'+CSSPRFX+'template').clone().removeClass(CSSPRFX+'template').addClass(CSSPRFX+'cond-and-section');
		
		$anchorTr.prepend($tr);
		
		$tr.find('.'+CSSPRFX+'cond-fieldname select, .'+CSSPRFX+'cond-operator select, .'+CSSPRFX+'cond-operand :input').on('change', function() {
			self.$field.trigger(EVTPRFX+'change');
		});
		
		$tr.find('.'+CSSPRFX+'cond-remove-operation').on('click', function() {
			self.removeOperation($tr);
		});
		
		return $tbody;
	};
	
	Field.prototype.removeOperation = function($tr) {
		var self = this;
		
		var $lang = this.$field.find('.'+CSSPRFX+'cond-lang');
		
		var $deleteConfirm = $('<td class="'+CSSPRFX+'cond-delete-confirm" colspan="5">');
		var $deleteConfirmDiv = $('<div class="'+CSSPRFX+'cond-delete-confirm-div">').appendTo($deleteConfirm);
		$deleteConfirmDiv.append($('<div>').text($lang.data('delete_option_confirm')));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'cond-btn">').text($lang.data('yes')).on('click', function() {
			$deleteConfirm.remove();
			
			$tbody = $tr.parent();
			$tr.remove();
			
			if($tbody.children('.'+CSSPRFX+'cond-fieldname').length == 0) {
				$tbody.remove();
			}
			
			self.$field.trigger(EVTPRFX+'change');
		}));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'cond-btn">').text($lang.data('no')).on('click', function() {
			$deleteConfirm.remove();
			
			$tr.removeClass(CSSPRFX+'cond-deleting');
		}));
		
		$tr.addClass(CSSPRFX+'cond-deleting').append($deleteConfirm);
	};
	
	Field.prototype.getValue = function() {
		var condition = '';
		var $table = this.$field.find('table');
		
		var first_or, first_and;
		first_or = true;
		
		$table.find('tbody.'+CSSPRFX+'cond-or-section').each(function() {
			var $tbody = $(this);
			
			if(!first_or) {
				condition += ' or ';
			}
			else {
				first_or = false;
			}
			
			first_and = true;
			$tbody.children('tr.'+CSSPRFX+'cond-operation').each(function() {
				var $tr = $(this);
				
				if(!first_and) {
					condition += ' and ';
				}
				else {
					first_and = false;
				}
				
				condition += ' ' + $tr.find('td.'+CSSPRFX+'cond-fieldname select').val();
				condition += ' ' + $tr.find('td.'+CSSPRFX+'cond-operator select').val();
				condition += ' ' + $tr.find('td.'+CSSPRFX+'cond-operand :input').val();
			});
			
		});
		
		return condition;
	};
	
	Field.prototype.validate = function() {
		var self = this;
		
		this.removeErrors();
		
		var $trs = this.$field.find('table tbody tr');
		
		if(this.$field.attr('data-num_opt_min') > 0 && $trs.length < this.$field.attr('data-num_opt_min')) {
			this.setError('error-min-options');
			return false;
		}
		
		if(this.$field.attr('data-max_checked_def') > 0 && $trs.find('.'+CSSPRFX+'cond-default').filter(':checked').length > this.$field.attr('data-max_checked_def')) {
			this.setError('error-max-checked-def');
			return false;
		}
		
		var valid = true;
		var uniqueCheck = {};
		$trs.find('.'+CSSPRFX+'cond-name').each(function() {
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
