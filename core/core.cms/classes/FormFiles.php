<?php

$this->useClass('FormSimple');

class FormFiles extends FormSimple  
{
	protected $upload;
	protected $max_file_size = 55242880; //максимальный размер файла для загрузки
	protected $template_files = 'formfiles.html';
	
	//До каких размеров картинки показывать просто, а больше - через превью и попап?
	var $view_width_max = 300;
	var $view_height_max = 300;
	var $field_file = "file";

	function __construct( &$config )
	{
		parent::__construct($config);

		//upload
		$this->upload = &$this->rh->upload;
		$this->upload->dir = $config->upload_dir ? $this->rh->front_end->file_dir.$config->upload_dir."/" : $this->rh->front_end->file_dir;
	}

	public function handle()
	{
		//грузим форму
		$this->load();

		$this->renderFiles();

		//по этапу
		parent::handle();
	}

	function renderFiles()
	{
		if( $this->filesRendred ) return;

		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		$config =& $this->config;

		//рендерим файлы
		if( is_array($config->FILES) )
		{
			$this->_renderFilesOld();
		}
		elseif(is_array($config->_FILES))
		{
			$this->_renderFiles();
		}

		$tpl->set( '__max_file_size', $config->max_file_size ? $config->max_file_size : $this->max_file_size );
		$this->files_rendered = true;
	}

