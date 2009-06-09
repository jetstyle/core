<?php
/*

  Форм-процессор
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_image( &$config )
      - $field -- $field->config instance-a поля  

  -------------------

  Что добавляем?
  * interface   : превьюшку (@todo), изготовление их при получении картинки (@todo)
  * validator   : габариты картинки (@todo)

  -------------------

  Опции в конфиге

  * file_size = "8" -- max size in Kilobytes
  * file_ext  = array( "gif", "jpg", etc. )
  * file_dir  = -- путь, куда класть файлы.
  * file_random_name = false (true)

  + image_thumbs = array( x, y, ... ) 

  -------------------

  // Валидатор
  * Validate()

  // Интерфейс (парсинг и обработка данных)
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/file" );

class FormComponent_image extends FormComponent_file
{
  
   // VALIDATOR ==============================================================================
   function Validate()
   {
     parent::Validate();
     if (!$this->valid) return $this->valid; // ==== strip one
     // @todo: validate Width and Height of picture
     return $this->valid;
   }
  
   // INTERFACE ==============================================================================
   // парсинг полей интерфейса
   function Interface_Parse()
   { 
     FormComponent_model_plain::Interface_Parse();

     $name = $this->field->model->Model_GetDataValue();
     $name = preg_replace( "/\.[^\.]*$/", "_1.jpg", $name );
     $file_size = $this->_GetSize( $name );
     if (($name == "") || ($file_size === false))
     {
       $this->field->tpl->Set("interface_file", false);
     }
     else
     {
       $size = getimagesize( $this->field->config["file_dir"].$name ); 
       $this->field->tpl->Set("interface_w", $size[0] );
       $this->field->tpl->Set("interface_h", $size[1] );
       $this->field->tpl->Set("interface_path", $this->field->config["file_url"] );
       $this->field->tpl->Set("interface_file", $name );
     }

     return $this->field->tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );

     // @todo: ссылку на первую превьюшку
     return parent::Interface_Parse();
   }
   // преобразование из поста в массив для загрузки моделью
   function Interface_PostToArray( $post_data )
   {
     $a = parent::Interface_PostToArray( $post_data );

     if ($this->file_uploaded && isset($this->field->config["image_thumbs"]))
       $this->_Thumb( $this->file_name, $this->field->config["image_thumbs"] );

     return $a; 
   }

   // ---------------------------------------------------------------------------
   // IMAGE specific
   function _Thumb( $file_name, $thumbs )
   {
      $tl = sizeof($thumbs);

      if ($file_name == "") return;

      $name = preg_replace( "/\.[^\.]*$/", "", $file_name );
      
      $src = $this->field->config["file_dir"].$file_name;
      if (!file_exists($src)) return;

      Debug::Trace( "thumbnailing $src" );

      $size = getimagesize( $src ); 

      // Debug::Error_R( $size );

      // GIF, JPG, PNG -- is goood.
      if ($size[2] == 1) $img = imagecreatefromgif ( $src ); else
      if ($size[2] == 2) $img = imagecreatefromjpeg( $src ); else
      if ($size[2] == 3) $img = imagecreatefrompng ( $src ); else return;
      
      for ($i=0; $i<$tl; $i+=2)
      {
        if (is_numeric($thumbs[$i]) && is_numeric($thumbs[$i+1]))
        {
          $coef1 = $size[0] / $thumbs[$i];
          $coef2 = $size[1] / $thumbs[$i+1];
          if ($coef1 > $coef2) unset( $thumbs[$i+1] );
          else                 unset( $thumbs[$i]   );
        }
        if (!is_numeric($thumbs[$i]))   $thumbs[$i]   = floor($size[0]*$thumbs[$i+1]/$size[1]);
        if (!is_numeric($thumbs[$i+1])) $thumbs[$i+1] = floor($size[1]*$thumbs[$i]  /$size[0]);

        Debug::Trace( $thumbs[$i]." x ".$thumbs[$i+1] );
        $thumb = imagecreatetruecolor( $thumbs[$i], $thumbs[$i+1] ); 
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbs[$i], $thumbs[$i+1], $size[0], $size[1]);
        imagejpeg($thumb, $this->field->config["file_dir"].
                          $name."_".($i/2+1).".jpg", $this->field->config["image_quality"]);
        imagedestroy($thumb);
      }
      imagedestroy($img);

      // removing original afterwards
      if (!$this->field->config["image_save_original"])
      {
        unlink( $src );
      }
   }


}

?>
