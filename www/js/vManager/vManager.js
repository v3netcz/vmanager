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
		