	function _renderFiles()
	{
		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		$upload =& $this->upload;
		$config =& $this->config;

		foreach($this->config->_FILES AS $field_file => $v)
		{
			if(is_array($v))
			{
				unset($exts);
				foreach($v AS $vv)
				{
					if($vv['show'])
					{
						if ($vv['exts'])
						{
							$upload->ALLOW = $vv['exts'];
						}
						elseif ($vv['graphics'])
						{
							$upload->ALLOW = array_intersect($upload->ALLOW, $upload->GRAPHICS);
						}
												
						$file = $upload->getFile(str_replace('*', $this->id, $vv['filename']));
												
						if($file->name_full && in_array($file->ext, $upload->GRAPHICS ) )
						{
							$r = array(
								'src' => $this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short,
							);
							
							if($vv['link_to'])
							{
								foreach($this->config->_FILES[$vv['link_to']] AS $_vv)
								{
									if($_vv['show'])
									{
										$file = $upload->GetFile(str_replace('*', $this->id, $_vv['filename']));

										if($file->name_full && in_array($file->ext, $upload->GRAPHICS ) )
										{
											$A = getimagesize($file->name_full);
											$r['width'] = $A[0];
											$r['height'] = $A[1];
											$r['src_original'] = $this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'?popup=1';
											$this->rh->tpl->setRef('file', $r);
											$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':image_with_link');
										}
									}
								}
							}
							
							if(!$this->item[$field_file])
							{
								$this->rh->tpl->setRef('file', $r);
								$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':image');
							}
						}
						else if ($file->name_full)
						{
							$r = array(
								'filesize' => $file->size,
								'format'   => $file->format,
								'src'      => $this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short,
								'name_short' =>  $file->name_short,
							);
							
							$this->rh->tpl->setRef('file', $r);
													
							if($file->ext == 'flv')
							{
								$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':file_video');
							}
							elseif($file->ext == 'mp3')
							{
								$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':file_mp3');
							}
							elseif($file->ext == 'swf')
							{
								$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':file_flash');
							}
							else
							{
								$this->item[$field_file] = $this->rh->tpl->parse($this->template_files.':file');
							}
							
                            $this->item[$field_file."_down"] = $this->item[$field_file];
						}
                        $this->file = $file;
					}
				}
				if (isset($vv['exts']))
				{
					$exts = $vv['exts'];
				}
				
				$ar = $this->_getExts($exts);
				$this->item[$field_file."_exts"] = $ar['all'];
				$this->item[$field_file."_exts_graphics"] = $ar['graphics'];
			}
		}
	}

	function _getExts($exts)
	{
		if (is_array($exts))
		{
			$exts = array_unique($exts);
		}

		if (empty($exts))
		{
			$exts = array_keys($this->upload->TYPES);
		}

		return array("all" => "(".implode(", ",$exts).")", "graphics" => "(".implode(", ", array_intersect($exts, $this->upload->GRAPHICS)).")");
	}

	function _renderFilesOld()
	{
		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		$upload =& $this->upload;
		$config =& $this->config;

		foreach($config->FILES as $row)
		{
			if( $file = $upload->GetFile( str_replace('*', $this->id, $row[0])) )
			{
				//файл? рисуем статистику и ссылку "скачать"
				$_href = $this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short;
				//            $_href = $rh->url.'files/'.$file->name_short;
				$tpl->set( 'file_'.$row[1], '('.$file->size.'kb, '.$file->format.", <a href='".$_href."'>скачать</a>)" );
				$this->item[$this->field_file] = '<img src="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'" />';
			}
		}
	}

	function update()
	{
		if( parent :: update() )
		{
			$rh =& $this->rh;
			//загружаем и удаляем файлы
			$upload =& $this->upload;
			if( is_array($this->config->FILES) )	{
				foreach($this->config->FILES as $row)
				{
					$fname = str_replace('*', $this->id, $row[0]);
					//грузим файл и проверяем формат, если нужно
					if($this->config->RESIZE)
					$file = $upload->UploadFile( $this->prefix.$row[1], $fname, false, array($row[3][0], $row[3][1]));
					else
					$file = $upload->UploadFile( $this->prefix.$row[1], $fname );
					$kill = false;
					if( is_array($row[2]) && count($row[2]) && !in_array( $file->ext, $row[2]) )
					$kill = true;
					//проверяем ограничение на линейные размеры
					if(is_array($row[3]))
					{
						$A = @getimagesize( $file->name_full );
						if(
						$row[3][2] && ( $row[3][0] && $A[0]!=$row[3][0] || $row[3][1] && $A[1]!=$row[3][1]) || //строгие размеры
						($row[3][0]>0 && $A[0]>$row[3][0]) || //по щирине
						($row[3][1]>0 && $A[1]>$row[3][1]) //по высоте
						)
						$kill = true;
					}
					//удаляем файл, если нужно
					if( $_POST[$this->prefix.$row[1].'_del'] ){
						if( !$file ) $file = $upload->GetFile($fname);
						$kill = true;
						//            @unlink( $file->name_full );
					}
					if($kill && $file)
					@unlink( $file->name_full );
				}
			} /* added by lunatic */
			elseif(is_array($this->config->_FILES))
			{
				foreach($this->config->_FILES AS $field_file => $result_arrays)
				{
					/**
             		* файл заусунули в инпут
             		*/
					if(is_uploaded_file($_FILES[$this->prefix.$field_file]['tmp_name']))
					{
						$this->_handleUpload($field_file, $result_arrays, true);
					}
					/**
             		* не засунули в инпут ничего, да еще и галочку удалить включили
             		*/
					elseif($_POST[$this->prefix.$field_file.'_del'])
					{
						$this->_handleUpload($field_file, $result_arrays);
					}
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	function _shouldTakeFromIfEmpty($from_field_file)
	{
		foreach($this->config->_FILES AS $field_file => $result_arrays)
		{
			foreach($result_arrays AS $vv)
			{
				if ($vv['take_from_if_empty'][0]==$from_field_file && !is_uploaded_file($_FILES[$this->prefix.$field_file]['tmp_name']))
				{
					$this->take_to = $vv;
					return $field_file;
				}
			}
		}
		return false;
		//die();
	}

	function _handleUpload($field_file, &$result_arrays, $do_upload=false)
	{
		$rh =& $this->rh;
		$upload =& $this->upload;

		if(is_array($result_arrays))
		{
			foreach($result_arrays AS $vv)
			{
				$file = $upload->GetFile( str_replace('*', $this->id, $vv['filename']));
				if($file->name_full)
				{
					@unlink( $file->name_full );
				}

				if ($do_upload)
				{
					if ($vv['exts'])
					{
						$upload->ALLOW = $vv['exts'];
					}
					elseif ($vv['graphics'])
					{
						$upload->ALLOW = array_intersect($upload->ALLOW, $upload->GRAPHICS);
					}
					
					//нужно сохранить превью?
					if ($me_too = $this->_shouldTakeFromIfEmpty($field_file))
					{
						$vvv = $this->take_to;
						$upload->UploadFile($this->prefix.$field_file, str_replace('*', $this->id, $vvv['filename']), false, $this->buildParams($vvv));
					}
					
					$this->current_file = $upload->UploadFile($this->prefix.$field_file, str_replace('*', $this->id, $vv['filename']), false, $this->buildParams($vv));
				}
			}
		}
	}

	function buildParams($d)
	{
		return array(
				'size' => $d['size'],
				'filesize' => $d['filesize'],
				'crop' => $d['crop'],
				'base' => $d['base'],
				'to_flv' => $d['convert_to_flv'],			
			);
	}

	function delete()
	{
		$upload =& $this->upload;

		$res = parent :: delete();
		if( 2 == $res )
		{
			if (!empty($this->config->_FILES))
			foreach($this->config->_FILES AS $field_file => $v)
			{
				if(is_array($v))
				{
					foreach($v AS $vv)
					{
						$file = $upload->GetFile(str_replace('*', $this->id, $vv['filename']));
						if($file->name_full)
						{
							unlink($file->name_full);
						}
					}
				}
			}
		}
		return $res;
	}

}

?>