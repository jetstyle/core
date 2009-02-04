var Gallery = {

	baseUrl: '',

	init: function() {
		$('#gallery').sortable({
        	start: function(e,ui) {
            	$(ui.helper[0]).find('img.gallery-delete').hide();
            	$(ui.helper[0]).find('img.gallery-edit').hide();
            	$(ui.item[0]).find('img.gallery-delete').hide();
            	$(ui.item[0]).find('img.gallery-edit').hide();
        	},
        	stop: function() {
        		var order = '';
        		$('#gallery div.gallery-image').each(function() {
        			order += Gallery.getId(this)+',';
       			});
       			order = order.substr(0, order.length-1);
       			$.post(Gallery.baseUrl, {'action': 'reorder','order': order},function(data) {
					if (data != '1') alert(data);
       			},'text');
        	},
        	items: '> div.gallery-image'
		});
		//control for upload images
		imagesUploadSettings.upload_url = Gallery.baseUrl+'?id='+Gallery.rubricId+'&session_hash='+Gallery.sessionHash;
		imagesUploadSettings.flash_url = Gallery.imagesUrl+"swfupload.swf";
		imagesUploadSettings.button_width = Gallery.thumbWidth;
		imagesUploadSettings.button_height = Gallery.thumbHeight;
		Gallery.swfUpload = new SWFUpload(imagesUploadSettings);
		//control for replace one image
		imageOneUploadSettings.upload_url = Gallery.baseUrl+'?id='+Gallery.rubricId+'&session_hash='+Gallery.sessionHash;
		imageOneUploadSettings.flash_url = Gallery.imagesUrl+"swfupload.swf";
		Gallery.swfUploadOne = new SWFUpload(imageOneUploadSettings);
		//control buttons
		$('#gallery div.gallery-image').each(function() {
        	Gallery.initImage(this);
       	});
       	//edit image form
       	$('#editImageOK').click(Gallery.editImageTitle);
  		$('#editImageTitle').keypress(function(e){  			if (e.which == 13) {  				Gallery.editImageTitle();  				return false;
  			}  		});
  		$(document).click(function(){
  			$('#editImageForm:visible').hide();
  		});
  		$('#editImageForm').click(function(){
  			return false;
  		});
  		//refresh page on forbidden
  		$.ajaxSetup({
        	'error': function(XMLHttpRequest, textStatus, errorThrown) {
            	alert(textStatus);
				location.reload(true);
        	}
  		});
	},

	initImage: function(image) {
    	$(image).find('img.gallery-delete').parent().click(Gallery.deleteImage);
    	$(image).find('div.image-title').click(Gallery.showEditImageForm);
    	tb_init($(image).find('a.popup').get(0));
    	$(image).hover(
        	function(event) {
        		Gallery.lastOveredImageId = this.id;
                $(this).find('img.gallery-delete')
                	   .css('left',$(this).offset().left+$(this).width()-32-5-$('#gallery').offset().left)
                	   .css('top',$(this).offset().top+5-$('#gallery').offset().top)
                	   .show();
                $(this).find('img.gallery-edit').add('#replaceFileButton')
                	   .css('left',$(this).offset().left+$(this).width()-(32+5)*2-$('#gallery').offset().left)
                	   .css('top',$(this).offset().top+5-$('#gallery').offset().top)
                	   .show();
        	},
        	function (event) {
        		if (
        			event.pageX <= $(this).offset().left ||
                    event.pageY <= $(this).offset().top ||
                    event.pageX >= $(this).offset().left + $(this).width() ||
                    event.pageY >= $(this).offset().top + $(this).height()
        		) {                	$(this).find('img.gallery-delete').hide();
            		$(this).find('img.gallery-edit').hide();
        		}
        	}
		);
	},

	getId: function(element)
	{
		return element.id.match(/([0-9]+)$/)[0];
	},

	deleteImage: function()
	{
		if (!confirm('Удалить?')) return false;
    	var id = Gallery.getId(this.parentNode);
    	$.post(Gallery.baseUrl, {'action': 'delete','id': id}, function(data)
		{
       		if (data == '1')
			{
				$('#image'+id).remove();
			}
		},'text');
   		return false;
	},

	editImageTitle: function() {
    	var values = {
           	'action': 'edit',
           	'title': $('#editImageTitle').attr('value'),
           	'id': $('#editImageId').attr('value')
    	};
       	$.post(Gallery.baseUrl, values, function(data) {
			$('#image'+values.id+' div.image-title').text(values.title);
			if (data != '1') alert(data);
    	},'text');
    	$('#editImageForm').hide();
  	},

	showEditImageForm: function() {
		$('#editImageForm').css('left',$(this).offset().left-15);
		$('#editImageForm').css('top',$(this).offset().top-35);
		$('#editImageTitle').attr('value',$(this).text());
		$('#editImageId').attr('value',Gallery.getId($(this).parent().get(0)));
    	$('#editImageForm').show();
    	$('#editImageTitle').focus();
    	return false;
	},

	uploadUpdateFileCounter: function(stats) {
		var fileUploaded = stats.successful_uploads + stats.upload_errors + stats.upload_cancelled;    	$('#fileCounter').show().text(
    		'Закачано файлов ' + fileUploaded + '/' + (fileUploaded + stats.files_queued)
    	);
	}
};

