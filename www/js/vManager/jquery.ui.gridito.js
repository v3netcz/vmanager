(function ($, undefined) {

$.widget("ui.gridito_custom", $.extend({}, $.ui.gridito.prototype, {     

	 _create: function() {
		$.ui.gridito.prototype._create.apply(this, arguments);
		
		this.table = this.element.find("table.gridito-table");
		this.table.find("tbody tr:even").addClass('even');
		this.table.find("tbody tr:odd").addClass('odd');
		
		this.table.find("th").removeClass("ui-widget-header");
		
		this.table.find("thead .globalHeader").addClass("ui-widget-header");		
				
		this.table.find("tfoot td").addClass("ui-widget-header");		
		
		/*this.element.find("a.gridito-button").each(function () {
			var el = $(this);
			el.addClass("ui-button");
		}); */ 
	}

}));

})(jQuery);