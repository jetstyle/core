<?php
/*
  Обёртка для форм-процессора:
  * реализация принципа "один конфиг - одна форма" при работе с форм-процессором
  * see http://in.jetstyle.ru/rocket/rocketforms
  * see http://in.jetstyle.ru/rocket/rocketforms/specs/easyforms

  Управляющий класс.

  EasyForm( $config=false )
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
    Пакеты хранятся в classes/forms/packages/:
      поле: [conf_name].php
      кнопка: button_[conf_name].php
    Значения из $_config перекрывают значения из пакета.

*/

class EasyForm {

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

	//построение формы, обработка формы
	function Handle($ignore_post     =false,  $ignore_load   =false,
                    $ignore_validator=false,  $ignore_session=false )
	{
		$this->CreateForm( $this->config );
		return $this->form->Handle( $ignore_post, $ignore_load, $ignore_validator, $ignore_session );
 	}

	function CreateForm($config)
	{
		//инициализируем форму
		$class_name = isset($config["class"]) ? $config["class"] : "Form";
		Finder::useClass( 'forms/'.$class_name );
		$this->form = new $class_name($config);

		//привязываем строку к БД
		if( $id = isset($config["id"]) ? $config["id"] : false )
			$this->form->AssignId( $id );
		else
			if( $id = isset($_REQUEST[ $this->id_var_name ]) ? $_REQUEST[ $this->id_var_name ] : false )
				$this->form->AssignId( $id );

		//добавляем поля
		$this->AddFields( $this->form, $config["fields"] );

		//добавляем кнопки
		$this->AddButtons( $this->form, $config["buttons"] );
	}

	//добавляем поля к форме или группе
	protected function addFields(&$form, $config, $is_field=false) {
		//тут добавляем поля
        if ($config)
        {
            foreach ($config AS $name => $rec)
            {
                //формируем конфиг для поля
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
                //определяем wrapper_tpl
                if (!isset($conf["wrapper_tpl"]))
                    foreach ($this->wrapper_tpl as $k=>$v)
                    {
                        if (in_array($pack_name, explode(",",$k)))
                        {
                            $conf["wrapper_tpl"] = $v[ $is_field ? 1 : 0 ];
                            break;
                        }
                    }
                //генерируем конфиг для поля
                $conf = $this->ConstructConfig( $pack_name, $conf, false, $name );
                //создаём поле
                if ($is_field)
                    $field =& $form->model->Model_AddField( $name, $conf );
                else
                    $field =& $form->AddField( $name, $conf );
                //если указан пакет группы, обрабатываем вложение
                if (in_array($pack_name, $this->groups))
                {
                    $this->AddFields($field, $conf['fields'], true);
                }
            }
        }
	}

  //добавляем кнопки к форме
  function AddButtons( &$form, $config ){
    //тут добавляем кнопки
    foreach($config as $btn => $rec)
    {
      //формируем конфиг для кнопки
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
		/*
		else
		{
			include( Finder::findScript("handlers","forms/packages/".$conf_name) );
		}
		*/

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