<?php
/*
  Обёртка для форм-процессора:
  * реализация принципа "один конфиг - одна форма" при работе с форм-процессором
  * see http://in.jetstyle.ru/rocket/rocketforms
  * see http://in.jetstyle.ru/rocket/rocketforms/specs/easyforms

  Управляющий класс.

  EasyForm( &$rh, $config=false )
      - $rh              -- ссылка на RH, как обычно
      - $config          -- конфиг-массив, если есть, то сразу запускается обработка

  -------------------

  function Handle( $config,
                    $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
      - $config -- конфиг-массив формы
      - $ignore* -- аналогично параметрам Form::Handle()
    Строит форму на основе конфиг-массиа и запускает на обработку.

  AddFields( &$form, $config, $is_field=false )
      - $form -- ссылка на объект класса Form или FormField
      - $config -- конфиг-массив, содержащий информацию о полях
      - $is_field -- true, если $form является объектом класса FormField
    Добавляет поля к форме или группе.

  AddButtons( &$form, $config )
      - $form -- ссылка на объект класса Form
      - $config -- конфиг-массив, содержащий информацию о кнопках
    Добавляет кнопки к форме.

  ConstructConfig( $conf_name, $_config=false )
      - $conf_name -- имя пакета с конфигом поля или кнопки
      - $_config -- конфиг-массив с изменёнными параметрами
    Генерирует конифиг-массив для поля или кнопки на основе пакета.
    Пакеты хранятся в handlers/FormPackages/:
      поле: [conf_name].php
      кнопка: button_[conf_name].php
    Значения из $_config перекрывают значения из пакета.

*/

class EasyForm{

	//ссылки на окружение
	var $rh;
	var $tpl;

	var $form; //объект класса Form
	var $id_var_name = "_id"; //из какой переменной запроса брать ID  к записи в БД
	var $groups = array("group","tab_list","tab_child"); //список пакетов, которые считаются группами

	/*
	  Вариация врапперов в зависимости от того, в группе эелемент или нет.
	  Если вариация не найдена, поле выводится с прописанным в пакете враппером.
	*/

	var $wrapper_tpl = array(
		"label,number,radio,select,string,password,checkbox" => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"file,image"             => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"date,date_optional"     => array( "wrapper.html:Div", "wrapper.html:Row" ),
		"textarea,htmlarea"      => array( "wrapper.html:Div", "wrapper.html:RowSpan" ),
	);

	//конструктор
	//возможно, сразу же построение и обработка формы
	public function __construct(&$rh, $config=false) {
		$this->rh =& $rh;
		$this->tpl =& $rh->tpl;
		if ($config) $this->Handle($config);
	}

	//построение формы, обработка формы
	function Handle( $config,
                    $ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
	{
		$this->CreateForm( $config );
		//поехали!
		return $this->form->Handle( $ignore_post, $ignore_load, $ignore_validator, $ignore_session );
 	}

	function CreateForm( $configName )
	{
		/*if(!is_array($config))
		{
			throw new Exception("EasyForm::CreateForm -- \$config should be an array, now it is: <strong>[$config]</strong>");
		}*/

		//Загрузка конфига из YML-файла
		$ymlFile  = Finder::findScript('classes/forms', $configName, 0, 1, 'yml');

		if ( $ymlFile )
		{
			$config = YamlWrapper::load($ymlFile);
			//var_dump('<pre>',$ymlConfig,'</pre>');
		}

		//инициализируем форму
		$class_name = isset($config["class"]) ? $config["class"] : "Form";
		Finder::useClass( 'forms/'.$class_name );
		$form =& new $class_name($this->rh, $config);
		$this->form =& $form;

		//привязываем строку к БД
		if( $id = isset($config["id"]) ? $config["id"] : false )
			$form->AssignId( $id );
		else
			if( $id = isset($_REQUEST[ $this->id_var_name ]) ? $_REQUEST[ $this->id_var_name ] : false )
				$form->AssignId( $id );

		//добавляем поля
		$this->AddFields( $form, $config["fields"] );

		//добавляем кнопки
		$this->AddButtons( $form, $config["buttons"] );
	}

	//добавляем поля к форме или группе
	protected function addFields(&$form, $config, $is_field=false) {
		//тут добавляем поля
    	foreach($config as $name=>$rec)
    	{
      		//формируем конфиг для поля
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
			//определяем wrapper_tpl
			if(!isset($conf["wrapper_tpl"]))
				foreach($this->wrapper_tpl as $k=>$v)
				{
					if(in_array($pack_name, explode(",",$k)))
					{
						$conf["wrapper_tpl"] = $v[ $is_field ? 1 : 0 ];
						break;
					}
				}
			//генерируем конфиг для поля
			$conf = $this->ConstructConfig( $pack_name, $conf, false, $name );
			//создаём поле
			if($is_field)
				$field =& $form->model->Model_AddField( $name, $conf );
			else
				$field =& $form->AddField( $name, $conf );
			//если указан пакет группы, обрабатываем вложение
			if(in_array($rec[0],$this->groups))
				$this->AddFields($field, $rec[2], true);
		}
	}

  //добавляем кнопки к форме
  function AddButtons( &$form, $config ){

    if(!is_array($config))
      $this->rh->error("EasyForm::AddButtons -- \$config should be an array, now it is: <strong>[$config]</strong>");

    //тут добавляем кнопки
    foreach($config as $rec)
    {
      //формируем конфиг для кнопки
      $rec_cfg = false;
      if( is_array($rec) )
      {
        $rec_cfg = $rec[1];
        $rec = $rec[0];
      }

      $conf = $this->ConstructConfig( "button_".$rec, $rec_cfg, $rec );

      //создаём кнопку
      $field =& $form->AddButton( $conf );
    }
  }

	//формирует конфиг на основе пакета
	function constructConfig($conf_name, $_config=false, $is_btn=false, $field_name="")
	{
		//конструируем конфиг
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

		//возвращаем смесь из пакета и твиков
		if (is_array($_config))
			$config = $this->_MergeConfig($config, $_config);

		return $config;
	}

  // рекурсивная функция перекрывающая пакет твиком
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