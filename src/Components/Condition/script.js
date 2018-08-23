Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.auto_inc = 1;
		this.builder = this.Reef.getBuilder();
		this.$lang = this.$field.find('.'+CSSPRFX+'cond-lang');
	};
	
	Field.componentName = 'reef:condition';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('tr.'+CSSPRFX+'cond-add-or div.'+CSSPRFX+'cond-add-or-button').on('click', function() {
			self.addAnd(self.addOr().find('tr.'+CSSPRFX+'cond-add-and'));
		});
		
		$(function() {
			var $condition = self.$field.find('.'+CSSPRFX+'cond-condition');
			var condition = $condition.val().trim();
			
			var condType;
			if(['true', 'yes', '1'].indexOf(condition) > -1) {
				condType = 'true';
			}
			else if(['false', 'no', '0'].indexOf(condition) > -1) {
				condType = 'false';
			}
			else {
				condType = 'condition';
			}
			
			var $condType = self.$field.find('select.'+CSSPRFX+'cond-type');
			
			$condType.on('change', function(evt) {
				self.changeCondType(evt);
			});
			
			$condType.val(condType).change();
			
			self.builder.$builderWrapper.on(EVTPRFX+'name_change', function(evt, old_name, new_name) {
				if(!$.contains(document.documentElement, self.$field[0])) {
					self.builder.$builderWrapper.off(EVTPRFX+'name_change', this);
					return;
				}
				
				var condition = self.getValue();
				var new_condition = ReefConditionEvaluator.conditionRename(self.builder.reef, condition, old_name, new_name);
				if(condition != new_condition) {
					setTimeout(function() {
						var prevCondType = $condType.val();
						if(prevCondType == 'condition') {
							$condType.val('manual').change();
						}
						$condition.val(new_condition);
						$condition.change();
						if(prevCondType == 'condition') {
							$condType.val('condition').change();
						}
					}, 0);
				}
			});
			
			self.builder.$builderWrapper.on(EVTPRFX+'delete_field_before', function(evt, rbfield, state) {
				if(!$.contains(document.documentElement, self.$field[0])) {
					self.builder.$builderWrapper.off(EVTPRFX+'delete_field_before', this);
					return;
				}
				
				if(state.prevent) {
					return;
				}
				
				var conditionArray = ReefConditionEvaluator.conditionToArray(self.builder.reef, self.getValue());
				var found = false;
				var i_or, i_and, operation;
				
				for(i_or in conditionArray) {
					for(i_and in conditionArray[i_or]) {
						operation = conditionArray[i_or][i_and];
						if(operation[0] == rbfield.current_name) {
							found = true;
							break;
						}
					}
					if(found) {
						break;
					}
				}
				
				if(found) {
					alert(self.$lang.data('field_delete_in_condition'));
					state.prevent = true;
				}
			});
			
			$condition.on('change', function() {
				try {
					self.manualToUI();
				}
				catch(e) {
					// Probably invalid manual input
				}
			});
			
			self.Reef.$wrapper.on(EVTPRFX+'builder-open', function() {
				if($condType.data('current_type') == 'condition') {
					try {
						self.getValue();
						self.manualToUI();
					}
					catch(e) {
						// Probably invalid manual input
					}
				}
			});
		});
	};
	
	Field.prototype.changeCondType = function(evt) {
		var $condition = this.$field.find('.'+CSSPRFX+'cond-condition');
		var $condType = this.$field.find('select.'+CSSPRFX+'cond-type');
		
		// Parse current type
		if(typeof $condType.data('current_type') !== 'undefined') {
			if(['true', 'false'].indexOf($condType.val()) > -1 && ['manual', 'condition'].indexOf($condType.data('current_type')) > -1) {
				if(!confirm(this.$lang.data('boolean_lose_condition_confirm'))) {
					evt.preventDefault();
					return;
				}
			}
			
			this.getValue();
		}
		
		var condType = $condType.val();
		
		this.$field.find('.'+CSSPRFX+'cond-type-section').hide();
		this.$field.find('.'+CSSPRFX+'cond-type-'+condType).show();
		
		if(condType == 'condition') {
			try {
				this.manualToUI();
			}
			catch(e) {
				evt.preventDefault();
			}
		}
		
		$condType.data('current_type', condType);
		
	};
	
	Field.prototype.clearOrs = function() {
		 this.$field.find('table tbody.'+CSSPRFX+'cond-or-section').remove();
	};
	
	Field.prototype.manualToUI = function() {
		this.clearOrs();
		
		var $condition = this.$field.find('.'+CSSPRFX+'cond-condition');
		var $condType = this.$field.find('select.'+CSSPRFX+'cond-type');
		
		var condition = $condition.val().trim();
		var conditionArray = null;
		
		if(['', 'true', 'yes', '1', 'false', 'no', '0'].indexOf(condition) > -1) {
			conditionArray = [];
		}
		else {
			try {
				conditionArray = ReefConditionEvaluator.conditionToArray(this.builder.reef, condition);
			}
			catch(e) {
				$condType.val('manual').change();
				throw e;
			}
		}
		
		if(conditionArray.length == 0) {
			this.addAnd(this.addOr().find('tr.'+CSSPRFX+'cond-add-and'));
		}
		else {
			var i_or, i_and, operation, $addAndAnchor, $tr;
			
			for(i_or in conditionArray) {
				$addAndAnchor = this.addOr().find('tr.'+CSSPRFX+'cond-add-and');
				
				for(i_and in conditionArray[i_or]) {
					operation = conditionArray[i_or][i_and];
					$tr = this.addAnd($addAndAnchor);
					
					var $fieldName = $tr.find('.'+CSSPRFX+'cond-fieldname select');
					$fieldName.val(operation[0]).change();
					
					var $operator = $tr.find('.'+CSSPRFX+'cond-operator select');
					$operator.val(operation[1]).change();
					
					var $operand = $tr.find('.'+CSSPRFX+'cond-operand');
					var $operandInput = $operand.data('operandInput');
					if(typeof $operandInput !== 'undefined') {
						$operandInput.val(operation[2]).change();
					}
				}
			}
		}
	};
	
	Field.prototype.addOr = function() {
		var self = this;
		
		var $tbody = this.$field.find('table tbody.'+CSSPRFX+'template').clone().removeClass(CSSPRFX+'template').addClass(CSSPRFX+'cond-or-section');
		
		this.$field.find('tr.'+CSSPRFX+'cond-add-or').parent().before($tbody);
		
		$tbody.find('tr.'+CSSPRFX+'cond-add-and div.'+CSSPRFX+'cond-add-and-button').on('click', function() {
			self.addAnd($(this).closest('tr.'+CSSPRFX+'cond-add-and'));
		});
		
		return $tbody;
	};
	
	Field.prototype.addAnd = function($anchorTr) {
		var self = this;
		
		var $tr = this.$field.find('table thead tr.'+CSSPRFX+'template').clone().removeClass(CSSPRFX+'template');
		
		$anchorTr.before($tr);
		
		$tr.find('.'+CSSPRFX+'cond-fieldname select, .'+CSSPRFX+'cond-operator select').on('change', function() {
			self.$field.trigger(EVTPRFX+'change');
		});
		
		$tr.find('.'+CSSPRFX+'cond-remove-operation').on('click', function() {
			self.removeOperation($tr);
		});
		
		var $fieldName = $tr.find('.'+CSSPRFX+'cond-fieldname select').html($('<option value=""></option>').text(this.$lang.data('choose_field')));
		
		var name, rbfield;
		var fields = this.builder.getConditionFieldsByName();
		for(name in fields) {
			rbfield = fields[name];
			if(typeof rbfield.field.constructor.getConditionOperators == 'undefined' || rbfield.field.constructor.getConditionOperators().length == 0) {
				continue;
			}
			$fieldName.append($('<option>').val(name).text(name));
		}
		
		var $operator = $tr.find('.'+CSSPRFX+'cond-operator select').html('');
		
		$fieldName.on('change', function() {
			$operator.html('');
			$operand.html('').removeData(['operandInput']);
			
			if($fieldName.val() == '') {
				return;
			}
			
			rbfield = fields[$fieldName.val()];
			
			if(typeof rbfield === 'undefined') {
				return;
			}
			
			var operators = rbfield.field.constructor.getConditionOperators();
			
			var operatorLangs = self.builder.$builderWrapper.find('.'+CSSPRFX+'builder-component[data-component-name="'+rbfield.field.constructor.componentName+'"]').attr('data-operators');
			operatorLangs = (typeof operatorLangs != 'undefined' && operatorLangs != '') ? JSON.parse(atob(operatorLangs)) : {};
			
			for(var i in operators) {
				var operatorLang = (typeof operatorLangs[operators[i]] != 'undefined') ? operatorLangs[operators[i]] : operators[i];
				$operator.append($('<option>').val(operators[i]).text(operatorLang));
			}
			
			$operator.trigger('change');
		});
		
		var $operand = $tr.find('.'+CSSPRFX+'cond-operand');
		
		$operator.on('change', function() {
			rbfield = fields[$fieldName.val()];
			var operators = rbfield.field.constructor.getConditionOperators();
			var operator = $operator.val();
			
			var $operandInput = rbfield.field.getConditionOperandInput(operator, self.Reef.config.layout_name);
			
			if(typeof $operand.data('operandInput') != 'undefined'
				&& $operand.data('operandInput') != null
				&& $operandInput != null
				&& $operand.data('operandInput').prop('outerHTML') == $operandInput.prop('outerHTML')) {
				return;
			}
			
			$operand.html($operandInput);
			
			$operand.data('operandInput', $operandInput);
		});
		
		return $tr;
	};
	
	Field.prototype.removeOperation = function($tr) {
		var self = this;
		
		var $deleteConfirm = $('<td class="'+CSSPRFX+'cond-delete-confirm" colspan="4">');
		var $deleteConfirmDiv = $('<div class="'+CSSPRFX+'cond-delete-confirm-div">').appendTo($deleteConfirm);
		$deleteConfirmDiv.append($('<div>').text(this.$lang.data('condition_delete_confirm')));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'cond-btn">').text(this.$lang.data('yes')).on('click', function() {
			$deleteConfirm.remove();
			
			var $tbody = $tr.parent();
			$tr.remove();
			
			if($tbody.children('.'+CSSPRFX+'cond-operation').length == 0) {
				$tbody.remove();
			}
			
			self.$field.trigger(EVTPRFX+'change');
		}));
		
		$deleteConfirmDiv.append($('<div class="'+CSSPRFX+'cond-btn">').text(this.$lang.data('no')).on('click', function() {
			$deleteConfirm.remove();
			
			$tr.removeClass(CSSPRFX+'cond-deleting');
		}));
		
		$tr.addClass(CSSPRFX+'cond-deleting').append($deleteConfirm);
	};
	
	Field.prototype.getValue = function() {
		var $condition = this.$field.find('.'+CSSPRFX+'cond-condition');
		var condType = this.$field.find('select.'+CSSPRFX+'cond-type').data('current_type');
		
		if(condType == 'condition') {
			$condition.val(this.getUIValue());
		}
		else if(condType == 'true') {
			$condition.val('true');
		}
		else if(condType == 'false') {
			$condition.val('false');
		}
		
		return $condition.val();
	};
	
	Field.prototype.getUIValue = function() {
		var condition = '', subcondition;
		var $table = this.$field.find('table');
		
		var first_or, first_and;
		first_or = true;
		
		$table.find('tbody.'+CSSPRFX+'cond-or-section').each(function() {
			var $tbody = $(this);
			
			first_and = true;
			subcondition = '';
			$tbody.children('tr.'+CSSPRFX+'cond-operation').each(function() {
				var $tr = $(this);
				
				if($tr.find('td.'+CSSPRFX+'cond-fieldname select').val() == '') {
					return;
				}
				
				if(!first_and) {
					subcondition += ' and ';
				}
				else {
					first_and = false;
				}
				
				var $operandInput = $tr.find('td.'+CSSPRFX+'cond-operand').data('operandInput');
				
				subcondition += $tr.find('td.'+CSSPRFX+'cond-fieldname select option:selected').val();
				subcondition += ' ' + $tr.find('td.'+CSSPRFX+'cond-operator select option:selected').val();
				if(typeof $operandInput !== 'undefined' && $operandInput != null) {
					subcondition += ' ' + JSON.stringify($operandInput.val());
				}
			});
			
			if(subcondition == '') {
				return;
			}
			
			if(!first_or) {
				condition += ' or ';
			}
			else {
				first_or = false;
			}
			
			condition += subcondition;
		});
		
		return condition;
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		try {
			ReefConditionEvaluator.evaluate(this.builder.reef, this.getValue());
		}
		catch(e) {
			this.setError(e);
			return false;
		}
		
		return true;
	};
	
	Field.prototype.setError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		this.removeErrors();
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('.'+CSSPRFX+'cond-feedback').text(message).show();
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$field.find('input').parent().append($('<div class="invalid-feedback"></div>').text(message));
		}
	};
	
	return Field;
})());
