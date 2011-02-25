<?php
  $config = array(
            "extends_from" => "GroupField",
            
            "group_tpl"       => "group.html:CmsList",
            "wrapper_collapsed" => false,
            
            "interface_tpl_params" => array( "class" => "w100" ),
            
            "wrapper_tpl"     => "wrapper.html:CmsDefaultGroupWrapper",
            "wrapper_title" => "",
            "wrapper_desc"  => "",
            
            "fields" => array(
              "meta_title" => array(
                "extends_from" => "StringCmsField",
                "wrapper_title" => "��������� SEO",
              ),
              "meta_keywords" => array(
                "extends_from" => "StringCmsField",
                "wrapper_title" => "�������� ����� (meta keywords)",
              ),
              "meta_description" => array(
                "extends_from" => "StringCmsField",
                "wrapper_title" => "�������� (meta description)",
              ),
            ),
            
            "group_title" => "seo",
            "interface_tpl_params" => array("closed" => true),
  );
?>
