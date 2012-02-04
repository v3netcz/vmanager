/**
 * Dependent on uniform
 */
(function ($) {
	$.fn.vMultipleFileUpload = function (options) {
		var defaults = {
				linkText: 'Add another',
				containerClass: 'vMultipleFileUpload',
				fileBtnText: 'Choose File',
				fileDefaultText: 'No file selected'
			},
			config = $.extend(defaults, options);
		this.filter('input:file').each(function () {
			var $this = $(this),
				id = $this.attr('id');
			if (!$this.is('.multipleUpload')) {
				$this.uniform({
					fileBtnText: config.fileBtnText,
					fileDefaultText: config.fileDefaultText
				});
				return true; // continue;
			}
			$this.removeAttr('id');
			var masterClone = $this.clone(),
				getLi = function () {
					var el = $('<li>').css({
							display: 'block'
						}).append(masterClone.clone().change(function() {
							var $el = $(this),
								container = $el.closest('ul'),
								li = getLi().hide(),
								empty = 0;
								
							container.find("input:file").each(function () {
								if (!$(this).val()) {
									empty++;
								}
							});	
							if (empty === 0) {
								container.append(li);
								var input = li.find('input:file');
								input.uniform({
									fileBtnText: config.fileBtnText,
									fileDefaultText: config.fileDefaultText
								});
								li.slideDown();
							}
						}));
					return el;
				},
				div = $('<div>').attr('id','vMFU-'+id).addClass(config.containerClass).append(
					$('<ul>').append(
						getLi()
					)
				);
			$this.closest('form').submit(function () {
				$(this).find('input:file').each(function () {
					var k = $(this);
					if (!k.val()) {
						k.remove();
					}
				});
			})
			$this.after(div);
			$this.remove();
			div.find('input:file').uniform({
				fileBtnText: config.fileBtnText,
				fileDefaultText: config.fileDefaultText
			});
		});
		
		return this;
	};
})(jQuery)