var imagesUploadSettings = {
	file_size_limit:  "2 MB",
	file_types:       '*.jpg;*.jpeg;*.gif;*.png',
	file_types_description: "Графические файлы",

	post_params: {
		'swfupload_user_agent': navigator.userAgent || navigator.vendor || window.opera
	},

	button_placeholder_id : "spanButtonPlaceholder",
	button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
	button_cursor: SWFUpload.CURSOR.HAND,

	file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
		if (numFilesSelected > 0) this.startUpload();
	},
	upload_start_handler: function() {
		Gallery.uploadUpdateFileCounter(this.getStats());
		$('#progressCont').show();
		$('#addImageButton').css('backgroundImage','url('+Gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
		return true
	},
	upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
		$('#progressBar').css(
			'width',
			Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
		);
	},
	upload_error_handler: function(file, errorCode, message) {},
	upload_success_handler: function(file, data) {
		if (data.indexOf('ok,')==0) {
           	$('#addImageButton').before(data.substring(3));
           	$('#gallery div.gallery-image:last .image-title').html('Заголовок');
			Gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
		} else {
           	location.reload(true);
		}
	},
	upload_complete_handler: function() {
		var stats = this.getStats();
		Gallery.uploadUpdateFileCounter(stats);
		if (stats.files_queued === 0) {
			$('#addImageButton').css('backgroundImage','url('+Gallery.imagesUrl+'gallery/add.png)');
			$('#fileCounter').text('').hide();
			$('#progressCont').hide();
			stats.successful_uploads = stats.upload_errors = stats.upload_cancelled = 0;
			this.setStats(stats);
		} else {
           	this.startUpload();
		}
	}
};

var imageOneUploadSettings = {
	file_size_limit:  "2 MB",
	file_types:       '*.jpg;*.jpeg;*.gif;*.png',
	file_types_description: "Графические файлы",

	post_params: {
		'swfupload_user_agent': navigator.userAgent || navigator.vendor || window.opera,
		'replace_image': true
	},

	button_placeholder_id : "spanReplaceButtonPlaceholder",
	button_width: 32,
	button_height: 32,
	button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
	button_cursor: SWFUpload.CURSOR.HAND,
	button_action: SWFUpload.BUTTON_ACTION.SELECT_FILE,

	file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
		if (numFilesSelected > 0) {			Gallery.swfUploadOne.removePostParam('item_id');
			Gallery.swfUploadOne.addPostParam('item_id',Gallery.getId($('#'+Gallery.lastOveredImageId).get(0)));			this.startUpload();
		}
	},
	upload_start_handler: function() {
		Gallery.uploadUpdateFileCounter(this.getStats());
		$('#progressCont').show();
		$('#addImageButton').css('backgroundImage','url('+Gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
		return true
	},
	upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
		$('#progressBar').css(
			'width',
			Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
		);
	},
	upload_error_handler: function(file, errorCode, message) {},
	upload_success_handler: function(file, data) {
		if (data.indexOf('ok,')==0) {
           	$('#'+Gallery.lastOveredImageId+' img').get(0).src += '?'+Math.random;
			Gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
		} else {
           	location.reload(true);
		}
	},
	upload_complete_handler: function() {
		var stats = this.getStats();
		Gallery.uploadUpdateFileCounter(stats);
		if (stats.files_queued === 0) {
			$('#addImageButton').css('backgroundImage','url('+Gallery.imagesUrl+'gallery/add.png)');
			$('#fileCounter').text('').hide();
			$('#progressCont').hide();
			stats.successful_uploads = stats.upload_errors = stats.upload_cancelled = 0;
			this.setStats(stats);
		} else {
           	this.startUpload();
		}
	}
};