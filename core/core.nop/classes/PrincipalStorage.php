<?php
/*

  Фабрика, которая делает классы, реализующие "хранилище профилей принципалов"

  PrincipalStorage( &$principal )
      - $principal       -- к какому принципалу прикрепляться

  -------------------

  // Фабрика (статический метод)

  * &Factory( &$principal, $model_name ) -- вернуть экземпляр класса PrincipalStorage_<model_name>
      - $principal  -- к какому принципалу прикрепляться
      - $model_name -- название модели "хранилища"

  // Политика наследников (конкретных моделей), использование модели:

  * LoadById( $id ) -- загрузить по цифровому идентификатору (использующемуся как FKEY как правило)
      - $id -- число-идентификатор
  * LoadByLogin( $login, $realm="" ) -- загрузить учётную запись по логину
      - $login -- тот самый логин
      - $realm -- необязательный параметр, позволяющий делить всех пользователей по, например, "узлам"

  * SetStoredPassword( $user_data, $new_invariant ) -- изменить в БД сохранённый инвариант
      - $user_data      -- профиль принципала (по нему модель определяет, где ей менять, например, используя "user_id"
      - $new_invariant  -- новое значение поля "stored_invariant"
      - true, if success

  * SetLoginDatetime( $user_data, $datetime="" ) -- изменить в БД дату последнего логина
      - $user_data      -- профиль принципала (по нему модель определяет, где ей менять, например, используя "user_id"
      - $datetime       -- на какую дату-время изменить? если пустое, то берёт текущую.
                           в формате time()

  NB: оба метода возвращают структуру для размещения в $principal->data,
      либо false -- если загрузка не удалась.

================================================================== v.1 (kuso@npj)
*/

class PrincipalStorage
{
   function PrincipalStorage( &$principal )
   {
     $this->principal = &$principal;
     $this->rh = &$principal->rh;
   }

   function LoadById($id)
   { return false; }
   function LoadByLogin($login, $realm="")
   { return false; }

   function SetStoredPassword( $user_data, $new_invariant )
   { return true; }

   function SetLoginDatetime( $user_data, $datetime="" )
   {
     if ($datetime == "") $datetime = time();
     return $this->_SetLoginDatetime( $user_data, $datetime );
   }
   function _SetLoginDatetime( $user_data, $datetime )
   {
   }

   function &Factory( &$principal, $model_name )
   {
     $class_name = "PrincipalStorage_".$model_name;
     // find script or die
     $file_source = Finder::FindScript_( "classes/PrincipalModels", $class_name );

     // uplink
     include_once( $file_source );

     eval('$product = &new '.$class_name.'( $principal );');
     return $product;
   }

// EOC{ PrincipalStorage }
}


?>