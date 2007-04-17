<?
/*
  В конфиге должно быть:
  ---------------------
  $this->FILES = array(
    array( file_name, input_name [, array('doc','rtf',...), array(max_width,max_height,strict)] ),
    ...
  );
*/
  
  $this->UseClass('FormSimple',1);
  
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
  
  function RenderFiles(){
    if( $this->files_rendred ) return;
    
    $rh =& $this->rh;
    $tpl =& $rh->tpl;
    $upload =& $this->upload;
    $config =& $this->config;
    
    //рендерим файлы
    if( is_array($config->FILES) )
    {
      foreach($config->FILES as $row)
      {
          
        if( $file = $upload->GetFile( str_replace('*', $this->id, $row[0])) )
        {

        /*        
          if( in_array( $file->ext, $this->GRAPHICS ) )
          {

            //графика? показываем
            $A = @getimagesize( $file->name_full );
            $width_max = $config->view_width_max ? $config->view_width_max : $this->view_width_max;
            $height_max = $config->view_height_max ? $config->view_height_max : $this->view_height_max;
            
            $file->link = realpath($file->name_full);
            
            //die(realpath($upload->dir)."/".$file->name_short);
            
            if( $A[0]>$width_max || $A[1]>$height_max )
            {
              
              //слишком большая? показываем превью
              $_href = $rh->url.'picture?pict='.$file->name_short;
              $_onclick = "pictwnd('$_href','popup_".$this->id."','top=100,left=100,width=".$A[0].", height=".$A[1]."');";
              $_str = '<a href="'.$_href.'" onclick="'.$_onclick.'return false;"><img src="'.$rh->url.'pict.php?img='.$file->link.'" width="100" height="100" alt="'.$file->link.'" border="0"></a>';
              $tpl->Assign( 'file_'.$row[1], $_str );
            }
            else
            {
              //достаточно маленькая? показываем целиком
              
              $tpl->Assign( 'file_'.$row[1], '<img src="'.$rh->url.'pict.php?img='.$file->link.'" "'.$A[3].'" alt="'.$file->link.'">' );
            }
          }
          else
          { */
            //файл? рисуем статистику и ссылку "скачать"
            $_href = $this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short;
//            $_href = $rh->url.'files/'.$file->name_short;
            $tpl->Assign( 'file_'.$row[1], '('.$file->size.'kb, '.$file->format.", <a href='".$_href."'>скачать</a>)" );
            $this->item[$this->field_file] = '<img src="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'" />';

/*
          }
          */
        }
      }
    }
    elseif(is_array($this->config->_FILES))	{
    	foreach($this->config->_FILES AS $field_file => $v)	{
      		if(is_array($v))	{
      			foreach($v AS $vv)	{
      				if($vv['show'])	{
      					$file = $upload->GetFile(str_replace('*', $this->id, $vv['filename']));      					
      					if($file->name_full)	{
	      					$this->item[$field_file] = '<img src="'.$this->rh->front_end->path_rel.'files/'.($this->config->upload_dir ? $this->config->upload_dir."/" : "").$file->name_short.'" />';
  	    				}
  	    			}
      			}
      		}
      	}
    }
    
    $tpl->Assign( '__max_file_size', $config->max_file_size ? $config->max_file_size : $this->max_file_size );
    $this->files_rendered = true;
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
        foreach($this->config->FILES as $row){
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
      elseif(is_array($this->config->_FILES))	{
      	foreach($this->config->_FILES AS $field_file => $v)	{

     		if(is_uploaded_file($_FILES[$this->prefix.$field_file]['tmp_name']))	{
      			if(is_array($v))	{
      				foreach($v AS $vv)	{
      					$file = $upload->GetFile( str_replace('*', $this->id, $vv['filename']));
    						if($file->name_full)
    							@unlink( $file->name_full );
    							
      					$upload->UploadFile($this->prefix.$field_file, str_replace('*', $this->id, $vv['filename']), false, array($vv['size'][0], $vv['size'][1], $vv['crop']));      					
      				}
      			}
      		}
      		elseif($this->rh->GetVar($this->prefix.$field_file.'_del'))	{
      			if(is_array($v))	{
      				foreach($v AS $vv)	{
      					$file = $upload->GetFile( str_replace('*', $this->id, $vv['filename']));
    						if($file->name_full)
    							@unlink( $file->name_full );
      				}
      			}
      		}
      	}
      }
      
      return true;
    }
    else 
    {
        //die('22');
        return false;
    }
  }

}
  
?>