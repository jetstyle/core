<?php
/**
 * File
 *
 * @package config
 * @author lunatic <lunatic@jetstyle.ru>
 * @since version 0.4
 */
class File implements ArrayAccess {
    private static $filesInfoCache = array();
    private static $filesInfoByIdCache = array();
    private static $imageExts = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
    private static $flashExts = array('swf');
    private static $webFilesDir = null;

    const FILE_NEW = 1;
    const FILE_DUPLICATE = 2;

    /**
     * File config
     *
     * @var array
     */
    private $config = array();

    /**
     * Linked object id
     *
     * @var int
     */
    private $objId = null;

    /**
     * File id
     *
     * @var int
     */
    private $id = null;

    /**
     * Directory of the file
     *
     * @var string
     */
    private $dir = '';

    private $data = array();
    private $loaded = false;

    private $html = null;

    public static function precacheByObjIds($key, $ids) {
        if (!is_array($ids) || empty($ids)) {
            return;
        }

        if (!is_array(self::$filesInfoCache[$key])) {
            self::$filesInfoCache[$key] = array();
        }

        $db = &Locator::get('db');
        $sql = "
                SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`, f.`filesize`, `f2o`.`obj_id`
                FROM ??files2objects AS f2o
                INNER JOIN ??files AS f ON (f2o.`file_id` = f.`id`)
                WHERE f2o.`obj_id` IN (".$db->quote($ids).") AND f2o.`key` = ".$db->quote($key)."
        ";
        $sqlResult = $db->execute($sql);

        while ($result = $db->getRow($sqlResult)) {
            self::$filesInfoCache[$key][$result['obj_id']] = $result['id'];
            self::$filesInfoByIdCache[$result['id']] = $result;
        }

        foreach ($ids AS $id) {
            if (!array_key_exists($id, self::$filesInfoCache[$key])) {
                self::$filesInfoCache[$key][$id] = null;
            }
        }
    }

    public static function precacheByIds($ids) {
        if (!is_array($ids) || empty($ids)) {
            return;
        }

        $db = &Locator::get('db');
        $sql = "
                SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`, f.`filesize`
                FROM ??files AS f
                WHERE f.`id` IN (".$db->quote($ids).")
        ";

        $sqlResult = $db->execute($sql);

        while ($result = $db->getRow($sqlResult)) {
            self::$filesInfoByIdCache[$result['id']] = $result;
        }
    }

    public static function getFileInfoByObjId($key, $id) {
        if (!is_array(self::$filesInfoCache[$key])) {
            self::$filesInfoCache[$key] = array();
        }

        if (!array_key_exists($id, self::$filesInfoCache[$key])) {
            $db = &Locator::get('db');
            $sql = "
                    SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`, f.`filesize`
                    FROM ??files2objects AS f2o
                    INNER JOIN ??files AS f ON (f2o.`file_id` = f.`id`)
                    WHERE f2o.`obj_id` = ".$db->quote($id)." AND f2o.`key` = ".$db->quote($key)."
            ";
            $result = $db->queryOne($sql);

            self::$filesInfoCache[$key][$id] = $result['id'];
            self::$filesInfoByIdCache[$result['id']] = $result;
        }

        return self::$filesInfoByIdCache[self::$filesInfoCache[$key][$id]];
    }

    public static function getFileInfoById($id) {
        if (!array_key_exists($id, self::$filesInfoByIdCache)) {
            $db = &Locator::get('db');
            $sql = "
                    SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`, f.`filesize`
                    FROM ??files AS f
                    WHERE f.`id` = ".$db->quote($id)."
            ";
            self::$filesInfoByIdCache[$id] = $db->queryOne($sql);
        }

        return self::$filesInfoByIdCache[$id];
    }

    public static function isImageExt($ext) {
        return in_array($ext, self::$imageExts);
    }

    public static function isFlashExt($ext) {
        return in_array($ext, self::$flashExts);
    }

