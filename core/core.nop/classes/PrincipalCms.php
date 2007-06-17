<?
  //системные роли
  define("ROLE_GUEST",0);
  define("ROLE_GOD",1);
  define("ROLE_ADMIN",2);
  define("ROLE_USER",3);
  
  // states
  define ("PRINCIPAL_UNKNOWN", 0);
  define ("PRINCIPAL_WRONG_LOGIN", 1);
  define ("PRINCIPAL_WRONG_PWD", 2);
  define ("PRINCIPAL_NOT_ALIVE", 3);
  define ("PRINCIPAL_AUTH", 4);
  define ("PRINCIPAL_RESTORED", 5);
  define ("PRINCIPAL_ACL_NEGATIVE", 6);
  define ("PRINCIPAL_ACL_GRANTED", 7);
  define ("PRINCIPAL_ACL_NOT_FOUND", 8);
  
  
class PrincipalCms {
  
  var $rh; //ссылка на $rh
  var $user; //хэш текущего пользователя
  //массив записей прав доступа [локация]=>[массив ролей]
  var $ACL = array(
      '*' => array( ROLE_GUEST ),
    );
  var $acl_default = true; //флаг инициализации ACL
  var $state; //константа состояния авторизовации
  var $granted_state; //константа состояния проверки доступа
  var $is_granted_default = true; //есть ли доступ по умолчанию?
  
  var $input_name_login = 'login';
  var $input_name_password = 'password';
  
  //человеческие названия ролей
  var $ROLES = array(
    ROLE_GUEST => 'гость',
    ROLE_GOD => 'бог',
    ROLE_ADMIN => 'администратор',
    ROLE_USER => 'пользователь',
  );
  
  //for toolbar
  var $ADMIN_ROLES = array( ROLE_GOD, ROLE_ADMIN ); //для каких ролей показывать тулбар
  
  function PrincipalCms(&$rh)
  {
    $this->rh =& $rh;
    $this->user = array();//$this->GetByID(0);
    $this->state = PRINCIPAL_UNKNOWN;
  }
  
  //перегружать, перегружать и ещё раз перегружать
  function SessionStore(){}
  function SessionReStore(){ return false;}
  function SessionDestroy(){}
  
  //восстанавливаемся из сессии
  //или пытаемся залогиниться
  function Authorise()
  {
    $this->rh->debug->Trace("Principal::Authorise() - ...");
    
    //пытаемемся восстановить из сессии
    if( $this->SessionRestore() )
    {
      $this->state = PRINCIPAL_RESTORED;
      return true;
    }
    //пытаемся авторизоваться как в первый раз
    return $this->Login( $_POST[$this->input_name_login], $_POST[$this->input_name_password] );
  }
  
  //пытаемся залогиниться
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
    if( $this->user['password']!=$password )
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
  
  function Logout( $redirect='' ){
    $this->SessionDestroy();
    if( $redirect )
      $this->rh->Redirect($redirect);
  }
  
  //перегружать
  function GetByID($id){
    return array( 'id'=>0, 'role_id'=>ROLE_GUEST, 'login'=>'guest' );
  }
  
  function GetByLogin($login){
    return $this->GetById(0);
  }
  
  function getUserRole()
  {
     return (integer)$this->user['role_id'];
  }
  
  function IsGrantedTo( $location )
  {
    
    $ACL =& $this->ACL;
    $N = count($A);
    
    $this->rh->debug->Trace("Principal::IsGrantedTo() - location = [$location] ...");
    
    //бежим по всему ACL
    $granted = false;

    /* способ каким берется роль пользователя теперь можно перегружать */
    $str = $this->getUserRole();
    $str1 = '!'.$this->getUserRole();

    $this->rh->debug->Trace("Principal::IsGrantedTo() - ищем [$str] [$str1]");
    
    
    foreach($ACL as $loc=>$roles)
    {
      //наша строка?
      
      $OK = ($loc=='*' || $loc==$location);
      if( !$OK ){
        $n = strlen($loc)-1;
        $OK = ($loc[$n]=='*' && substr($location,0,$n)==substr($loc,0,$n));
      }
      //проверяем права
      if( $OK )
      {
//        $this->rh->debug->Trace("Principal::IsGrantedTo() - строка $loc => [".implode(',',$roles)."]");
        //нашли строку с запретом?
        if( in_array($str1,$roles,true) )
        {
          
          $this->granted_state = PRINCIPAL_ACL_NEGATIVE;
          $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='red'>denied</font>");
          return false;
        }

//$this->rh->debug->trace_r($roles);
//$this->rh->debug->trace($str);
        //нашли строку, которая позволяет?
        if( in_array($str,$roles,true) )
        {
           
          $granted = true;
          $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='green'>granted</font>");
        }
      }else
        $this->rh->debug->Trace("<font color='grey'>Principal::IsGrantedTo() - строка loc = $loc</font>");
//      $this->rh->debug->Trace("Principal::IsGrantedTo() - ***");
    }
    //в конце концов, нашли строку, которая позволяет?
    if( $granted ){
      $this->granted_state = PRINCIPAL_ACL_GRANTED;
      $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='green'>granted</font>");
      return true;
    }
    
    //ничего не нашли
    $this->granted_state = PRINCIPAL_ACL_NOT_FOUND;
    $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='red'>not found</font>");
    return $this->is_granted_default;
  }
  
  function IsAuth(){
    return $this->state==PRINCIPAL_AUTH || $this->state==PRINCIPAL_RESTORED;
  }
  
} 
?>