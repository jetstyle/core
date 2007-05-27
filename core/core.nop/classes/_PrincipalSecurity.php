<?php
/*

  Фабрика, которая делает классы, реализующие "контроль доступа"

  PrincipalSecurity( &$principal )
      - $principal       -- к какому принципалу прикрепляться

  -------------------

  // Фабрика (статический метод)

  * &Factory( &$principal, $model_name ) -- вернуть экземпляр класса PrincipalSecurity_<model_name>
      - $principal  -- к какому принципалу прикрепляться
      - $model_name -- название модели "контроля доступа"

  // Политика наследников (конкретных моделей), нужды кэширования модели:

  * OnRestore( &$user_data )  -- вызывается в момент, когда "Identify" восстанавливает принципала из сессии.
                                 Модель может дополнить/расширить этот массив, как ей нужно для дальнейшего
                                 использования. В сессию эти правки класться НЕ БУДУТ
  * OnLogin( &$user_data )    -- после успешного логина by "Login", до складывания в сессию.
                                 Модель может дополнить/расширить этот массив, как ей нужно для дальнейшего
                                 использования, результат будет ПОЛОЖЕН В СЕССИЮ
  * OnGuest( &$user_data )    -- вызывается в момент, когда "Guest" загрузил гостевой профиль.
                                 Модель может дополнить/расширить этот массив, как ей нужно для дальнейшего
                                 использования. В сессию эти правки класться НЕ БУДУТ.
                                 Вообще, как правило достаточно идентичности поведения OnGuest ==> OnLogin,
                                 что и прописано в "предке"

    NB: $user_data -- во всем методы передаётся то, что записано в $principal->data, 
                      оно же извлечено из сессии или будет положено в неё.

  // Основной метод для наследников:

  * Check( &$user_data, $params="" ) - при вызове $principal->Security 
      - $user_data -- то же, что и выше
      - $params    -- параметры, переданные принципалу в его метод.

================================================================== v.1 (kuso@npj)
*/
define( "GRANTED", true );
define( "DENIED",  false );

class PrincipalSecurity
{
   function PrincipalSecurity( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;
   }

   function OnRestore( &$user_data )
   { return true; }

   function OnLogin( &$user_data )
   { return true;  }

   function OnGuest( &$user_data )
   { return $this->OnLogin( $user_data );  }

   function Check( &$user_data, $params="" )  // denied by default;
   { return DENIED; }

   function &Factory( &$principal, $model_name )
   {
     $class_name = "PrincipalSecurity_".$model_name;
     // find script or die
     $file_source = $principal->rh->FindScript_( "classes/PrincipalModels", $class_name );

     // uplink
     include_once( $file_source );

     eval('$product = &new '.$class_name.'( $principal );');
     return $product; 
   }

// EOC{ PrincipalSecurity }
}


?>