<?php
$config = array(
                "extends_from"    => "FileField",
                "wrapper_tpl"   => "wrapper.html:DefaultStringWrapper",

                "interface_tpl"   => "file.html:Image",

                "file_size" => "100",
                "file_ext"  => array( "gif", "jpg", "png" ),
                "file_chmod" => "775",

                "image_thumbs"  => array( "100", "100",  // 100x100 and so forth
                                          "50",  "50",
                                          "25",  "25" ),
                "image_quality" => 80, // quality of a thumb
                "image_save_original" => false,

                // "file_dir"   => "[always supply this]",
                // "file_url"   => доступ к папке картинок через веб
);
?>