    public function __construct($config = array()) {
        if (null === self::$webFilesDir) {
            self::$webFilesDir = (Config::get('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl).str_replace(Config::get('project_dir'), '', Config::get('files_dir'));
        }

        if (is_array($config)) {
            $this->config = $config;
        }

        // default dir for new files
        $this->dir = $this->config['actions_hash'];
        $this->dir .= ($this->dir ? '/' : '').date('Y/m');
    }

    public function setObjId($id) {
        if ($this->id !== null || $this->objId !== null) {
            throw new JSException("File: can't set both id AND object_id");
        }

        $this->objId = intval($id);
        $this->init();
    }

    public function setId($id) {
        if ($this->id !== null || $this->objId !== null) {
            throw new JSException("File: can't set both id AND object_id");
        }

        $this->id = intval($id);
        $this->init();
    }


    public function __toString() {
        if (null === $this->html) {
            $result = '';

            if ($this->isLoaded()) {
                if (self::isImageExt($this->data['ext'])) {
                    $result = '<img src="'.$this->data['link'].'" />';
                }
                else {
                    $result = '<a href="'.$this->data['link'].'">скачать</a>';
                }
            }

            $this->html = $result;
        }

        return $this->html;
    }

    public function isLoaded() {
        return $this->loaded;
    }

    public function isImage() {
        if (!array_key_exists('is_image', $this->data)) {
            if ($this->isLoaded()) {
                $this->data['is_image'] = self::isImageExt($this->data['ext']);
            }
            else {
                $this->data['is_image'] = false;
            }
        }

        return $this->data['is_image'];
    }

    public function isFlash() {
        if (!array_key_exists('is_flash', $this->data)) {
            if ($this->isLoaded()) {
                $this->data['is_flash'] = self::isFlashExt($this->data['ext']);
            }
            else {
                $this->data['is_flash'] = false;
            }
        }

        return $this->data['is_flash'];
    }

    /**
     * Upload file
     *
     */
    public function upload($data, $rubricId = 0) {
    // TODO: add states (new file, restored from db, err)

        Finder::useClass('FileUpload');
        $upload = new FileUpload();
        $upload->setDir($this->dir);
        $fileData = $upload->uploadFile($data, $this->buildParams());

        // primary file
        if (!array_key_exists('subkey', $this->config)) {
        // @TODO: check filehash before uploading file
            $fileHash = md5_file($fileData['name_full']);
            if ($fileHash) {
                $db = &Locator::get('db');

                // check for duplicates
                $checkResult = $db->queryOne("
                        SELECT id, dirname, filename, ext
                        FROM ??files
                        WHERE hash = ".$db->quote($fileHash)."
                ");

                // duplicate
                if ($checkResult['id']) {
                    $dir = '';
                    if ($checkResult['dirname']) {
                        $dir .= ($dir ? '/' : '').$checkResult['dirname'];
                    }

                    $dir = Config::get('files_dir').$dir;

                    if (!file_exists($dir.'/'.$checkResult['filename'].'.'.$checkResult['ext'])) {
                        // @TODO: same code as in FileUpload.php
                        // need refactor
                        if (!is_dir($dir)) {
                            if (!@mkdir($dir, 0775, true)) {
                                throw new UploadException("Can't create directory ".str_replace(Config::get('project_dir'), '', $dir));
                            }
                        }
                        elseif (!is_writable($dir)) {
                            throw new UploadException("Directory ".str_replace(Config::get('project_dir'), '', $dir)." is not writable");
                        }

                        copy($fileData['name_full'], $dir.'/'.$checkResult['filename'].'.'.$checkResult['ext']);
                    }

                    if ($dir.'/'.$checkResult['filename'].'.'.$checkResult['ext'] != $fileData['name_full'])
                    {
                        // delete uploaded file
                        @unlink($fileData['name_full']);
                    }

                    if ($this->objId) {
                        $this->id = $checkResult['id'];

                        // link file to object
                        if ($this->config['conf'] && $this->config['key']) {
                            $db->insert("
                                    REPLACE INTO ??files2objects
                                    (`obj_id`, `key`, `file_id`)
                                    VALUES
                                    (
                                            ".$db->quote($this->objId).",
                                            ".$db->quote($this->config['conf'].':'.$this->config['key']).",
                                            ".$db->quote($this->id)."
                                    )
                            ");
                        }
                    }
                    elseif ($this->id) {
                        if ($this->id == $checkResult['id']) {
                            return;
                        }
                        else {
                            throw new UploadException("Same file already exists");
                        //$this->id = $checkResult['id'];
                        }
                    }
                    else {
                        $this->id = $checkResult['id'];
                    }
                }
                // insert file in DB
                else {
                    if ($this->id && !$this->objId) {
                        $this->deleteFromFilesystem();

                        // update data in DB
                        $db->query("
                                UPDATE ??files
                                SET
                                        filename = ".$db->quote($fileData['filename']).",
                                        ext = ".$db->quote($fileData['ext']).",
                                        dirname = ".$db->quote($this->dir).",
                                        hash = ".$db->quote($fileHash).",
                                        filesize = ".$db->quote(filesize($fileData['name_full'])).",
                                        is_image = ".(self::isImageExt($fileData['ext']) ? 1 : 0)."
                                        WHERE id = ".intval($this->id)."
                        ");
                    }
                    else {
                        $this->id = $db->insert("
                                INSERT INTO ??files
                                (filename, ext, dirname, hash, filesize, is_image, _created)
                                VALUES
                                (
                                        ".$db->quote($fileData['filename']).",
                                        ".$db->quote($fileData['ext']).",
                                        ".$db->quote($this->dir).",
                                        ".$db->quote($fileHash).",
                                        ".$db->quote(filesize($fileData['name_full'])).",
                                        ".(self::isImageExt($fileData['ext']) ? 1 : 0).",
                                        NOW()
                                )
                        ");
                    }

                    // link file to object
                    if ($this->objId && $this->config['conf'] && $this->config['key']) {
                        $db->insert("
                                REPLACE INTO ??files2objects
                                (`obj_id`, `key`, `file_id`)
                                VALUES
                                (
                                        ".$db->quote($this->objId).",
                                        ".$db->quote($this->config['conf'].':'.$this->config['key']).",
                                        ".$db->quote($this->id)."
                                )
                        ");
                    }
                }
            }
            // smthg wrong
            else {
                throw new UploadException("Can't calculate md5 hash for uploaded file");
            }

            unset(self::$filesInfoCache[$this->config['conf'].':'.$this->config['key']][$this->objId]);
            unset(self::$filesInfoByIdCache[$this->id]);
        }

        $this->init();
    }

    public function updateData($data) {
        if (!$this->id) {
            return;
        }

        $model = DBModel::factory('Files');
        $model->update($data, '{'.$model->getPk().'} = '.DBModel::quote($this->id));
    }

    public function getLinkedRubricsIds() {
        $result = array();

        if ($this->id) {
            $db = &Locator::get('db');
            $sqlResult = $db->execute("
				SELECT `rubric_id` FROM ??files2rubrics WHERE `file_id` = ".$db->quote($this->id)."
			");
            while ($r = $db->getRow($sqlResult)) {
                $result[] = $r['rubric_id'];
            }
        }

        return $result;
    }
    
    public function getLinkedObjectsIds() {
        $result = array();

        if ($this->id) {
            $db = &Locator::get('db');
            $sqlResult = $db->execute("
				SELECT `obj_id`, `key` FROM ??files2objects WHERE `file_id` = ".$db->quote($this->id)."
			");
            while ($r = $db->getRow($sqlResult)) {
                $result[$r['key']][] = $r['obj_id'];
            }
        }

        return $result;
    }

    public function addToRubric($rubricId) {
        $rubricId = intval($rubricId);
        if (!$this->id || !$rubricId) {
            return;
        }

        $rubricsIds = $this->getLinkedRubricsIds();
        if (in_array($rubricId, $rubricsIds)) {
            return;
        }

        $db = &Locator::get('db');

        $maxOrderResult = $db->queryOne("
                SELECT MAX(`_order`) AS mo
                FROM ??files2rubrics
                WHERE `rubric_id` = ".$db->quote($rubricId)."
        ");

        $maxOrder = $maxOrderResult['mo'] + 1;

        $db->query("
                REPLACE INTO ??files2rubrics
                (`file_id`, `rubric_id`, `_order`)
                VALUES
                (".$db->quote($this->id).", ".$db->quote($rubricId).", ".$db->quote($maxOrder).")
        ");
    }

    public function removeFromRubric($rubricId) {
        $rubricId = intval($rubricId);
        if (!$this->id || !$rubricId) {
            return;
        }

        $db = &Locator::get('db');
        $db->query("
                DELETE FROM ??files2rubrics
                WHERE
                        `file_id` = ".$db->quote($this->id)."
                                AND
                        `rubric_id` = ".$db->quote($rubricId)."
        ");
    }

    public function delete() {
        if (!$this->id) {
            return;
        }

        $db = &Locator::get('db');
        $db->query("
                DELETE FROM ??files
                WHERE `id` = ".$db->quote($this->id)."
        ");

        $this->deleteLinksToObjects();
        $this->deleteLinksToRubrics();

        $this->deleteFromFilesystem();

        unset(self::$filesInfoCache[$this->config['conf'].':'.$this->config['key']][$this->objId]);
        unset(self::$filesInfoByIdCache[$this->id]);

        unset($this->id, $this->objId);

        $this->init();
    }

    public function deleteLink() {
        if (!$this->objId) {
            return;
        }

        $db = &Locator::get('db');
        $db->query("
                DELETE FROM ??files2objects
                WHERE `obj_id` = ".$db->quote($this->objId)." AND `key` = ".$db->quote($this->config['conf'].':'.$this->config['key'])."
        ");

        unset(self::$filesInfoCache[$this->config['conf'].':'.$this->config['key']][$this->objId]);
        unset(self::$filesInfoByIdCache[$this->id]);

        $this->init();
    }

    public function deleteLinksToObjects() {
        if (!$this->id) {
            return;
        }

        $db = &Locator::get('db');

        $db->query("
                DELETE FROM ??files2objects
                WHERE `file_id` = ".$db->quote($this->id)."
        ");
    }

    public function deleteLinksToRubrics() {
        if (!$this->id) {
            return;
        }

        $db = &Locator::get('db');
        $db->query("
                DELETE FROM ??files2rubrics
                WHERE `file_id` = ".$db->quote($this->id)."
        ");
    }

    public function deleteFromFilesystem() {
        if (!$this->data['name_short']) {
            return;
        }

        $dirname = Config::get('files_dir');
        if ($handle = opendir($dirname)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && is_dir($dirname.'/'.$file) && strlen($file) == 32) {
                    @unlink($dirname.'/'.$file.'/'.$this->dir.($this->dir ? '/' : '').$this->data['name_short']);
                }
            }
            closedir($handle);
        }

        @unlink($this->data['name_full']);
    }

    public function getArray() {
        $result = array();
        if ($this->id) {
            $result = $this->data;
            foreach (array('is_loaded', 'is_image', 'width', 'height', 'filesize') AS $key) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = $this[$key];
                }
            }
        }

        return $result;
    }

    // array access
    public function offsetExists($key) {
        return isset($this->data[$key]);
    }

    public function offsetGet($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
        elseif ($key == 'is_loaded') {
            return $this->isLoaded();
        }
        elseif ($key == 'is_flash') {
            return $this->isFlash();
        }
        elseif ($key == 'is_image') {
            return $this->isImage();
        }
        elseif (($key == 'width' || $key == 'height') && $this->data['name_full']) {
            if ($this->isImage()) {
                list($width, $height) = @getimagesize($this->data['name_full']);
                $this->data['height'] = $height;
                $this->data['width'] = $width;
            }
            else {
                $this->data['height'] = 0;
                $this->data['width'] = 0;
            }

            return $this->data[$key];
        }
        elseif ($key == 'filesize' && $this->data['name_full']) {
            $this->data['filesize'] = @filesize($this->data['name_full']);
            return $this->data[$key];
        }
    }

    public function offsetSet($key, $value) {
        $this->data[$key] = $value;
    }

    public function offsetUnset($key) {
        unset($this->data[$key]);
    }
    // END array access


    private function init() {
        $this->data = array();
        $this->loaded = false;

        $data = array();

        if ($this->objId && $this->config['conf'] && $this->config['key']) {
            $data = self::getFileInfoByObjId($this->config['conf'].':'.$this->config['key'], $this->objId);
        }
        elseif ($this->id) {
            $data = self::getFileInfoById($this->id);
        }

        if (is_array($data) && !empty($data)) {
            $this->dir = $this->config['actions_hash'];
            if ($data['dirname']) {
                $this->dir .= ($this->dir ? '/' : '').$data['dirname'];
            }

            if (file_exists(Config::get('files_dir').$this->dir.'/'.$data['filename'].'.'.$data['ext'])) {
                $this->loaded = true;
                $this->data = $data;
                $this->data['name_short'] = $data['filename'].'.'.$data['ext'];
                $this->data['name_full'] = Config::get('files_dir').$this->dir.'/'.$this->data['name_short'];
                $this->data['link'] = self::$webFilesDir.$this->dir.'/'.$this->data['name_short'];

                $this->id = $this->data['id'];
            }
            // try to generate file from parent
            elseif ($this->config['subkey']) {
                if ($this->objId) {
                    $id = $this->objId;
                    $isFileId = false;
                }
                else {
                    $id = $this->id;
                    $isFileId = true;
                }

                $sourceFile = FileManager::getFile($this->config['conf'].':'.$this->config['key'], $id, $isFileId);

                if ($sourceFile->isLoaded()) {
                    $this->upload($sourceFile['name_full']);
                    // we stop here, because init method is called again from upload
                    return;
                }
            }
        }

        if (empty($this->data)) {
            $this->dir = $this->config['actions_hash'];
            $this->dir .= ($this->dir ? '/' : '').date('Y/m');
        }
    }

    private function buildParams() {
        return array(
        'actions' => $this->config['actions'],
        );
    }
}
?>