<?
 /** 
 * @author nop
 * 
 * требует от конфига таблицу и условие на селект
 * $this->table_opts
 * $this->where_opts
 * $this->order_opts
 **/

$this->useClass("ListAdvanced");
class ListSelect extends ListAdvanced
{
  var $topic_field = 'topic_id';
  function Handle()
  {
    $id_opts = $this->config->id_opts ? $this->config->id_opts : "id";
//    $this->topic_field = $this->config->topic_field;
    $this->rh->tpl->assign('_name', $topic_field);
    $sql = "SELECT $id_opts, CONCAT(SUBSTRING(title,1,40),'..') as title".($this->config->format_opts ? ", _level" : "")." FROM ".$this->config->table_opts." WHERE ".$this->config->where_opts.($this->config->order_opts ? " ORDER by ".$this->config->order_opts : "");
    
    $opts = $this->rh->db->Query($sql);
    foreach ($opts as $i=>$k)
    {
      $_opts .= "<option value='".$k[$id_opts]."'".($_GET['topic_id'] == $k[$id_opts] ? " selected='selected'" : '').">".($this->config->format_opts ? str_repeat("&nbsp;&nbsp;", $k['_level']-1)  : '' ).$k['title']."</option>";
    }
    $this->rh->tpl->assign('__options', $_opts);
    $this->rh->tpl->parse("select_topic.html", $this->store_to);
    
    
    if (isset($_GET[$this->topic_field]) && $_GET[$this->topic_field] > 0)
    {
        /*
      echo '<pre>+';
      print_r($this->item);
      echo '</pre>';
*/
      parent::Handle();
    }
  }

}
?>