<?
/*
   Arrows (  &$rh, $table='', $where='' )  -- Класс для орагнизации страничной прокрутки
      - $rh -- ссылка на $rh
      - $table, $where -- см. Setup($table,$where)
  
  ---------
  * Setup($table,$where='') -- привязка таблицы и вычисление статисик
      - $table -- имя таблицы
      - $where -- условие на выборку
  
  * Restore() -- восстанавливает переменную $start из $rh->GLOBALS, проверяет на корректность
  
  * Limit() -- возвращает массив значения для LIMIT вхождения в sql, array( pos, length )
  
  * Parse( $template, $store_to, $append ) -- возвращает HTML-код листалки согласно заданным мини-шаблонам
      !! при случае переделать под вызов шаблонов OK
  
=============================================================== v.2 (Zharik)
*/

class Arrows extends StateSet {

  var $rh;
  var $configured = 0;

  //arrows stats
  var $start = -1;
  var $sum = 0;
  var $mega_sum = 0;
  var $outpice = 1;
  var $mega_outpice = 10;
  
  //view stats
  var $prefix = '';
  var $linksall_mode = 1;
  var $href_prefix = '?';
  var $href_suffix = '';
  
  var $table = '';
  var $where = '';
  
  function Arrows( &$rh, $table='', $where=''){
    $this->rh =& $rh;
    if($table!='') $this->Setup($table,$where);
  }
  
  function Setup($table,$where){
    $this->where = $where;
    $this->table = $table;
    $where = ($where!="")? "WHERE ".$where : "";
    $query = "SELECT count(*) as sum FROM $table $where";    
    $res = $this->rh->db->query($query);
    $this->SetupSum(intval($res[0]['sum']));
  }

  function SetupSum($sum){
    if(!is_numeric($sum)){
      $this->rh->debug->trace_r($sum);
      $this->rh->debug->error("Arrows::SetupSum() - \$sum is not a number.");
    }
    $this->sum = $sum;
    $this->mega_sum = ceil($this->sum/$this->outpice);
    if($this->start<0) $this->start = 0;
    $this->configured = true; 
  }
  
  function Restore(){
    if(!$this->configured) return FALSE;
/*    $i = 0;
    foreach($this->values as $k=>$v) $str2 .= "\$this->values[".$k."] = \$GLOBALS[\"".$this_prefix."_".$k."\"];";
    eval($str2);*/
    $this->start = $this->rh->GetVar($this->prefix.'p','integer');
    if($this->start<0) $this->start = 0;
    if($this->start*$this->outpice >= $this->sum) $this->start = ceil($this->sum/$this->outpice)-1;
    $this->Set($this->prefix.'p',$this->start);
    return TRUE;
  }
  
  function Limit(){
    if(!$this->configured) return "";
    return array( ($this->start*$this->outpice) , $this->outpice );
//    return "LIMIT ".($this->start*$this->outpice).",".$this->outpice;
  }
  
  //only GET version, for a while.
  function Parse( $template, $store_to='', $append=false ){
    if(!$this->configured) return '';
    $tpl =& $this->rh->tpl;
    
    $mega_start = (integer)($this->start/$this->mega_outpice);
    
    $_start = $this->Get($this->prefix."p");
    $this->Free($this->prefix.'p');
    
    //down href
    if($mega_start>0){
      $tpl->Assign( '_href', $this->href_prefix.$this->State()."&".$this->prefix."p=".($mega_start*$this->mega_outpice - 1) );
      $tpl->Parse( $template.':down', '_down' );
    }else $tpl->Parse( $template.':down_plain', '_down' );
    
    //items
    $template_item = $template.':item';
    $template_sep = $template.':sep';
    for($i=0;$i<$this->mega_outpice && ($mega_start*$this->mega_outpice+$i)<$this->mega_sum;$i++){
      //stats
      $tmp = $mega_start*$this->mega_outpice + $i;
      $a = ($tmp)*$this->outpice + 1;
      $b = $a + $this->outpice - 1; 
      $tstr = ($this->linksall_mode)? $tmp+1 : $a."-".$b;           
      //parse
      $tpl->Assign( '_string', $this->linksall_mode==1 ? $tmp+1 : $a.'-'.$b );
      if($mega_start*$this->mega_outpice + $i != $this->start){
        $tpl->Assign( '_href', $this->href_prefix.$this->State().$this->prefix.'p='.($mega_start*$this->mega_outpice+$i).$this->href_suffix );
        $tpl->Parse( $template_item, '_items', true );
      }else $tpl->Parse( $template_item.'_sel', '_items', true );
      //separator
      if( ($i< ($this->mega_outpice-1)) && (($mega_start*$this->mega_outpice+$i) < ($this->mega_sum-1)) )
        $tpl->Parse( $template_sep, '_items', true );
    }
    
    //up href
    if( ($mega_start+1)*$this->mega_outpice<$this->mega_sum ){
      $tpl->Assign( '_href', $this->href_prefix.$this->State()."&".$this->prefix."p=".(($mega_start + 1)*$this->mega_outpice) );
      $tpl->Parse( $template.':up', '_up' );
    }else $tpl->Parse( $template.':up_plain', '_up' );
    
    //сохраняем состояние
    $this->Set($this->prefix."p",$_start);
    
    //парсим и возвращаем результат
    return $tpl->Parse( $template, $store_to, $append );
  }
}

?>