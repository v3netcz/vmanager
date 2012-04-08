$(document).ready(function () {
	// gridito init
	$("div.gridito").livequery(function () {
		$(this).gridito_custom();
	});

	// nette ajax init
	$("a.ajax").live("click", function (event) {
		event.preventDefault();
		$.get(this.href);
	});
	
	//$("select, input:checkbox, input:radio, input:file").uniform();
	
	$("pre.php").snippet("php",{style:"ide-eclipse", clipboard:"/snippet-shjs/ZeroClipboard.swf",showNum:false});	
	$("pre.js").snippet("javascript",{style:"ide-eclipse", clipboard:"/snippet-shjs/ZeroClipboard.swf",showNum:false});	
	$("pre.css").snippet("css",{style:"ide-eclipse", clipboard:"/snippet-shjs/ZeroClipboard.swf",showNum:false});	
	$("pre.html").snippet("html",{style:"ide-eclipse", clipboard:"/snippet-shjs/ZeroClipboard.swf",showNum:false});	
	$("pre.sql").snippet("sql",{style:"ide-eclipse", clipboard:"/snippet-shjs/ZeroClipboard.swf",showNum:false});	
	
	$("input").each(function () {
		var el = $(this);
		
		if(el.attr('title') != '') {
			el.tipsy({
				gravity: 'w', 
				delayOut: 1000, 
				fade: true,
				offset: 10
			});
		}
		
		if(el.attr('type') == 'text' && el.attr('autocomplete-src') != undefined) {
			var cache = {}, lastXhr;
			
			el.autocomplete({
				source: function( request, response ) {
					var term = request.term;
					if ( term in cache ) {
						response( cache[ term ] );
						return;
					}

					lastXhr = $.getJSON( el.attr('autocomplete-src'), request, function( data, status, xhr ) {
						// Nemame radi asociativni pole
						parsedData = [];
						for(var item in data) {
								parsedData[parsedData.length] = data[item];
						}
						
						cache[ term ] = parsedData;
						if ( xhr === lastXhr ) {
							response( parsedData );
						}
					});
				}
				
			}); 
		}
	});
	
	$("input.date").each(function () { // input[type=date] does not work in IE
		var el = $(this);
		var value = el.val();
		var date = (value ? $.datepicker.parseDate($.datepicker.W3C, value) : null);

		var minDate = el.attr("min") || null;
		if (minDate) minDate = $.datepicker.parseDate($.datepicker.W3C, minDate);
		var maxDate = el.attr("max") || null;
		if (maxDate) maxDate = $.datepicker.parseDate($.datepicker.W3C, maxDate);

		// input.attr("type", "text") throws exception
		if (el.attr("type") == "date") {
			var tmp = $("<input/>");
			$.each("class,disabled,id,maxlength,name,readonly,required,size,style,tabindex,title,value".split(","), function(i, attr)  {
				tmp.attr(attr, el.attr(attr));
			});
			el.replaceWith(tmp);
			el = tmp;
		}
		el.datepicker({
			minDate: minDate,
			maxDate: maxDate
		});
		el.val($.datepicker.formatDate(el.datepicker("option", "dateFormat"), date));
	});
	
	// Tlacitka dalsi a zpet do tabovanych bloku	
	$('.tabs .btnTabNext').each(function () {
		var tabs = $(this).parents('.tabs');
						
		$(this).click(function (e) {
			if(tabs.tabs('option', 'selected') != tabs.tabs('length') - 1 && !$(this).hasClass('btnTabDirectSubmit')) {
				tabs.tabs('select', tabs.tabs('option', 'selected') + 1);
				
				e.preventDefault();
				return false;
			}
		});
	});
		
	$('.tabs .btnTabPrev').each(function () {
		var tabs = $(this).parents('.tabs');
		
		if(tabs.tabs('option', 'selected') == 0)
			$(this).hide();
			
		$(this).click(function (e) {
			tabs.tabs('select', tabs.tabs('option', 'selected') - 1);
						
			e.preventDefault();
			return false;
		});
	});
		
	
	$('.tabs').each(function () {
		var tabs = $(this);
		tabs.bind('tabsselect', function(event, ui) {
			
			if(ui.index != 0) {
				$('.tabs .btnTabPrev:hidden').each(function () {
					$(this).show('slide');
				});
			} else {
				$('.tabs .btnTabPrev:visible').each(function () {
					$(this).hide('slide');
				});
			}
			
			/*if(ui.index == tabs.tabs('length') - 1) {
				$('.tabs .btnTabNext').each(function () {
					$(this).hide('slide', {direction: 'right'});
				});
			} else {
				$('.tabs .btnTabNext').each(function () {
					$(this).show('slide', {direction: 'right'});
				});
			} */
			
			if(ui.index == tabs.tabs('length') - 1) {
				if($('.tabs .btnTabNext SPAN.submit').size()) {
					$('.tabs .btnTabNext SPAN').each(function () {
						$(this).hide();
					});
					
					$('.tabs .btnTabNext SPAN.submit').show();
				}
				
				$('.tabs .btnTabFinish').each(function () {
					$(this).hide('slide', {direction: 'right'});
				});
				
			} else {
				if($('.tabs .btnTabNext SPAN.submit').size()) {
					$('.tabs .btnTabNext SPAN').each(function () {
						$(this).show();
					});
					
					$('.tabs .btnTabNext SPAN.submit').hide();
				}
				
				$('.tabs .btnTabFinish:hidden').each(function () {
						$(this).show('slide');
				});
			}
			
		});
	});
	
	$('textarea.texyla').each(function () {
		var $this = $(this);
		$this.texyla();
		$this.addClass('expand');
		$this.resizable('destroy');
		$this.autogrow();
	});
	
	$('a.starLink').starLink({
		errorMessage: 'Vyskytla se chyba. Zkuste to prosím znovu'
	});
});