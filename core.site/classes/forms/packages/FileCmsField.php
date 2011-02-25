<?php
$config = array(
                "extends_from"    => "FileField",

                "interface_tpl"   => "file_cms.html:File",

                "file_size" => "100",
                "file_ext"  => array( "zip", "rar", "ppt", "doc", "docx", "xls", "xlsx", "pdf",
                                      "swf", "gif", "jpg", "png" ),
                "file_chmod" => "775",
                "wrapper_tpl"=> "wrapper.html:CmsStringWrapper",
);
?>

