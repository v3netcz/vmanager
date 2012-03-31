(function ($) {
	$.fn.starLink = function (options) {
		var defaults = {
				notStarredClass: 'star',
				starredClass: 'unstar'
			},
			config = $.extend(defaults, options);
		this.filter('a').each(function () {
			$(this).click(function (e) {
				var $this = $(this);
				$.getJSON($this.attr('href'), function (data, textStatus) {
					$this.toggleClass(config.starredClass);
					$this.toggleClass(config.notStarredClass);
				});
				e.preventDefault();
			});
		});
		return this;
	};
})(jQuery);