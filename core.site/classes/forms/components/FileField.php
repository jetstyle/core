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
Finder::UseClass( "forms/FormField" );

class FileField extends FormField
{
    const FILES_RUBRIC_TYPE_ID = 0;
    const PICTURES_RUBRIC_TYPE_ID = 1;

    // MODEL ==============================================================================
    function Model_DbInsert( &$fields, &$values )
    {
        //if ($this->file_uploaded)
        //{
        //  $fields[] = $this->field->name;
        //  $values[] = $this->model_data;
        //}
    }

    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        //return $this->Model_DbInsert( $fields, $values );
    }

    function Model_DbAfterInsert($data_id)
    {
        if ($this->config['variants'] )
        {
            foreach ($this->config['variants'] as $key => $variant)
            {
                $this->config['variants'][$key]['file_name'] = str_replace('*', $data_id, $this->config['variants'][$key]['file_name']);
            }
        }
        $this->_UploadFile($data_id);
    }

    function Model_DbAfterUpdate($data_id)
    {
        $this->Model_DbAfterInsert($data_id);
    }

    // VALIDATOR ==============================================================================
    function Validate()
    {
        parent::Validate();

        if (!$this->valid) return $this->valid; // ==== strip one

        if ($this->config['validator_params']['not_empty'] && !$_FILES['_'.$this->name]['name'])
            $this->_Invalidate( "file_empty", "Файл не выбран" );

        $this->file_size = $_FILES[ '_'.$this->name ]['size'];
        $this->file_ext = substr($_FILES[ '_'.$this->name ]['name'], strrpos($_FILES[ '_'.$this->name ]['name'], '.')+1);

        if ($this->file_size)
            if (isset( $this->config["file_size"]))
                if ($this->file_size > $this->config["file_size"]*1024)
                    $this->_Invalidate( "file_size", "Слишком большой файл" );

        if ($this->file_ext)
            if (isset( $this->config["file_ext"]))
                if (!in_array($this->file_ext,$this->config["file_ext"]))
                    $this->_Invalidate( "file_ext", "Недопустимый тип файла" );

        if ($this->file_size)
            if (@$this->config["validator_func"]) {
                if ($result = call_user_func( $this->config["validator_func"],
                                              $this->model->Model_GetDataValue(),
                                              $this->config ))
            $this->_Invalidate( "func", $result );
        }

        if ((isset( $this->config["min_width"]) || isset( $this->config["min_height"])) && $_FILES['_'.$this->name]['tmp_name'])
        {
            $imageSize = getimagesize($_FILES['_'.$this->name]['tmp_name']);
            if ($imageSize[0] < $this->config["min_width"] || $imageSize[1] < $this->config["min_height"])
                $this->_Invalidate( "file_size", "Слишком маленькая картинка" );
        }

        return $this->valid;
   }
   // quick pre-validation
   function _CheckExtSize( $ext, $size )
   {
     if (isset( $this->config["file_size"]))
       if ($size > $this->config["file_size"]*1024)
         return false;
     if (isset( $this->config["file_ext"]))
       if (!in_array($ext,$this->config["file_ext"]))
         return false;
     return true;
   }

    // INTERFACE ==============================================================================
    // парсинг полей интерфейса
    function Interface_Parse()
    {
        $tpl = Locator::get('tpl');
        parent::Interface_Parse();

        $file = $this->Model_GetDataValue();

        if (!$file || !$file['name_full'])
        {
            $tpl->Set("interface_file", false);
        }
        else
        {
            $tpl->Set("interface_file", $file );
        }

        $ret = $tpl->Parse( $this->form->config["template_prefix_interface"].
                $this->config["interface_tpl"] );

        return $ret;
    }

    function Model_GetDataValue()
    {
        $result = null;
        $id = $this->form->data_id;

        $result = FileManager::getFile($this->config["config_key"], $id);

        if ($result['is_image'])
        {
            list($config, $key) = explode(':', $this->config["config_key"]);
            $config = FileManager::getConfig($config, $key);
            $variants = $config['variants'];
            
            if ($variants)
            {
                foreach ($variants as $variant_name=>$variant)
                {
                    if ($variant['actions']['show'] || count($variants)==1 )
                    {
                        $ret = FileManager::getFile($this->config["config_key"]."/".$variant_name, $id);
                        $ret['original'] = $result;
                        return $ret;
                    }
                }
            }
        }

        return $result;
    }

    // преобразование из поста в массив для загрузки моделью
    function Interface_PostToArray( $post_data )
    {
        if ($value === false) return array(); // no data here
        $a = array(
            $this->name => $value,
        );
        return $a;
    }

   // ---------------------------------------------------------------------------
   // UPLOAD specific handlers
   function _GetSize( $file_name )
   {
     $full_name = $this->config["file_dir"].$file_name;
     if (file_exists($full_name))
       return filesize($full_name);
     else return false;
   }

    function _UploadFile($data_id)
    {
        //$file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
        // News/items:picture , 43, false
        $upload = Locator::get('upload');
        $file = FileManager::getFile($this->config['config_key'], $data_id);

        if ($this->config['variants'])
        {
            foreach ($this->config['variants'] as $variant)
            {
                if ($_POST['_'.$this->name.'_del'])
                {

                    $file = FileManager::getFile($this->config['config_key'], $data_id);
                    $file->deleteLink();
                }
                //$result = $upload->uploadFile('_'.$this->field->name, $this->field->config['file_dir'].'/'.$variant['file_name'], false, $variant['params']);

                //$file = FileManager::getFile($this->field->config['config_key'], $data_id);
            }
            //$file->upload($_FILES['_'.$this->field->name]);
        }

        if ($_FILES['_'.$this->name][tmp_name])
        {
            //var_dumP($this->field->config['config_key'], $data_id);die();

            $file->upload($_FILES['_'.$this->name]);
            if ($this->config["add_to_rubric"])
            {
                $filesRubric = $this->getFilesRubric($file);
                //var_dump($filesRubric['id']); die();
                $file->addToRubric($filesRubric['id']);
            }
        }

        return $result;
    }

    /**
     * Rubric for files
     * c&p from FormFiles
     */
    protected function getFilesRubric($file = null) {
        if ($file && $file->isImage()) {
            $rubricTypeId = self::PICTURES_RUBRIC_TYPE_ID;
        }
        else {
            $rubricTypeId = self::FILES_RUBRIC_TYPE_ID;
        }

        if ( !array_key_exists($rubricTypeId, $this->filesRubrics) ) {
            //$parts = explode('/', $this->config['module_path']);
            //$moduleName = array_shift($parts);

            $rubric = DBModel::factory('FilesRubrics');
            $rubric->loadOne('{type_id} = '.DBModel::quote($rubricTypeId).' AND {module} = '.DBModel::quote($this->config["config_key_module"]));

            if (!$rubric['id']) {

                $data = array(
                    'module' => $this->config["config_key_module"],
                    'title' => ModuleConstructor::factory($this->config["config_key_module"])->getTitle(),
                    'type_id' => $rubricTypeId,
                    '_state' => 0,
                    '_created' => date('Y-m-d H:i:s'),
                );
                $id = $rubric->insert($data);

                $data = array(
                    '_order' => $id,
                );
                $rubric->update($data, '{id} = '.DBModel::quote($id));
                $rubric->loadOne('{id} = '.DBModel::quote($id));
            }
            $this->filesRubrics[$rubricTypeId] = $rubric;
        }

        return $this->filesRubrics[$rubricTypeId];
    }

}

?>

