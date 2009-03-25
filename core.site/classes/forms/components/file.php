<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_file( &$config )
      - $field -- $field->config instance-a поля

  -------------------

  * model       : наследуем из него: сохранение файла в файловую систему и запись его имени в БД
  * interface   : вывод поля загрузки файла
  * validator   : нормальный ли размер файла? формат файла?

  -------------------

  Опции в конфиге

  * file_size = "8" -- max size in Kilobytes
  * file_ext  = array( "gif", "jpg", etc. )
  * file_dir  = -- путь, куда класть файлы.
  * file_random_name = false (true)

  -------------------

  // Модель. Операции с данными и хранилищем
  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )

  // Валидатор
  * Validate()

  // Интерфейс (парсинг и обработка данных)
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/model_plain" );

class FormComponent_file extends FormComponent_model_plain
{
    // MODEL ==============================================================================
    function Model_DbInsert( &$fields, &$values )
    {
        /*if (!$this->field->config['db_ignore'] && $this->file_uploaded)
        {
            $fields[] = $this->field->name;
            $values[] = $this->model_data;
        }*/
    }
    
    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        //return $this->Model_DbInsert( $fields, $values );
    }

    // VALIDATOR ==============================================================================
    function Validate()
    {
        parent::Validate();

        if (!$this->valid) return $this->valid; // ==== strip one

        if ($this->file_size)
            if (isset( $this->field->config["file_size"]))
                if ($this->file_size > $this->field->config["file_size"]*1024)
                    $this->_Invalidate( "file_size", "Слишком большой файл" );

        if ($this->file_ext)
            if (isset( $this->field->config["file_ext"]))
                if (!in_array($this->file_ext,$this->field->config["file_ext"]))
                    $this->_Invalidate( "file_ext", "Недопустимый тип файла" );

        if ($this->file_size)
            if (@$this->field->config["validator_func"]) {
                if ($result = call_user_func( $this->field->config["validator_func"],
                                              $this->field->model->Model_GetDataValue(),
                                              $this->field->config ))
                    $this->_Invalidate( "func", $result );
        }

        return $this->valid;
    }
    
    // quick pre-validation
    function _CheckExtSize( $ext, $size )
    {
        if (isset( $this->field->config["file_size"]))
            if ($size > $this->field->config["file_size"]*1024)
                return false;
        if (isset( $this->field->config["file_ext"]))
            if (!in_array($ext,$this->field->config["file_ext"]))
                return false;
        return true;
    }

    // INTERFACE ==============================================================================
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        parent::Interface_Parse();

        $tpl = &Locator::get('tpl');
        $name = $this->field->model->Model_GetDataValue();
        $file_size = $this->_GetSize( $this->field->name );
        if ($file_size === false)
        {
            $tpl->set("interface_file", false);
        }
        else
        {
            $tpl->Set("interface_file", $name );
            $tpl->Set("interface_size_Kb", floor(($file_size+512)/1024));
        }

        return $tpl->Parse( $this->field->form->config["template_prefix_interface"].
               $this->field->config["interface_tpl"] );
    }
    
    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray( $post_data )
    {
        // @todo: загрузить файл, положить его свойства в себя, а имя в массив
        /*$value = $this->_UploadFile($post_data);

        if ($value === false) return array(); // no data here

        $a = array(
            $this->field->name => $value,
        );*/
        
        $this->file_size = $_FILES['_'.$this->field->name]['size'];
        $fileName = $_FILES['_'.$this->field->name]['name'];
        $this->file_ext = $ext = substr($fileName, strrpos($fileName, '.') + 1);

        return array();
    }
    
    function Model_DbAfterInsert($dataId)
    {
        $this->_uploadFile();
    }
    
    function Model_DbAfterUpdate($dataId)
    {
        $this->_uploadFile();
    }

    // ---------------------------------------------------------------------------
    // UPLOAD specific handlers
    function _GetSize( $field_name )
    {
        return $_FILES['_'.$field_name]['size'];
    }
    
    function _UploadFile()
    {
        $upload = &Locator::get('upload');
        $upload->dirSwap(Config::get('files_dir').$this->field->config['file_dir'].'/');
        $file = $upload->uploadFile(
            '_'.$this->field->name,
            str_replace('*', $this->field->form->data_id, $this->field->config['file_name']),
            false,
            $this->field->config['params']
        );
        $upload->dirUnSwap();
        return $file;
    }
    
    /*function _UploadFile( $post_data )
    {
        $uploaded_file = @$_FILES[ '_'.$this->field->name ]["tmp_name"];
        if(is_uploaded_file($uploaded_file))
        {
            //клиентские данные
            $type = $_FILES[ '_'.$this->field->name ]['type'];
            $size = $_FILES[ '_'.$this->field->name ]['size'];
            $ext = explode(".",$_FILES[ '_'.$this->field->name ]['name']);
            $ext = strtolower(end($ext));

            $this->file_size = $size;
            $this->file_ext  = $ext;
            $this->file_type = $type;
            $this->file_uploaded = true;

            if ($this->_CheckExtSize($ext, $size))
            {
                Finder::useLib( "Translit", "php/translit" );

                if (isset($this->field->config["file_random_name"]) && $this->field->config["file_random_name"])
                {
                    $name = substr( md5(time()), 0, 6 );
                }
                else
                {
                    $name = basename( $_FILES[ '_'.$this->field->name ]['name'] );
                    $name = substr($name, 0, strlen($name)-strlen($ext)-1 );
                    $name = Translit::Supertag( $name, TR_NO_SLASHES);
                }

                $count=1; $_name = $name;
                while (file_exists($this->field->config["file_dir"].$name.".".$ext))
                {
                    if ($name === $_name) $name = $_name.$count;
                    else $name = $_name.(++$count);
                }
                $file_name = $name.".".$ext;
                $full_name = $this->field->config["file_dir"].$file_name;
                move_uploaded_file($uploaded_file,$full_name);
                chmod($full_name,$this->field->config["file_chmod"]);
                $this->file_name = $file_name;
                return $file_name;
            }
            else return "[error]";
        }
        else
        {
            $this->file_uploaded = false;
            return false;
        }
    }*/
}

?>
