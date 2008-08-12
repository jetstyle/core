<?php
/*
    TYPOGRAFICA
    -----------

    Плагин-форматтер, производящий типокоррекцию текста.
    Базирован на проекте http://pixel-apes.com/typografica

    Используют:  * при предобработке текста в CMS
                 * при внезапном выводе текстов
                 * инстант-вариант для обработки текста в шаблонах

    ==================================================== v.0 (kuso@npj)

    $params:   "_", "0"    => форматируемый текст
*/

    // text берём из параметров, которые нам даёт Rockette
    if (!is_array($params)) $params = array("_"=>$params);
    $text = $params["_"]?$params["_"]:$params[0];

    if ($text == "") return;

    Finder::useLib("typografica", "classes/typografica");

    $typo = &new Typografica( $rh );

    $ret = $typo->correct($text);

    echo $ret;


?>