var editables = [[]];
/* включяет-выключает режим редактирования
 */
function makeEditable(id, prefix)
{
	if (!prefix)
		var prefix = "f_";
	
	
	var item_label = findObj(prefix + "label_"+id);
	//alert(item_label.childNodes.length)
	if(!editables[prefix+id])
	{
		var label = item_label.innerHTML;

		item_label.innerHTML = "";

		//alert(item.innerHTML);
		graft( item_label, ["input", {"type":"text", "value": label} ] );
		item_label.for = "";
		editables[prefix+id] = 1;
	}
	else
	{
		var item = findObj(prefix + id);
		item.value = item_label.childNodes[0].value;
		
		item_label.removeChild(item_label.childNodes[0]);
		item_label.innerHTML = item.value;
		
		
		editables[prefix+id] = null;
		
	}
}

/* не используется
 */
function makeEditableOff(prefix)
{

	
	if (editables.length == 0)
		return;

	for (i in editables)
	{
		id = editables[i];
		var item = findObj(prefix+id);
		item.value = item_label.childNodes[0].value;
		item_label.removeChild(item_label.childNodes[0]);
		item_label.innerHTML = item.value;

		editables[id] = null;
	}

}

/* удаляет ряд
 */
function delEditable(id)
{
	var x = findObj('r_'+id);
	x.parentNode.removeChild(x, true);
}

/* добавляет ряд в редиме редактирования
 */
function addEditable(id)
{
	var new_id = 666;
	var row = findObj("r_" + id);
/*	
	
	var r1   = new RegExp("('"+id+"')", 'ig');
	var r2   = new RegExp("_"+id, 'ig');
	
	var str = row.innerHTML;
	str		= str.replace(r1, "('"+new_id+"')");	
	str		= str.replace(r2, "_"+new_id);
	
	var tr	= document.createElement("tr");
	tr.innerHTML = str;
	row.parentNode.appendChild(tr);
*/
	//var td1	= graft(document, ["a", {"href": "#"}], "1");
	graft( row.parentNode, ["tr", {"id": "r_" + new_id},
								 ["td", ["a", {"href": "#", "onclick":"addEditable('"+new_id+"'); return false;"}, "+"], 
										 ["a", {"href": "#", "onclick":"makeEditable('"+new_id+"', 'f_'); return false;"}, 
										 	 ["img", {"src" : "/keramika/cms/images/generators/pen.gif"}],
										 ],
										 ["a", {"href": "#", "onclick":"delEditable('"+new_id+"'); return false;"}, "-"],
										 ["label", {"id":"f_label_"+new_id, "ondblclick":"makeEditable('"+new_id+"'); return false;"}]
								  ],
								  ["td", ["input", {"type": "checkbox", "name":"fields[]", "checked":"checked", "id":"f_"+new_id}],
								  	  	 ["a", {"href": "#", "onclick":"delEditable('"+new_id+"'); return false;"}, "-"],
								   		
								  ],
								  ["td"]
						   ]  
		 );
	//graft (td, );
	//graft( row.parentNode, ["td", ["td", "td"] ] );
	//graft( row.parentNode, ["td", ["td", "td"] ] );
}