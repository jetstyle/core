<?
/*
  В конфиге должно быть:
  ---------------------
  $this->FILES = array(
    array( file_name, input_name [, array('doc','rtf',...), array(max_width,max_height,strict)] ),
    ...
  );
*/
  
  $this->UseClass('FormSimple');
  
class FormFiles extends FormSimple  {
  
  var $upload;
  var $GRAPHICS = array('gif','jpg','png','bmp','jpeg'); //какие форматы показывать как картинки
  //var $max_file_size = 2097152; //максимальный размер файла для загрузки
  var $max_file_size = 55242880; //максимальный размер файла для загрузки

  //До каких размеров картинки показывать просто, а больше - через превью и попап?
  var $view_width_max = 300;
  var $view_height_max = 300;
  var $field_file = "file";
      
  function FormFiles( &$config )
  {
    FormSimple::FormSimple($config);
    //upload
    $this->rh->UseClass('Upload');
    $this->upload =& new Upload( $this->rh, $config->upload_dir ? $this->rh->front_end->file_dir.$config->upload_dir."/" : $this->rh->front_end->file_dir );
  }
  
  function Handle(){
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    $upload =& $this->upload;
    $config =& $this->config;
    
    //грузим форму
    $this->Load();
    
    $this->RenderFiles();
    
    //по этапу
    FormSimple::Handle();
  }
  
  function RenderFiles()
  {
    if( $this->files_rendred ) return;
    
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    $upload =& $this->upload;
    $config =& $this->config;
    
    //рендерим файлы
    if( is_array($config->FILES) )
    {
        $this->_renderFilesOld();
    }
    elseif(is_array($this->config->_FILES))	
    {
    	$this->_renderFiles();
    }
    
    $tpl->Assign( '__max_file_size', $config->max_file_size ? $config->max_file_size : $this->max_file_size );
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
      					$file = $upload->GetFile(str_replace('*', $this->id, $vv['filename']));
                        //var_dump($file);
      					if($file->name_full && in_array($file->ext, $this->GRAPHICS ) )	
                        {
          					$this->item[$field_file] = '<img src="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'" />';
          					if($vv['link_to'])
          					{
          						foreach($this->config->_FILES[$vv['link_to']] AS $_vv)
        						{
               						if($_vv['show'])	
                   					{
   										$file = $upload->GetFile(str_replace('*', $this->id, $_vv['filename']));
   										
                      					if($file->name_full && in_array($file->ext, $this->GRAPHICS ) )	
                      					{
                      						$A = getimagesize($file->name_full);
                      						$this->item[$field_file] = '<a title="Открыть оригинал изображения" href="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'?popup=1" onclick="popup_image(this.href, \''.$A[0].'\', \''.$A[1].'\'); return false;">'.$this->item[$field_file].'</a><br /><a title="Открыть оригинал изображения" href="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'?popup=1" onclick="popup_image(this.href, \''.$A[0].'\', \''.$A[1].'\'); return false;">'.$A[0].'x'.$A[1].'px</a>';
                      					}
               						}
         						}
          					}
        				}
                        else if ($file->name_full)
                        {
           					$this->item[$field_file] = '('.$file->size.'kb, '.$file->format.", <a href='".$_href."'>скачать</a>)";
                         //    $tpl->Assign( 'file_'.$row[1], '('.$file->size.'kb, '.$file->format.", <a href='".$_href."'>скачать</a>)" );
                        }
        			}
      			}
                if (isset($vv['exts']))
                        $exts = $vv['exts'];
                $this->item[$field_file."_exts"] = $this->_getExts($exts);
            }
      	}
  }
  
  function _getExts($exts)
  {
        if (is_array($exts))
            $exts = array_unique($exts);

        if (empty($exts))
            $exts = array_keys($this->upload->TYPES);

        return "(".implode(", ",$exts).")";
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
            $tpl->Assign( 'file_'.$row[1], '('.$file->size.'kb, '.$file->format.", <a href='".$_href."'>скачать</a>)" );
            $this->item[$this->field_file] = '<img src="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'" />';
        }
      }
  }

  function Update()
  {
    $fields = array('text', 'lead', 'blog_descr', 'down_descr');
    foreach ($fields as $field)
    {
        if (strpos($this->rh->GLOBALS[$this->prefix.$field.$this->suffix], "<div class=\"text\">")!==false)
        {
            $post = str_replace("<div class=\"text\">", "", $this->rh->GLOBALS[$this->prefix.$field.$this->suffix]);
            $post = substr($post, 0, -6);
            
            $post = preg_replace("/<div(.*)>\s+<\/div>/", "", $post);
            $this->rh->GLOBALS[$this->prefix.$field.$this->suffix] = $post;
        }
    }
    if( FormSimple::Update() )
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
          if(is_array($row[3])){
            $A = @getimagesize( $file->name_full );
            if(
              $row[3][2] && ( $row[3][0] && $A[0]!=$row[3][0] || $row[3][1] && $A[1]!=$row[3][1]) || //строгие размеры
              ($row[3][0]>0 && $A[0]>$row[3][0]) || //по щирине
              ($row[3][1]>0 && $A[1]>$row[3][1]) //по высоте
              )
              $kill = true;
          }
          //удаляем файл, если нужно
          if( $rh->GetVar($this->prefix.$row[1].'_del') ){
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
      		elseif($this->rh->GetVar($this->prefix.$field_file.'_del'))	
            {
      			$this->_handleUpload($field_file, $result_arrays);
      		}
      	}
        //die();
      }
      
      return true;
    }
    else 
    {
        //die('22');
        return false;
    }
  }

  function _shouldTakeFromIfEmpty($from_field_file)
  {
      foreach($this->config->_FILES AS $field_file => $result_arrays)	
      {
          foreach($result_arrays AS $vv)
          {
              if ($vv['take_from_if_empty'][0]==$from_field_file)
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
    					@unlink( $file->name_full );

                if ($do_upload)
                {
                    if ($vv['exts'])
                        $upload->ALLOW = $vv['exts'];
                    //нужно сохранить превью?
                    if ($me_too = $this->_shouldTakeFromIfEmpty($field_file))
                    {
                        $vvv = $this->take_to;
                        $upload->UploadFile($this->prefix.$field_file, str_replace('*', $this->id, $vvv['filename']), false, array($vvv['size'][0], $vvv['size'][1], $vvv['crop']));      					
                    }
                    
    			    $upload->UploadFile($this->prefix.$field_file, str_replace('*', $this->id, $vv['filename']), false, array($vv['size'][0], $vv['size'][1], $vv['crop']));
                }
    		}
    	}   
  }

}
  
?>