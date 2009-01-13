<?
  //��������� ����
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
  
  var $rh; //������ �� $rh
  var $user; //��� �������� ������������
  //������ ������� ���� ������� [�������]=>[������ �����]
  var $ACL = array(
      '*' => array( ROLE_GUEST ),
    );
  var $acl_default = true; //���� ������������� ACL
  var $state; //��������� ��������� �������������
  var $granted_state; //��������� ��������� �������� �������
  var $is_granted_default = true; //���� �� ������ �� ���������?
  
  var $input_name_login = 'login';
  var $input_name_password = 'password';
  
  //������������ �������� �����
  var $ROLES = array(
    ROLE_GUEST => '�����',
    ROLE_GOD => '���',
    ROLE_ADMIN => '�������������',
    ROLE_USER => '������������',
  );
  
  //for toolbar
  var $ADMIN_ROLES = array( ROLE_GOD, ROLE_ADMIN ); //��� ����� ����� ���������� ������
  
  function PrincipalCms(&$rh)
  {
    $this->rh =& $rh;
    $this->user = array();//$this->GetByID(0);
    $this->state = PRINCIPAL_UNKNOWN;
  }
  
  //�����������, ����������� � ��� ��� �����������
  function SessionStore(){}
  function SessionReStore(){ return false;}
  function SessionDestroy(){}
  
  //����������������� �� ������
  //��� �������� ������������
  function Authorise()
  {
    $this->rh->debug->Trace("Principal::Authorise() - ...");
    
    //���������� ������������ �� ������
    if( $this->SessionRestore() )
    {
      $this->state = PRINCIPAL_RESTORED;
      return true;
    }
    //�������� �������������� ��� � ������ ���
    return $this->Login( $_POST[$this->input_name_login], $_POST[$this->input_name_password] );
  }
  
  //�������� ������������
  function Login( $login, $password )
  {
    
    //�������� ��������� ������������ �� ������
    if( !($this->user = $this->GetByLogin($login)) ){
      $this->user = $this->GetByID(0);
      $this->state = PRINCIPAL_WRONG_LOGIN;
      $this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - �������� �����</font> ");
      return false;
    }

    //��������� ������ 
    if( $this->user['password']!=$password )
    {
      $this->user = $this->GetByID(0);
      $this->state = PRINCIPAL_WRONG_PWD;
      $this->rh->debug->Trace("<font color='red'>Principal::Login('$login','$password') - �������� ������</font>");
      return false;
    }
    //��������� ������������ � ������
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
  
  //�����������
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
    
    //����� �� ����� ACL
    $granted = false;

    /* ������ ����� ������� ���� ������������ ������ ����� ����������� */
    $str = $this->getUserRole();
    $str1 = '!'.$this->getUserRole();

    $this->rh->debug->Trace("Principal::IsGrantedTo() - ���� [$str] [$str1]");
    
    
    foreach($ACL as $loc=>$roles)
    {
      //���� ������?
      
      $OK = ($loc=='*' || $loc==$location);
      if( !$OK ){
        $n = strlen($loc)-1;
        $OK = ($loc[$n]=='*' && substr($location,0,$n)==substr($loc,0,$n));
      }
      //��������� �����
      if( $OK )
      {
//        $this->rh->debug->Trace("Principal::IsGrantedTo() - ������ $loc => [".implode(',',$roles)."]");
        //����� ������ � ��������?
        if( in_array($str1,$roles,true) )
        {
          
          $this->granted_state = PRINCIPAL_ACL_NEGATIVE;
          $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='red'>denied</font>");
          return false;
        }

//$this->rh->debug->trace_r($roles);
//$this->rh->debug->trace($str);
        //����� ������, ������� ���������?
        if( in_array($str,$roles,true) )
        {
           
          $granted = true;
          $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='green'>granted</font>");
        }
      }else
        $this->rh->debug->Trace("<font color='grey'>Principal::IsGrantedTo() - ������ loc = $loc</font>");
//      $this->rh->debug->Trace("Principal::IsGrantedTo() - ***");
    }
    //� ����� ������, ����� ������, ������� ���������?
    if( $granted ){
      $this->granted_state = PRINCIPAL_ACL_GRANTED;
      $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='green'>granted</font>");
      return true;
    }
    
    //������ �� �����
    $this->granted_state = PRINCIPAL_ACL_NOT_FOUND;
    $this->rh->debug->Trace("Principal::IsGrantedTo() - <font color='red'>not found</font>");
    return $this->is_granted_default;
  }
  
  function IsAuth(){
    return $this->state==PRINCIPAL_AUTH || $this->state==PRINCIPAL_RESTORED;
  }
  
} 
?>