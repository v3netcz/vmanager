/**
 * All texyla settings at one place
 */
(function ($, undefined) {
	$.fn.setupvManagerTexyla = function (options) {
		var defaults = {
				apiPromptClassSource: [],
				apiPromptMethodSource: [],
				apiPromptMinLength: 4,
				apiPromptInputClass: 'API-text',
				
				ticketPromptSource: [],
				ticketPromptMinLength: 4,
				ticketNamePromptInputClass: 'Ticket-text'
			},
			config = $.extend(defaults, options);
			
		
		$.texyla.addButton('preview', function () {
			var $this = $(this.textarea);
			$('div.preview-wrapper').animate({
				height: $this.height() + 25 + 'px'
			}, 800);
			this.view("preview");
		});
		$.texyla.addWindow("API", {
			dimensions: [400, 200],
			createContent: function () {
				var inputs = [$('<input type="text" id="apiPromptClass">').addClass(config.apiPromptInputClass).autocomplete({
							source: config.apiPromptClassSource,
							minLength: config.apiPromptMinLength
						}),
						$('<input type="text" id="apiPromptMethod">').addClass(config.apiPromptInputClass)/*.autocomplete({
							source: config.apiPromptMethodSource,
							minLength: config.apiPromptMinLength
						}).focus(function (e) {
							if (!$('#apiPromptClassInput').val()) {
								return false;
							}
						})*/
					],
					labels = [this.lng.className, this.lng.methodName],
					container = $('<div><table></table></div>');
					
				$.each(inputs, function (key, value) {
					var row = $('<tr>' +
							'<th><label>' + labels[key] + '</label></th>' +
							'<td></td>' +
						'</tr>')
					row.find('td').append(value)
					container.find('table').append(row)
				});
				return container;
			},

			action: function (el) {
				var className = el.find('#apiPromptClass').val(),
					methodName = el.find('#apiPromptMethod').val();
				if (className == '') {
					alert(this.lng.noClassName);
					return;
				}
				methodName = methodName == '' ? '' : ('::'+methodName);
				var linkText = methodName;
				var output = '"'+linkText+'":api://'+className+methodName;
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
		
		this.each(function () {
			var $this = $(this);
			$this.texyla();
			$this.addClass('expand');
			$this.resizable('destroy');
			$this.autogrow();
		});
	};
})(jQuery);