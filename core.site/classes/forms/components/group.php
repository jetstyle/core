<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  Инкапсуляция группы полей

  FormComponent_group( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * model       : передаёт всё, что знает, списку полей в себе
  * validator   : передаёт всё, что знает, списку полей в себе
  * wrapper     : - нет
  * view        : переключает все вложенные поля в readonly и рендерит их список
  * interface   : рендерит список полей внутри
                  передаёт всё, что знает, списку полей в себе

  -------------------

  // Добавление полей:

  1) в конструкторе:      $config["fields"] = array{ name=>field_config/field_object, ... }
  2) после конструктора:  $group->model->Model_AddField( "title", array( ...<field_config>... ) );

  * &Model_AddField( $field_name, $config ) -- создаёт поле и добавляем его
  * &_AddField( &$field_object ) -- для внутреннего использования в AddField

  // Настройка и подстройка
  * Event_Register() -- регистрация в форме. Перегружать имеет смысл только для групп

================================================================== v.0 (nikolay@jetstyle)
*/

class FormComponent_group extends FormComponent_abstract
{
    var $childs = array();

    function FormComponent_group( &$config )
    {
        $result = FormComponent_abstract::FormComponent_abstract( $config );

        /*
        if (isset($config["fields"]) && is_array($config["fields"]))
        {
            foreach($config["fields"] as $name=>$_config)
            {
                if (is_array($_config))
                {
                    $this->Model_AddField( $name, $_config );
                }
                else
                {    
                    $this->_AddField( $config["fields"][$name] );
                }
            }
        }
         */

        return $result;
    }

    // добавление полей
    function &Model_AddField( $field_name, $config )
    {
        $f = &new FormField( $this->field->form, $field_name, $config );
        return $this->_AddField($f);
    }

    function &_AddField( &$field_object )
    {
        $this->childs[] = $field_object;
        $field_object->_LinkToForm( $this->field->form );
        return $field_object;
    }

    // регистрация в форме
    function Event_Register()
    {
        // NB: себя регистрируем, только если непустое имя
        if ($this->field->name) FormComponent_abstract::Event_Register();
        // регистрируем всех детей всегда
        foreach ($this->childs as $k=>$v)
        $this->field->form->hash[ $v->name ] = &$this->childs[$k];
    }

    // MODEL ==============================================================================
    // сброс значения в "значение по-умолчанию"
    function Model_SetDefault()
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->model->Model_SetDefault();
    }
    // возврат значения в виде "шифра" или "ключа"
    function Model_GetDataValue()
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->model->Model_GetDataValue();
    }
    // dbg purposes: dump
    function Model_Dump()
    {
        $dump = array();
        foreach ($this->childs as $k=>$v)
        $dump[] = $this->childs[$k]->model->Model_Dump();
        return implode( "; ", $dump );
    }
    // ---- сессия ----
    function Model_ToSession( &$session_storage )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->ToSession( $session_storage );
    }
    function Model_FromSession( &$session_storage )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->FromSession( $session_storage );
    }
    // ---- работа с хранилищем в массиве ----
    function Model_LoadFromArray( $a )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->LoadFromArray( $a );
    }
    function Model_ToArray( &$a )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->model->Model_ToArray( $a );
    }
    // ---- работа с БД ----
    function Model_DbLoad( $db_row )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbLoad( $db_row );
    }
    function Model_DbInsert( &$fields, &$values )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbInsert( $fields, $values );
    }
    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbUpdate( $data_id, $fields, $values );
    }
    function Model_DbAfterInsert( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbAfterInsert( $data_id );
    }
    function Model_DbAfterUpdate( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbAfterUpdate( $data_id );
    }
    function Model_DbDelete( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbDelete( $data_id );
    }


    // VALIDATOR ==============================================================================
    // валидация
    // все потомки должны вызывать его ПЕРЕД своей валидацией
    function Validate()
    {
        $this->valid = true;
        foreach ($this->childs as $k=>$v)
        {
            $this->valid = $this->childs[$k]->validator->Validate() && $this->valid; // порядок важен
        }

        if (!$this->valid)
        {
            $this->_Invalidate( "have_errors", "Есть поля, содержащие ошибку в заполнении" );
        }

        return $this->valid;
    }

    // VIEW ==============================================================================
    // парсинг readonly значения
    function View_Parse()
    {
        return $this->_ParseChilds( "readonly" );
    }

    // INTERFACE ==============================================================================
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        return $this->_ParseChilds();
    }
    // вспомогательная процедура для парсинга полей/значений
    function _ParseChilds( $readonly=false )
    {
        $result = array();
        foreach($this->childs as $child)
        {
            $result[] = array(
                  "child" => $child->name,
                  "field" => $child->Parse( $readonly ) 
            );
        }
        
        $tpl = &Locator::get('tpl');
        $tpl->set( "parent", $this->field->name );
        $tpl->set( "fields", $result);
        
        return $tpl->parse($this->field->form->config["template_prefix_group"].$this->field->config["group_tpl"]);
    }
    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray( $post_data )
    {
        $result = array();
        foreach ($this->childs as $k=>$v)
        $result = array_merge( $result, $this->childs[$k]->interface->Interface_PostToArray( $post_data ));
        return $result;
    }

    // EOC{ FormComponent_group }
}  


?>