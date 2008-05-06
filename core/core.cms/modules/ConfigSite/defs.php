<?php
  $this->module_title = "Настройки сайта";
  $this->class_name = "FormConfigSite";
  $this->table_name = $this->rh->project_name."_config";
  $this->SELECT_FIELDS = array("name","value");

  $this->id_field="name";
  
  $this->admin_password = false;
  $this->upload_price = false;
  
  $this->_FILES = array( 
    'file' => array(
    	array(
    		'filename' => 'price',
    		'show' => 1,
    		'exts' => array('zip', 'rar', 'xls', 'doc', 'rtf'),
    		),
    	)
  );
  
  //$this->hide_delete_button = true;
  
?>