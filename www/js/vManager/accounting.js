$(document).ready(function () {
	$('.accountingRecordListing ABBR').each(function () {
		var el = $(this);
			
		if(el.attr('title') != '') {
			el.tipsy({
				gravity: 'n', 
				delayOut: 1000, 
				fade: true,
				offset: 10
			});
		}	
	});
		
});