<?php
/*
  ������ ��� ����-����������:
  * ���������� �������� "���� ������ - ���� �����" ��� ������ � ����-�����������
  * see http://in.jetstyle.ru/rocket/rocketforms
  * see http://in.jetstyle.ru/rocket/rocketforms/specs/easyforms

  ����������� �����.

  EasyForm( $config=false )
      - $config          -- ������-������, ���� ����, �� ����� ����������� ���������

  -------------------

  function Handle( $config,
                    $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
      - $config -- ������-������ �����
      - $ignore* -- ���������� ���������� Form::Handle()
    ������ ����� �� ������ ������-������ � ��������� �� ���������.

  AddFields( &$form, $config, $is_field=false )
      - $form -- ������ �� ������ ������ Form ��� FormField
      - $config -- ������-������, ���������� ���������� � �����
      - $is_field -- true, ���� $form �������� �������� ������ FormField
    ��������� ���� � ����� ��� ������.

  AddButtons( &$form, $config )
      - $form -- ������ �� ������ ������ Form
      - $config -- ������-������, ���������� ���������� � �������
    ��������� ������ � �����.

  ConstructConfig( $conf_name, $_config=false )
      - $conf_name -- ��� ������ � �������� ���� ��� ������
      - $_config -- ������-������ � ���������� �����������
    ���������� �������-������ ��� ���� ��� ������ �� ������ ������.
    ������ �������� � classes/forms/packages/:
      ����: [conf_name].php
      ������: button_[conf_name].php
    �������� �� $_config ����������� �������� �� ������.

*/

class EasyForm {

	var $form; //������ ������ Form
	var $id_var_name = "_id"; //�� ����� ���������� ������� ����� ID  � ������ � ��
	var $groups = array("group","tab_list","tab_child"); //������ �������, ������� ��������� ��������

	/*
	  �������� ��������� � ����������� �� ����, � ������ �������� ��� ���.
	  ���� �������� �� �������, ���� ��������� � ����������� � ������ ���������.
	*/

	var $wrapper_tpl = array(
		"label,number,radio,select,string,password,checkbox" => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"file,image"             => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"date,date_optional"     => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"textarea,htmlarea"      => array( "wrapper.html:Div", "wrapper.html:RowSpan" ),
	);

	public function __construct()
	{
		$this->config = array();
		if ($args = func_get_args())
		{
			foreach ($args as $arg)
			{
	   			if (is_string($arg))
	   			{
	            	$ymlFile  = Finder::findScript('classes/forms', $arg, 0, 1, 'yml');
	   				if ( $ymlFile )
					{
						$arg = YamlWrapper::load($ymlFile);
					}
					else
					{
						throw new FileNotFoundException('classes/forms/'.$arg.'.yml');
					}
	   			}
	   			$this->config = $this->_mergeConfig($this->config, $arg);
			}
		}

		$this->tpl =& Locator::get('tpl');
	}

	//���������� �����, ��������� �����
	function Handle($ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
	{
		$this->CreateForm( $this->config );
		return $this->form->Handle( $ignore_post, $ignore_load, $ignore_validator, $ignore_session );
 	}

	function CreateForm($config)
	{
		//�������������� �����
		$class_name = isset($config["class"]) ? $config["class"] : "Form";
		Finder::useClass( 'forms/'.$class_name );
		$this->form = new $class_name($config);

		//����������� ������ � ��
		if( $id = isset($config["id"]) ? $config["id"] : false )
			$this->form->AssignId( $id );
		else
			if( $id = isset($_REQUEST[ $this->id_var_name ]) ? $_REQUEST[ $this->id_var_name ] : false )
				$this->form->AssignId( $id );

		//��������� ����
		$this->AddFields( $this->form, $config["fields"] );

		//��������� ������
		$this->AddButtons( $this->form, $config["buttons"] );
	}

	//��������� ���� � ����� ��� ������
	protected function addFields(&$form, $config, $is_field=false) {
		//��� ��������� ����
        if ($config)
        {
            foreach ($config AS $name => $rec)
            {
                //��������� ������ ��� ����
                if ( is_array($rec) )
                {
                    $pack_name = $rec['extends_from'];
                    $conf = $rec;
                    /*if (isset($rec[1]))
                        if (is_array($rec[1])) $conf = $rec[1];
                            else $conf = array( "model_default" => $rec[1] );
                    else $conf = array();*/
                }
                else
                {
                    $pack_name = $rec;
                    $conf = array();
                }
                //���������� wrapper_tpl
                if (!isset($conf["wrapper_tpl"]))
                    foreach ($this->wrapper_tpl as $k=>$v)
                    {
                        if (in_array($pack_name, explode(",",$k)))
                        {
                            $conf["wrapper_tpl"] = $v[ $is_field ? 1 : 0 ];
                            break;
                        }
                    }
                //���������� ������ ��� ����
                $conf = $this->ConstructConfig( $pack_name, $conf, false, $name );
                //������ ����
                if ($is_field)
                    $field =& $form->model->Model_AddField( $name, $conf );
                else
                    $field =& $form->AddField( $name, $conf );
                //���� ������ ����� ������, ������������ ��������
                if (in_array($pack_name, $this->groups))
                {
                    $this->AddFields($field, $conf['fields'], true);
                }
            }
        }
	}

  //��������� ������ � �����
  function AddButtons( &$form, $config ){
    //��� ��������� ������
    foreach($config as $btn => $rec)
    {
      //��������� ������ ��� ������
      $rec_cfg = false;
      if( is_array($rec) && isset($rec[1]) && isset($rec[0]) )
      {
        $rec_cfg = $rec[1];
        $rec = $rec[0];
      }
      else if ( is_array($rec) )
      {
        $rec_cfg = $rec;
	$rec = $btn; 
      }

      $conf = $this->ConstructConfig( "button_".$rec, $rec_cfg, $rec );

      //������ ������
      $field =& $form->AddButton( $conf );
    }
  }

	//��������� ������ �� ������ ������
	function constructConfig($conf_name, $_config=false, $is_btn=false, $field_name="")
	{
		//������������ ������
		$config = array();

		if ($filename = Finder::findScript("classes","forms/packages/".$conf_name))
		{
			include( $filename );
		}
		/*
		else
		{
			include( Finder::findScript("handlers","forms/packages/".$conf_name) );
		}
		*/

		if (isset($_config["easyform_override"]))
			foreach( $_config["easyform_override"] as $v )
				unset($config[$v]);

		//���������� ����� �� ������ � ������
		if (is_array($_config))
			$config = $this->_MergeConfig($config, $_config);

		return $config;
	}

  // ����������� ������� ������������� ����� ������
  function _MergeConfig($config, $_config)
  {
    foreach( $_config as $k=>$v )
    {
      if (is_array($v) && isset($config[$k])) $config[$k] = $this->_MergeConfig($config[$k], $v);
      else $config[$k] = $v;
    }

    return $config;
  }
}

?>