Inplace = function( editorType, cmsUrl, inplaceObject, field )
{
    this.editorType = editorType;
    this.cmsUrl = cmsUrl;
    
    //инплейсный объект - редактор+кнопки
    this.inplaceObject = $(inplaceObject);
    
    if ( this.inplaceObject.length )
	this.initContainer(true);
    
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

	if (this.container)
	{
	    this.bindAll();
	    
	    this.saveButton.click(   this.save.prototypeBind(this)   );
	    this.cancelButton.click( this.cancel.prototypeBind(this) );
	}
    },

    edit: function()
    {
    	//загрузим в редактор содержимое редактируемого контейнера 
	this.loadDataToInplaceEditor();

	//unbind events from container
    	this.unbindAll();	
    },

    loadDataToInplaceEditor: function(field)
    {
	this.showIndicator();

	$.get(  this.cmsUrl, 
		{ 'ret': this.field }, 
		function(data)
		{
		    this.setEditorData(data);
		    this.hideIndicator();
		    
		    if ( ! this.inplaceContainer )
			this.inplaceContainer = this.container.clone().empty().append( $( this.inplaceObject ) );
		    
		    //–€дом с контейнером создаем его пустой клон, с инплейсным редактором
		    this.container.parent().append( this.inplaceContainer );
		    
		    //show inplaceObject and its container
		    $( this.inplaceObject ).add( this.inplaceContainer ).removeClass("invisible");

		    //контейнер спр€чем
		    this.container.addClass("invisible");

		}.prototypeBind(this), 'html'
	    );
    },

    setEditorData: function( data )
    {
	if ( this.editorType=='input' || this.editorType=='textarea' )
	{
	    $( this.inplaceObject ).children(":first").attr("value", data);    
	    //$( this.inplaceObject ).children(":first").width( this.container.width() );
	    //$( this.inplaceObject ).children(":first").height( "100%" );
	}
    },
    
    getEditorData: function( data )
    {
	if ( this.editorType=='input' || this.editorType=='textarea' )
	{
	    return $( this.inplaceObject ).children(":first").attr("value");
	}
    },
    
    showIndicator: function( parent )
    {
	if (parent)
	    parent.append( /*"<br>",*/ $("#cms_panel_loading").css('display', 'block').removeClass('invisible') );
	else
	    this.container.append(/*"<br>",*/ $("#cms_panel_loading").css('display', 'block').removeClass('invisible') );
    },
    
    hideIndicator: function()
    {
	$("#cms_panel_loading").addClass('invisible');
    },

    save: function()
    {
	this.showIndicator( this.saveButton.parent() );
	var params =  {'ajax_update': this.field+'_pre'};
	params[ this.field ] = this.getEditorData();

	self = this;

	$.post( this.cmsUrl, 
	    params, 
    	    this.onSave.prototypeBind( this )
	);
    },
    
    onSave: function(data)
    {
	this.hideIndicator(); 
	this.container.html( data );
	this.cancel();
    }, 
	    
    
    cancel: function()
    {
	//спр€чем inplaceObject и его клонированный контйентер
	$( this.inplaceObject ).add( this.inplaceContainer ).addClass("invisible");

	//оригинальный контейнер покажем
	this.container.removeClass("invisible inplace-over");
	this.bindAll();
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
//    	this.saveButton.unbind('click');
//    	this.cancelButton.unbind('click');
    },

    initContainer: function(parent)
    {	
	//контейнер инплейсного объекта
	if ( parent )
	    this.container = $( this.inplaceObject ).parent();
	else 
	    this.container = $( this.inplaceObject ).prev();

	this.saveButton = $(document.createElement("input")).val("—охранить").attr("type", "button").addClass("cms-save-but hand");
	this.cancelButton = $(document.createElement("input")).val("ќтменить").attr("type", "button").addClass("cms-delete-but hand");

	this.inplaceObject.append( this.cancelButton, this.saveButton );
    }
}