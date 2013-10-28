$(function(){
	$('ul#admin-menu').menu({
		position: {my: "left top", at: "left bottom"},
		icons: {submenu: "ui-icon-triangle-1-se"}
	});
	//$('ul#admin-menu .ui-icon').remove();

	/*$('a.model-new').button({
		icons: {
			primary: "ui-icon-plusthick"
		}
	});*/
	
	//Confirm deletion
	$('a.edit-link').button({
		icons: {
			primary: "ui-icon-pencil"
		}
	});
	
	$('a.delete-link').button({
		icons: {
			primary: "ui-icon-close"
		}
	}).click(function(){
		var targetUrl = $(this).attr("href");
		var name = $(this).parent().parent().children(":first-child").html();
		$("#dialog-confirm #deleted-model-name").html(name);
		$("#dialog-confirm").dialog({
			resizable: false,
			width: '500px',
			modal: true,
			buttons: {
				"Delete": function() {
					$( this ).dialog( "close" );
					window.location.href = targetUrl;
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
		return false;
	});
	
	$('input.model-save').button({
		icons: {
			primary: "ui-icon-check"
		}
	});
	
	$('p.message').click(function(){
		$(this).hide();
	});
	
	$('input.date-field').datepicker({
		dateFormat: 'yy-mm-dd',
		showButtonPanel: true,
		changeMonth: true,
		changeYear: true
	});
	
	$('textarea.html').tinymce({
		// General options
		height: "500",
		width: "500",
		theme : "advanced",
		plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

		// Theme options
		theme_advanced_buttons1 : "undo,redo,|,cut,copy,paste,|,bold,italic,underline,strikethrough,sub,sup,|,outdent,indent,blockquote,|,bullist,numlist,|,justifyleft,justifycenter,justifyright,justifyfull,|,link,unlink,anchor,image,cleanup,code",
		theme_advanced_buttons2 : "forecolor,backcolor,|,formatselect,fontselect,fontsizeselect,|,tablecontrols",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing : true,

		// Example content CSS (should be your site CSS)
		content_css : "../css/wittrock.css",
		
		forced_root_block : false,
		
		// Drop lists for link/image/media/template dialogs
		template_external_list_url : "lists/template_list.js",
		external_link_list_url : "lists/link_list.js",
		external_image_list_url : "lists/image_list.js",
		media_external_list_url : "lists/media_list.js"
	});

	$( "#admin-tabs" ).tabs().addClass( "ui-tabs-vertical ui-helper-clearfix" );
	$( "#admin-tabs li" ).removeClass( "ui-corner-top" ).addClass( "ui-corner-left" );
});
