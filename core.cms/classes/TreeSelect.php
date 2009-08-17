<?php
Finder::useClass("TreeControl");
class TreeSelect extends TreeControl
{
	protected $select_template = 'list_multi_select.html';
	protected $storeTo = '__filters';

	function handle()
	{
		if (is_array($this->config['selects']))
		{
			foreach ($this->config['selects'] AS $config)
			{
				if (!is_array($config['opts']))
				{
					$sql = "
						SELECT ".$config['id']." AS id, CONCAT(SUBSTRING(".$config['title'].",1,40),'..') AS title
						FROM ".$config['table']."
						".($config['where'] ? "WHERE ".$config['where'] : "")."
						".($config['order'] ? "ORDER BY ".$config['order'] : "")."
					";
					$config['opts'] = $this->db->query($sql);
				}
				if (is_array($config['opts']))
				{
					$options = '';
					if (!$config['hide_choose_row'])
					{
						$options .= "<option value=''>Выберите</option>";
					}

					foreach ($config['opts'] AS $k)
					{
						$options .= "<option value='".$k['id']."'".($_GET[$config['get_var']] == $k['id'] ? " selected='selected'" : '').">".$k['title']."</option>";
					}

					$this->tpl->set('__options', $options);
					$this->tpl->set('__title', $config['select_title']);
					$this->tpl->set('__topic_field', $config['get_var']);

					$this->tpl->parse($this->select_template.":select", "__selects", 1);
				}
			}

			$this->tpl->parse($this->select_template.":main", $this->storeTo);
		}
		parent::Handle();
	}

}
?>
