<?
  $this->UseClass('Principal');
  
class PrincipalDB extends Principal 
{
  
  var $users_table = 'cms_users'; //нужно задавать явно в _prp.php
  var $sessions_table = 'cms_sessions'; //нужно задавать явно в _prp.php
  var $users_where = '_state=0';
  
  var $cookie_prefix = '';
  var $session = 0; //хэщ сессии
  
  var $USERS = array(); //массив юзерей изначально пуст

  var $id_field="id";
  var $SELECT_FIELDS = array("id","role_id","login","password");
  
  function PrincipalDB(&$rh)
  {
    Principal::Principal($rh);
    $this->cookie_prefix = $rh->project_name.'_';
    $prp =& $this;
    include( $rh->FindScript('handlers','_prp') );
  }
  
  /*** изымание юзера из БД ***/
  
  function _GetBy($where){
    $this->rh->debug->Trace("PrincipalDB::_GetBy() - [$where] ...");
    $db =& $this->rh->db;
    $rs = $db->execute('SELECT '.implode(",",$this->SELECT_FIELDS).' FROM '.$this->users_table.' WHERE '.$this->users_where.' AND '.$where);
    $user = $rs->fields;
    if( $user[$this->id_field] )
    {
      $this->rh->debug->Trace("PrincipalDB::_GetBy() - OK");
      $this->rh->debug->Trace_R($user,0,'PrincipalDB::user');
      $this->USERS[ $user[$this->id_field] ] = $user;
      return $user;
    }
    else
    {
      $this->rh->debug->Trace("PrincipalDB::_GetBy() - не найден");
      return false;
    }
  }
  
  function GetById($id)
  {
    return $this->_GetBy($this->id_field.'='.((integer)$id));
  }
  
  function GetByLogin($login){
    return $this->_GetBy("login='".$login."'");
  }
  
  /*** работа с сессиями ***/
  
  function _Session(){
    $this->rh->debug->Trace("PrincipalDB::_Session() - ...");
    $db =& $this->rh->db;
    if( !$this->session['id'] )
    {
      $this->rh->debug->Trace("PrincipalDB::_Session() - сессии пока нет");
      //прибиваем старые сессии - брошенные на час и больше
      $db->execute('DELETE FROM '.$this->sessions_table.' WHERE time<'.(time()-3600));
      //пытаемся загрузить сессию
      $rs = $db->execute('SELECT * FROM '.$this->sessions_table.' WHERE id='.((integer)$_COOKIE[$this->cookie_prefix.'_sessid']));
      if( !$rs->EOF ){
        //помечаем текущую сессию как используемую
        $session = $rs->fields;
        $db->execute('UPDATE '.$this->sessions_table.' SET time='.time()." WHERE id='".$session['id']."'");
        $this->rh->debug->Trace("PrincipalDB::_Session() - восстановлена через куки [".$session['id']."]");
      }else{
        //нужна новая сессия
        //генерим sessid
        do{
          $sessid = rand(1,1000000);
          $rs = $db->execute('SELECT id FROM '.$this->sessions_table.' WHERE id='.$sessid);
        }while($rs->fields['id']);
        //вставляем запись
        $ip = ($_SERVER["HTTP_X_FORWARDED_FOR"]!="") ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $db->execute('INSERT DELAYED INTO '.$this->sessions_table.'(id,ip,time) VALUES('.$sessid.',\''.$ip.'\','.time().')');
        //грузим новую запись
        $rs = $db->execute('SELECT * FROM '.$this->sessions_table.' WHERE id='.$sessid);
        $session = $rs->fields;
        $this->rh->debug->Trace("PrincipalDB::_Session() - вставили новую [".$sessid."]");
      }
      //сохраняем sessid
      $this->session = $session;
      setcookie($this->cookie_prefix.'_sessid',$session['id'],0,"/");
    }
    $this->rh->debug->Trace_R($this->session,0,'PrincipalDB::session');
  }
  
  //сохраняем в сессию
  function SessionStore(){
    $this->rh->debug->Trace("PrincipalDB::SessionStore()");
    $this->_Session();
    $this->rh->db->execute('UPDATE '.$this->sessions_table." SET user_id='".$this->user[$this->id_field]."' WHERE id='".$this->session['id']."'");
    $this->session['user_id'] = $this->user['id'];
  }
  
  //восстанавливаем из сессии
  function SessionRestore()
  {
      
    $this->rh->debug->Trace("PrincipalDB::SessionRestore()");
    $this->_Session();
    
    return $this->user = $this->GetById($this->session['user_id']);
  }
  
  //убиваем сессию
  function SessionDestroy(){
    $this->rh->debug->Trace("PrincipalDB::SessionDestroy()");
    $this->rh->db->execute('DELETE FROM '.$this->sessions_table." WHERE id='".$this->session['id']."'");
    $this->session = array();
    setcookie( $this->cookie_prefix.'_sessid', "", 0, "/" );
  }
}
  
?>