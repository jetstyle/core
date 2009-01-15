<?php

class FormGallerySimple
{
	var $template_item = "form_gallery_simple.html";
	var $template = "form_simple.html";
	
	function FormGallerySimple(& $config)
	{
		$this->rh = &$config->rh;
		$this->table_name = $config->table_name;
		//base modules binds
		$this->config = & $config;
		$this->prefix = $config->module_name . '_form_';
		$this->new_suffix = "";
		//настройки шаблонов
		$this->store_to = "form_" . $config->module_name;
		if ($config->template_item)
		{
			$this->template_item = $config->template_item;
		}
		if (!$this->template_item)
		{
			$_template_item = $config->_template_item ? $config->_template_item : $this->_template_item;
			$this->template_item = $this->rh->FindScript($config->handlers_type, $this->config->module_name . '/' . $_template_item, CURRENT_LEVEL, SEARCH_DOWN, true);
			$this->full_path = true;
		}
		//StateSet
		$this->state = & new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		
		$this->rh->UseClass('Upload');
		$this->upload = & new Upload($this->rh, $this->config->upload_dir ? $this->rh->front_end->file_dir . $this->config->upload_dir . "/" : $this->rh->front_end->file_dir);
		$this->web_upload_dir = $this->rh->front_end->path_rel . 'files/' . ($this->config->upload_dir ? $this->config->upload_dir . "/" : "");
		
		$this->_FILES = &$this->config->_FILES;
	}

	function Handle()
	{
		$this->handlePost();
		$this->handleUpload();

		$this->loaded = true;
		$tpl = & $this->rh->tpl;


		if( $this->config->edit_url !== false )
		    $tpl->set('edit_url',  $this->config->edit_url ? $this->config->edit_url : $this->rh->url . 'resize?module='.$this->config->module_name);
		$tpl->set('ajax_url', $this->rh->url . $this->rh->url_rel);
		$tpl->set('prefix', $this->prefix);

		//		parent :: Handle();

		$tpl = & $this->rh->tpl;

		$tpl->set('prefix', $this->prefix);
		$tpl->set('POST_STATE', $this->state->State(1));
		$tpl->set('__form_name', $this->prefix . '_simple_form');
		$tpl->set('_rubric_id', intval($this->rh->getVar($this->config->rubric_var ? $this->config->rubric_var : 'topic_id')));

//		$res = $this->rh->db->Query("SELECT id, title FROM ".$this->rh->project_name."_gallery_rubrics WHERE _state = 0 ORDER BY _order ASC");
//		$rubrics = array();
//		if(is_array($res) && !empty($res))
//		{
//			$opts = '';
//			foreach($res AS $r)
//			{
//				$opts .= '<option value="'.$r['id'].'">'.$r['title'].'</option>'; 
//			}
//			$this->rh->tpl->set('options_rubrics', $opts);
//			$this->rh->tpl->parse($this->template_item.':rubrics', 'rubric_select');
//		}

		$tpl->Parse($this->template_item, '___form', false, $this->full_path);
		$tpl->Parse($this->template, $this->store_to, true );
	}

	function handlePost()
	{
		$upload = &$this->upload;
		if (!isset ($_POST['action']))
		{
			return;
		}	
		
		$this->rubric_id = intval($_POST['gallery_id']) ? intval($_POST['gallery_id']) : intval($_POST['rubric']);
		
		
		
		switch ($_POST['action'])
		{
			case 'list' :
				$gallery_id = $this->rubric_id;
				$res = $this->rh->db->Query("SELECT id, title FROM " . $this->table_name . " WHERE fid = " . $gallery_id . " ORDER BY _order ASC");
				$out = array ();
				if (is_array($res) && !empty ($res))
				{
					
					$need_update = false;
					
					foreach ($res AS $r)
					{
						foreach($this->_FILES AS $v)
						{
							foreach($v AS $vv)
							{
								if($vv['show'])
								{
									if ($file = $upload->getFile(str_replace('*', $r['id'], $vv['filename'])))
									{
										$A = getimagesize($file->name_full);
										$out[$r['id']] = array(
											'src' => $this->web_upload_dir . $file->name_short, 
											'height' => $A[1],
											'width' => $A[0],
//											'title' => iconv('cp1251', 'UTF-8', $r['title']),
										);
									}
									else
									{
										$out[$r['id']] = array(
											'src' => $this->web_upload_dir . "nofile.png", 
											'height' => 48,
											'width' => 48,
//											'title' => iconv('cp1251', 'UTF-8', $r['title']),
										);
									/*
										$this->delFile($v, $r['id']);
										$this->rh->db->Query("DELETE FROM " . $this->table_name . " WHERE id = " . $r['id'] . "");
										$need_update =  true;
										break;
										*/
									}
								}
							}
						}
					}
					
					if($need_update)
					{
						if(method_exists($this, 'afterAction'))
						{
							$this->afterAction('delete_after_list');
						}
					}
					
				}
				//var_dump($out);
				//die();
				echo $this->json($out);
				break;

			case 'delete' :
				$out = array ();

				if ($id = intval($_POST['picid']))
				{
					foreach($this->_FILES AS $input_field => $result_array)
					{
						$this->delFile($result_array, $id);
					}
					
					$this->rh->db->Query("DELETE FROM " . $this->table_name . " WHERE id = " . $id);
					echo '1';
					$this->afterAction('delete');
				} 
				else
				{
					echo '0';
				}

			break;
			
			case 'updateorder':
			
				unset($_POST['action'], $_POST['rubric']);
				
				if(is_array($_POST))
				{
					foreach($_POST AS $id => $v)
					{
						$this->rh->db->Query("UPDATE " . $this->table_name . " SET _order = ".intval($v)." WHERE id = " . intval($id));
					}
					$this->afterAction('updateorder');
				}
				
				echo '1';
			break;
		}

		die();
	}

