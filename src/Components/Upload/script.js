Reef.addComponent((function() {
	
	'use strict';
	
	var Field = function(Reef, $field) {
		this.$field = $field;
		this.Reef = Reef;
		
		this.$upload = this.$field.find('input[type="file"]');
	};
	
	Field.componentName = 'reef:upload';
	
	Field.prototype.attach = function() {
		var self = this;
		
		this.$upload.on('change', function(evt) {
			var i;
			var uploads = [];
			
			for(i in this.files) {
				if(this.files[i].size) {
					uploads.push(this.files[i]);
				}
			}
			$(this).val('');
			
			for(i in uploads) {
				self.upload(uploads[i]);
			}
		});
		
		this.$upload.on('click', function(evt) {
			if($(this).closest('.'+CSSPRFX+'builder-workspace').length > 0) {
				evt.preventDefault();
			}
		});
		
		/*var requiredCondition = (this.$upload.attr('data-required-if') || '') + '';
		if(requiredCondition.length > 0) {
			requiredCondition = '(' + requiredCondition + ') and ';
		}
		requiredCondition += this.$field.data(CSSPRFX+'name')+' is not empty ';
		this.$upload.attr('data-required-if', requiredCondition);*/
		
		this.Reef.listenRequired(this, this.$field.find('input'));
		
		var existingFiles = JSON.parse(this.$field.find('.'+CSSPRFX+'upload-files').attr('data-files') || '[]');
		
		for(var i in existingFiles) {
			var $file = this.$field.find('.'+CSSPRFX+'template.'+CSSPRFX+'upload-file').clone().removeClass(CSSPRFX+'template');
			$file.find('.'+CSSPRFX+'upload-file-name').text(existingFiles[i].name);
			$file.find('.'+CSSPRFX+'upload-file-size').text(existingFiles[i].size);
			$file.find('input').val(existingFiles[i].uuid);
			this.$field.find('.'+CSSPRFX+'upload-files').append($file);
		}
	};
	
	Field.prototype.upload = function(file) {
		var self = this;
		
		var $file = this.$field.find('.'+CSSPRFX+'template.'+CSSPRFX+'upload-file').clone().removeClass(CSSPRFX+'template');
		$file.find('.'+CSSPRFX+'upload-file-name').text(file.name);
		$file.find('.'+CSSPRFX+'upload-file-size').text(file.size);
		this.$field.find('.'+CSSPRFX+'upload-files').append($file);
		
		var formData = new FormData();
		formData.append('files', file, file.name);
		
		$.ajax({
			type: 'POST',
			url: this.Reef.internalRequestURL('component:reef:upload:upload'),
			xhr: function () {
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					myXhr.upload.addEventListener('progress', function(evt) { self.upload_progress(evt, $file); }, false);
				}
				return myXhr;
			},
			success: function(response) {
				if(response.success) {
					$file.find('input').val(response.files[0]);
					alert('success');
				}
				else {
					alert(response.error);
				}
			},
			error: function (error) {
				alert('error');
			},
			async: true,
			data: formData,
			dataType: 'JSON',
			cache: false,
			contentType: false,
			processData: false,
			timeout: 60000
		});
	};
	
	Field.prototype.upload_progress = function(evt, $file) {
		var percent = 0;
		var position = evt.loaded || evt.position;
		if (evt.lengthComputable) {
			percent = Math.ceil(position / evt.total * 100);
		}
		
		$file.find('.'+CSSPRFX+'upload-file-progress-bar').css('width', +percent + '%');
		$file.find('.'+CSSPRFX+'upload-file-progress-bar').text(percent + '%');
	};
	
	Field.prototype.getValue = function() {
		return this.$field.find('input').val();
	};
	
	Field.prototype.setValue = function(value) {
		this.$field.find('input').val(value).change();
	};
	
	Field.prototype.toDefault = function() {
		this.setValue(this.$field.find('input').attr('data-default'));
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		var $input = this.$field.find('input');
		
		if($input.prop('required')) {
			if($.trim($input.val()) == '') {
				this.setError('error-required-empty');
				return false;
			}
		}
		
		if($input.attr('maxlength') && $input.attr('maxlength') > 0 && $input.val().length > $input.attr('maxlength')) {
			this.setError('error-value-too-long');
			return false;
		}
		
		if($input.attr('pattern')) {
			if(!$input.val().match(new RegExp($input.attr('pattern')))) {
				this.setError('error-regexp');
				return false;
			}
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
	
	Field.getConditionOperators = function() {
		return [
			'is empty',
			'is not empty'
		];
	};
	
	Field.prototype.getConditionOperandInput = function(operator, layout) {
		var classes = '';
		if(layout == 'bootstrap4') {
			classes += ' form-control';
		}
		
		return null;
	};
	
	Field.prototype.validateConditionOperation = function(operator, operand) {
		if(['is empty', 'is not empty'].indexOf(operator) > -1) {
			if(operand != '') {
				throw 'Empty does not take an operand';
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
		};
	};
	
	return Field;
})());
