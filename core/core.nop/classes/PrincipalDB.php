<?php
  $this->UseClass('PrincipalCms');
  
class PrincipalDB extends PrincipalCms
{
 	var $id_field="user_id";
    var $SELECT_FIELDS = array("user_id","roles","login","password");
        
  
  var $users_table = 'users';
  var $sessions_table = 'users_sessions';
  var $users_where = '_state=0';
  
  var $cookie_prefix = '';
  var $session = 0; //хэщ сессии
  
  var $USERS = array(); //массив юзерей изначально пуст

var $ROLES_REVERT = array(
        '' => ROLE_GUEST,
        'user' => ROLE_USER,
        'admin'=>ROLE_ADMIN, 
        'admin'=>ROLE_GOD,
        );
      
      var $ADMIN_ROLES = array( ROLE_GOD, ROLE_ADMIN, ROLE_USER);
        /*
         * берем id роли юзера из текстового поля в БД
         */
        function getUserRole()
        {
           return ($this->ROLES_REVERT[$this->user['roles']]);
           
        }
        
        /*
         * принципал билдера не хотел логинить с md5
         */
        function Login( $login, $password )
        {
            //пытаемся загрузить пользователя по логину
            if( !($this->user = $this->GetByLogin($login)) ){
              $this->user = $this->GetByID(0);
              $this->state = PRINCIPAL_WRONG_LOGIN;
              $this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - неверный логин</font> ");
              return false;
            }

            //проверяем пароль 
            if( $this->user['password']!=md5($password) )
            {
              $this->user = $this->GetByID(0);
              $this->state = PRINCIPAL_WRONG_PWD;
              $this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - неверный пароль</font>");
              return false;
            }


            //сохраняем пользователя в сессии
            $this->rh->debug->Trace("<font color='green'>Principal::Login('$login','$password') - OK</font>");
            $this->SessionStore();
            $this->state = PRINCIPAL_AUTH;

            return true;
        }

  function PrincipalDB(&$rh)
  {
    PrincipalCms::PrincipalCms($rh);
    $this->cookie_prefix = $rh->project_name.'_';
    $prp =& $this;    
    $this->users_table = $rh->db_prefix.$this->users_table;
    $this->sessions_table = $rh->db_prefix.$this->sessions_table;
  }
  
  /*** изымание юзера из БД ***/
  
  function _GetBy($where){
    $this->rh->debug->Trace("PrincipalDB::_GetBy() - [$where] ...");
    $db =& $this->rh->db;
    $user = $db->queryOne('SELECT '.implode(",",$this->SELECT_FIELDS).' FROM '.$this->users_table.' WHERE '.$this->users_where.' AND '.$where);

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
      $session = $db->QueryOne('SELECT * FROM '.$this->sessions_table.' WHERE id='.((integer)$_COOKIE[$this->cookie_prefix.'_sessid']));
      if( !empty($session) ){
        //помечаем текущую сессию как используемую

        $db->execute('UPDATE '.$this->sessions_table.' SET time='.time()." WHERE id='".$session['id']."'");
        $this->rh->debug->Trace("PrincipalDB::_Session() - восстановлена через куки [".$session['id']."]");
      }
      else
      {
        //нужна новая сессия
        //генерим sessid
        do
        {
          $sessid = rand(1,1000000);
          $rs = $db->queryOne('SELECT id FROM '.$this->sessions_table.' WHERE id='.$sessid);
        }while($rs['id']);
        //вставляем запись
        $ip = ($_SERVER["HTTP_X_FORWARDED_FOR"]!="") ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $db->execute('INSERT DELAYED INTO '.$this->sessions_table.'(id,ip,time) VALUES('.$sessid.',\''.$ip.'\','.time().')');
        //грузим новую запись
        $session = $db->queryOne('SELECT * FROM '.$this->sessions_table.' WHERE id='.$sessid);
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