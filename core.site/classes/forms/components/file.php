<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_file( &$config )
      - $field -- $field->config instance-a ����

  -------------------

  * model       : ��������� �� ����: ���������� ����� � �������� ������� � ������ ��� ����� � ��
  * interface   : ����� ���� �������� �����
  * validator   : ���������� �� ������ �����? ������ �����?

  -------------------

  ����� � �������

  * file_size = "8" -- max size in Kilobytes
  * file_ext  = array( "gif", "jpg", etc. )
  * file_dir  = -- ����, ���� ������ �����.
  * file_random_name = false (true)

  -------------------

  // ������. �������� � ������� � ����������
  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )

  // ���������
  * Validate()

  // ��������� (������� � ��������� ������)
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/model_plain" );

class FormComponent_file extends FormComponent_model_plain
{
   // MODEL ==============================================================================
   function Model_DbInsert( &$fields, &$values )
   {
     if ($this->file_uploaded)
     {
       $fields[] = $this->field->name;
       $values[] = $this->model_data;
     }
   }
   function Model_DbUpdate( $data_id, &$fields, &$values )
   {
     return $this->Model_DbInsert( $fields, $values );
   }

   // VALIDATOR ==============================================================================
   function Validate()
   {
     parent::Validate();

     if (!$this->valid) return $this->valid; // ==== strip one

     if ($this->file_size)
       if (isset( $this->field->config["file_size"]))
         if ($this->file_size > $this->field->config["file_size"]*1024)
           $this->_Invalidate( "file_size", "������� ������� ����" );

     if ($this->file_ext)
       if (isset( $this->field->config["file_ext"]))
         if (!in_array($this->file_ext,$this->field->config["file_ext"]))
           $this->_Invalidate( "file_ext", "������������ ��� �����" );

     if ($this->file_size)
       if (@$this->field->config["validator_func"]) {
         if ($result = call_user_func( $this->field->config["validator_func"],
                                       $this->field->model->Model_GetDataValue(),
                                       $this->field->config ))
           $this->_Invalidate( "func", $result );

        }

     return $this->valid;
   }
   // quick pre-validation
   function _CheckExtSize( $ext, $size )
   {
     if (isset( $this->field->config["file_size"]))
       if ($size > $this->field->config["file_size"]*1024)
         return false;
     if (isset( $this->field->config["file_ext"]))
       if (!in_array($ext,$this->field->config["file_ext"]))
         return false;
     return true;
   }

   // INTERFACE ==============================================================================
   // ������� ����� ����������
   function Interface_Parse()
   {
     parent::Interface_Parse();
     
     $tpl = Locator::get('tpl');

     $name = $this->field->model->Model_GetDataValue();
     $file_size = $this->_GetSize( $name );
     if ($file_size === false)
     {
       $tpl->Set("interface_file", false);
     }
     else
     {
       $tpl->Set("interface_file", $name );
       $tpl->Set("interface_size_Kb", floor(($file_size+512)/1024));
     }

     return $tpl->Parse( $this->field->form->config["template_prefix_interface"].
                                      $this->field->config["interface_tpl"] );
   }
   // �������������� �� ����� � ������ ��� �������� �������
   function Interface_PostToArray( $post_data )
   {
     // @todo: ��������� ����, �������� ��� �������� � ����, � ��� � ������
     $value = $this->_UploadFile($post_data);

     if ($value === false) return array(); // no data here

     $a = array(
          $this->field->name           => $value,
               );

     return $a;
   }

   // ---------------------------------------------------------------------------
   // UPLOAD specific handlers
   function _GetSize( $file_name )
   {
     $full_name = $this->field->config["file_dir"].$file_name;
     if (file_exists($full_name))
       return filesize($full_name);
     else return false;
   }
   function _UploadFile( $post_data )
   {
    $uploaded_file = @$_FILES[ '_'.$this->field->name ]["tmp_name"];
    if(is_uploaded_file($uploaded_file))
    {
      //���������� ������
      $type = $_FILES[ '_'.$this->field->name ]['type'];
      $size = $_FILES[ '_'.$this->field->name ]['size'];
      $ext = explode(".",$_FILES[ '_'.$this->field->name ]['name']);
      $ext = strtolower(end($ext));

      $this->file_size = $size;
      $this->file_ext  = $ext;
      $this->file_type = $type;
      $this->file_uploaded = true;

      if ($this->_CheckExtSize($ext, $size))
      {
        Finder::useLib( "Translit", "php/translit" );

        if (isset($this->field->config["file_random_name"]) && $this->field->config["file_random_name"])
        {
          $name = substr( md5(time()), 0, 6 );
        }
        else
        {
          $name = basename( $_FILES[ '_'.$this->field->name ]['name'] );
          $name = substr($name, 0, strlen($name)-strlen($ext)-1 );
          $name = Translit::Supertag( $name, TR_NO_SLASHES);
        }

        $count=1; $_name = $name;
        while (file_exists($this->field->config["file_dir"].$name.".".$ext))
        {
          if ($name === $_name) $name = $_name.$count;
          else $name = $_name.(++$count);
        }
        $file_name = $name.".".$ext;
        $full_name = $this->field->config["file_dir"].$file_name;
        move_uploaded_file($uploaded_file,$full_name);
        chmod($full_name,$this->field->config["file_chmod"]);
        $this->file_name = $file_name;
        return $file_name;
      }
      else return "[error]";
    }
    else
    {
      $this->file_uploaded = false;
      return false;
    }
   }


}

?>
