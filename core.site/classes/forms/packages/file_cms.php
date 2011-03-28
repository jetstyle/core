<?php
$config = array(
                "model"           => "file_cms",
                "validator"       => "file",
                "view"            => "view_plain",
                "interface"       => "file_cms",
                "interface_tpl"   => "file_cms.html:File",

                "file_size" => "100",
                "file_ext"  => array( "zip", "rar", "ppt", "doc", "docx", "xls", "xlsx", "pdf",
                                      "swf", "gif", "jpg", "png" ),
                "file_chmod" => "775",
                "wrapper_tpl"=> "wrapper.html:CmsStringWrapper",

                //adds file to files rubric defined in $config['config_key_module']
                "add_to_rubric"=>true

                // "file_dir"   => "[always supply this]",
                // "file_name"   => "file_*",
);
?>

