/*
 * Adminica UI
 *
 * Copyright (c) 2010 Tricycle Interactive
 *
 * http://www.tricycle.ie
 *
 * This file configures all the different jQuery scripts used in the Adminica Admin template. Links to the scripts can be found at the beginning of each function
 *
 */
 
 
 $(function() {

//jQuery UI elements (more info can be found at http://jqueryui.com/demos/)
	
	// Sidenav Accordion Config
		$( "ul#accordion" ).accordion({
			collapsible: true,
			active:false,
			header: 'li a.top_level',
			autoHeight:false,
			icons:false
		}); 

	// Top Nav Dropdown Accordion Config				
		$( "ul.dropdown" ).accordion({
			collapsible: true,
			active:false,
			header: 'li a.has_slide', // this is the element that will be clicked to activate the accordion 
			autoHeight:false,
			event: 'mousedown',
			icons:false
		});
 	
 	// Content Box Toggle Config 
		$("a.toggle").click(function(){
			$(this).toggleClass("toggle_closed").next().slideToggle("slow");
			return false; //Prevent the browser jump to the link anchor
		});
 	
 	// Content Box Tabs Config
			$( ".tabs" ).tabs({ 
				fx: {opacity: 'toggle', duration: 'normal', height:'toggle'} 
			});

			$( ".side_tabs" ).tabs({ 
				fx: {opacity: 'toggle', duration: 'slow', height:'auto'} 
			});
		

	// Content Box Accordion Config		
		$( ".content_accordion" ).accordion({
			collapsible: true,
			// active:false,
			header: 'h3.bar', // this is the element that will be clicked to activate the accordion 
			autoHeight:false,
			event: 'mousedown',
			icons:false,
			animated: true
		}); 
		
	// Sortable Content Boxes Config				
		$( ".main_container" ).sortable({
			handle:'.grabber',  // the element which is used to 'grab' the item
			items:'div.box', // the item to be sorted when grabbed!
			opacity:0.8,
			revert:true,
			tolerance:'pointer',
			helper:'original',
			forceHelperSize:true,
			placeholder: 'dashed_placeholder',		
			forcePlaceholderSize:true
		});
/*
	// Sortable Accordion Items Config			
		$( ".content_accordion" ).sortable({
			handle:'a.handle',
			axis: 'y', // the items can only be sorted along the y axis
			revert:true,
			tolerance:'pointer',
			forcePlaceholderSize:true
		});
		
	// Input Datepicker Config
		$( ".datepicker" ).datepicker({ dateFormat: 'd M yy' });; // the time format which will be input to the datepicker field upon selection. more info on formatting here: http://docs.jquery.com/UI/Datepicker/formatDate
	
	// input Slider	Config
		$( ".slider" ).slider(); // creates a simple slider with default settings
	
		
	// input Range Slider Config	
		$( ".slider_range" ).slider({
			range: true, // creates a range slider
			min: 0,
			max: 500,
			values: [ 75, 300 ],
			slide: function( event, ui ) {
				$( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
			}
		});
		
		$( "#amount" ).val( "$" + $( "#slider_range" ).slider( "values", 0 ) +
			" - $" + $( "#slider_range" ).slider( "values", 1 ) );
		
	// Dialog Config
		$( "#dialog" ).dialog({
			autoOpen: false, 
			show: "fade",
			hide: "fade",
			modal: true 
		});
		
		$( "#opener" ).click(function() {
			$( "#dialog" ).dialog( "open" ); // the #dialog element activates the modal box specified above
			return false;
		});
	
	//Progress Bar Config
		$( "#progressbar" ).progressbar({
			value: 37
		});
		*/
	// Dismiss alert box
		$(".alert").click(function(){
			$(this).fadeOut('slow');
		});
			/*	
		
// Other Scripts

	// Uniform Config (more info can found at http://pixelmatrixdesign.com/uniform/)
        $( "select, input:checkbox, input:radio, input:file").uniform();
	
    // Tipsy Top Config (more info found at http://onehackoranother.com/projects/jquery/tipsy/)
		$('[title]').tipsy({
			fade: true,     // fade tooltips in/out?
			fallback: '',    // fallback text to use when no tooltip text
			gravity: 's',    // gravity
			opacity: 1,    // opacity of tooltip
			title: 'title',  // attribute/callback containing tooltip text
			trigger: 'hover' // how tooltip is triggered - hover | focus | manual    	
		});  
		
	// Tipsy Side Config
		$('input[title]').tipsy({
			trigger: 'focus',  
			offset:'5',
			gravity: 'w'
		});
	
	// focus on first field in form
	//$("input[type='text']:first", document.forms[0]).focus();
	
		
	// iOS Device Touch Config (more info can be found at http://old.nabble.com/jQuery-UI-Support-on-the-iPhone-td22011162s27240.html)
	
		$('.main_container').sortable();
		$('.grabber').addTouch();
		$('ul.content_accordion').sortable();
		$('a.handle').addTouch();
		$('.ui-slider-handle').addTouch();
		*/
	// DataTables Config (more info can be found at http://www.datatables.net/)
		/* var oTable = $('.table').dataTable( {
					"bJQueryUI": true,
					"sScrollX": "",
					"bSortClasses": false,
					"aaSorting": [[0,'asc']],
					"bAutoWidth": true,
					"bInfo": true,
					"sScrollY": "100%",	
					"sScrollX": "100%",
					"bScrollCollapse": true,
					"sPaginationType": "full_numbers",
					"bRetrieve": true
					} ); */

/*

	// DataTables Config - this reloads dataTables when it is placed in a hidden tab			
		$(".tabs").tabs( {
			"show": function(event, ui) {
				var oTable = $('div.dataTables_scrollBody>.table', ui.panel).dataTable();
				if ( oTable.length > 0 ) {
					oTable.fnAdjustColumnSizing();
				}
			}
		} );

	// DataTables Config - this reloads dataTables when it is placed in a closed accordion			
		$( ".content_accordion" ).accordion( {
			"change": function(event, ui) {
				var oTable = $('div.dataTables_scrollBody>.table', ui.panel).dataTable();
				if ( oTable.length > 0 ) {
					oTable.fnAdjustColumnSizing();
				}
			}
		} );

	
	// FileTree Config (more info can be found at http://abeautifulsite.net/blog/2008/03/jquery-file-tree/)
		$('#file_tree').fileTree({ 
					root: '/var/www/vhosts/tricycle.ie/httpdocs/adminica/file_tree/', 
					script: '../../../js/jqueryFileTree/jqueryFileTree.php', 
					loadMessage:'Loading...'  }, function(file) {
					alert(file);
					});
	
	//FancyBox Config (more info can be found at http://www.fancybox.net/)
		$(".gallery ul li a").fancybox({
        'overlayColor':'#000' 
			
		});
		$("a img.fancy").fancybox();
	
	//This configures the iPhone style Contacts display)	  
 		$('#slider_list').sliderNav({height:'500'});

		

	//Slide to top link
		$().UItoTop({ easingType: 'easeOutQuart' });
	*/
});
