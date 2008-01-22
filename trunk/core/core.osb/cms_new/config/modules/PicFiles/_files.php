<?php

  	$this->upload_dir = "picfiles";
    $this->_FILES = array
    (
        'file' => array
        (
        	array
            (
    		//вместо * будет id записи
    		'filename' => 'picfile_*',
    		'show' => 1,
            //если есть - то добавляется в upload->ALLOW, и при аплоаде проверяется 
            //'exts' => array('rar', 'zip')
    		),
   	    ),
    );

?>