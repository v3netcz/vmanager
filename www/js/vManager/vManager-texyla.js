/**
 * All texyla settings at one place
 */
(function ($, undefined) {
	$.fn.setupvManagerTexyla = function (options) {
		var defaults = {
				apiPromptClassSource: [],
				apiPromptMemberSource: [],
				apiPromptMinLength: 3,
				apiPromptInputClass: 'API-text',
				apiPromptMemberClassParamName: 'class',
				
				ticketPromptSource: [],
				ticketPromptMinLength: 4,
				ticketNamePromptInputClass: 'Ticket-text',
				
				attachFilesInputClass: 'AttachFiles-input',
				attachFilesInputName: 'texylaFiles',
				attachFilesHiddenWithTokenName: 'texylaFilesToken',
				attachFilesFinalFilenameSource: '',
				attachFilesErrorHandler: function () {
					alert('An error occured. Please try again.')
				},
				randomToken: ''
			},
			config = $.extend(defaults, options);
			
		
		$.texyla.addButton('preview', function () {
			var $this = $(this.textarea);
			
			$('div.preview-wrapper').animate({
				height: $this.height() + 40 + 'px'
			}, 800);
			this.view("preview");
		});
		$.texyla.addWindow("API", {
			dimensions: [400, 200],
			createContent: function () {
				var inputs = [$('<input type="text" id="apiPromptClass">').addClass(config.apiPromptInputClass).autocomplete({
							source: config.apiPromptClassSource,
							minLength: config.apiPromptMinLength,
							change: function (e, ui) {
								var baseUri = config.apiPromptMemberSource,
									uri = baseUri + (baseUri.indexOf('?') != -1 ? '&' : '?');
								uri += config.apiPromptMemberClassParamName + '=' + encodeURIComponent(ui.item.value);

								$('#apiPromptMember').autocomplete('option', 'source', uri);
							}
						}),
						$('<input type="text" id="apiPromptMember">').addClass(config.apiPromptInputClass).autocomplete({
							source: config.apiPromptMemberSource,
							minLength: config.apiPromptMinLength
						}).focus(function (e) {
							if (!$('#apiPromptClassInput').val()) {
								return false;
							}
						})
					],
					labels = [this.lng.className, this.lng.memberName],
					container = $('<div><table></table></div>');
					
				$.each(inputs, function (key, value) {
					var row = $('<tr>' +
							'<th><label>' + labels[key] + '</label></th>' +
							'<td></td>' +
						'</tr>');
					row.find('td').append(value);
					container.find('table').append(row);
				});
				return container;
			},

			action: function (el) {
				var className = el.find('#apiPromptClass').val(),
					memberName = el.find('#apiPromptMember').val();
				if (className == '') {
					alert(this.lng.noClassName);
					return;
				}
				var linkText;
				memberName = memberName == '' ? (linkText = '') : ('::'+(linkText = memberName));
				var output = '"'+linkText+'":api://'+className+memberName;
				this.texy.replace(output);
			}
		});
			
		$.texyla.addWindow('ticket', {
			dimensions: [320, 180],
			createContent: function () {
				var inputId = 'ticketNamePrompt',
					input = $('<input type="text" id="'+inputId+'">').addClass(config.ticketNamePromptInputClass).autocomplete({
						source: config.ticketPromptSource,
						minLength: config.ticketPromptMinLength,
						select: function (e, ui) {
							var res = /^#(\d+)\s(.+)$/.exec(ui.item.value),
								$this = $(this);
							$this.data('ticketId', res[1]);
							$this.data('ticketName', res[2]);
						}
					}),
					container = $('<div></div>');
				container.append($('<label for="'+inputId+'">').html(this.lng.ticketName))
						 .append(input);
				return container;
			},
			action: function (el) {
				var input = el.find('.'+config.ticketNamePromptInputClass),
					id = input.data('ticketId'),
					name = input.data('ticketName');
				this.texy.replace('"'+name+'":#'+id);
			}
		});
		
		// helper functions
		var getFilename = function (val) {
				return /(?:[\\\/]*)([^\\/]+)$/g.exec(val)[1];
			},
			getExtension = function (filename) {
				return /\.([a-zA-Z0-9]+)$/.exec(filename)[1];
			},
			transferInput = function (input) {
				if (input.val()) {
					input.removeAttr('id');
					input.prependTo(this.textarea.closest('form')).hide();
				}
			},
			inArray = function (needle, haystackArray) { // case insensitive!!!
				for (var i=0; i<haystackArray.length; i++) {
					if (needle.toLowerCase() == haystackArray[i].toLowerCase()) return true;
				}
				return false;
			};
		
		$.texyla.addWindow('attachFile', {
			dimensions: [350, 270],
			createContent: function () {
				arguments.callee.fileNumber || (arguments.callee.fileNumber = 0);
				var _this = this,
					fileNumber = ++arguments.callee.fileNumber,
					form = $('<form action="" method="post" enctype="multipart/form-data"></form>'),
					// dummy form: otherwise uniform wouldn't work. And IE wouldn't probably like it either.
					newInput = $('<input type="file" id="texylaAttachFileInput">')
							.addClass(config.attachFilesInputClass)
							.data('fileNumber', fileNumber)
							.data('changed', false)
							.change(function () {
								var $this = $(this),
									imageExtensions = ['jpg','jpeg','png','gif','ico','bmp'];
								if (inArray(getExtension(getFilename($this.val())), imageExtensions)) {
									// image
									addTableRow(_this.lng.imageWidth, $('<input>').hide().attr('id', 'texylaAttachFileImgWidth').slideDown());
									addTableRow(_this.lng.imageHeight, $('<input>').hide().attr('id', 'texylaAttachFileImgHeight').slideDown());
								}
							})
							.attr({
								name: config.attachFilesInputName + '['+fileNumber+']'
							}),
					inputs = [newInput, $('<input type="text" id="texylaAttachFileDescription">')],
					labels = [this.lng.attachFileUploadLabel, this.lng.attachFileDescriptionLabel],
					container = $('<div></div>'),
					table = $('<table></table>'),
					addTableRow = function (label, input) {
						var row = $('<tr>' +
								'<th><label for="'+input.attr('id')+'">' + label + '</label></th>' +
								'<td></td>' +
							'</tr>');
						row.find('td').append(input);
						table.append(row);
					};
				container.append(form.append(table));
				
				$.each(inputs, function (key, value) {
					addTableRow(labels[key], value);
				});
				container.find('input:file').uniform({
					fileBtnText: config.fileBtnText,
					fileDefaultText: config.fileDefaultText
				});
				return container;
				
			},
			action: function (el) {
				var files = el.find('input:file'),
					description = el.find('#texylaAttachFileDescription').val(),
					_this = this,
					widthInput = el.find('#texylaAttachFileImgWidth'),
					heightInput = el.find('#texylaAttachFileImgHeight'),
					width = widthInput.length === 1 ? (widthInput.val() ? parseInt(widthInput.val(), 10) : '?') : '',
					height = heightInput.length === 1 ? (heightInput.val() ? parseInt(heightInput.val(), 10) : '?') : '';
				if (width === NaN) width = '';
				if (height === NaN) height = '';
				var dimensions = ((height === '?' && width === '?') || (height === '' && width === '')) ? '' : ''+width+'x'+height;
				
				if (!arguments.callee.inputCreated) {
					_this.textarea.closest('form').find('input[type=hidden]').each(function () {
						var $this = $(this);
						if ($this.attr('name') === config.attachFilesHiddenWithTokenName) {
							$this.val(config.randomToken);
						}
					});
					arguments.callee.inputCreated = true;
				}
					
				files.each(function (){ // because we will be handling more of them in future
					var $this = $(this);
					// spinner?
					// spinner start
					
					if (!$this.val()) {
						return true; // continue;
					}
					
					//_this.textarea.attr('readonly', true);
					//_this.textarea.attr('disabled', true);
					$.ajax(config.attachFilesFinalFilenameSource, {
						type: 'GET',
						dataType: 'json',
						data: {
							fileNumber: $this.data('fileNumber'),
							token: config.randomToken,
							filename: getFilename($this.val())
						},
						success: function (data, textStatus, jqXHR) {
							description = description ? ('.('+description+') '): '';
							_this.texy.replace('[* '+data.finalFilename+' '+dimensions+' '+description+'*]');
							// spinner end
						},
						error: function (jqXHR, textStatus, errorThrown) {
							config.attachFilesErrorHandler.call(this);
						},
						complete: function () {
							//_this.textarea.attr('readonly', false);
							//_this.textarea.attr('disabled', false);
						}
					});
					transferInput.call(_this, $(this));
				});
				
			}
		});
		
		this.each(function () {
			var $this = $(this);
			$this.texyla();
			$this.addClass('expand');
			$this.resizable('destroy');
			$this.autogrow();
		});
	};
})(jQuery);