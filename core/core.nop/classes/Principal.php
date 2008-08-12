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
      - $stored              -- ���� 1, �� ������ ������������� ��� "���������� �� ���"
                             -- ���� 2, �� ������ ������������� ��� ���������� �� GET, ������� ��� ������ ������
      - $store_to_session    -- ��������� �� ��������� ������ � ������ ��� ���������� "�������������"
      - �������� �������� ��� ���������:
          * PRINCIPAL_AUTH
          * PRINCIPAL_WRONG_PWD
          * PRINCIPAL_WRONG_LOGIN
  * Logout( $redirect=PRINCIPAL_REDIRECT, $url=NULL ) -- ��������� ��� �������� ����������
      - $redirect=1 -- �������� �� �� �� ��������, ����� ���������� true
      - $url=NULL   -- ���� �� NULL, �� �����������. $url ������ ���� ����������, �.�. ����������� $ri->Href()

  * GetStoredLogin() -- ������ �� ��� "���������� ���� �����"

  * GenerateTempPassword( $user_data ) -- ������ ����� ��������� ������
  * GenerateTempPasswordHash( $user_data ) -- ������ ��� �� ���������� ������ ��� �������� ��� �� ������

  // "���������" ������. ��� ��������, ����� ��� ����� �������� ��������� � ������ ����.
     �� ������� ���� ������������ �� ������� �������. ������ ������� � �������

  * _Cheat( $login, $realm="" ) -- ��� ����������� �� ������ ���������������� ��� ������������
  * _CheatById( $user_id )      -- same but by user_id
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
                                  ����� ������ ������ ��-��� �����������, ����������� �� $this->data
  * _GenerateInvariant( $user_data ) -- ���������� ����� ��������� ������, ���������� �� �����������

  // ������ � �������� �������
  * Security( $model, $params="" ) -- ���� �� ������ � ������ ������� �������� ������ �������
       - $model -- ������-������� PrincipalSecurity
       - $params = array( key => value )


================================================================== v.2 (kuso@npj)
*/
define( "PRINCIPAL_RESTORED",       -1  );
define( "PRINCIPAL_AUTH",            0  );
define( "PRINCIPAL_WRONG_LOGIN",     1  );
define( "PRINCIPAL_WRONG_PWD",       2  );
define( "PRINCIPAL_WRONG_COOKIE",    3  );
define( "PRINCIPAL_NO_CREDENTIALS",  4  );
define( "PRINCIPAL_TEMP_TIMEOUT",    5  );
define( "PRINCIPAL_OLD_SESSION",    13  );
define( "PRINCIPAL_NOT_IDENTIFIED",  100  );

define( "PRINCIPAL_NO_REDIRECT",  0  );
define( "PRINCIPAL_REDIRECT",     1  );

define( "PRINCIPAL_NO_SESSION",     0  );
define( "PRINCIPAL_STORE",          1  );

define( "PRINCIPAL_POST_LOGIN",     0  );
define( "PRINCIPAL_COOKIE_LOGIN",   1  );
define( "PRINCIPAL_EMAIL_LOGIN",    2  );

class Principal
{
   var $id = 0; // user id
   var $id_field_name = 'user_id'; // user id's field name
   var $data = array( "login" => "!", ); // ���� ����� = "!", ������ ��������� ������ ������ � �� �����������������
   var $_cheat_stack = array();

   var $identify_status = PRINCIPAL_NOT_IDENTIFIED; // ��������� �������������

   // ��������� ��������� �������
   var $tmp_pwd_length  = 7;
   var $tmp_pwd_timeout = 604800; // = 3600*24*7 ����� ������������ � ������� ������

   function Principal( &$rh, $storage_model="profiles", $security_models = "noguests" )
   {
     if ($storage_model == "")   $storage_model  = "profiles";
     if ($security_models == "") $security_models = "noguests";

     $this->rh = &$rh;

     //. ��������� ������ ������
     Finder::useClass("PrincipalStorage");
     $this->storage_model =& PrincipalStorage::Factory( $this, $storage_model );

     //. ��������� ������ ������������
     Finder::useClass("PrincipalSecurity");
     $this->security_models = array();
     if (!is_array($security_models)) $security_models = array( $security_models );
     foreach( $security_models as $model )
       $this->security_models[ $model ] =& PrincipalSecurity::Factory( $this, $model );

   }

