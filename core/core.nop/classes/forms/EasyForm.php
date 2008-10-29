<?php
/*
  ������ ��� ����-����������:
  * ���������� �������� "���� ������ - ���� �����" ��� ������ � ����-�����������
  * see http://in.jetstyle.ru/rocket/rocketforms
  * see http://in.jetstyle.ru/rocket/rocketforms/specs/easyforms

  ����������� �����.

  EasyForm( &$rh, $config=false )
      - $rh              -- ������ �� RH, ��� ������
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
    ������ �������� � handlers/FormPackages/:
      ����: [conf_name].php
      ������: button_[conf_name].php
    �������� �� $_config ����������� �������� �� ������.

*/

class EasyForm{

	//������ �� ���������
	var $rh;
	var $tpl;

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

	//�����������
	//��������, ����� �� ���������� � ��������� �����
	public function __construct(&$rh, $config=false) {
		$this->rh =& $rh;
		$this->tpl =& $rh->tpl;
		if ($config) $this->Handle($config);
	}

	//���������� �����, ��������� �����
	function Handle( $config,
                    $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
	{
		$this->CreateForm( $config );
		//�������!
		return $this->form->Handle( $ignore_post, $ignore_load, $ignore_validator, $ignore_session );
 	}

	function CreateForm( $configName )
	{
		/*if(!is_array($config))
		{
			throw new Exception("EasyForm::CreateForm -- \$config should be an array, now it is: <strong>[$config]</strong>");
		}*/

		//�������� ������� �� YML-�����
		$ymlFile  = Finder::findScript('classes/forms', $configName, 0, 1, 'yml');

		if ( $ymlFile )
		{
			$config = YamlWrapper::load($ymlFile);
			//var_dump('<pre>',$ymlConfig,'</pre>');
		}

		//�������������� �����
		$class_name = isset($config["class"]) ? $config["class"] : "Form";
		Finder::useClass( 'forms/'.$class_name );
		$form =& new $class_name($this->rh, $config);
		$this->form =& $form;

		//����������� ������ � ��
		if( $id = isset($config["id"]) ? $config["id"] : false )
			$form->AssignId( $id );
		else
			if( $id = isset($_REQUEST[ $this->id_var_name ]) ? $_REQUEST[ $this->id_var_name ] : false )
				$form->AssignId( $id );

		//��������� ����
		$this->AddFields( $form, $config["fields"] );

		//��������� ������
		$this->AddButtons( $form, $config["buttons"] );
	}

	//��������� ���� � ����� ��� ������
	protected function addFields(&$form, $config, $is_field=false) {
		//��� ��������� ����
    	foreach($config as $name=>$rec)
    	{
      		//��������� ������ ��� ����
			if( is_array($rec) )
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
			if(!isset($conf["wrapper_tpl"]))
				foreach($this->wrapper_tpl as $k=>$v)
				{
					if(in_array($pack_name, explode(",",$k)))
					{
						$conf["wrapper_tpl"] = $v[ $is_field ? 1 : 0 ];
						break;
					}
				}
			//���������� ������ ��� ����
			$conf = $this->ConstructConfig( $pack_name, $conf, false, $name );
			//������ ����
			if($is_field)
				$field =& $form->model->Model_AddField( $name, $conf );
			else
				$field =& $form->AddField( $name, $conf );
			//���� ������ ����� ������, ������������ ��������
			if(in_array($rec[0],$this->groups))
				$this->AddFields($field, $rec[2], true);
		}
	}

  //��������� ������ � �����
  function AddButtons( &$form, $config ){

    if(!is_array($config))
      $this->rh->error("EasyForm::AddButtons -- \$config should be an array, now it is: <strong>[$config]</strong>");

    //��� ��������� ������
    foreach($config as $rec)
    {
      //��������� ������ ��� ������
      $rec_cfg = false;
      if( is_array($rec) )
      {
        $rec_cfg = $rec[1];
        $rec = $rec[0];
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
		else
		{
			include( Finder::findScript("handlers","forms/packages/".$conf_name) );
		}

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