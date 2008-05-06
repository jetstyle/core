<?php
	$this->class_name = "ToolbarTreeControl";
	$this->table_name = $this->rh->project_name."_toolbar";
	$this->SELECT_FIELDS = array("id","title");
	$this->level_limit = 2;
    $this->old_style=true;
?>