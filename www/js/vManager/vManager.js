// gridito init
$("div.gridito").livequery(function () {
	$(this).gridito_custom();
});

// nette ajax init
$("a.ajax").live("click", function (event) {
	event.preventDefault();
	$.get(this.href);
});

$.texyla.setDefaults({
	//texyCfg: "admin",
	baseDir: '/texyla',
	previewPath: "/texyla/preview.php",
	filesPath: "/texyla/filesplugin/files.php",
	filesThumbPath: "/texyla/filesplugin/thumbnail.php?image=%var%",
	filesUploadPath: "/texyla/filesplugin/files/upload.php"
});
		
$(function () {			
	$.texyla({
		buttonType: "button"
	});
			
});
		
$(document).ready(function () {
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
});