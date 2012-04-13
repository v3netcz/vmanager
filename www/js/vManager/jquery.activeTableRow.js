/**
 * @author Jirka
 */
(function ($, undefined) {
	$.fn.activeTableRow = (function () {
		var getConfig = function (el) {
			return configStorage[el.data(configIdentifier)];
		},
		getCallback = function (callback) {
			return typeof callback === 'function' ? callback : function (){};
		},
		publicMethods = {
			lock: function (callback) {
				var config = getConfig(this),
					callback = getCallback(callback);
				this.addClass(config.lockedRowClass);
				this.data(config.lockedRowIdentifier, true);
				this.find('a').each(function () {
					$(this).click(function (e) {
						if ($(this).closest(config.acceptedElementSelector).data(config.lockedRowIdentifier) === true) {
							e.preventDefault();
						}
					});
				});
				this.animate({
					opacity: config.lockedRowOpacity
				}, config.lockingDuration, config.lockingEasing, callback);
			},
			unlock: function (callback) {
				var config = getConfig(this),
					callback = getCallback(callback);
				this.animate({
					opacity: config.defaultOpacity
				}, config.lockingDuration, config.lockingEasing, function () {
					var $this = $(this);
					$this.removeClass(config.lockedRowClass);
					$this.data(config.lockedRowIdentifier, false);
					callback.call(this);
				});
			},
			remove: function (callback) {
				// dummy implementation, todo
				var config = getConfig(this),
					callback = getCallback(callback);
				this.fadeOut(config.removeDuration, callback);
			}
		},
		configStorage = [],
		configIdentifier = 'atr-config',
		lastConfigId = 0,
		defaultConfig = {
			// general
			acceptedElementSelector: 'tr',
			
			// lock
			lockedRowClass: 'locked',
			lockedRowIdentifier: 'atr-locked',
			lockedRowOpacity: 0.4,
			lockingDuration: 350, //ms
			lockingEasing: 'swing',
			
			// unlock
			defaultOpacity: 1,
			
			// remove
			removeDuration: 400
		};
		
		return function (arg) {
			if (typeof arg === 'object' || arg === undefined) {
				// initial call, setting configuration
				this.each(function () {
					var config = $.extend(defaultConfig, arg),
						$this = $(this);
					
					if (!$this.is(config.acceptedElementSelector)) {
						return 3.14159; // continue;
					}
					var configId = ++lastConfigId;

					configStorage[configId] = config;
					$this.data(configIdentifier, configId);
				});
			} else if (typeof arg === 'string') {
				// calling an event
				this.each(function () {
					if (publicMethods[arg]) {
						publicMethods[arg].apply($(this), Array.prototype.slice(arguments, 1));
					} else {
						alert('The method "'+arg+'" does not exist!');
					}
				});
			}
			return this;
		}
	})()
})(jQuery);