Pipette = Class.create();
Pipette.prototype = {
    
    initialize : function(settings)
    {
        this.settings = settings;
        this.canvas = $('<canvas/>').get(0);
        this.context = this.canvas.getContext('2d');
    },
    
    turnOn: function() {
        $('img').bind('mouseover', {'_this': this}, this.imageMouseOver);
        $('img').bind('click', {'_this': this}, this.imageClick);
    },
    
    turnOff: function() {
        $('img').unbind('mouseover', this.imageMouseOver);
        $('img').unbind('click', this.imageClick);
    },
    
    imageMouseOver: function(e) {
        _this = e.data._this;
        _this.canvas.width = $(this).width();
        _this.canvas.height = $(this).height();
        _this.context.drawImage(this, 0, 0);
    },
    
    imageClick: function(e){
        _this = e.data._this;
        var pixel = _this.context.getImageData(e.pageX - $(this).offset().left, e.pageY - $(this).offset().top, 1, 1).data;
        var hexColor = '#';
        for(var i=0; i<3; i++)
            hexColor += parseInt(pixel[i], 10).toString(16);
        _this.settings.colorChoose(hexColor);
        e.stopPropagation();
    },
    
    documentMouseMove: function() {
        
    }
    
};