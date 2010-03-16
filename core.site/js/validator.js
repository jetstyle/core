Validator = Class.create();
Validator.prototype = {
		f : null,
		objs : null,
		dynamic : false,		
		formErrorHandler : null,
		instant: true,
		
		initialize : function(formName, params)
		{
			this.f = $(document.getElementById(formName));
			
			if(params)
			{
				if(params.dynamic) this.dynamic = true;
				if(params.instant != 'undefined')  this.instant = params.instant;
			}
			
			if(!this.dynamic)
			{
				this.collectItems();
			}
			
			this.setAfterValidateHandler(this.standartFormHandler);
			this.f.bind('submit', this.validate.prototypeBindAsEventListener(this));
		},
			
		collectItems : function()
		{
			this.objs = new Array();
			var els = $('[validate]', this.f).get();
			for(i in els)
			{
				this.objs[this.objs.length] = new ValidatorItem(els[i], this.instant);
			}
		},
		
		validate : function()
		{
			if(this.dynamic)
			{
				this.collectItems();
			}
			
			var send = true;
			for(i in this.objs)
			{
				if(!this.objs[i].validate())
				{
					send = false;
				}
			}

			return this.afterValidateHandler(send);					
		},
		
		standartFormHandler : function(result)
		{
			if(result)
			{
				return true;
			}
			else
			{
				return false;
			}	
		},
		
		setAfterValidateHandler : function(func)
		{
			this.afterValidateHandler = func;
		}
	};
	
ValidatorItem = Class.create();
ValidatorItem.prototype = {
	obj : null,
	t : '',
	error : false,
	instantCheck : false,
	firstCheck : false,
	errorMessageObj : null,
	errorMessageText : 'Поле заполнено неверно',
	
	initialize : function(obj, instant)
	{
		this.obj = $(obj);
		this.t= this.obj.attr('validate');
		
		if(instant)
		{
			this.instantCheck = true;
			this.firstCheck = true;
			if (this.obj.get(0).type == 'radio')
			{
				this.obj.click(this._validate.prototypeBindAsEventListener(this));
			}
			else
			{
				this.obj.keyup(this._validate.prototypeBindAsEventListener(this));
			}
		}

		this.errorMessageObj = $('#' + this.obj.attr('name') + '_err');
	},
	
	validate : function()
	{
		if(this.instantCheck && !this.firstCheck) return !this.error;
		this.firstCheck = false;
		return this._validate();
	},
	
	_validate : function()
	{
		var s = false;
		
		switch(this.t)
		{
			case 'userfunc' : 
			
				var params = this.obj.attr('validate_params');
				if(params)
				{
					var v = $.trim(this.obj.val());
					eval("s = " + params + "(v, this.obj, this);");
				}
			
			break;
			
			case 'email' : 
				
				var v = $.trim(this.obj.val());
				var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				if (filter.test(v)) 
				{
					s = true;
				}
				else
				{
					s = false;
				}
											
			break; 
                        
        		case 'emails' : 
				
				var vs = $.trim(this.obj.val()).split(",");
                                for (i in vs)
                                {
                                    v = $.trim(vs[i]);
                                    var filter  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
                                    if (filter.test(v)) 
                                    {
                                            s = true;
                                    }
                                    else
                                    {
                                            s = false;
                                            break;
                                    }
                                }
                                
											
			break; 
			
			case 'numeric':
			case 'number':
				
				var v = $.trim(this.obj.val());
				
				s = v.match(/^\d+$/);
				
				var params = this.obj.attr('validate_params');
				if(params && s)
				{
					params = params.split(':');
					var r = {};
					r.from = parseInt(params[0], 10);
					r.to = parseInt(params[1], 10);
					r.from = isNaN(r.from) ? 0 : r.from;
					r.to = isNaN(r.to) ? 0 : r.to;
										
					s = this.validateRange($.trim(this.obj.val()), r, 'number');
				}
				else
				{
					this.setErrorText('Поле должно содержать только цифры');
				}
							
			break;
			
			case 'string':
			
				var params = this.obj.attr('validate_params');
				params = params.split(':');
				var r = {};
				r.from = parseInt(params[0], 10);
				r.to = parseInt(params[1], 10);
				r.from = isNaN(r.from) ? 0 : r.from;
				r.to = isNaN(r.to) ? 0 : r.to;
				s = this.validateRange($.trim(this.obj.val()).length, r, 'string');
			break;
			
			case '1':
			case 'true':
			case 'default' : 
			case 'notblank':
				s = this.validateDefault($.trim(this.obj.val()).length);
			break;
		}
				
		
		if(s)
		{
			this.ok();
			return true;
		}
		else
		{	
			this.err();
			return false;
		}
	},
	
	setErrorText : function(v)
	{
		this.errorMessageText = v;
	},
	
	validateDefault : function(v)
	{
		if(v == 0)
		{
			this.setErrorText('Поле не заполнено');
			return false;
		}
		else
		{
			return true;
		}
	},
	
	validateRange : function(v, r, type)
	{
		var s = false;
		if(r.from && r.to)
		{
			if(v >= r.from && v <= r.to)
			{
				s = true;
			}
			else
			{
				s = false;
				if(v >= r.from)
				{
					if(type == 'string')
					{
						this.setErrorText('Текст слишком длинный');
					}
					else if(type == 'number')
					{
						this.setErrorText('Число слишком большое');
					}
				}
				else
				{
					if(type == 'string')
					{
						this.setErrorText('Текст слишком короткий');
					}
					else if(type == 'number')
					{
						this.setErrorText('Число слишком маленькое');
					}
				}
			}
		}
		else if(r.from)
		{
			if(v >= r.from)
			{
				s = true;
			}
			else
			{
				s = false;
				if(type == 'string')
				{
					this.setErrorText('Текст слишком длинный');
				}
				else if(type == 'number')
				{
					this.setErrorText('Число слишком большое');
				}
			}
		}
		else if(r.to)
		{
			if(v <= r.to)
			{
				s = true;
			}
			else
			{
				s = false;
				if(type == 'string')
				{
					this.setErrorText('Текст слишком короткий');
				}
				else if(type == 'number')
				{
					this.setErrorText('Число слишком маленькое');
				}
			}
		}
		return s;
	},
	
	ok : function()
	{
		if(!this.instantCheck)
		{
			this.instantCheck = true;
			if (this.obj.get(0).type == 'radio')
			{
				this.obj.click(this._validate.prototypeBindAsEventListener(this));
			}
			else
			{
				this.obj.keyup(this._validate.prototypeBindAsEventListener(this));
			}
		}
		
		if(!this.error) return;
		
		this.error = false;
		this.obj.removeClass('error');
		this.errorMessageObj.hide();
	},
	
	err : function()
	{
		if(this.error) return;
		
		if(!this.instantCheck)
		{
			this.instantCheck = true;	
			if (this.obj.get(0).type == 'radio')
			{
				
				this.obj.click(this._validate.prototypeBindAsEventListener(this));
			}
			else
			{
				this.obj.keyup(this._validate.prototypeBindAsEventListener(this));
			}
		}
		
		this.error = true;		
		this.obj.addClass('error');
		this.errorMessageObj.show();
		this.errorMessageObj.html(this.errorMessageText);
	}
};