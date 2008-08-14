<?
/**
 * @author nop
 *
 * требует от конфига таблицу и условие на селект
 * $this->table_opts
 * $this->where_opts
 * $this->order_opts
 **/

Finder::useClass("ListSimple");
class ListSelect extends ListSimple
{
	var $topic_field = 'topic_id';
	function Handle()
	{
		$db = &$this->db;
		
		$id_opts = $this->config->id_opts ? $this->config->id_opts : "id";
		if($this->config->topic_field)
		{
			$this->topic_field = $this->config->topic_field;
		}

		if(is_array($this->config->opts))
		{
			$opts = $this->config->opts;
		}
		else
		{
			$this->tpl->set('_name', $topic_field);
			$sql = "SELECT $id_opts, CONCAT(SUBSTRING(title,1,40),'..') as title".($this->config->format_opts ? ", _level" : "")." FROM ".$this->config->table_opts." WHERE ".$this->config->where_opts.($this->config->order_opts ? " ORDER by ".$this->config->order_opts : "");

			$opts = $db->query($sql);
		}

		if(is_array($opts) && !empty($opts))
		{
			if(!$this->config->hide_choose_row)
			{
				$_opts .= "<option value=''>Выберите</option>";
			}

			foreach ($opts as $i=>$k)
			{
				$_opts .= "<option value='".$k[$id_opts]."'".($_GET[$this->topic_field] == $k[$id_opts] ? " selected='selected'" : '').">".($this->config->format_opts ? str_repeat("&nbsp;&nbsp;", $k['_level']-1)  : '' ).$k['title']."</option>";
			}

			$this->tpl->set('__options', $_opts);

			if($this->config->opts_title)
			{
				$this->tpl->set('opts_title', $this->config->opts_title);
			}
			else
			{
				$this->tpl->set('opts_title', 'Раздел:');
			}

			$this->tpl->set('__topic_field', $this->topic_field);
			$this->tpl->parse("select_topic.html", $this->store_to);
		}

		if((isset($_GET[$this->topic_field]) && $_GET[$this->topic_field] > 0) || $this->config->passthru)
		{
			parent::Handle();
		}
	}

}
?>