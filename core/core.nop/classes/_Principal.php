<?php
/*

  ���������� �� ������ �������� ������ � ������������:
  * �����/������/������������ �����
  * �������� ���������� � ������� ������������
  * ����������� ������ ������� ��������� "�������� �������" 

  Principal( &$rh, $storage_model="profiles", $security_models = "noguests" )
      - $rh              -- ������ �� RH, ��� ������
      - $storage_model   -- ������ ��������� �������� (������� PrincipalStorage)
      - $security_models -- ������ ��� ������ �������� ������� (������� PrincipalSecurity)

  -------------------

  // �����/������

  * Identify( $redirect=PRINCIPAL_REDIRECT, $_skip_cookies=false ) -- 
                               ������� ������, ����� �� �� ���������������� ���������� 
                               � ������� ������. �������� � ������� ������.
      - $redirect=1 -- � ������ ������ ��� ������ �������� �� �� �� ��������
      - $_skip_cookies=false -- ��� ����������� �������������, ����� �������������� ������ ��� ������� ���
      - �� �� ���� � ����� � �����
          * � ����� ����: "_principal_login", "_principal_password", 
                          "_principal_permanent_login", "_principal_permanent_password"
          * � ����� ����: "_principal_stored_login", "_principal_stored_password"
      - �������� �������� ��� ���������:
          * PRINCIPAL_RESTORED
          * PRINCIPAL_AUTH
          * PRINCIPAL_WRONG_PWD
          * PRINCIPAL_WRONG_LOGIN
          * PRINCIPAL_WRONG_COOKIE
          * PRINCIPAL_NO_CREDENTIALS
  * Login( $login="", $realm="", $pwd="", $stored=0, $store_to_session=PRINCIPAL_NO_SESSION ) -- ����� ����������
      - $login, $realm, $pwd -- credentials
      - $stored              -- ���� true, �� ������ ������������� ��� "���������� �� ���"
      - $store_to_session    -- ��������� �� ��������� ������ � ������ ��� ���������� "�������������"
      - �������� �������� ��� ���������:
          * PRINCIPAL_AUTH
          * PRINCIPAL_WRONG_PWD
          * PRINCIPAL_WRONG_LOGIN
  * Logout( $redirect=PRINCIPAL_REDIRECT, $url=NULL ) -- ��������� ��� �������� ����������
      - $redirect=1 -- �������� �� �� �� ��������, ����� ���������� true
      - $url=NULL   -- ���� �� NULL, �� �����������. $url ������ ���� ����������, �.�. ����������� $ri->Href()

  * GetStoredLogin() -- ������ �� ��� "���������� ���� �����"

  // "���������" ������. ��� ��������, ����� ��� ����� �������� ��������� � ������ ����.
     �� ������� ���� ������������ �� ������� �������. ������ ������� � �������

  * _Cheat( $login, $realm="" ) -- ��� ����������� �� ������ ���������������� ��� ������������
  * _UnCheat()      -- ������������ ������� � ����������� ���������

  // ������ ������� ������ ����������?

  * Guest( $profile="guest" ) -- ������������� "������" ��� �������/������ ������. 
      - $profile ��������� �� ��� �����, ������� ����� � ���������� ��������� ����� "principal_profiles"
  * LoadById($id), LoadByLogin($login, $realm="") -- ��������� ��������� ���������� �� "���������", 
                                                     ��������� �. ��� �������� �����������.

  // ������ � ������� (����������)
  * _Store(), _Restore() -- ������ � �������������� ������ � ���������� � ������. 
                            ��� ���� "cheated" ��������� � ������ �� ������������ � �� �����������������.
  * _StoreReset()        -- �������� ���������� � ������ ���������

  // ��������� ��������� � ����������� ����������� ������
  * _CheckStoredPassword( $stored_password, $stored_invariant ) -- ���������, �������� �� ������, 
                                                                   ������ �� ��������� ��������
       - $stored_password  -- ������, ���������� "�� ���"
       - $stored_invariant -- �������� � ������� ���������� ��������� ������
       - ���������� �� Login
  * _GenerateStoredPassword( $stored_invariant ) -- ���������� ������ ��� ����������, ������ �� ��������� ��������
       - $stored_invariant -- �������� � ������� ���������� ��������� ������
       - ���������� �� Identify

  * InvalidateStoredPassword() -- ����������� ������ (���������� ����� ��������� �� ������ �������� �������, 
                                  ���������� ��� � ������� ����� ���������� �������)
                                  ����� ������ ������ �� ��� �����������, ����������� �� $this->data
  * _GenerateInvariant( $user_data ) -- ���������� ����� ��������� ������, ���������� �� �����������

  // ������ � �������� �������
  * Security( $model, $params="" ) -- ���� �� ������ � ������ ������� �������� ������ �������
       - $model -- ������-������� PrincipalSecurity
       - $params = array( key => value )


================================================================== v.0 (kuso@npj)
*/
define( "PRINCIPAL_RESTORED",       -1  );
define( "PRINCIPAL_AUTH",            0  );
define( "PRINCIPAL_WRONG_LOGIN",     1  );
define( "PRINCIPAL_WRONG_PWD",       2  );
define( "PRINCIPAL_WRONG_COOKIE",    3  );
define( "PRINCIPAL_NO_CREDENTIALS",  4  );
define( "PRINCIPAL_OLD_SESSION",    13  );

