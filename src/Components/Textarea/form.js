Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		this.layouts = {};
		for(var layoutName in Field.layoutPrototypes) {
			this.layouts[layoutName] = new Field.layoutPrototypes[layoutName](this);
		}
	};
	
	Field.componentName = 'reef:textarea';
	
	Field.layoutPrototypes = {};
	Field.addLayout = function(layout) {
		Field.layoutPrototypes[layout.layoutName] = layout;
	};
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$field.find('textarea').on('change blur keyup', function(evt) {
			
			// Only perform validation on-key-up if there were errors previously
			if(evt.type == 'keyup' && !self.$field.hasClass(CSSPRFX+'invalid')) {
				return;
			}
			
			self.validate();
		});
		
		this.Reef.listenRequired(this, this.$field.find('textarea'));
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('textarea').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('textarea').val(value).change();
	};
	
	Field.prototype.toDefault = function() {
		this.setValue(this.$field.find('input').attr('data-default'));
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		var $textarea = this.$field.find('textarea');
		
		if($textarea.prop('required')) {
			if($.trim($textarea.val()) == '') {
				this.setError('error-required-empty');
				return false;
			}
		}
		
		if($textarea.attr('maxlength') && $textarea.attr('maxlength') > 0 && $textarea.val().length > $textarea.attr('maxlength')) {
			this.setError('error-value-too-long');
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
	
	Field.getConditionOperators = function() {
		return [
			'is empty',
			'is not empty',
			'is longer than',
			'is shorter than'
		];
	};
	
	Field.prototype.getConditionOperandInput = function(operator, layout) {
		
		if(['is longer than', 'is shorter than'].indexOf(operator) > -1) {
			var $input = $('<input type="number" min="0" step="1" />');
			
			if(this.layouts[layout]) {
				this.layouts[layout].styleConditionOperandInput($input);
			}
			
			return $input;
		}
		
		return null;
	};
	
	Field.prototype.validateConditionOperation = function(operator, operand) {
		if(['is empty', 'is not empty'].indexOf(operator) > -1) {
			if(operand != '') {
				throw 'Empty does not take an operand';
			}
		}
		
		if(['is longer than', 'is shorter than'].indexOf(operator) > -1) {
			if(!$.isNumeric(operand)) {
				throw 'Operand to longer/shorter should be numeric';
			}
		}
	};
	
	Field.prototype.evaluateConditionOperation = function(operator, operand) {
		var value = this.getValue();
		
		switch(operator) {
			case 'is empty':
				return $.trim(value) === '';
			case 'is not empty':
				return $.trim(value) !== '';
			case 'is longer than':
				return value.length > +operand;
			case 'is shorter than':
				return value.length < +operand;
		};
	};
	
	return Field;
})());
