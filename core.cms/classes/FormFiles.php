<?php
Finder::useClass('FormSimple');

class FormFiles extends FormSimple {
    const FILES_RUBRIC_TYPE_ID = 0;
    const PICTURES_RUBRIC_TYPE_ID = 1;
    
    protected $upload;
    protected $max_file_size = 55242880; //максимальный размер файла для загрузки
    protected $template_files = 'formfiles.html';
    
    protected $filesConfig = array();
    protected $configKey = '';
    
    protected $filesRubrics = array();
    
    private $uploadedFiles = array();
    
    protected $upload_errors = array(
        UPLOAD_ERR_INI_SIZE  => 'Размер принятого файла превысил максимально допустимый размер',
        UPLOAD_ERR_FORM_SIZE => 'Размер загружаемого файла превысил значение, указанное в HTML-форме',
        UPLOAD_ERR_PARTIAL   => 'Загружаемый файл был получен только частично',
        UPLOAD_ERR_NO_FILE   => 'Файл не был загружен'
    );
    
    public function __construct( &$config ) {
        Finder::useClass('FileManager');

        $fullConfigKey = $config['module_path'];
        $shortConfigKeyParts = explode('/', $config['module_path']);
        array_pop($shortConfigKeyParts);
        $shortConfigKey = implode('/', $shortConfigKeyParts);

        $filesConfig = FileManager::getConfig($shortConfigKey);
        if (is_array($filesConfig) && !empty($filesConfig)) {
            $this->configKey = $shortConfigKey;
            $this->filesConfig = $filesConfig;
        }
        else {
            $this->configKey = $fullConfigKey;
            $this->filesConfig = FileManager::getConfig($fullConfigKey);
        }

        parent::__construct($config);

        if ($_POST['from_flash'])
        {
            foreach ($_FILES AS $key => $fileData) {
                $_FILES[$key]['name'] = iconv('utf-8', 'cp1251', $_FILES[$key]['name']);
            }
        }

    }

    /**
     * Rubric for files
     *
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
            $rubric->loadOne('{type_id} = '.DBModel::quote($rubricTypeId).' AND {module} = '.DBModel::quote($this->configKey));

            if (!$rubric['id']) {

                $data = array(
                    'module' => $this->configKey,
                    'title' => ModuleConstructor::factory($this->configKey)->getTitle(),
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

    protected function getUploadedFiles() {
        return $this->uploadedFiles;
    }

    public function update() {
        $updateResult = parent :: update();
        if( $updateResult ) {
            return $this->uploadFiles();
        }

        return $updateResult;
    }
    
    protected function uploadFiles($objId = null, $isId = false) {
        $result = true;
        if ($objId === null) {
            $objId = $this->id;
        }
        
        //загружаем и удаляем файлы
        foreach ($this->filesConfig AS $inputName => $conf) {
            if ($conf['input']) {
                $inputName = $conf['input'];
            }
            $file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
            //var_dump($this->configKey.':'.$conf['key'], $objId, $isId);die();
            // проверяем загруженный файл на ошибки
            if ($_POST[$this->prefix.$inputName.'_del']) {
                if ($isId) {
                    $filesRubric = $this->getFilesRubric($file);
                    $file->removeFromRubric($filesRubric['id']);
                }
                else {
                    $file->deleteLink();
                }
            } elseif (isset($_FILES[$this->prefix.$inputName]) && $_FILES[$this->prefix.$inputName]['error']) {
                $err_no = $_FILES[$this->prefix.$inputName]['error'];
                if ($err_no == UPLOAD_ERR_INI_SIZE) {
                    $result = false;
                    $this->tpl->set($inputName.'_err', $this->upload_errors[UPLOAD_ERR_INI_SIZE]);
                } elseif ($err_no == UPLOAD_ERR_FORM_SIZE) {
                    $result = false;
                    $this->tpl->set($inputName.'_err', $this->upload_errors[UPLOAD_ERR_FORM_SIZE]);
                } elseif ($err_no == UPLOAD_ERR_PARTIAL) {
                    $result = false;
                    $this->tpl->set($inputName.'_err', $this->upload_errors[UPLOAD_ERR_PARTIAL]);
                }
            } elseif (is_uploaded_file($_FILES[$this->prefix.$inputName]['tmp_name'])) {
                try {
                    $file->upload($_FILES[$this->prefix.$inputName]);
                    $filesRubric = $this->getFilesRubric($file);
                    $file->addToRubric($filesRubric['id']);
                } catch( UploadException $e ) {
                    $result = false;
                    $this->tpl->set($inputName.'_err', $e->getMessage());
                }

                $this->uploadedFiles[$inputName] = $file;
            }
        }
        
        return $result;
    }

    public function delete() {
        $deleteRes = parent :: delete();

        // delete forever
        if( 2 == $deleteRes ) {
            $this->deleteFiles();
        }

        return $deleteRes;
    }

    protected function deleteFiles($objId = 0, $isId = false) {
        if (!empty($this->filesConfig)) {
            $objId = intval($objId);
            if (!$objId) {
                $objId = $this->id;
            }

            if (!$objId) return;

            foreach ($this->filesConfig AS $conf) {
                $file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
                if ($isId) {
                    //$filesRubric = $this->getFilesRubric($file);
                    //$file->removeFromRubric($filesRubric['id']);
                    $file->delete();
                }
                else {
                    $file->deleteLink();
                }
            }
        }
    }
}
?>
