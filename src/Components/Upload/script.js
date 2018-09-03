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
			if(this.files.length == 0) {
				return;
			}
			
			self.removeErrors();
			
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
		
		var existingFiles = JSON.parse(this.$field.find('.'+CSSPRFX+'upload-files').attr('data-files') || '[]');
		var $file;
		
		for(var i in existingFiles) {
			$file = this.addFile({
				name: existingFiles[i].name,
				size: existingFiles[i].size,
				uuid: existingFiles[i].uuid
			});
			if(existingFiles[i].deleted) {
				self.deleteFile($file);
			}
		}
		
		this.Reef.listenRequired(this, this.$upload, {
			override : function(result) {
				if(self.getValue().length > 0) {
					return false;
				}
				
				return result;
			}
		});
		
		this.$field.on('change '+EVTPRFX+'change', function() {
			self.determineState();
		});
		
		$(function() {
			// Wrap this in $() to make sure the builder does not fail
			self.determineState();
		});
	};
	
	Field.prototype.determineState = function() {
		var numFiles = this.getValue().length;
		this.$upload.prop('required', numFiles == 0 && ReefConditionEvaluator.evaluate(this.Reef, this.$upload.attr('data-required-if')));
		this.$upload.prop('disabled', numFiles >= this.$upload.attr('data-max-files'));
	}
	
	Field.prototype.upload = function(file) {
		var self = this;
		
		if(this.getValue().length >= this.$upload.attr('data-max-files')) {
			this.setError('error-max-files');
			return;
		}
		
		var allowed_types = this.$upload.attr('data-accepted-types').split(',');
		var found = false;
		for(var i in allowed_types) {
			if(file.name.substr(-allowed_types[i].length-1) == '.'+allowed_types[i]) {
				found = true;
				break;
			}
		}
		if(!found) {
			this.setError('error-file-type');
			return;
		}
		
		if(file.size > this.$upload.attr('data-max-size')) {
			this.setError('error-max-size');
			return;
		}
		
		var $file = this.addFile({
			name: file.name,
			size: file.size,
			uuid: ''
		});
		$file.find('.'+CSSPRFX+'upload-file-progress').show();
		
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
				$file.data('xhr', myXhr);
				return myXhr;
			},
			success: function(response) {
				$file.data('xhr', null);
				if(response.success) {
					$file.find('input').val(response.files[0]);
					$file.find('.'+CSSPRFX+'upload-file-progress-percent').text('100%');
				}
				else {
					self.addError(response.error);
				}
			},
			error: function () {
				$file.data('xhr', null);
				self.addError('An unknown error occurred');
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
		$file.find('.'+CSSPRFX+'upload-file-progress-percent').text(Math.min(percent, 99) + '%');
	};
	
	Field.prototype.addFile = function(file_data) {
		var self = this;
		
		var $file = this.$field.find('.'+CSSPRFX+'template.'+CSSPRFX+'upload-file').clone().removeClass(CSSPRFX+'template');
		$file.find('.'+CSSPRFX+'upload-file-name').text(file_data.name);
		$file.find('.'+CSSPRFX+'upload-file-size').text(ReefUtil.bytes_format(file_data.size, this.Reef.config.byte_base));
		$file.find('input').val(file_data.uuid);
		this.$field.find('.'+CSSPRFX+'upload-files').append($file);
		
		$file.find('.'+CSSPRFX+'upload-file-action-delete').on('click', function() {
			self.deleteFile($file, 'toggle');
		});
		
		this.$field.trigger(EVTPRFX+'change');
		return $file;
	};
	
	Field.prototype.deleteFile = function($file, doDelete) {
		if(typeof doDelete === 'undefined') {
			doDelete = true;
		}
		
		var wasDeleted = $file.hasClass(CSSPRFX+'upload-delete');
		if(doDelete === 'toggle') {
			doDelete = !wasDeleted;
		}
		else if(wasDeleted == doDelete) {
			return;
		}
		
		if(doDelete && $file.data('xhr')) {
			$file.data('xhr').abort();
			$file.remove();
			
			this.$field.trigger(EVTPRFX+'change');
			return;
		}
		
		$file.toggleClass(CSSPRFX+'upload-delete', doDelete);
		
		var uuid = $file.find('input').val().substr(-32);
		if(uuid.length != 32) {
			// Can happen if you delete the file before it has been successfully uploaded
			uuid = '';
		}
		$file.find('input').val(doDelete ? 'x'+uuid : uuid);
		
		this.$field.trigger(EVTPRFX+'change');
	};
	
	Field.prototype.getValue = function() {
		var uuids = [];
		this.$field.find('.'+CSSPRFX+'upload-files .'+CSSPRFX+'upload-file input').each(function() {
			var uuid = $(this).val();
			if(uuid.substr(0, 1) !== 'x') {
				uuids.push(uuid);
			}
		});
		return uuids;
	};
	
	Field.prototype.setValue = function(value) {
		alert('Cannot set value on upload');
		throw 'Cannot set value on upload';
	};
	
	Field.prototype.toDefault = function() {
		this.$field.find('.'+CSSPRFX+'upload-files .'+CSSPRFX+'upload-file input').remove();
		this.$field.trigger(EVTPRFX+'change');
	};
	
	Field.prototype.validate = function() {
		this.removeErrors();
		
		if(this.$upload.prop('required')) {
			this.setError('error-required-empty');
			return false;
		}
		
		var uuids = this.getValue();
		if(uuids.length > this.$upload.attr('data-max-files')) {
			this.setError('error-max-files');
			return false;
		}
		
		for(var i in uuids) {
			if(uuids[i] == '') {
				this.setError('error-still-uploading');
				return false;
			}
		}
		
		return true;
	};
	
	Field.prototype.setError = function(message_key) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$upload.addClass('is-invalid');
			this.$field.find('.invalid-feedback').hide().filter('.'+CSSPRFX+message_key).show();
		}
	};
	
	Field.prototype.removeErrors = function() {
		this.$field.removeClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$upload.removeClass('is-invalid');
			this.$field.find('.invalid-feedback').hide();
		}
	};
	
	Field.prototype.addError = function(message) {
		this.$field.addClass(CSSPRFX+'invalid');
		
		if(this.Reef.config.layout_name == 'bootstrap4') {
			this.$upload.addClass('is-invalid');
			this.$upload.parent().append($('<div class="invalid-feedback"></div>').text(message));
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
				return value.length == 0;
			case 'is not empty':
				return value.length > 0;
		};
	};
	
	return Field;
})());
