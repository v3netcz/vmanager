(function ($) {
	$.fn.starLink = function (options) {
		var defaults = {
				notStarredClass: 'star',
				starredClass: 'unstar',
				errorMessage: 'An error has occured. Please try again.',
				handleError: function (message) {
					alert(message);
				}
			},
			config = $.extend(defaults, options);
		this.filter('a').each(function () {
			var $this = $(this);
			$this.closest('tr').activeTableRow(config);
			$this.click(function (e) {
				var $this = $(this),
					tr = $this.closest('tr'),
					href = $this.attr('href');
				if ($this.data('starLinkLocked') === true) {
					e.preventDefault();
					return;
				}
				$this.data('starLinkLocked', true);
				tr.activeTableRow('lock');
				$.ajax({
					url: href,
					dataType: 'json',
					success: function (data) {
						$this.attr('href', data.newUrl);
						$this.toggleClass(config.starredClass);
						$this.toggleClass(config.notStarredClass);
					},
					error: function () {
						config.handleError(config.errorMessage);
					},
					complete: function () {
						$this.data('starLinkLocked', false);
						tr.activeTableRow('unlock');
					}
				});
				e.preventDefault();
			});
		});
		return this;
	};
})(jQuery);