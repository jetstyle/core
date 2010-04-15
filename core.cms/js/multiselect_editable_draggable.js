MultiselectEditable = Class.create();
MultiselectEditable.prototype = {
	url: '',
	images: '',			// путь до картинок
	cont: null, 		// контейнер
	table: null,		// таблица внутри контейнера, в которой будут данные
	timeout: null,
	currentRow : -1,	// номер текущей строки в таблице
	updateCont: null,
	
	initialize: function(){	},
	
	init: function(params)
	{
		this.cont = $('#' + params.cont);
		this.updateCont = $('#' + params.updateCont);
		this.table = document.createElement('table');
                this.table.className = "b-catalogue-field-table";
		this.cont.append(this.table);
		this.url = params.url;
		this.images = params.images;

		this.loadData();
	},
	
	/**
	 * load JSON data from `url`
	 */
	loadData: function()
	{
		this.showUpdateMsg();
		var params = {'a' : 'list'};
		$.post(this.url, params, this.parseData.prototypeBind(this), 'json');
	},
	
	parseData: function(data)
	{
		this.hideUpdateMsg();
		var i = null;
		for(i in data)
		{
			this.addNewRow(data[i]);
		}
                var self = this;
                
                $(".b-catalogue-field-table").tableDnD({dragHandle: "b-catalogue-field-table_td-move", 
                    onDragClass: "dragging",
                    /*
                    onDragStart: function(table, row){
  
                    },*/
                    onDrop: function(table, row){
                        
                        self.saveOrder();
                        /*
                        $.post(document.location.href+ "&"+$.tableDnD.serialize('id'), {"action": "reorder", "ajax": true}, function(){
                            

                        });
                        */
                    }

                });
	},
	
	addNewRow: function(data)
	{
		var row = this.table.insertRow(++this.currentRow);
		row.setAttribute('item_id', data.id);
		
		// sortable things
		var cell = row.insertCell(-1);
                var img = document.createElement('div');
                cell.setAttribute("class", "b-catalogue-field-table_td-move");
                cell.style.paddingTop = '3px';
		cell.appendChild(img);

		// title			
		var cell = row.insertCell(-1);
		var input = document.createElement('input');
		input.type = 'text';
		input.name = this.cont.attr("id") + '_data['+data.id+']';
		input.value = data.title;
                input.style.width = '178px';
                input.style.marginLeft = '3px';
                
		//cell.innerHTML = data.title;
		cell.appendChild(input);
		
		// delete
		var cell = row.insertCell(-1);
		/*
                var span = document.createElement('span');
		span.innerHTML = 'X';
                */
                var span = document.createElement('img');
                span.src = this.images + 'x_small.png';
                span.className = "delVariant";
                span.style.position = 'relative';
                span.style.left = '-10px';
                span.style.top = '-6px';
                span.style.visibility = 'hidden';
		span.style.cursor = 'pointer';
                $(row).hover(         function(){
                                        if ( $.tableDnD.currentTable == null )
                                            $(".delVariant", this).css("visibility", "visible");
                                        
                                      }, 
                                      function(){ 
                                        
                                            $(".delVariant", this).css("visibility", "hidden");
                                      }
                            );
		$(span).bind('click', this.delItem.prototypeBind(this, data.id, row));
		cell.appendChild(span);
	},
	
	addItem: function(title)
	{
		this.showUpdateMsg();
		var params = {'a' : 'add', 'item_title' : title};
		$.post(this.url, params, this._addItem.prototypeBind(this), 'json');
	},
	
	_addItem: function(data)
	{
		this.hideUpdateMsg();

		if(data && data.id && parseInt(data.id, 10) > 0)
		{
			this.addNewRow(data);
		}
                
	},
	
	delItem: function(id, row)
	{
            if (confirm('Удалить это значение?'))
            {
                this.showUpdateMsg();
                var params = {'a' : 'del', 'item_id' : id};
                $.post(this.url, params, this._delItem.prototypeBind(this, row), 'json');
            }
	},
	
	_delItem: function(row)
	{
		this.hideUpdateMsg();
		this.table.deleteRow(row.rowIndex);
        this.currentRow--;
	},
	
	showUpdateMsg: function()
	{
		this.updateCont.css('display', '');
	},
	
	hideUpdateMsg: function()
	{
		this.updateCont.css('display', 'none');
	},
	/*
	moveUp: function(curNode)
	{
		var beforeNode = curNode.previousSibling;
                if(!beforeNode || beforeNode.getAttribute('noswap') == 1) 
                {
                        return false;
                }

                this.saveOrder();
                curNode.parentNode.insertBefore(curNode, beforeNode);
	},
	
	moveDown: function(curNode)
	{
		var nextNode = curNode.nextSibling;
        if(!nextNode || nextNode.getAttribute('noswap') == 1)
        {
        	return false;
        }
        this.saveOrder();
        
        var nnextNode = nextNode.nextSibling;
        
        if(nnextNode)   
        {
                curNode.parentNode.insertBefore(curNode, nnextNode);
        }
        else
        {
                curNode.parentNode.appendChild(curNode);
        }
	},
	*/
	saveOrder : function()
	{
        /*
		if(this.timeout)
		{
			clearTimeout(this.timeout);
		}
		this.timeout = setTimeout(this._saveOrder.prototypeBind(this), 2000);*/
                this._saveOrder();
	},
	
	_saveOrder: function()
	{
		var i = null;
		var order = 0;
		var params = {};
		
		$("tr", this.table).each(function(){
			var id = this.getAttribute('item_id');
			id = parseInt(id, 10);
			if(id > 0)
			{
				params[id] = order++;
			}
		})
		
		this.showUpdateMsg();
		params.a = 'changeorder';
		$.post(this.url, params, this.hideUpdateMsg.prototypeBind(this), 'text');
	}
};