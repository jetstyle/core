<?php
  $this->module_title = "Все настройки";
  $this->class_name = "FormConfig";
  $this->table_name = $this->rh->project_name."_config";
  $this->SELECT_FIELDS = array("name","value");

  $this->id_field="name";
  //$this->hide_delete_button = true;
  
?>