   function _SetUserData($user_data)
   {
      $this->data = $user_data;
      $this->id = $this->data[$this->id_field_name];
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
   // -- ����������� ������ (���������� ����� ��������� �� ������ �������� �������,
   //    ���������� ��� � ������� ����� ���������� �������)
   function InvalidateStoredPassword()
   {
     if ($this->data["login"] == "!") return false;        // no principal @ all
     if ($this->data["guest_profile"] != "") return false; // guest principal
     return $this->storage_model->SetStoredPassword( $this->data, $this->_GenerateInvariant( $this->data ) );
   }

   // -- ������ ��� �� ���������� ������ ��� �������� ��� �� ������
   function _GenerateTempPasswordHash( $temp_password )
   { return $this->_GenerateStoredPassword( $temp_password ); }
   function _CheckTempPassword( $given_password, $temp_password )
   { return $this->_CheckStoredPassword( $given_password, $temp_password ); }

   // -- ������ ���� ��������� ������ + �������
   var $_temp_pwd_count = 0;
   function _GenerateTempPassword()
   {
     $t = md5($this->rh->magic_word.time().($this->_temp_pwd_count++));
     return array( "password" => (substr($t, 0, $this->tmp_pwd_length)),
                   "timeout"  => date("Y-m-d H:i:s", time()+$this->tmp_pwd_timeout));
   }
   // -- ������������ ������� ������
   function InvalidateTempPassword( $user_data )
   {
     $tmp = $this->_GenerateTempPassword();
     $this->storage_model->SetTempPassword( $user_data, $tmp );
     return $tmp["password"];
   }
   function GenerateTempPasswordHash( $user_data )
   {
     // ?������
     if (time() > strtotime($user_data["temp_timeout"]))
       $tmp = $this->InvalidateTempPassword( $user_data );
     else
       $tmp = $user_data["temp_password"];

     return $this->_GenerateTempPasswordHash( $tmp );
   }

   function GetStoredLogin()
   {
      return @$_COOKIE["_principal_stored_login"];
   }

   // -- ������������� �� �������
   function Identify( $redirect=PRINCIPAL_REDIRECT, $_skip_cookies = false )
   {
     // ����������� ����� �� ������ ����������
     $status = PRINCIPAL_NO_CREDENTIALS;

     // 1. ������� �� �����?
     $login = $realm = $pwd = "";
     $have_credentials = 0;
     $login_mode = PRINCIPAL_POST_LOGIN;
     if (!$_skip_cookies)
     if (!isset($_COOKIE[$this->rh->cookie_prefix."_principal_auth"]))
     if (isset($_COOKIE[$this->rh->cookie_prefix."_principal_stored_password"]))
     {
       $login  = $_COOKIE[$this->rh->cookie_prefix."_principal_stored_login"];
       $realm  = "";
       $pwd    = $_COOKIE[$this->rh->cookie_prefix."_principal_stored_password"];
       $login_mode = PRINCIPAL_COOKIE_LOGIN;
       $have_credentials = 1;
     }

     // 2. ����� �� �����
     if (!$have_credentials && isset($_POST["_principal_login"]) && ($_POST["_principal_login"] !== ""))
     {
       $login  = $_POST["_principal_login"];
       $realm  = "";
       $pwd    = $_POST["_principal_password"];
       $login_mode = PRINCIPAL_POST_LOGIN;
       $have_credentials = 1;
     }

     // 2+. ����� �� ���� (�.�. ��������� �����������)
     if (!$have_credentials && isset($_GET["_principal_email"]))
     {
       $login = $_GET["_principal_email"];
       $realm = "";
       $pwd   = $_GET["_principal_password"];
       $login_mode = PRINCIPAL_EMAIL_LOGIN;
       $have_credentials = 1;
     }

     // 3. login (and store to session)
     if ($have_credentials)
       $status = $this->Login( $login, $realm, $pwd, $login_mode, PRINCIPAL_STORE );

     // ���� �����?
     if ($status == PRINCIPAL_AUTH)
     {
       //. �������� ���� ������������� ������
       if ($_POST["_principal_permanent_login"])
         $this->_WritePermanentCookieLogin( $this->data["login"] );
       //. �������� ���� ������������� ������
       if ($_POST["_principal_permanent_password"])
         $this->_WritePermanentCookiePassword( $this->data["stored_invariant"] );

       //. ����� ����� ����� ������ ������������
       if ($login_mode == PRINCIPAL_EMAIL_LOGIN)
         $this->SetPermanent();

       if ($redirect)
         $this->rh->Redirect( $this->rh->ri->Href( $this->rh->ri->url, STATE_USE ) );
       else
         return $this->identify_status = $status;
     }
     else
     {
       if (!$_skip_cookies) return $this->Identify( $redirect, true );
     }

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
        if ($restoral) return $this->identify_status = PRINCIPAL_RESTORED;
        else return $this->identify_status = $status;
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

   function SetPermanent()
   {
     if ($this->id > 1)
     {
        $this->_WritePermanentCookieLogin( $this->data["login"] );
        $this->_WritePermanentCookiePassword( $this->data["stored_invariant"] );
     }
   }

   // -- ����� ����������
   function Login( $login="", $realm="", $pwd="", $login_mode=PRINCIPAL_POST_LOGIN, $store_to_session=PRINCIPAL_NO_SESSION )
   {
     // �������� ������ ����������
     $user_data = $this->LoadByLogin( $login, $realm );
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;
     // ��������� ������
     if ($login_mode == PRINCIPAL_POST_LOGIN)
     {
       if ($user_data["password"] != "")
         if (md5($pwd) == $user_data["password"]) ;
         else
         {
           if ($pwd != $user_data["temp_password"])              return PRINCIPAL_WRONG_PWD;
           if (time() > strtotime( $user_data["temp_timeout"] )) return PRINCIPAL_TEMP_TIMEOUT;
         }
       else
       {
         if ($pwd != $user_data["temp_password"])              return PRINCIPAL_WRONG_PWD;
         if (time() > strtotime( $user_data["temp_timeout"] )) return PRINCIPAL_TEMP_TIMEOUT;
       }
     }
     else
       if ($login_mode == PRINCIPAL_COOKIE_LOGIN)
       {
         if (!$this->_CheckStoredPassword( $pwd, $user_data["stored_invariant"] )) return PRINCIPAL_WRONG_COOKIE;
       }
       else // PRINCIPAL_EMAIL_LOGIN
       {
         if (time() > strtotime($user_data["temp_timeout"]))                  return PRINCIPAL_TEMP_TIMEOUT;
         if (!$this->_CheckTempPassword( $pwd, $user_data["temp_password"] )) return PRINCIPAL_WRONG_PWD;

         if ($user_data["email_confirmed"] == 0)
           $this->storage_model->ConfirmEmail( $this->data );
       }

     // ����� ������!
     $this->_StoreLogin( $user_data, $store_to_session );
     return PRINCIPAL_AUTH;
   }
   // -- (����������) ��� ������ ����� ��������� ������
   function _StoreLogin( $user_data, $store_to_session )
   {
     $this->_SetUserData($user_data);
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );

     // ��������� ���� ���������� ������
     $dt = time();
     $this->data["last_login_datetime"] = $this->data["login_datetime"]; // ����� ����������� ������ for sure
     $this->data["login_datetime"] = date( "Y-m-d H:i:s", $dt );
     $this->storage_model->SetLoginDatetime( $this->data, $dt );

     // ���������� ��� � ������
     if ($store_to_session) $this->_Store();
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
     return $this->__Cheat( $user_data );
   }
   function _CheatById( $user_id )
   {
     $user_data = $this->LoadById( $user_id );
     return $this->__Cheat( $user_data );
   }
   function __Cheat( $user_data )
   {
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;

     $this->_cheat_stack[] = $this->data;
     $this->_SetUserData($user_data);
     // ��� �� ��������� "�����"
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );
     // �� � ������ ������ �� �����
     return PRINCIPAL_AUTH;
   }
   // -- ������������ ������� � ����������� ���������
   function _UnCheat()
   {
     $this->_SetUserData(array_pop($this->_cheat_stack));

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
        $this->_SetUserData($_SESSION[$this->rh->cookie_prefix."principal"]);

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
     // ���� �������� ������� ����� � ����� ���������, �� ��� ����������� ������.
     if ($this->rh->principal_guest_from_realm !== false)
     {
       $user_data = $this->LoadByLogin( $profile, $this->rh->principal_guest_from_realm );
       $this->_SetUserData($user_data);
       $this->data["guest_profile"] = $profile;
     }
     else
     {
       // find script or return
       $file_source = Finder::findScript( "principal_profiles", $profile, false, -1 );
       if ($file_source === false) return false;

       // uplink
       include( $file_source );
       $this->_SetUserData($included_profile);
       $this->data["guest_profile"] = $profile;
     }

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