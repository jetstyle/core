WebPalette = Class.create();
WebPalette.prototype = {

    elementId: null,
    visibility: false,
    
    initialize : function(settings)
    {
        this.elementId = settings.id;
        this.generate(settings.id);
        this.settings = settings;
        var _this = this;
        $('#'+this.elementId+' div').mouseover(function(){
            //console.log(_this);
            if (_this.settings.colorChange) {
                _this.settings.colorChange(
                    _this.normalizeColor($(this).css('background-color'))
                );
            }
        });
        $('#'+this.elementId+' div').click(function(){
            if (_this.settings.colorClick) {
                _this.settings.colorClick(
                    _this.normalizeColor($(this).css('background-color'))
                );
            }
            return false;
        });
        $('#'+this.elementId).mouseout(function(){
            if (_this.visibility && _this.settings.colorChange) _this.settings.colorChange(false);
        });
        if (typeof(Pipette) != 'undefined') {
            var pipetteSettings = {'colorChoose': this.settings.colorClick};
            this.pipette = new Pipette(pipetteSettings);
        }
    },
    
    getElement: function()
    {
        return $('#'+this.elementId).get(0);    
    },
    
    hide: function()
    {
        this.visibility = false;
        $('#'+this.elementId).hide();
        if (this.pipette) this.pipette.turnOff();
    },
    
    show: function(snapTo)
    {
        if (snapTo) {
            if ($(snapTo).offset().left + $(snapTo).width() + $('#'+this.elementId).width() + 3 > $(document).width()) {
                $('#'+this.elementId).css('left', $(snapTo).offset().left - $('#'+this.elementId).width() - 3);   
            } else {
                $('#'+this.elementId).css('left', $(snapTo).offset().left + $(snapTo).width() + 3);   
            }
            $('#'+this.elementId).css('top', $(snapTo).offset().top - 1);   
        }
        this.visibility = true;
        $('#'+this.elementId).show();
        if (this.pipette) this.pipette.turnOn();
    },
    
    generate: function(contId)
    {
        var color = 0;
        var html = '';
        for (var i=5; i>=0; i--)
        {
            for (var j=5; j>=0; j--)
            {
                for (var k=5; k>=0; k--)
                {
                    color = i*51*256*256 + k*51*256 + j*51;
                    var hexColor = this.dec2hex(color);
                    hexColor = '#' + this.padString(hexColor, '0', 6);
                    html += '<div style="background-color: '+hexColor+'"></div>';
                }
            }
        }
        $('#'+contId).html(html).addClass('webPalette');
    },
    
    dec2hex: function(dec)
    {
        return parseInt(dec, 10).toString(16);
    },
    
    normalizeColor: function(color)
    {
        if (color.indexOf('rgb(') == 0)
        {
            var rgb = color.replace('rgb(', '').replace(')', '').split(',');
            return '#'+this.padString(this.dec2hex(rgb[0]*256*256 + rgb[1]*256 + rgb[2]*1), '0', 6);
        }
        return color;
    },
    
    padString: function(string, symbol, length)
    {
        string = '' + string;
        while (string.length < length)
        {
            string = symbol + string;
        }
        return string;
    }
    
};