define( "PRINCIPAL_NO_REDIRECT",  0  );
define( "PRINCIPAL_REDIRECT",     1  );

define( "PRINCIPAL_NO_SESSION",     0  );
define( "PRINCIPAL_STORE",          1  );

class Principal
{
   var $data = array( "login" => "!", ); // ���� ����� = "!", ������ ��������� ������ ������ � �� �����������������
   var $_cheat_stack = array();

   function Principal( &$rh, $storage_model="profiles", $security_models = "noguests" )
   {
     if ($storage_model == "")   $$storage_model  = "profiles";
     if ($security_models == "") $security_models = "noguests";

     $this->rh = &$rh;

     //. ��������� ������ ������
     $this->rh->UseClass("PrincipalStorage");
     $this->storage_model =& PrincipalStorage::Factory( $this, $storage_model );  

     //. ��������� ������ ������������
     $this->rh->UseClass("PrincipalSecurity");
     $this->security_models = array();
     if (!is_array($security_models)) $security_models = array( $security_models );
     foreach( $security_models as $model )
       $this->security_models[ $model ] =& PrincipalSecurity::Factory( $this, $model ); 
    
   }

   // ������ � ������������ �������
   // -- ���������, �������� �� ������, ������ �� ��������� ��������
   function _CheckStoredPassword( $stored_password, $stored_invariant ) 
   {
     $gen_md5 = $this->_GenerateStoredPassword( $stored_invariant );
     if ($gen_md5 == $stored_password) return true;
     else return false;
   }
   // -- ���������� ������ ��� ����������, ������ �� ��������� ��������
   function _GenerateStoredPassword( $stored_invariant )
   {
     $gen = $stored_invariant.$this->rh->magic_word;
     return md5($gen);
   }
   // -- ���������� ����� ��������� ������, ���������� �� �����������
   function _GenerateInvariant( $user_data ) 
   {
     $invariant = $user_data["login"].date("Ymdhis");
     return $invariant;
   }
   // -- ����������� ������ (���������� ����� ��������� �� ������ �������� �������, ���������� ��� � ������� ����� ���������� �������)
   function InvalidateStoredPassword() 
   {
     if ($this->data["login"] == "!") return false;        // no principal @ all
     if ($this->data["guest_profile"] != "") return false; // guest principal
     return $this->storage_model->SetStoredPassword( $this->data, $this->_GenerateInvariant( $this->data ) );
   }

   function GetStoredLogin()
   {
      return $_COOKIE["_principal_stored_login"];
   }

