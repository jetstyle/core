Inplace = function( editorType, cmsUrl, inplaceObject, field )
{
    this.editorType = editorType;
    this.cmsUrl = cmsUrl;

    //инплейсный объект - редактор+кнопки
    this.inplaceObject = $(inplaceObject);
    this.inplaceObjectID = inplaceObject;
    this.field  = field ? field : 'text';
}

Inplace.prototype =
{
    editorType: 'input',

    cmsUrl: '',
    field: 'text',

    container: null,

    validTypes: ['input', 'textarea', 'wysiwyg'],

    init: function()
    {
		this.initContainer(true);
		this.initButtons();
        this.bindAll();

    },

    edit: function()
    {

    	//загрузим в редактор содержимое редактируемого контейнера
		this.loadDataToInplaceEditor();

		//unbind events from container
    	this.unbindAll();
    },

    loadDataToInplaceEditor: function()
    {
        this.showIndicator();

        if (this.cacheData )
		{
            this.onLoad( this.cacheData );
		}
        else
		{
	        this.__get();
		}
    },

    __get: function ()
    {
	    $.get(this.cmsUrl,
				{ 'ret': this.field },
                this.onLoad.prototypeBind(this), 'html'
	    );
    },

    onLoad: function (data)
    {
        if ( ! this.inplaceContainer )
        {
            this.inplaceContainer = this.container.clone().empty().append( $( this.inplaceObject ) ).css("padding", "0").removeClass("inplace-over");

            //–€дом с контейнером создаем его пустой клон, с инплейсным редактором
            //this.container.parent().append( this.inplaceContainer );
			this.container.after( this.inplaceContainer );
        }

        //show inplaceObject and its container
        $( this.inplaceObject ).add( this.inplaceContainer ).removeClass("invisible");

		//width fix
		if (this.editorType!='wysiwyg')
		    $( this.inplaceObject ).css( "width", $( this.inplaceObject ).find('textarea').width()-6 );

        //контейнер спр€чем
        this.container.addClass("invisible");

		// важно, что после манипул€ций с домом
        this.setEditorData(data);

        this.hideIndicator();
    },

    setEditorData: function( data )
    {
	    if ( this.editorType=='' || this.editorType=='textarea' )
	    {
	        $( this.inplaceObject ).children(":first").children(":first").children(":first").attr("value", data);
	    }
	    else
	    {
	        $( this.inplaceObject ).find('textarea').attr("value", data);

	        if (!this.inited)
	        {
				var textarea_id = $( this.inplaceObject ).find("textarea").attr("id");
		        tinyMCE.execCommand("mceAddControl", true, textarea_id );
		        this.inited = true;
	        }
	        //else
		    //    tinyMCE.activeEditor.setContent( data );
	    }
    },

    getEditorData: function( data )
    {
	    if ( this.editorType=='' || this.editorType=='textarea' )
	    {
	        return $( this.inplaceObject ).children(":first").children(":first").children(":first").attr("value");
	    }
	    else
	    {
	        return tinyMCE.activeEditor.getContent();
	    }
    },

    showIndicator: function( before )
    {
		this.container.removeClass("inplace-over");

		if (before)
            before.before( $("#cms_panel_loading").css('padding', "0 5px 0 0").css('display', '').removeClass('invisible') );
		else
            this.container.append( $("#cms_panel_loading").css('padding', 5).css('display', 'block').removeClass('invisible') );

        this.saveButton.addClass("invisible");
        this.cancelButton.addClass("invisible");
    },

    hideIndicator: function()
    {
		//console.log( $("#cms_panel_loading") );
		$("#cms_panel_loading").css('display', '').addClass('invisible');
        this.saveButton.removeClass("invisible");
        this.cancelButton.removeClass("invisible");
    },

    save: function()
    {
	    this.showIndicator( this.saveButton );

	    var params =  {'ajax_update': this.field+'_pre'};
	    params[ this.field ] = this.getEditorData();

	    $.post( this.cmsUrl,
	        params, this.onSave.prototypeBind( this )
	    );
    },

    onSave: function(data)
    {
	    this.container.html( data );
	    this.cancel();
    },


    cancel: function()
    {
	    this.cacheData = this.getEditorData();

/*
	    if (this.editorType=='wysiwyg')
	    {
		tinyMCE.activeEditor.remove();
		this.inited = false;
    	    }
*/
	    //спр€чем inplaceObject и его клонированный контйентер
	    $( this.inplaceObject ).add( this.inplaceContainer ).addClass("invisible");

	    //оригинальный контейнер покажем
	    this.container.removeClass("invisible inplace-over");

	    this.bindAll();

	    this.hideIndicator();
    },

    bindAll: function()
    {
	    this.container.click( this.edit.prototypeBind( this ) );
	    this.container.mouseover( function(){ $(this).addClass("inplace-over")    } );
	    this.container.mouseout ( function(){ $(this).removeClass("inplace-over") } );
    },

    unbindAll: function()
    {
		this.container.unbind('click');
        this.container.unbind('mouseover');
        this.container.unbind('mouseout');
    },

    initContainer: function(parent)
    {
		//контейнер инплейсного объекта
		if ( parent )
			this.container = $( this.inplaceObject ).parent();
		else
			this.container = $( this.inplaceObject ).prev();

		this.inplaceObject.css("z-index", 1000).css("position", "relative");
		var dif = this.container.width() - this.inplaceObject.width();
		//if(dif){ this.inplaceObject.css("margin-left", dif); }

		if ( this.editorType == "wysiwyg" )
		{
			textarea_id = this.inplaceObject.children(":first").attr("id");

			//setTimeout( this.initMCE.prototypeBind(this, textarea_id),  100 );
			preInitMce(textarea_id);
		}
    },

    initButtons: function()
    {
		//bind buttons
		this.saveButton = $(document.createElement("input")).val("—охранить").attr("type", "button").addClass("cms-save-but hand");
		this.cancelButton = $(document.createElement("input")).val("ќтменить").attr("type", "button").addClass("cms-delete-but hand");

		this.buttons = $(document.createElement("div")).append( this.saveButton, this.cancelButton ).css("padding-top", "8px");//;

		if (this.editorType=="wysiwyg")
			$(this.buttons).css("padding-left", "8px");
		else
			$(this.buttons).css("float", "right");

		this.saveButton.click(   this.save.prototypeBind(this)   );
		this.cancelButton.click( this.cancel.prototypeBind(this) );

		//append buttons
		this.inplaceObject.append( this.buttons );
    }

}

