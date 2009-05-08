Gallery = Class.create();
Gallery.prototype = {

	baseUrl: '',
	selectedItems: [],
	lastSelected: null,

	initialize: function(){},

	init: function() {
		var self = this;
		$('#gallery').sortable({
        	start: function(e,ui) {
            	$(ui.helper[0]).find('img.control').hide();
            	$(ui.item[0]).find('img.control').hide();
        	},
        	stop: function() {
        		var order = '';
        		$('#gallery div.gallery-image').each(function() {
        			order += self.getId(this)+',';
       			});
       			order = order.substr(0, order.length-1);
       			$.post(self.baseUrl, {'action': 'reorder','order': order},function(data) {
					if (data != '1') alert('Ошибка!');
       			},'text');
        	},
        	items: '> div.gallery-image'
		});
		//control for upload images
		imagesUploadSettings.upload_url = this.baseUrl+'?id='+this.rubricId+'&session_hash='+this.sessionHash;
		imagesUploadSettings.flash_url = this.imagesUrl+"swfupload.swf";
		imagesUploadSettings.button_width = this.thumbWidth;
		imagesUploadSettings.button_height = this.thumbHeight;
		imagesUploadSettings.file_types = this.fileExtensions;
		this.swfUpload = new SWFUpload(imagesUploadSettings);
		this.swfUpload.customSettings.gallery = this;
		//control for replace one image
		imageOneUploadSettings.upload_url = this.baseUrl+'?id='+this.rubricId+'&session_hash='+this.sessionHash;
		imageOneUploadSettings.flash_url = this.imagesUrl+"swfupload.swf";
		imageOneUploadSettings.file_types = this.fileExtensions;
		this.swfUploadOne = new SWFUpload(imageOneUploadSettings);
		this.swfUploadOne.customSettings.gallery = this;
		//control buttons
		$('#gallery div.gallery-image').each(function() {
        	self.initImage(this);
       	});
       	//edit image form
       	$('#editImageOK').click(this.editImageTitle.prototypeBind(this));
  		$('#editImageTitle').keypress(function(e){
  			if (e.which == 13) {
  				self.editImageTitle();
  				return false;
  			}
  		});
  		$(document).click(function(){
  			$('#editImageForm:visible').hide();
  			self.clearSelected();
  		});
  		$('#editImageForm').click(function(){
  			return false;
  		});
  		//progress bar position
  		$('#progressCont').css('margin-top', (this.thumbHeight-40)+'px');
  		//refresh page on forbidden
  		$.ajaxSetup({
        	'error': function(XMLHttpRequest, textStatus, errorThrown) {
            	alert('Ошибка!');
				location.reload(true);
        	}
  		});
	},

	initImage: function(image) {
		$(image).find('img:first').click(this.itemClick.prototypeBind(
    		this,
    		$(image).find('img:first')[0]
    	));
    	$(image).find('img.gallery-delete').parent().click(this.deleteImage.prototypeBind(
    		this,
    		$(image).find('img.gallery-delete')[0]
    	));
    	$(image).find('div.image-title').click(this.showEditImageForm.prototypeBind(
    		this,
    		$(image).find('div.image-title')
    	));
		if ($(image).find('a.popup').get(0))
		{
			tb_init($(image).find('a.popup').get(0));
		}
    	var self = this;
    	$(image).hover(
        	function(event) {
        		self.lastOveredImageId = this.id;
                $(this).find('img.gallery-delete')
                	   .css('left',$(this).offset().left+$(this).width()-24-5)
                	   .css('top',$(this).offset().top+5)
                	   .show();
                $(this).find('img.gallery-edit').add('#replaceFileButton')
                	   .css('left',$(this).offset().left+$(this).width()-(24+5)*2)
                	   .css('top',$(this).offset().top+5)
                	   .show();
                $(this).find('img.gallery-zoom')
                	   .css('left',$(this).offset().left+$(this).width()-(24+5)*3)
                	   .css('top',$(this).offset().top+5)
                	   .show();
        	},
        	function (event) {
        		if (
        			event.pageX <= $(this).offset().left ||
                    event.pageY <= $(this).offset().top ||
                    event.pageX >= $(this).offset().left + $(this).width() ||
                    event.pageY >= $(this).offset().top + $(this).height()
        		) {
                	$(this).find('img.control').hide();
        		}
        	}
		);
	},

	itemClick: function(img, e) {
		var id = this.getId($(img).parent()[0]);
		if (e.ctrlKey) {
			var index = this.isSelected(id);
			if (index != -1)
				this.deleteSelected(index);
			else
	        	this.addSelected(id);
		} else if (e.shiftKey) {
        	if (this.lastSelected) {
            	var items = [];
            	var clickedEl = $(img).parent()[0];
            	var lastClickedEl = $('#image'+this.lastSelected)[0];
            	var clickedIndex = $('div.gallery-image').index(clickedEl);
            	var lastClickedIndex = $('div.gallery-image').index(lastClickedEl);
            	var fromIndex = Math.min(clickedIndex, lastClickedIndex);
            	var toIndex = Math.max(clickedIndex, lastClickedIndex);
            	var self = this;
            	$('div.gallery-image:gt('+(fromIndex-1)+'):lt('+(toIndex-fromIndex+1)+')').each(function(){
					items.push(self.getId(this));
            	});
            	this.addSelectedAr(items);
        	} else {
            	this.setSelected(id);
        	}
		} else {
        	this.setSelected(id);
		}
		return false;
	},

	getId: function(element)
	{
		return element.id.match(/([0-9]+)$/)[0];
	},

	deleteImage: function(deleteBtn)
	{
		var message = this.selectedItems.length > 0 ? 'Удалить все выбранные картинки?' : 'Удалить?';
		if (!confirm(message)) return false;
		var items = '';
		if (this.selectedItems.length > 0) {
           	items = this.selectedItems.join(',');
		} else {
    		items = this.getId(deleteBtn.parentNode.parentNode);
    	}
    	var self = this;
    	$.post(this.baseUrl, {'action': 'delete','items': items}, function(data)
		{
       		if (data == '1')
			{
				if (self.selectedItems.length > 0) {
                	for (var i=0; i<self.selectedItems.length; i++)
                		$('#image'+self.selectedItems[i]).remove();
                	self.selectedItems[i] = [];
                 	self.lastSelected = null;
				} else {
					$('#image'+items).remove();
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
       	$.post(this.baseUrl, values, function(data) {
			$('#image'+values.id+' div.image-title').text(values.title);
			if (data != '1') alert("Ошибка!");
    	},'text');
    	$('#editImageForm').hide();
  	},

	showEditImageForm: function(title) {
		$('#editImageForm').css('left',$(title).offset().left-15);
		$('#editImageForm').css('top',$(title).offset().top-35);
		$('#editImageTitle').attr('value',$(title).text());
		$('#editImageId').attr('value',this.getId($(title).parent().get(0)));
    	$('#editImageForm').show();
    	$('#editImageTitle').focus();
    	return false;
	},

	uploadUpdateFileCounter: function(stats) {
		var fileUploaded = stats.successful_uploads + stats.upload_errors + stats.upload_cancelled;
    	$('#fileCounter').show().text(
    		'Закачано файлов ' + fileUploaded + '/' + (fileUploaded + stats.files_queued)
    	);
	},

	isSelected: function(id) {
    	for(var i=0; i<this.selectedItems.length; i++)
			if (this.selectedItems[i] == id) return i;
		return -1;
	},

	addSelected: function(id) {
    	if (-1 == this.isSelected(id)) {
    		this.selectedItems.push(id);
			$('#image'+id).css('opacity','0.4');
    		this.lastSelected = id;
    	}
	},

	setSelected: function(id) {
		this.clearSelected();
   		this.addSelected(id);
	},

	addSelectedAr: function(ids) {
		for(var i=0; i<ids.length; i++) {
        	this.addSelected(ids[i]);
		}
	},

	deleteSelected: function(index) {
   		var id = this.selectedItems[index];
   		$('#image'+id).css('opacity','1');
   		if (this.lastSelected == id) this.lastSelected = null;
   		this.selectedItems.splice(index,1);
	},

	clearSelected: function() {
		var selLength = this.selectedItems.length;
    	for(var i=0; i<selLength; i++) {
			this.deleteSelected(0);
		}
	}
};

var imagesUploadSettings = {
	file_size_limit:  "8 MB",
	file_types_description: "Допустимые типы файлов",

	post_params: {
		'swfupload_user_agent': navigator.userAgent || navigator.vendor || window.opera
	},

	button_placeholder_id : "spanButtonPlaceholder",
	button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
	button_cursor: SWFUpload.CURSOR.HAND,

	file_dialog_complete_handler: function(numFilesSelected, numFilesQueued) {
		$('#SWFUpload_0').blur();
		if (numFilesSelected > 0) this.startUpload();
	},
	upload_start_handler: function() {
		this.customSettings.gallery.uploadUpdateFileCounter(this.getStats());
		$('#progressCont').show();
		$('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
		return true
	},
	upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
		$('#progressBar').css(
			'width',
			Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
		);
	},
	upload_error_handler: function(file, errorCode, message) {
		alert('Ошибка!');
		location.reload(true);
	},
	upload_success_handler: function(file, data) {
		if (data.indexOf('ok,')==0) {
           	$('#addImageButton').before(data.substring(3));
           	$('#gallery div.gallery-image:last .image-title').html('Заголовок');
			this.customSettings.gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
		} else {
           	location.reload(true);
		}
	},
	upload_complete_handler: function() {
		var stats = this.getStats();
		this.customSettings.gallery.uploadUpdateFileCounter(stats);
		if (stats.files_queued === 0) {
			$('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/add.png)');
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
	file_size_limit:  "8 MB",
	file_types_description: "Допутимые типы файлов",

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
		if (numFilesSelected > 0) {
			this.customSettings.gallery.swfUploadOne.removePostParam('item_id');
			this.customSettings.gallery.swfUploadOne.addPostParam(
				'item_id',
				this.customSettings.gallery.getId($('#'+this.customSettings.gallery.lastOveredImageId).get(0))
			);
			this.startUpload();
		}
	},
	upload_start_handler: function() {
		this.customSettings.gallery.uploadUpdateFileCounter(this.getStats());
		$('#progressCont').show();
		$('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/ajax-loader-arrows.gif)');
		return true
	},
	upload_progress_handler: function(file, bytesLoaded, bytesTotal) {
		$('#progressBar').css(
			'width',
			Math.round(($('#progressCont').width()-2)*(bytesLoaded / bytesTotal))
		);
	},
	upload_error_handler: function(file, errorCode, message) {
		alert('Ошибка!');
		location.reload(true);
	},
	upload_success_handler: function(file, data) {
		if (data.indexOf('ok,')==0) {
			var ext = file.name.match(/\.(\w+)$/)[1];
			var isImageFile = false;
			var imageExts = ['jpg','jpeg','gif','png'];
			for($i=0; $i<imageExts.length; $i++) {
				if (imageExts[$i] == ext) isImageFile = true;
			}
			if (isImageFile) {
				$('#'+this.customSettings.gallery.lastOveredImageId+' img').get(0).src += '?'+Math.random();
			}
           	else
			{
				$('#'+this.customSettings.gallery.lastOveredImageId+' img').get(0).src = '/cms/skins/images/file_icons/'+ext+'.gif';
			}
			//this.customSettings.gallery.initImage($('#gallery div.gallery-image').get().reverse()[0]);
		} else {
           	location.reload(true);
		}
	},
	upload_complete_handler: function() {
		var stats = this.getStats();
		this.customSettings.gallery.uploadUpdateFileCounter(stats);
		if (stats.files_queued === 0) {
			$('#addImageButton').css('backgroundImage','url('+this.customSettings.gallery.imagesUrl+'gallery/add.png)');
			$('#fileCounter').text('').hide();
			$('#progressCont').hide();
			stats.successful_uploads = stats.upload_errors = stats.upload_cancelled = 0;
			this.setStats(stats);
		} else {
           	this.startUpload();
		}
	}
};
