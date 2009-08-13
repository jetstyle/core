<?php
/**
 *
 * TODO:
 *  предупреждать юзера о несовпадении размеров
 *
 */

Finder::useClass('FormCalendar');

class FormGallery extends FormCalendar
{
	protected $galleryFilesModel = null;

	public function handle()
	{
		$this->load();
		
		$this->handleGalleryUpload();

		if ($_POST['action'] == 'delete')
		{
			$this->deleteGalleryFiles(explode(',',$_POST['items']));
			
			Finder::useClass('Json');
            echo Json::encode(array('ok' => true));
            die();
		}

		if ($_POST['action'] == 'reorder')
		{
			$this->reorderGallery(explode(',',$_POST['order']));
			
			Finder::useClass('Json');
            echo Json::encode(array('ok' => true));
            die();
		}

		if ($_POST['action'] == 'edit')
		{
			$title = iconv('utf-8', 'cp1251', $_POST['title']);
			$data = array(
				'title' => $title,
				'title_pre' => Locator::get('typografica')->correct($title, '')
			);
			
			$itemId = $_POST['id'];
			$this->editGalleryFileData($itemId, $data);
					
			Finder::useClass('Json');
            echo Json::encode(array('ok' => true));
            die();
		}

		
		$tpl = Locator::get('tpl');
		
		$tpl->set('file_extensions', $this->getAllowedExtsForGallery());
		
		$thumbSize = $this->getThumbSize();
		if ($thumbSize)
		{
			$tpl->set('thumb_width', $thumbSize[0]);
			$tpl->set('thumb_height', $thumbSize[1]);
		}
		
		$tpl->set('session_hash', Locator::get('principal')->getSessionModel()->getSessionHash());
		$tpl->set('base_url', RequestInfo::$baseUrl.RequestInfo::$pageUrl);
		$tpl->set('rubric_id', $this->id);
		
		parent::handle();
	}

	protected function getAllowedExtsForGallery()
	{
		$exts = '';
		Finder::useClass('File');

		foreach (explode(',', Config::get('upload_ext')) AS $ext)
		{
			$ext = trim($ext);
			if ($this->config['allowNonImageFiles'] || File::isImageExt($ext))
				$exts .= '*.'.$ext.';';
		}
		
		return $exts;
	}

	protected function reorderGallery($newOrder)
	{
		if (is_array($newOrder))
		{
			$filesModel = $this->getGalleryFilesModel();
			if ($filesModel) 
			{
				foreach($newOrder AS $pos => $itemId)
				{
					$itemId = intval($itemId);
					$pos = intval($pos);

					if ($itemId && $pos >= 0)
					{
						$data = array('_order' => $pos);
						$filesModel->update($data, '{'.$filesModel->getPk().'} = '.DBModel::quote($itemId));
					}
				}
			}
		}
	}
	
	protected function editGalleryFileData($itemId, $data)
	{
		$filesModel = $this->getGalleryFilesModel();
		if ($filesModel) 
		{
			$filesModel->update($data, '{'.$filesModel->getPk().'} = '.DBModel::quote($itemId));
		}
	}

	protected function getGalleryFilesModel()
	{
		if ($this->galleryFilesModel === null)
		{
            if (!$this->config['files_model'])
            {
                throw new JSException("You should set `files_model` param in config");
            }
			$this->galleryFilesModel = DBModel::factory($this->config['files_model']);
		}
		
		return $this->galleryFilesModel;
	}

	protected function getGalleryFile($itemId)
	{
		$filesModel = $this->getGalleryFilesModel();
		if ($filesModel) 
		{
			$result = $filesModel->loadOne('{'.$filesModel->getPk().'} = '.DBModel::quote($itemId).'')->getArray();
		}
		else
		{
			$result = array();
		}
		
		return $result;
	}

	protected function handleGalleryUpload()
	{
		if (!empty($_FILES[$this->prefix.'Filedata']) && !empty($_FILES[$this->prefix.'Filedata']['name']))
		{
			if ($_POST['item_id'])
			{
				$this->replaceUploadedGalleryFile($_POST['item_id']);
				$item = $this->getGalleryFile($_POST['item_id']);
				$item['ok'] = true;
		        
                Finder::useClass('Json');
                echo Json::encode($item);
                die();
			}
			else
			{
				$itemId = $this->uploadGalleryFile();
				
				if ($itemId)
				{
					$item = $this->getGalleryFile($itemId);
				}
				else
				{
					$item = array();
				}
				
				$tpl = Locator::get('tpl');
				$tpl->set('*', $item);
				
				$thumbSizes = $this->getThumbSize();
				if ($thumbSizes)
				{
					$tpl->set('thumb_width', $thumbSizes[0]);
					$tpl->set('thumb_height', $thumbSizes[1]);
				}

				$result = array(
					'ok' => true,
					'html' => $tpl->parse('gallery.html:gallery_item')
				);
				
				Finder::useClass('Json');
                echo Json::encode($result);
                die();
			}
		}
	}
	
	protected function uploadGalleryFile()
	{
		$itemId = 0;
		$filesModel = $this->getGalleryFilesModel();
		if ($filesModel) 
		{
			$data = array(
				'rubric_id' => $this->id,
				'title' => 'Заголовок',
				'title_pre' => 'Заголовок'
			);

			$itemId = $filesModel->insert($data);

			$data = array('_order' => $itemId);
			$filesModel->update($data, '{'.$filesModel->getPk().'} = '.DBModel::quote($itemId));
		}
		
		$this->uploadFiles($itemId);
		
		return $itemId;
	}
	
	protected function replaceUploadedGalleryFile($itemId)
	{
		$this->uploadFiles($itemId);
	}
	
	protected function initModel()
	{
		parent::initModel();
		
		$this->model->addField('>>gallery_items', array(
			'pk' => 'id',
			'fk' => 'rubric_id'
		));
		$this->model->addForeignModel('gallery_items', $this->getGalleryFilesModel());
	}

	public function delete()
	{
		$deleteRes = parent::delete();
		if ($deleteRes == 2)
		{
			foreach ($this->item['gallery_items'] AS $item)
            {
                $this->deleteGalleryFile($item['id']);	
            }
		}

		return $deleteRes;
	}
	
	protected function deleteGalleryFiles($items)
	{
		if (is_array($items))
		{
			foreach($items AS $itemId) 
			{
				$this->deleteGalleryFile($itemId);
			}
		}
	}

	protected function deleteGalleryFile($itemId)
	{
		$itemId = intval($itemId);
		
		if ($itemId)
		{
			$filesModel = $this->getGalleryFilesModel();
			if ($filesModel)
			{
				$filesModel->delete('{'.$filesModel->getPk().'} = '.DBModel::quote($itemId));
			}
			
			// form files method
			$this->deleteFiles($itemId);
		}
	}

	protected function getThumbConfig()
	{		
		if ($this->filesConfig['picture']['variants']['thumb'])
		{
			$result = $this->filesConfig['picture']['variants']['thumb'];
		}
		else
		{
			$result = false;
		}
		
		return $result;
	}

	protected function getThumbSize()
	{
		if ($config = $this->getThumbConfig())
		{
			$result = $config['actions']['crop'];
		}
		else
		{
			//$result = false;
			$result = array(100, 100);
		}
		
		return $result;
	}
}
?>