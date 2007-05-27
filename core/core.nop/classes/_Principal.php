<?php
/*

  Абстракция от метода хранения данных о пользователе:
  * логин/логаут/перманентный логин
  * хранение информации о текущем пользователе
  * обеспечение других модулей функциями "контроля доступа" 

  Principal( &$rh, $storage_model="profiles", $security_models = "noguests" )
      - $rh              -- ссылка на RH, как обычно
      - $storage_model   -- модель хранилища профилей (потомок PrincipalStorage)
      - $security_models -- модель или модели контроля доступа (потомки PrincipalSecurity)

  -------------------

  // Логин/Логаут

  * Identify( $redirect=PRINCIPAL_REDIRECT, $_skip_cookies=false ) -- 
                               попытка понять, можем ли мы идентифицировать принципала 
                               в текущей сессии. Вызывает и попытку логина.
      - $redirect=1 -- в случае успеха сам делает редирект на ту же страницу
      - $_skip_cookies=false -- для внутреннего использования, чтобы авторизоваться постом при провале кук
      - он же ищет в посте и куках
          * в посте поля: "_principal_login", "_principal_password", 
                          "_principal_permanent_login", "_principal_permanent_password"
          * в куках поля: "_principal_stored_login", "_principal_stored_password"
      - варианты возврата без редиректа:
          * PRINCIPAL_RESTORED
          * PRINCIPAL_AUTH
          * PRINCIPAL_WRONG_PWD
          * PRINCIPAL_WRONG_LOGIN
          * PRINCIPAL_WRONG_COOKIE
          * PRINCIPAL_NO_CREDENTIALS
  * Login( $login="", $realm="", $pwd="", $stored=0, $store_to_session=PRINCIPAL_NO_SESSION ) -- логин принципала
      - $login, $realm, $pwd -- credentials
      - $stored              -- если true, то пароль расценивается как "полученный из кук"
      - $store_to_session    -- сохраняет ли результат логина в сессию для дальнейшей "идентификации"
      - варианты возврата без редиректа:
          * PRINCIPAL_AUTH
          * PRINCIPAL_WRONG_PWD
          * PRINCIPAL_WRONG_LOGIN
  * Logout( $redirect=PRINCIPAL_REDIRECT, $url=NULL ) -- забывание про текущего принципала
      - $redirect=1 -- редирект на ту же страницу, иначе возвращает true
      - $url=NULL   -- если не NULL, то редиректить. $url должен быть абсолютным, т.е. результатом $ri->Href()

  * GetStoredLogin() -- достаёт из кук "сохранённый туда логин"

  // "Хакерские" штучки. Для ситуаций, когда нам нужно временно выступить в другом лице.
     Не следует этим пользоваться на высоких уровнях. Только глубоко в системе

  * _Cheat( $login, $realm="" ) -- вне зависимости от пароля идентифицируется под пользователя
  * _UnCheat()      -- возвращается обратно к предыдущему состоянию

  // Откуда берутся данные принципала?

  * Guest( $profile="guest" ) -- инициализация "гостем" без подъёма/записи сессии. 
      - $profile указывает на имя файла, которое лежит в специально указанной папке "principal_profiles"
  * LoadById($id), LoadByLogin($login, $realm="") -- загружают структуру принципала из "хранилища", 
                                                     возвращая её. Тут встроено кэширование.

  // Работа с сессией (внутренние)
  * _Store(), _Restore() -- запись и восстановление данных о принципале в сессию. 
                            При этом "cheated" состояния в сессию не записываются и не восстанавливаются.
  * _StoreReset()        -- сбросить сохранённое в сессии состояние

  // Механизмы генерации и инвалидации сохранённого пароля
  * _CheckStoredPassword( $stored_password, $stored_invariant ) -- проверяет, подходит ли пароль, 
                                                                   исходя из выбранной политики
       - $stored_password  -- пароль, полученный "из кук"
       - $stored_invariant -- хранимый в профиле принципала инвариант пароля
       - вызывается из Login
  * _GenerateStoredPassword( $stored_invariant ) -- генерирует пароль для сохранения, исходя из выбранной политики
       - $stored_invariant -- хранимый в профиле принципала инвариант пароля
       - вызывается из Identify

  * InvalidateStoredPassword() -- инвалидация пароля (генерирует новый инвариант на основе текущего профиля, 
                                  записывает его в профиль через внутренние функции)
                                  Можно делать только из под авторизации, основываясь на $this->data
  * _GenerateInvariant( $user_data ) -- генерирует новый инвариант пароля, вызывается из инвалидации

  // Работа с моделями доступа
  * Security( $model, $params="" ) -- есть ли доступ у данной персоны согласно модели доступа
       - $model -- объект-потомок PrincipalSecurity
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
   var $data = array( "login" => "!", ); // если логин = "!", значит принципал совсем сломан и не инициализировался
   var $_cheat_stack = array();

   function Principal( &$rh, $storage_model="profiles", $security_models = "noguests" )
   {
     if ($storage_model == "")   $$storage_model  = "profiles";
     if ($security_models == "") $security_models = "noguests";

     $this->rh = &$rh;

     //. достроить стораж модель
     $this->rh->UseClass("PrincipalStorage");
     $this->storage_model =& PrincipalStorage::Factory( $this, $storage_model );  

     //. построить модели безопасности
     $this->rh->UseClass("PrincipalSecurity");
     $this->security_models = array();
     if (!is_array($security_models)) $security_models = array( $security_models );
     foreach( $security_models as $model )
       $this->security_models[ $model ] =& PrincipalSecurity::Factory( $this, $model ); 
    
   }

   // работа с инвариантами паролей
   // -- проверяет, подходит ли пароль, исходя из выбранной политики
   function _CheckStoredPassword( $stored_password, $stored_invariant ) 
   {
     $gen_md5 = $this->_GenerateStoredPassword( $stored_invariant );
     if ($gen_md5 == $stored_password) return true;
     else return false;
   }
   // -- генерирует пароль для сохранения, исходя из выбранной политики
   function _GenerateStoredPassword( $stored_invariant )
   {
     $gen = $stored_invariant.$this->rh->magic_word;
     return md5($gen);
   }
   // -- генерирует новый инвариант пароля, вызывается из инвалидации
   function _GenerateInvariant( $user_data ) 
   {
     $invariant = $user_data["login"].date("Ymdhis");
     return $invariant;
   }
   // -- инвалидация пароля (генерирует новый инвариант на основе текущего профиля, записывает его в профиль через внутренние функции)
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

   // -- идентификация из запроса
   function Identify( $redirect=PRINCIPAL_REDIRECT, $_skip_cookies = false )
   {
     // попробовать логин из разных источников
     $status = PRINCIPAL_NO_CREDENTIALS;

     // 1. сначала из куков?
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

     // 2. потом из поста
     if (!$stored && isset($_POST["_principal_login"]))
     {                                                                
       $login  = $_POST["_principal_login"];
       $realm  = "";
       $pwd    = $_POST["_principal_password"];
       $stored = 0;
     }

     // 3. login (and store to session)
     $status = $this->Login( $login, $realm, $pwd, $stored, PRINCIPAL_STORE ); 

     // если вышло?
     if ($status == PRINCIPAL_AUTH)
     {
       //. записать куку перманентного логина
       if ($_POST["_principal_permanent_login"])
         $this->_WritePermanentCookieLogin( $this->data["login"] );
       //. записать куку перманентного пароля
       if ($_POST["_principal_permanent_password"])
         $this->_WritePermanentCookiePassword( $this->data["stored_invariant"] );

       if ($redirect)
         $this->rh->Redirect( $this->rh->ri->Href( $this->rh->ri->url, STATE_USE ) );
     }
     else
     if (!$_skip_cookies) return $this->Identify( $redirect, true );

     // если не вышло -- пробуем загрузиться
     if ($status != PRINCIPAL_AUTH)
     {
       $restoral = $this->_Restore();
       if ($restoral === PRINCIPAL_OLD_SESSION)
       {
         // принципала надо попробовать перелогинить, стерев куку
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

   // -- логин принципала
   function Login( $login="", $realm="", $pwd="", $stored=0, $store_to_session=PRINCIPAL_NO_SESSION ) 
   {
     // получить данные принципала
     $user_data = $this->LoadByLogin( $login, $realm );
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;
     // проверить пароль
     if ($stored == 0)
     {
       $_pwd = md5($pwd);
       if ($_pwd != $user_data["password"]) return PRINCIPAL_WRONG_PWD;
     }
     else
     {
       if (!$this->_CheckStoredPassword( $pwd, $user_data["stored_invariant"] )) return PRINCIPAL_WRONG_COOKIE;
     }

     // логин удачен!
     $this->data = $user_data;
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );

     // обновляем дату последнего логина
     $dt = time();
     $this->data["last_login_datetime"] = $this->data["login_datetime"]; // время предыдущего логина for sure
     $this->data["login_datetime"] = date( "Y-m-d H:i:s", $dt );
     $this->storage_model->SetLoginDatetime( $this->data, $dt );

     // складываем его в сессию
     if ($store_to_session) $this->_Store();
     return PRINCIPAL_AUTH;
   }
   // -- забывание про текущего принципала
   function Logout( $redirect=PRINCIPAL_REDIRECT, $url=NULL )
   {
     $this->_StoreReset();
     if ($redirect)
       $this->rh->Redirect( isset($url) ? $url : $this->rh->ri->Href( $this->rh->ri->url, STATE_USE ) );
     return $this->Guest();
   }

   // -- вне зависимости от пароля идентифицируется под пользователя
   function _Cheat( $login, $realm="" ) 
   {
     $user_data = $this->LoadByLogin( $login, $realm );
     if ($user_data === false) return PRINCIPAL_WRONG_LOGIN;

     $this->_cheat_stack[] = $this->data;
     $this->data = $user_data;
     // как бы произошёл "логин" 
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );
     // но в сессию ничего не кладём
     return PRINCIPAL_AUTH;
   }
   // -- возвращается обратно к предыдущему состоянию
   function _UnCheat() 
   {
     $this->data = array_pop( $this->_cheat_stack );

     // как бы произошёл "логин"
     foreach( $this->security_models as $model=>$v )
       $this->security_models[ $model ]->OnLogin( $this->data );
     // но в сессию ничего не кладём
   }

   // -- загружают структуру принципала из "хранилища" 
   function LoadById($id) 
   { return $this->storage_model->LoadById( $id ); }
   function LoadByLogin($login, $realm="") 
   { return $this->storage_model->LoadByLogin( $login, $realm ); }

   // Работа с сессией (внутренние)
   function _StoreReset()
   {
      if (!$_COOKIE[$this->rh->cookie_prefix."_principal_auth"]) return false;

      // заодно запустим сессию.
      if (!session_id()) session_start();

      setcookie( $this->rh->cookie_prefix."_principal_auth", "", time()-3600, "/", $this->rh->cookie_domain ); 
      unset($_SESSION[$this->rh->cookie_prefix."principal"]);

      // снимем также и куку пермалогина
      setcookie( $this->rh->cookie_prefix."_principal_stored_password", "", time()-3600, "/", $this->rh->cookie_domain ); 

      return true;
   }
   function _Store()
   {
      // заодно запустим сессию.
      if (!session_id()) session_start();
      
      setcookie( $this->rh->cookie_prefix."_principal_auth", 1, 0, "/", $this->rh->cookie_domain ); 
      $_SESSION[$this->rh->cookie_prefix."principal"] = $this->data;
      return true;
   }
   function _Restore()
   {
      if (!isset($_COOKIE[$this->rh->cookie_prefix."_principal_auth"])) return false;

      // заодно запустим сессию.
      if (!session_id()) session_start();

      if (isset($_SESSION[$this->rh->cookie_prefix."principal"]))
      {
        $this->data = $_SESSION[$this->rh->cookie_prefix."principal"];

        foreach( $this->security_models as $model=>$v )
          $this->security_models[ $model ]->OnRestore( $this->data );
      }
      else
      {
        // сессия умерла, хотя пользователь залогинен. Чё делать бум?
        return PRINCIPAL_OLD_SESSION;
      }

      return true;
   }

   // -- принудительная авторизация гостевым профилем без попыток угадать, идентифицировать пользователя
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

   // -- есть ли доступ у данной персоны согласно модели доступа
   function Security( $model, $params="" ) 
   {
     if (!$this->security_models[$model]) return DENIED;
     else return $this->security_models[$model]->Check( $this->data, $params );
   }




// EOC{ Principal }
}

?>