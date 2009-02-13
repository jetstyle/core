var Gallery = {

	baseUrl: '',
	selectedItems: [],
	lastSelected: null,

	init: function() {
		$('#gallery').sortable({
        	start: function(e,ui) {
            	$(ui.helper[0]).find('img.control').hide();
            	$(ui.item[0]).find('img.control').hide();
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
  			Gallery.clearSelected();
  		});
  		$('#editImageForm').click(function(){
  			return false;
  		});
  		//progress bar position
  		$('#progressCont').css('margin-top', (Gallery.thumbHeight-40)+'px');
  		//$('#fileCounter').css('margin-top', (Gallery.thumbHeight-30)+'px');
  		//refresh page on forbidden
  		$.ajaxSetup({
        	'error': function(XMLHttpRequest, textStatus, errorThrown) {
            	alert(textStatus);
				location.reload(true);
        	}
  		});
	},

	initImage: function(image) {
		$(image).find('img:first').click(Gallery.itemClick);
    	$(image).find('img.gallery-delete').parent().click(Gallery.deleteImage);
    	$(image).find('div.image-title').click(Gallery.showEditImageForm);
    	tb_init($(image).find('a.popup').get(0));
    	$(image).hover(
        	function(event) {
        		Gallery.lastOveredImageId = this.id;
                $(this).find('img.gallery-delete')
                	   .css('left',$(this).offset().left+$(this).width()-24-5-$('#gallery').offset().left)
                	   .css('top',$(this).offset().top+5-$('#gallery').offset().top)
                	   .show();
                $(this).find('img.gallery-edit').add('#replaceFileButton')
                	   .css('left',$(this).offset().left+$(this).width()-(24+5)*2-$('#gallery').offset().left)
                	   .css('top',$(this).offset().top+5-$('#gallery').offset().top)
                	   .show();
                $(this).find('img.gallery-zoom')
                	   .css('left',$(this).offset().left+$(this).width()-(24+5)*3-$('#gallery').offset().left)
                	   .css('top',$(this).offset().top+5-$('#gallery').offset().top)
                	   .show();
        	},
        	function (event) {
        		if (
        			event.pageX <= $(this).offset().left ||
                    event.pageY <= $(this).offset().top ||
                    event.pageX >= $(this).offset().left + $(this).width() ||
                    event.pageY >= $(this).offset().top + $(this).height()
        		) {                	$(this).find('img.control').hide();
        		}
        	}
		);
	},

	itemClick: function(e) {
		var id = Gallery.getId($(this).parent()[0]);
		if (e.ctrlKey) {
			var index = Gallery.isSelected(id);
			if (index != -1)
				Gallery.deleteSelected(index);
			else	        	Gallery.addSelected(id);
		} else if (e.shiftKey) {        	if (Gallery.lastSelected) {            	var items = [];
            	var clickedEl = $(this).parent()[0];
            	var lastClickedEl = $('#image'+Gallery.lastSelected)[0];
            	var clickedIndex = $('div.gallery-image').index(clickedEl);
            	var lastClickedIndex = $('div.gallery-image').index(lastClickedEl);
            	var fromIndex = Math.min(clickedIndex, lastClickedIndex);
            	var toIndex = Math.max(clickedIndex, lastClickedIndex);
            	$('div.gallery-image:gt('+(fromIndex-1)+'):lt('+(toIndex-fromIndex+1)+')').each(function(){					items.push(Gallery.getId(this));            	});
            	Gallery.addSelectedAr(items);
        	} else {            	Gallery.setSelected(id);
        	}
		} else {        	Gallery.setSelected(id);
		}
		return false;
	},

	getId: function(element)
	{
		return element.id.match(/([0-9]+)$/)[0];
	},

	deleteImage: function()
	{
		var message = Gallery.selectedItems.length > 0 ? 'Удалить все выбранные картинки?' : 'Удалить?';
		if (!confirm(message)) return false;
		var items = '';
		if (Gallery.selectedItems.length > 0) {           	items = Gallery.selectedItems.join(',');
		} else {
    		items = Gallery.getId(this.parentNode);
    	}
    	$.post(Gallery.baseUrl, {'action': 'delete','items': items}, function(data)
		{
       		if (data == '1')
			{
				if (Gallery.selectedItems.length > 0) {                	for (var i=0; i<Gallery.selectedItems.length; i++)
                		$('#image'+Gallery.selectedItems[i]).remove();
                	Gallery.selectedItems[i] = [];
                 	Gallery.lastSelected = null;
				} else {
					$('#image'+id).remove();
				}
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
	},

	isSelected: function(id) {
    	for(var i=0; i<this.selectedItems.length; i++)
			if (this.selectedItems[i] == id) return i;
		return -1;
	},

	addSelected: function(id) {
    	if (-1 == this.isSelected(id)) {    		this.selectedItems.push(id);
			$('#image'+id).css('opacity','0.4');
    		this.lastSelected = id;
    	}
	},

	setSelected: function(id) {
		this.clearSelected();
   		this.addSelected(id);
	},

	addSelectedAr: function(ids) {
		for(var i=0; i<ids.length; i++) {        	this.addSelected(ids[i]);
		}
	},

	deleteSelected: function(index) {
   		var id = this.selectedItems[index];
   		$('#image'+id).css('opacity','1');
   		if (this.lastSelected == id) this.lastSelected = null;
   		this.selectedItems.splice(index,1);
	},

	clearSelected: function() {
		var selLength = this.selectedItems.length;    	for(var i=0; i<selLength; i++) {
			this.deleteSelected(0);
		}
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

	file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {		$('#SWFUpload_0').blur();
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