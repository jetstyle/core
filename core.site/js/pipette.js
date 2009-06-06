Pipette = Class.create();
Pipette.prototype = {

    initialize : function(settings)
    {
        this.settings = settings;
        this.canvas = $('<canvas/>').get(0);
        this.context = this.canvas.getContext('2d');
    },

    turnOn: function(property) {
        this.property = property;
        $('img:not(.change-color-text):not(.change-color-bg)')
                .bind('mouseover', {'_this': this}, this.imageMouseOver)
                .bind('mousemove', {'_this': this}, this.imageMouseMove)
                .bind('click', {'_this': this}, this.imageClick);
        $('body').bind('mouseover', {'_this': this}, this.elementMouseOver)
                 .bind('click', {'_this': this}, this.elementClick)
                 .css('cursor', 'crosshair');
        //$('body').append($('<div id="pipetteColorSample">').css({position:'absolute', left:0, top:0, width:16, height: 16}));
    },

    turnOff: function() {
        $('img:not(.change-color-text):not(.change-color-bg)')
                .unbind('mouseover', this.imageMouseOver)
                .unbind('mousemove', this.imageMouseMove)
                .unbind('click', this.imageClick)
        $('body').unbind('mouseover', this.elementMouseOver)
                 .unbind('click', this.elementClick)
                 .css('cursor', '');
        //$('#pipetteColorSample').remove();
    },

    imageMouseOver: function(e) {
        _this = e.data._this;
        _this.canvas.width = $(this).width();
        _this.canvas.height = $(this).height();
        _this.context.drawImage(this, 0, 0);
    },

    imageMouseMove: function(e) {
        _this = e.data._this;
        //$('#pipetteColorSample').css(_this.property, _this.getColorInPoint(this, e.pageX, e.pageY));
    },

    imageClick: function(e) {
        _this = e.data._this;
        _this.settings.colorChoose(_this.getColorInPoint(this, e.pageX, e.pageY));
        e.stopPropagation();
        return false;
    },

    elementMouseOver: function(e) {
        _this = e.data._this;
        var parents = $.merge([e.target], $(e.target).parents());
        for (var i=0; i<parents.length; i++) {
            if ($(parents[i]).css(_this.property) != 'transparent') {
                //$('#pipetteColorSample').css(_this.property, $(parents[i]).css(_this.property));
                break;
            }
        }
        return false;
    },

    elementClick: function(e) {
        _this = e.data._this;
        _this.settings.colorChoose($('#pipetteColorSample').css(_this.property));
        e.stopPropagation();
        return false;
    },

    getColorInPoint: function(el, x, y) {
        var pixel = this.context.getImageData(x - $(el).offset().left, y - $(el).offset().top, 1, 1).data;
        var hexColor = '#';
        for(var i=0; i<3; i++)
        {
            hexColor += pixel[i] < 16 ? '0' : '';
            hexColor += parseInt(pixel[i], 10).toString(16);
        }
        return hexColor;
    }

};
