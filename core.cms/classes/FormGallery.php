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
	const DELETE_OK = 1;
	const DELETE_NEED_APPROVEMENT = 2;

	protected $galleryFilesModel = null;

	public function handle()
	{
        $post = $_POST;
                $files = $_FILES;
                Finder::useLib("UTF8");
                UTF8::autoconvert_request();
        
		$this->handleGalleryUpload();
		$this->handleAjax();

                $_POST = $post;
                $_FILES= $files;
		parent::handle();
	}

	public function delete()
	{
		$deleteRes = parent::delete();
		if ($deleteRes == 2)
		{
			$item = &$this->getItem();
			foreach ($item['gallery_items'] AS $item)
			{
				$this->deleteGalleryFile($item['id']);
			}
		}

		return $deleteRes;
	}

	protected function renderFields()
	{
		$tpl = &$this->tpl;

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

		parent::renderFields();
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
                $this->galleryFilesModel = null;
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

	public function handleAjax()
	{
		if ($_REQUEST['action'] == 'delete')
		{
			$deleteResult = $this->deleteGalleryFiles(explode(',',$_REQUEST['items']));
			$jsonResult = array();

			switch ($deleteResult)
			{
				case self::DELETE_NEED_APPROVEMENT:
					$jsonResult['need_approvement'] = true;
					break;

				case self::DELETE_OK:
				default:
					$jsonResult['ok'] = true;
					break;
			}

			Finder::useClass('Json');
			echo Json::encode($jsonResult);
			die();
		}

		if ($_REQUEST['action'] == 'reorder')
		{
			$this->reorderGallery(explode(',',$_REQUEST['order']));

			Finder::useClass('Json');
			echo Json::encode(array('ok' => true));
			die();
		}

		if ($_REQUEST['action'] == 'edit')
		{
			$title = iconv('utf-8', 'cp1251', $_REQUEST['title']);
            $data = array(
                'title' => $title,
                'title_pre' => Locator::get('typografica')->correct($title, '')
            );
			if (isset($_REQUEST['link'])) {
			    $link = iconv('utf-8', 'cp1251', $_REQUEST['link']);
			    $data['link'] = $link;
			}
			if (isset($_REQUEST['short'])) {
			    $short = iconv('utf-8', 'cp1251', $_REQUEST['short']);
			    $data['short'] = $short;
			}

			$itemId = $_REQUEST['id'];
			$this->editGalleryFileData($itemId, $data);

			Finder::useClass('Json');
			echo Json::encode(array('ok' => true));
			die();
		}
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
                                $item["title"] = iconv("cp1251","utf8", $item["title"]);
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

                                //header("Content-Type: text/html; charset=utf-8");
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

	protected function constructModel()
	{
		$model = parent::constructModel();

		$model->addField('>>gallery_items', array(
			'pk' => 'id',
			'fk' => 'rubric_id'
		));
		$model->addForeignModel('gallery_items', $this->getGalleryFilesModel());

		return $model;
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
                $config = $this->getThumbConfig();
                if ( $config['actions']['view'] )
		{
			$result = $config['actions']['view'];
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