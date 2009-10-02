<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ��������������� �����.

  FormField( &$form, $field, &$config )
      - $field           -- ���������� ������������� ����, ����: "subject", "body_r", "author_id"
      - &$config         -- ������ �� ������ ����. ����������� ������, ������ ��� ����� ��������
                            ������ �� �����

  -------------------

  // ����������
  * _BuildComponents() -- ������ ��� ������ ���������� � ����������� �� � $this
  * _LinkToForm( &$form ) -- �������� � �����
  * _Dump() -- ������������ ��� ������ "�����������" ����� ��� �������

  // ������� � ������
  * Parse()                    -- ������� ���� ��� �����
  * LoadFromPost( $post_data ) -- ������������� ����� ������ �� �����
  * Validate()                 -- ��������� �������� ����
  * ToSession/FromSession      -- ������ � �������
  * Db<*> -- ����� �������� ��� ������ � ��

================================================================== v.0 (kuso@npj)
*/

class FormField {
    var $name=""; // ������������� ����
    var $default_config = array(
         /* target config, � ���� �� ��������� � ����� �������� ������������ */
    "model"      => "model_plain",
    "wrapper"    => "wrapper_field",
    "view"       => "view_plain",
    "interface"  => "interface_string",
    "validator"  => "validator_base",
    "event"      => "abstract",
         /*  ---- � ���� -- ���������, ������� ----
           "model"      => "_pile_of_junk",
           "wrapper"    => "_pile_of_junk",
           "view"       => "_pile_of_junk",
           "interface"  => "_pile_of_junk",
           "validator"  => "_pile_of_junk",
           "event"      => "_pile_of_junk",
         */
    );

    function FormField( &$form, $field=NULL, &$config ) {
        $this->form = &$form;

        if ($field == NULL) $field = $form->_NextInnerName();
        $this->name = $field;

        if (!is_array($config)) $config = $this->default_config;
        else                    Form::StaticDefaults($this->default_config, $config);

        $this->config = &$config;
        $this->_BuildComponents();
    }

    // (����������) ������ ��� ������ ����������
    var $components= array( "model", "wrapper", "view", "interface", "validator", "event" );
    var $components_hash = array();


    function _BuildComponents() {
        foreach( $this->components as $c ) {
            $c_name = &$this->config[$c];
            if (is_object($c_name)) // direct link to existing foreign object
            {
                $c_instance = &$c_name;
                $c_instance->LinkToField( $this );
            }
            else
                if (isset($this->components_hash[ $c_name ])) // link to object in same pack
                    $c_instance = &$this->components_hash[ $c_name ];
                else // independent object, need to create
                {
                    Finder::useClass( "forms/components/abstract" );
                    Finder::useClass( "forms/components/".$c_name );
                    $class_name = 'FormComponent_'.$c_name;
                    $c_instance = new $class_name( $this->config );
                    $c_instance->LinkToField( $this );
                    $this->components_hash[ $c_name ] = $c_instance;
                }
            switch ($c) {
                case "model":      $this->model     = $c_instance;
                    break;
                case "wrapper":    $this->wrapper   = $c_instance;
                    break;
                case "view":       $this->view      = $c_instance;
                    break;
                case "interface":  $this->interface = $c_instance;
                    break;
                case "validator":  $this->validator = $c_instance;
                    break;
                case "event":      $this->event     = $c_instance;
                    break;
            }
        }
    }

    // �������� � �����
    function _LinkToForm( &$form ) {
        $this->form = &$form;
        $this->event->Event_Register();
    }

    public function &getFieldByName($name) {
        $resultField = null;
        
        if ($this->model && method_exists($this->model, 'getFieldByName'))
        {
            $resultField = $this->model->getFieldByName($name);
        }

        return $resultField;
    }

    // ������� ���� �����
    function Parse( $is_readonly=false ) {
        Debug::trace("FormField: <b>Parsing field: { ".$this->name." } </b>");

        if ($is_readonly ||
            (isset($this->config["readonly"]) && $this->config["readonly"]) ||
            (isset($this->form->config["readonly"]) && $this->form->config["readonly"])
        )
            $result = $this->view->View_Parse();
        else
            if (isset($this->config["view_wrap_interface"]) && $this->config["view_wrap_interface"])
                $result = $this->view->View_Parse( $this->interface->Interface_Parse() );
            else
                $result = $this->interface->Interface_Parse();

        Debug::trace("FormField: interface parsed");

        return $this->wrapper->Wrapper_Parse( $result );
    }

    // ������������� ������ �� �����
    function LoadFromPost( $post_data ) {
        if (@$this->config["readonly"]) return;
        return $this->model->Model_LoadFromArray(
        $this->interface->Interface_PostToArray( $post_data )
        );
    }
    function LoadFromArray( $a ) {
        if (@$this->config["readonly"]) return;
        return $this->model->Model_LoadFromArray( $a );
    }

    // ��������� ������ ����
    function Validate() {
        return $this->validator->Validate();
    }

    // ������
    function ToSession( &$session_storage ) {
        if (@$this->config["readonly"]) return;
        $this->model->Model_ToSession( $session_storage );
    }
    function FromSession( &$session_storage ) {
        if (@$this->config["readonly"]) return;
        $this->model->Model_FromSession( $session_storage );
    }

    // ��� �������
    function _Dump() {
        return $this->model->Model_Dump();
    }

    // ���������� � ��
    function DbInsert( &$fields, &$values ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbInsert( $fields, $values );
    }
    function DbAfterInsert( $data_id ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbAfterInsert( $data_id );
    }
    function DbUpdate( $data_id, &$fields, &$values ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbUpdate( $data_id, $fields, $values );
    }
    function DbAfterUpdate( $data_id ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbAfterUpdate( $data_id );
    }
    function DbLoad( $data_id ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbLoad( $data_id );
    }
    function DbDelete( $data_id ) {
        if (@!$this->config["db_ignore"])
            return $this->model->Model_DbDelete( $data_id );
    }


// EOC{ FormField }
}


?>