   // -- ������������� �� �������
   function Identify( $redirect=PRINCIPAL_REDIRECT, $_skip_cookies = false )
   {
     // ����������� ����� �� ������ ����������
     $status = PRINCIPAL_NO_CREDENTIALS;

     // 1. ������� �� �����?
     $login = $realm = $pwd = "";
     $stored = 0;
     if (!$_skip_cookies)
     if (!isset($_COOKIE[$this->rh->cookie_prefix."_principal_auth"]))
     if (isset($_COOKIE[$this->rh->cookie_prefix."_principal_stored_password"]))
     {
       $login  = $_COOKIE[$this->rh->cookie_prefix."_principal_stored_login"];
       $realm  = "";
       $pwd    = $_COOKIE[$this->rh->cookie_prefix."_principal_stored_password"];
       $stored = 1;
     }

     // 2. ����� �� �����
     if (!$stored && isset($_POST["_principal_login"]))
     {                                                                
       $login  = $_POST["_principal_login"];
       $realm  = "";
       $pwd    = $_POST["_principal_password"];
       $stored = 0;
     }

     // 3. login (and store to session)
     $status = $this->Login( $login, $realm, $pwd, $stored, PRINCIPAL_STORE ); 

     // ���� �����?
     if ($status == PRINCIPAL_AUTH)
     {
       //. �������� ���� ������������� ������
       if ($_POST["_principal_permanent_login"])
         $this->_WritePermanentCookieLogin( $this->data["login"] );
       //. �������� ���� ������������� ������
       if ($_POST["_principal_permanent_password"])
         $this->_WritePermanentCookiePassword( $this->data["stored_invariant"] );

       if ($redirect)
         $this->rh->Redirect( $this->rh->ri->Href( $this->rh->ri->url, STATE_USE ) );
     }
     else
     if (!$_skip_cookies) return $this->Identify( $redirect, true );

     // ���� �� ����� -- ������� �����������
     if ($status != PRINCIPAL_AUTH)
     {
       $restoral = $this->_Restore();
       if ($restoral === PRINCIPAL_OLD_SESSION)
       {
         // ���������� ���� ����������� ������������, ������ ����
         setcookie( $this->rh->cookie_prefix."_principal_auth", "", time()-3600, "/", $this->rh->cookie_domain ); 
         unset($_COOKIE[$this->rh->cookie_prefix."_principal_auth"]);
         return $this->Identify( $redirect ); // ?????? possible recursion !
       }
       else
        if ($restoral) return PRINCIPAL_RESTORED;
        else return $status;
     }
   }

   function _WritePermanentCookieLogin( $login )
   {
      setcookie( $this->rh->cookie_prefix."_principal_stored_login", $login,
                 time()+$this->rh->cookie_expire_days*24*3600 , 
                 "/", $this->rh->cookie_domain ); 
   }
   function _WritePermanentCookiePassword( $password_invariant )
   {
      setcookie( $this->rh->cookie_prefix."_principal_stored_password", 
                 $this->_GenerateStoredPassword( $password_invariant ),
                 time()+$this->rh->cookie_expire_days*24*3600 , 
                 "/", $this->rh->cookie_domain ); 
   }

   // -- ����� ����������
   function Login( $login="", $realm="", $pwd="", $stored=0, $store_to_session=PRINCIPAL_NO_SESSION ) 
   {
     // �������� ������ ����������
     $user_data = $this->LoadByLogin( $login, $realm );
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;
     // ��������� ������
     if ($stored == 0)
     {
       $_pwd = md5($pwd);
       if ($_pwd != $user_data["password"]) return PRINCIPAL_WRONG_PWD;
     }
     else
     {
       if (!$this->_CheckStoredPassword( $pwd, $user_data["stored_invariant"] )) return PRINCIPAL_WRONG_COOKIE;
     }

     // ����� ������!
     $this->data = $user_data;
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );

