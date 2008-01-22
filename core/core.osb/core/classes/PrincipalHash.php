<?
  $this->UseClass('Principal');
  
class PrincipalHash extends Principal {
  
  var $USERS = array(
    0 => array( 'id'=>0, 'role_id'=>ROLE_GUEST, 'login'=>'guest' ),
    1 => array( 'id'=>1, 'role_id'=>ROLE_GOD, 'login'=>'god', 'password'=>'qwe' ),
  );
  var $cookie_prefix = '';
  
  function PrincipalHash(&$rh)
  {
    Principal::Principal($rh);
    $this->cookie_prefix = $rh->project_name.'_';
    $prp =& $this;
    
    include( $rh->FindScript('handlers','_prp') );
  }
  
  function GetById($id){
    if( isset($this->USERS[$id]) )
      return $this->USERS[$id];
    else return false;
  }
  
  function GetByLogin($login)
  {
    //ищем
    foreach($this->USERS as $i=>$user)
      if( $user['login']==$login )
        return $user;
    //ничего не нашли
    return false;
  }
  
  //сохраняем в куках
  function SessionStore()
  {
    $rh =& $this->rh;
    $this->rh->debug->Trace("PrincipalHash::SessionStore()");
//    setcookie( $this->cookie_prefix.'_usr', $this->user['login'] );
//    setcookie( $this->cookie_prefix.'_pwd', $this->user['password'] );
    setcookie( $this->cookie_prefix.'_usr', $this->user['login'], 0, '/' );
    setcookie( $this->cookie_prefix.'_pwd', $this->user['password'], 0, '/' );
  }
  
  //восстанавливаем из сессии
  function SessionRestore(){
    $this->rh->debug->Trace("PrincipalHash::SessionRestore()");
    return $this->Login( $_COOKIE[$this->cookie_prefix.'_usr'], $_COOKIE[$this->cookie_prefix.'_pwd'] );
  }
  
  //убиваем куки
  function SessionDestroy(){
    $rh =& $this->rh;
    $this->rh->debug->Trace("PrincipalHash::SessionDestroy()");
//    setcookie( $this->cookie_prefix.'_usr', '' );
//    setcookie( $this->cookie_prefix.'_pwd', '' );
    setcookie( $this->cookie_prefix.'_usr', '', 0, '/' );
    setcookie( $this->cookie_prefix.'_pwd', '', 0, '/' );
  }
}
  
?>