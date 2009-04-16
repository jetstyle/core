<?php
$config = array(
                "model"           => "file",
                "validator"       => "file",
                "view"            => "view_plain",
                "interface"       => "file",
                "interface_tpl"   => "file.html:File",
                "db_ignore"       => true,

                "file_size" => "100",
                "file_ext"  => array( "zip", "rar", "ppt", "doc", "xls",
                                      "swf", "gif", "jpg", "png" ),
                "file_chmod" => "775",

                // "file_dir"   => "[always supply this]",
                // "file_name"   => "file_*",
);
?>