	function delFile($fileArr, $itemId)
	{
		$upload = &$this->upload;
		foreach($fileArr AS $r)
		{
			$file = $upload->GetFile( str_replace('*', $itemId, $r['filename']));
			if($file->name_full)
			{
				@unlink($file->name_full);
			}	
		}
	}

	function handleUpload()
	{
		
		$id = intval($this->rh->getVar('picid'));
		$rubric_id = intval($this->rh->getVar($this->prefix . 'rubric'));
		
		$this->rubric_id = $rubric_id; 
	
		//var_dump($_FILES);
		//die();
		
		$upload = &$this->upload;
		
		foreach($this->_FILES AS $input_field => $result_array)
		{
			//die($input_field);
			//die($_FILES[$input_field]['tmp_name']);
			if (is_uploaded_file($_FILES[$input_field]['tmp_name']))
			{
				if(!$id)
				{
					$order = $this->rh->db->QueryOne("SELECT MAX(_order) AS m FROM " . $this->table_name . " WHERE fid = " . $rubric_id);
					$order = ($order['m'] > 0) ? ($order['m'] + 1) : 1;
					$sql = "INSERT INTO " . $this->table_name . "(fid, _order) VALUES('" . $rubric_id . "', '" . $order . "')";
					//die($sql);
					$new_id = $this->rh->db->Insert($sql);
				}
				else
				{
					$new_id = $id;
					//var_dump($new_id);
					//die();
				}
				
				$broken = false;
				
				foreach($result_array AS $r)
				{
					if($id)
					{
						$file = $upload->GetFile( str_replace('*', $id, $r['filename']));
						if($file->name_full)
						{
							@unlink($file->name_full);
						}
					}
					
					if ($r['exts'])
					{
						$upload->ALLOW = $r['exts'];
					}
					elseif ($r['graphics'])
					{
						$upload->ALLOW = array_intersect($upload->ALLOW, $this->GRAPHICS);
					}
					
					$file = $upload->UploadFile($input_field, str_replace('*', $new_id, $r['filename']), false, $this->buildParams($r));
					if(!$file->name_full)
					{
						$broken = true;
					}
					elseif($r['show'])
					{
						$A = getimagesize($file->name_full);
						$params = array (
							'id' => $new_id,
							'src' => $this->web_upload_dir . $file->name_short,
							'width' => $A[0],
							'height' => $A[1],
						);
					}
				}
				
				if($broken)
				{
					$this->rh->db->Query("DELETE FROM " . $this->table_name . " WHERE id = " . $new_id);
				}
				else
				{
					$this->afterAction('upload');
					die($this->json($params));
				}
			}
		}
	}

	function json($input)
	{
		foreach ($input as $key => $value)
		{
			if(is_array($value))
			{
				$out[] = $this->rh->db->quote($key) . ":" . $this->json($value);
			}
			else
			{
				$out[] = $this->rh->db->quote($key) . ":" . $this->rh->db->quote($value);
			}
		}
		return "{" . @ implode(",", $out) . "}";
	}

	function buildParams($d)
	{
		return array(
				'size' => $d['size'],
				'filesize' => $d['filesize'],
				'crop' => $d['crop'],
				'base' => $d['base'],				
			);
	}

	function afterAction($action = '')
	{
		
	}

}
?>