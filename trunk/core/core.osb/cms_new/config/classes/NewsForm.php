<?

/*
CREATE TABLE [project_name]_news (
  id int(11) NOT NULL auto_increment,
  inserted datetime NOT NULL default '0000-00-00 00:00:00',
  title varchar(255) NOT NULL default '',
  lid text NOT NULL,
  text text NOT NULL,
  year int(11) NOT NULL default '0',
  month int(11) NOT NULL default '0',
  _state tinyint(1) NOT NULL default '0',
  _modified timestamp(14) NOT NULL,
  _created timestamp(14) NOT NULL,
  _order int(11) NOT NULL default '0',
  title_pre varchar(255) NOT NULL default '',
  lid_pre text NOT NULL,
  text_pre text NOT NULL,
  PRIMARY KEY (id),
  KEY _state(_state),
  KEY _order(_order),
  KEY year(year,month),
  KEY inserted(inserted),
  KEY _created(_created),
  KEY title(title)
) TYPE=MyISAM;
*/
  
  $this->UseClass("FormFiles",1);
  
class NewsForm extends FormFiles  {
  
  function Handle(){
    
    //заполнение даты для новой позиции
    $this->Load();
    if( !$this->id )
      $this->rh->tpl->Assign('__inserted', date('Y-m-d H:i:s') );
    
    //запоминаем фильтр по дате
    $this->state->Keep('month','integer');
    $this->state->Keep('year','integer');
    
    //по этапу
    FormFiles::Handle();
  }
  
  function Update(){
    if( FormFiles::Update() ){
      $rh =& $this->rh;
      //пререндерим year и month
      $time = strtotime($rh->GetVar( $this->prefix.'inserted' ));
      $rh->db->execute("UPDATE ".$this->config->table_name." SET year='".date('Y',$time)."',month='".date('n',$time)."' WHERE id='".$this->id."'");
      return true;
    }
  }
  
  function AddNew(){
    //запись в крон-таблицу
    $db =& $this->rh->db;
    $table_name = $this->rh->project_name.'_htcron';
    $command = 'http://'.$this->rh->host_name.$this->rh->front_end->path_rel.'send_news';
    $rs = $db->execute("SELECT id FROM $table_name WHERE command='$command'");
    if(!$rs)
      $db->execute("INSERT INTO $table_name(spec,command,last_news) VALUES('* * * * * *','$command','".(time() - 10)."')");
    //по этапу
    return FormFiles::AddNew();
  }
  
}
  
?>