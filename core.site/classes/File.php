<?php
/**
 * File
 * 
 * @package config
 * @author lunatic <lunatic@jetstyle.ru>
 * @since version 0.4 
 */
class File implements ArrayAccess
{
	private static $filesInfoCache = array();
	private static $filesInfoByIdCache = array();
	private static $imageExts = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
	private static $webFilesDir = null;
	
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
	
	public static function getFileInfoByObjId($key, $id)
	{
		if (!is_array(self::$filesInfoCache[$key]))
		{
			self::$filesInfoCache[$key] = array();
		}
		
		if (!array_key_exists($id, self::$filesInfoCache[$key]))
		{
			$db = &Locator::get('db');
			$sql = "
				SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`
				FROM ??files2objects AS f2o
				INNER JOIN ??files AS f ON (f2o.`file_id` = f.`id`)
				WHERE f2o.`obj_id` = ".$db->quote($id)." AND f2o.`key` = ".$db->quote($key)."
			";
			$result = $db->queryOne($sql);
			
			self::$filesInfoCache[$key][$id] = $result;
			self::$filesInfoByIdCache[$result['id']] = $result;
		}
		
		return self::$filesInfoCache[$key][$id];
	}
	
	public static function getFileInfoById($id)
	{
		if (!array_key_exists($id, self::$filesInfoByIdCache))
		{
			$db = &Locator::get('db');
			$sql = "
				SELECT f.`id`, f.`title`, f.`filename`, f.`ext`, f.`dirname`
				FROM ??files AS f
				WHERE f.`id` = ".$db->quote($id)."
			";
			self::$filesInfoByIdCache[$id] = $db->queryOne($sql);
		}
		
		return self::$filesInfoByIdCache[$id];
	}
	
	public static function isImageExt($ext)
	{
		return in_array($ext, self::$imageExts);
	}
	
	public function __construct($config = array())
	{
		if (null === self::$webFilesDir)
		{
			self::$webFilesDir = (Config::get('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl).str_replace(Config::get('project_dir'), '', Config::get('files_dir'));
		}
		
		if (is_array($config))
		{
			$this->config = $config;
		}
		
		// default dir for new files
		$this->dir = $this->config['actions_hash'];
		$this->dir .= ($this->dir ? '/' : '').date('Y/m');
	}
	
	public function setObjId($id)
	{
		if ($this->id !== null || $this->objId !== null)
		{
			throw new JSException("File: can't set both id AND object_id");
		}
		
		$this->objId = intval($id);
		$this->init();
	}
	
	public function setId($id)
	{
		if ($this->id !== null || $this->objId !== null)
		{
			throw new JSException("File: can't set both id AND object_id");
		}
		
		$this->id = intval($id);
		$this->init();
	}


	public function __toString()
	{
		if (null === $this->html)
		{
			$result = '';
			
			if ($this->isLoaded())
			{
				if (self::isImageExt($this->data['ext']))
				{
					$result = '<img src="'.$this->data['link'].'" />';
				}
				else
				{
					$result = '<a href="'.$this->data['link'].'">скачать</a>';
				}
			}
			
			$this->html = $result;
		}
		
		return $this->html;
	}
	
	public function isLoaded()
	{
		return $this->loaded;
	}
	
	/**
	 * Upload file
	 *
	 */
	public function upload($data, $rubricId = 0)
	{
		// TODO: add states (new file, restored from db, err)
		
		Finder::useClass('FileUpload');
		$upload = new FileUpload();
		$upload->setDir($this->dir);
		$fileData = $upload->uploadFile($data, $this->buildParams());
		
		// primary file
		if (!array_key_exists('subkey', $this->config))
		{
			// @TODO: check filehash before uploading file
			if ($fileHash = md5_file($fileData['name_full']))
			{
				$db = &Locator::get('db');
	
				// check for duplicates
				$checkResult = $db->queryOne("
					SELECT id 
					FROM ??files
					WHERE hash = ".$db->quote($fileHash)."
				");
								
				// duplicate
				if ($checkResult['id'])
				{
					// delete uploaded file
					@unlink($fileData['name_full']);
					
					if ($this->objId)
					{
						$this->id = $checkResult['id'];
						
						// link file to object
						if ($this->config['conf'] && $this->config['key'])
						{
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
					elseif ($this->id)
					{
						if ($this->id == $checkResult['id'])
						{
							return;
						}
						else
						{
							throw new UploadException("Same file already exists");
						}
					}
					else
					{
						$this->id = $checkResult['id'];
					}
				}
				// insert file in DB
				else
				{
					if ($this->id)
					{
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
						");
					}
					else
					{
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
					if ($this->objId && $this->config['conf'] && $this->config['key'])
					{
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
			else
			{
				throw new UploadException("Can't calculate md5 hash for uploaded file");
			}
			
			unset(self::$filesInfoCache[$this->config['conf'].':'.$this->config['key']][$this->objId]);
			unset(self::$filesInfoByIdCache[$this->id]);
		}
		
		$this->init();
	}
	
	public function updateData($data)
	{
		if (!$this->id)
		{
			return;
		}
		
		$model = DBModel::factory('Files');
		$model->update($data, '{'.$model->getPk().'} = '.DBModel::quote($this->id));
	}
	
	public function addToRubric($rubricId)
	{
		$rubricId = intval($rubricId);
		if (!$this->id || !$rubricId)
		{
			return;
		}
		
		//@TODO check, if file is already in this rubric
		
		$db = &Locator::get('db');
		$db->query("
			REPLACE INTO ??files2rubrics
			(`file_id`, `rubric_id`)
			VALUES
			(".$db->quote($this->id).", ".$db->quote($rubricId).")
		");
	}
	
	public function delete()
	{
		if (!$this->id)
		{
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
		
		unset($this->id);
		
		$this->init();
	}
	
	public function deleteLink()
	{
		if (!$this->objId)
		{
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
	
	public function deleteLinksToObjects()
	{
		if (!$this->id)
		{
			return;
		}
		
		$db = &Locator::get('db');
		
		$db->query("
			DELETE FROM ??files2objects
			WHERE `file_id` = ".$db->quote($this->id)."
		");
	}
	
	public function deleteLinksToRubrics()
	{
		if (!$this->id)
		{
			return;
		}
		
		$db = &Locator::get('db');
		$db->query("
			DELETE FROM ??files2rubrics
			WHERE `file_id` = ".$db->quote($this->id)."
		");
	}
	
	public function deleteFromFilesystem()
	{
		if (!$this->data['name_short'])
		{
			return;
		}
		
		$dirname = Config::get('files_dir');
		if ($handle = opendir($dirname)) 
		{
		    while (false !== ($file = readdir($handle))) 
		    {
		    	if ($file != "." && $file != ".." && is_dir($dirname.'/'.$file) && strlen($file) == 32) 
		        {
					@unlink($dirname.'/'.$file.'/'.$this->dir.($this->dir ? '/' : '').$this->data['name_short']);
		        }
		    }
		    closedir($handle);
		}
		
		@unlink($this->data['name_full']);
	}
	
	// array access
	public function offsetExists($key)
	{
		return isset($this->data[$key]);
	}
	
	public function offsetGet($key)
	{
		if ($key == 'id')
		{
			return $this->id;
		}
		elseif ($key == 'is_loaded')
		{
			return $this->isLoaded();
		}
		elseif (($key == 'width' || $key == 'height') && !array_key_exists($key, $this->data) && $this->data['name_full'])
		{
			list($width, $height) = @getimagesize($this->data['name_full']);
			$this->data['height'] = $height;
			$this->data['width'] = $width;
		}
		elseif ($key == 'filesize' && !array_key_exists($key, $this->data) && $this->data['name_full'])
		{
			$this->data['filesize'] = @filesize($this->data['name_full']);
		}
		return $this->data[$key];
	}
	
	public function offsetSet($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function offsetUnset($key)
	{
		unset($this->data[$key]);
	}
	// END array access

	
	private function init()
	{
		$this->data = array();
		$this->loaded = false;
		
		$data = array();
		
		if ($this->objId && $this->config['conf'] && $this->config['key'])
		{
			$data = self::getFileInfoByObjId($this->config['conf'].':'.$this->config['key'], $this->objId);
		}
		elseif ($this->id)
		{
			$data = self::getFileInfoById($this->id);
		}
		
		if (is_array($data) && !empty($data))
		{			
			$this->dir = $this->config['actions_hash'];
			if ($data['dirname'])
			{
				$this->dir .= ($this->dir ? '/' : '').$data['dirname'];
			}
			
			if (file_exists(Config::get('files_dir').$this->dir.'/'.$data['filename'].'.'.$data['ext']))
			{
				$this->loaded = true;
				$this->data = $data;
				$this->data['name_short'] = $data['filename'].'.'.$data['ext'];
				$this->data['name_full'] = Config::get('files_dir').$this->dir.'/'.$this->data['name_short'];
				$this->data['link'] = self::$webFilesDir.$this->dir.'/'.$this->data['name_short'];
				
				$this->id = $this->data['id'];
			}
			// try to generate file from parent
			elseif ($this->config['subkey'])
			{
				if ($this->objId)
				{
					$id = $this->objId;
					$isFileId = false;
				}
				else
				{
					$id = $this->id;
					$isFileId = true;
				}
				
				$sourceFile = FileManager::getFile($this->config['conf'].':'.$this->config['key'], $id, $isFileId);
				
				if ($sourceFile->isLoaded())
				{
					$this->upload($sourceFile['name_full']);
					return;
				}
			}
		}
		
		if (empty($this->data))
		{	
			$this->dir = $this->config['actions_hash'];
			$this->dir .= ($this->dir ? '/' : '').date('Y/m');
		}
	}
	
	private function buildParams()
	{
		return array(
			'actions' => $this->config['actions'],
		);
	}
}
?>