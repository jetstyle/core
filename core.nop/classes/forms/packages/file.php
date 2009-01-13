<?php
$config = array(
                "model"           => "file",
                "validator"       => "file",
                "view"            => "view_plain",
                "interface"       => "file",
                "interface_tpl"   => "file.html:File",

                "file_size" => "100",
                "file_ext"  => array( "zip", "rar", "ppt", "doc", "xls",
                                      "swf", "gif", "jpg", "png" ),
                "file_chmod" => "775",

                // "file_dir"   => "[always supply this]",
);
?>