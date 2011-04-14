// gridito init
$("div.gridito").livequery(function () {
	$(this).gridito_custom();
});

// nette ajax init
$("a.ajax").live("click", function (event) {
	event.preventDefault();
	$.get(this.href);
});