/**
 * tree wrapper
 * @author: lunatic
 */

function TreeControl(params)
{
	this.params = params;
	this.tree = null;
}

TreeControl.prototype = {

	process : function()
	{
		var tree = new tree_component();
		params = {
			'data' : {
				'async' : false,
				'type'  : 'json'
			},
			'hide_buttons' : this.params.hide_buttons,
			'images_path' : this.params.images_path,
			'cookies' : false,
			'ui' : {
				'animation' : 100,
				'dots' : true
			},
			'rules' : {
				metadata	: 'data',
				use_inline	: true,
				clickable	: "all",
				draggable	: this.params.disable_drag ? "none" : "all",
				deletable	: "all",
				dragrules	: [
					"node after node",
					"node before node",
					"node inside node",
					"root after root",
					"root before root"
				]
			},
			callback	: {
				onload		: this.onloadCallback.prototypeBind(this),
				onmove		: this.onmoveCallback.prototypeBind(this),
				onclk 		: this.onclkCallback.prototypeBind(this),
				onrename	: this.onrenameCallback.prototypeBind(this),
				oncreate	: this.oncreateCallback.prototypeBind(this),
				ondelete	: this.ondeleteCallback.prototypeBind(this),
				beforerename: function(NODE,LANG,TREE_OBJ) {
					return true;
				}
			}
		};

		if (this.params.max_depth)
		{
			params['max_depth'] = this.params.max_depth;
		}

		if (this.params.ajax_auto_loading)
		{
			params['data']['async'] = true;
			params['data']['url'] = this.params.source_url;
		}
		else
		{
			params['data']['json'] = this.params.data;
		}

		tree.init(this.params.cont, params);
	},

	onloadCallback : function(TREE_OBJ)
	{
		if (this.params.current_id)
		{
			TREE_OBJ.select_branch($("#node-" + this.params.current_id));
		}
	},

	onmoveCallback : function(NODE,REF_NODE,TYPE)
	{
		var beforeId = 0;
		var parentId = 0;
		var id = NODE.getAttribute('id').split('-')[1];

		if (TYPE == 'before')
		{
			beforeId = REF_NODE.getAttribute('id').split('-')[1];
		}
		else if (TYPE == 'after')
		{
			var strId = $(NODE).next().attr('id');
			if (strId !== undefined)
			{
				beforeId = strId.split('-')[1];
			}
		}

		var parentNodeId = $(NODE).parents('li').attr('id');
		var parentId = 0;
		if (parentNodeId !== undefined)
		{
			parentId = parentNodeId.split('-')[1];
		}

		$.post(this.params.update_url, {'change' : 1, 'target' : parentId, 'before' : beforeId, 'id' : id});
	},

	onclkCallback : function(NODE, TREE)
	{
		var id = NODE.getAttribute('id').split('-')[1];
		window.location.assign(this.params.go_url + 'id=' + id);
	},

	onrenameCallback : function(NODE) {
		var id = NODE.getAttribute('id').split('-')[1];
		$.post(this.params.update_url, {'rename' : 1, 'id' : id, 'title' : NODE.childNodes[0].innerHTML});
	},

	oncreateCallback : function(NODE,REF_NODE,TYPE)
	{
		var parentNodeId = $(NODE).parents('li').attr('id');
		var parentId = 0;
		if (parentNodeId !== undefined)
		{
			parentId = parentNodeId.split('-')[1];
		}

		var beforeNodeId = $(NODE).next().attr('id');
		var beforeId = 0;
		if (beforeNodeId !== undefined)
		{
			beforeId = beforeNodeId.split('-')[1];
		}

		$.post(this.params.update_url, {'add' : 1, 'parent' : parentId, 'before' : beforeId}, function(id){
			id = parseInt(id, 10);
			if (id > 0)
			{
				NODE.setAttribute('id', 'node-' + id);
			}
		});
	},

	ondeleteCallback : function(NODE, TREE_OBJ)
	{
		var id = NODE.attr('id').replace('node-', '');
		$.post(this.params.update_url, {'delete' : id});
	}
};