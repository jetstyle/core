IframeUpload = Class.create();
IframeUpload.prototype = {
	id : '',				// iframe unique id
	action : '',			// url to submit
	form : null,			// form object
	formData : {},			// store some form data
	iframe : null,			// 
	afterResponse : null,	// callback func
	onLoadSet : false,
	msgCont : null,			// message object

	initialize : function()
	{
		this.generateId();
		this.createIframe();
	},

	generateId : function()
	{
		this.id = 'redirect-' + Math.round((Math.random() * 100));
	},

	createIframe : function()
	{
  		var div = document.createElement('div');
  		div.innerHTML = '<iframe name="'+this.id+'" id="'+this.id+'" class="redirect"></iframe>';
  		this.iframe = div.firstChild;
  		this.iframe.setAttribute('name', this.id);
  		this.iframe.setAttribute('id', this.id);
  		this.iframe.style.position = 'absolute';
  		this.iframe.style.height = '1px';
  		this.iframe.style.width = '1px';
  		this.iframe.style.visibility = 'hidden';

  		var els = document.getElementsByTagName('body');
  		els[0].appendChild(div);
	},

	submit : function()
	{
		this.showUpdateMsg();
		if(this.onLoadSet == false)
		{
	  		$(this.iframe).bind("load", this.response.prototypeBind(this));
	  		this.onLoadSet = true;
		}

		this.formData = {'action' : this.form.action, 'target' : this.form.target};

	   	this.form.action = this.action;
		this.form.target = this.id;

	    this.form.submit();
	},

	response : function()
	{
        this.hideUpdateMsg();

        // restore form action && target
        this.form.action = this.formData.action;
        this.form.target = this.formData.target;
        var response = null;
       	// Get response from iframe body
        try {
          response = (this.iframe.contentWindow || this.iframe.contentDocument || this.iframe).document.body.innerHTML;
          // Firefox 1.0.x hack: Remove (corrupted) control characters
          response = response.replace(/[\f\n\r\t]/g, ' ');
   	      if (window.opera) {
            // Opera-hack: it returns innerHTML sanitized.
            response = response.replace(/&quot;/g, '"');
          }
	    }
        catch (e) {
          response = null;
        }
        
		if(this.afterResponse)
		{
			this.afterResponse(response);
		}
	},

	setForm : function(obj)
	{
		if(typeof(obj) == "object")
		{
			this.form = obj;
		}
		else
		{
			this.form = document.getElementById(obj);
		}
	},

	setMessageObject : function(obj)
	{
		if(typeof(msgCont) == "object")
		{
			this.msgCont = msgCont;
		}
		else
		{
			this.msgCont = document.getElementById(msgCont);
		}
	},

	setAction : function(d)
	{
		this.action = d;
	},

	setCallbackFunc : function(d)
	{
		this.afterResponse = d;
	},

	showUpdateMsg : function()
	{
		if(this.msgCont)
		{
			this.msgCont.style.display = '';
		}
	},

	hideUpdateMsg : function()
	{
		if(this.msgCont)
		{
			this.msgCont.style.display = 'none';
		}
	}
}