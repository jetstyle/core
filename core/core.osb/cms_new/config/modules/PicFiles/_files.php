<?php

  	$this->upload_dir = "picfiles";
    $this->_FILES = array
    (
        'file' => array
        (
        	array
            (
    		//������ * ����� id ������
    		'filename' => 'picfile_*',
    		'show' => 1,
            //���� ���� - �� ����������� � upload->ALLOW, � ��� ������� ����������� 
            //'exts' => array('rar', 'zip')
    		),
   	    ),
    );

?>