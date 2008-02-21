<?
/*
  ¬ таблице должны быть пол€:
    
    inserted datetime NOT NULL default '0000-00-00 00:00:00',
    year int(11) NOT NULL default '0',
    month int(11) NOT NULL default '0',
    
  ѕол€ желательно проиндексировать.
  «аполнение полей на совести обработчика формы.
*/
  
$this->UseClass('ListSimple');
  
class ListNews extends ListSimple  {
  
  var $template = 'list_news.html';
  var $template_list = 'list_advanced.html:List';
  var $arrows_template = 'list_advanced.html:List_Arrows';
  var $template_new = 'list_advanced.html:add_new';
  var $template_calendar = 'list_news.html:calendar';
  
  var $state; //персональный StateSet
  
  var $arrows; //объект постраничной рубрикации
  
  var $year_first = 0;
  var $year_last = 0;
  var $year = 0;
  var $month = 0;
  var $date_where = ''; //фильтраци€ по дате
  
  function ListNews( &$config ){
    //упор€дочиваем список
    if(!$config->order_by) $config->order_by = 'inserted DESC';
    
    //по этапу
    ListSimple::ListSimple( $config );
    
    $this->prefix = $config->module_name.'_tree_';
    //StateSet
    $this->state =& new StateSet($this->rh);
    $this->state->Set($this->rh->state);
    
    //дл€ внутренних ссылок
    $this->url = $this->rh->url.'do/'.$config->module_name;
    
    //фильруем по дате
    $this->DateFilter();
  }
  
  function Handle(){
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    
    //assign some
    $tpl->Assign('prefix',$this->prefix);
    $tpl->Assign( 'POST_STATE', $this->state->State(1) );
    $tpl->Assign( '_action', $rh->path_rel.'do/'.$this->config->module_name );
    
    //рендерим фильтр по датам
    //мес€цы
    //грузим признаки загруженности по мес€цам
    $M = array();
    $rs = $rh->db->execute("SELECT DISTINCT month FROM ".$this->table_name." WHERE year='".$this->year."' AND _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" ) );
    while($row = $rh->db->getRow())
    {
      $M[ $row['month'] ] = true;
    }
    include( $rh->FindScript('handlers','_monthes') );
    for($i=1;$i<=12;$i++)
      $month_options .= "<option value='$i' ".( $i==$this->month ? "selected='true'" : '' ).' '.( $M[$i] ? "style='background-color:#eeeeee'" : '' ).">".$MONTHES_NOMINATIVE[$i]."</option>";
    $tpl->Assign( '_month_options', $month_options );
    //годы
    for($i=$this->year_first;$i<=$this->year_last;$i++)
      $year_options .= "<option value='$i' ".( $i==$this->year ? "selected='true'" : '' ).">$i</option>";
    $tpl->Assign( '_year_options', $year_options );
    $tpl->Parse( $this->template_calendar, '__calendar' );
    
    //постраничный рубрикатор
    $rh->UseClass('Arrows');
    $this->arrows = new Arrows( $rh );
    $this->arrows->outpice = $this->config->outpice ? $this->config->outpice : 10;
    $this->arrows->mega_outpice = $this->config->mega_outpice ? $this->config->mega_outpice : 10;
    $this->arrows->Setup( $this->table_name, $this->where.( $this->where ? ' AND ' : '').$this->date_where );
    $this->arrows->Set($this->state);
    $this->arrows->href_suffix = $__href_suffix;
    $this->arrows->Restore();
    if( $this->arrows->mega_sum > 1 ){
      $this->arrows->Parse('arrows.html','__links_all');
      $tpl->Parse( $this->arrows_template, '__arrows' );
    }
    $this->_href_template .= $this->arrows->State();
    
    //ссылка на новое
//    
//    $tpl->Parse( $this->template_new, '__add_new' );
    
    //ссылка на новое
    if( !$this->config->HIDE_CONTROLS['add_new'] ){
	    $this->_add_new_href = $this->url.'?'.$this->state->State(0,array( $this->id_get_var ));
	    $tpl->Assign( '_add_new_href', $this->_add_new_href );
//	    $tpl->Assign( '_add_new_href', $this->_href_template );
	    $tpl->Assign( '_add_new_title', $this->config->add_new_title ? $this->config->add_new_title : 'создать новый элемент' );
	
	    $tpl->Parse( $this->template_new, '__add_new' );
    }
    
    //по этапу
    ListSimple::Handle();
  }
  
  function Load()
  {
    ListSimple::Load($this->date_where);
  }
  
  function DateFilter(){
    $rh =& $this->rh;
    //самый первый год из заведЄнных
    //$rs = $rh->db->SelectLimit("SELECT year FROM ".$this->table_name." WHERE _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY year ASC",1);
    $rs = $rh->db->queryOne("SELECT year FROM ".$this->table_name." WHERE _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY year ASC");
    if($rs)
      $this->year_first = $rs['year'];
    else
      $this->year_first = date('Y');
    //самый последний год из заведЄнных
    //$rs = $rh->db->SelectLimit("SELECT year FROM ".$this->table_name." WHERE _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY year DESC",1);
    $rs = $rh->db->queryOne("SELECT year FROM ".$this->table_name." WHERE _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY year DESC");
    if($rs)
      $this->year_last = $rs['year'];
    else
      $this->year_last = date('Y');
    //текущие год и мес€ц
    $this->year = $rh->GetVar('year','integer');
    $this->month = $rh->GetVar('month','integer');
    if(!$this->year) $this->year = $this->year_last;
    if(!$this->month){
      //самый последний мес€ц из указанного года
      //$rs = $rh->db->SelectLimit("SELECT month FROM ".$this->table_name." WHERE year='".$this->year."' AND _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY month DESC",1);
      $rs = $rh->db->queryOne("SELECT month FROM ".$this->table_name." WHERE year='".$this->year."' AND _state<=1 ".($this->config->where ? " AND ".$this->config->where : "" )." ORDER BY month DESC");
      if($rs)
        $this->month = $rs['month'];
      else
        $this->month = date('n');
    }
    $this->date_where = "year='".$this->year."' AND month='".$this->month."'";
    $this->state->Set('year',$this->year);
    $this->state->Set('month',$this->month);
  }
}
  
?>