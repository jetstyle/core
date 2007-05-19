<?php
    $this->upload_dir = "pictures";
    $this->_FILES = array
    (
    'file_small' => array(
    	array(
    		'filename' => 'picture_preview_*',
    		'size' => array(100,100),
    		'crop' => false,
    		'base' => false,
    		'show' => 1,
            'take_from_if_empty' => array('file',0),
            'link_to' => 'file',
            'graphics' => 1,
    		),
    	),
    'file' => array(
    	array(
    		'filename' => 'picture_*',
    		'size' => array(),
    		'crop' => false,
    		'base' => false,
    		'show' => 1,
    		'graphics' => 1,
    		),
    	)
    );
?>