     // ��������� ���� ���������� ������
     $dt = time();
     $this->data["last_login_datetime"] = $this->data["login_datetime"]; // ����� ����������� ������ for sure
     $this->data["login_datetime"] = date( "Y-m-d H:i:s", $dt );
     $this->storage_model->SetLoginDatetime( $this->data, $dt );

     // ���������� ��� � ������
     if ($store_to_session) $this->_Store();
     return PRINCIPAL_AUTH;
   }
   // -- ��������� ��� �������� ����������
   function Logout( $redirect=PRINCIPAL_REDIRECT, $url=NULL )
   {
     $this->_StoreReset();
     if ($redirect)
       $this->rh->Redirect( isset($url) ? $url : $this->rh->ri->Href( $this->rh->ri->url, STATE_USE ) );
     return $this->Guest();
   }

   // -- ��� ����������� �� ������ ���������������� ��� ������������
   function _Cheat( $login, $realm="" ) 
   {
     $user_data = $this->LoadByLogin( $login, $realm );
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;

     $this->_cheat_stack[] = $this->data;
     $this->data = $user_data;
     // ��� �� ��������� "�����" 
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );
     // �� � ������ ������ �� �����
     return PRINCIPAL_AUTH;
   }
   // -- ������������ ������� � ����������� ���������
   function _UnCheat() 
   {
     $this->data = array_pop( $this->_cheat_stack );

     // ��� �� ��������� "�����"
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );
     // �� � ������ ������ �� �����
   }

   // -- ��������� ��������� ���������� �� "���������" 
   function LoadById($id) 
   { return $this->storage_model->LoadById( $id ); }
   function LoadByLogin($login, $realm="") 
   { return $this->storage_model->LoadByLogin( $login, $realm ); }

   // ������ � ������� (����������)
   function _StoreReset()
   {
      if (!$_COOKIE[$this->rh->cookie_prefix."_principal_auth"]) return false;

      // ������ �������� ������.
      if (!session_id()) session_start();

      setcookie( $this->rh->cookie_prefix."_principal_auth", "", time()-3600, "/", $this->rh->cookie_domain ); 
      unset($_SESSION[$this->rh->cookie_prefix."principal"]);

      // ������ ����� � ���� �����������
      setcookie( $this->rh->cookie_prefix."_principal_stored_password", "", time()-3600, "/", $this->rh->cookie_domain ); 

      return true;
   }
   function _Store()
   {
      // ������ �������� ������.
      if (!session_id()) session_start();
      
      setcookie( $this->rh->cookie_prefix."_principal_auth", 1, 0, "/", $this->rh->cookie_domain ); 
      $_SESSION[$this->rh->cookie_prefix."principal"] = $this->data;
      return true;
   }
   function _Restore()
   {
      if (!isset($_COOKIE[$this->rh->cookie_prefix."_principal_auth"])) return false;

      // ������ �������� ������.
      if (!session_id()) session_start();

      if (isset($_SESSION[$this->rh->cookie_prefix."principal"]))
      {
        $this->data = $_SESSION[$this->rh->cookie_prefix."principal"];

        foreach( $this->security_models as $model=>$v )
          $this->security_models[ $model ]->OnRestore( $this->data );
      }
      else
      {
        // ������ ������, ���� ������������ ���������. ׸ ������ ���?
        return PRINCIPAL_OLD_SESSION;
      }

      return true;
   }

   // -- �������������� ����������� �������� �������� ��� ������� �������, ���������������� ������������
   function Guest( $profile = "guest" )
   {
     // find script or return
     $file_source = $this->rh->FindScript( "principal_profiles", $profile, false, -1 );
     if ($file_source === false) return false;

     // uplink
     include( $file_source );
     $this->data = $included_profile;
     $this->data["guest_profile"] = $profile;

     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnGuest( $this->data );

     return true;
   }

   // -- ���� �� ������ � ������ ������� �������� ������ �������
   function Security( $model, $params="" ) 
   {
     if (!$this->security_models[$model]) return DENIED;
     else return $this->security_models[$model]->Check( $this->data, $params );
   }




// EOC{ Principal }
}

?>