var mce_ids = [];
var mce_timer = false

function preInitMce(textarea_id)
{
	mce_ids.push(textarea_id);

	if (!mce_timer)
	{
	    setTimeout( initMCE, 1000 );
	    mce_timer = true;
	}
}

function initMCE()
{
	var textarea_id = mce_ids;
	var base_url = '/';

	tinyMCE.init({
		mode : "none",
		theme : "advanced",
		elements: textarea_id,
		language : "ru",
    	plugin_preview_pageurl: base_url + "cms/preview",
    	content_css: base_url + "cms/css/editor.css",
    	plugin_preview_width: "400",
    	button_tile_map : false,
    	relative_urls : false,

    	plugins : "contextmenu,advlink,preview,jetimages",

    	valid_elements : "+a[name|href|target|title|onclick|mode|fileparams],-strong/-b/,-em/-i,-strike,-u,#p[id|style],-ol,-ul,-li,br,img[src|alt|title|bigwidth|bigheight|bigsrc|mode|width|height|title|style],-sub,-sup,-blockquote,-table[class],-tr[rowspan],tbody,thead,tfoot,#td[colspan|rowspan],-th[colspan|rowspan],caption,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr,dd,dl,dt,cite,abbr,acronym,del,ins,big",
    	theme_advanced_toolbar_location : "top",
    	theme_advanced_resizing : true,
    	theme_advanced_statusbar_location : "false",
    	theme_advanced_resize_horizontal : "false",
    	theme_advanced_toolbar_align : "left",
    	theme_advanced_buttons1 : "link,unlink,anchor,separator,justifyleft,justifycenter,justifyright,justifyfull,formatselect,bold,italic,underline,strikethrough,sub,sup,separator,bullist,numlist,separator,jetimages,jetfiles,code",
    	theme_advanced_buttons2 : "",
    	theme_advanced_buttons3 : "",
		theme_advanced_blockformats : "p,h3,address",
    	width: "100%",

		init_instance_callback: this.onMCEInit.prototypeBind(self)
	});

	tinyMCE.jetimages= base_url + "cms/do/Pictures/jetimages";
	tinyMCE.jetfiles = base_url + "cms/do/PicFiles/jetfiles";
	tinyMCE.jetcontent = base_url + "cms/jetcontent";

    tinyMCE.base_url = base_url + "cms/";
}

function onMCEInit(inst)
{
	inst.resizeToContent();
}
