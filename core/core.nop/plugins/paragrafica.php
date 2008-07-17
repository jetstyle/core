<?php
/*
    PARAGRAFICA
    -----------

    Плагин-форматтер, производящий обёртку текста тагами <p>
    Получаем что-то вроде:
    <a name="p1249-1"></a><p...>
    <a name="h1249-1"></a><hX..>
    Кроме того, выделяет из документа TableOfContents.
    Подробнее -- на странице проекта

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

    $rh->useLib("typografica", "classes/paragrafica");

    // we got pure HTML on input.
    $para = &new paragrafica( &$this->rh );
    $result = $para->correct($text);

    // NB: в $para->toc у нас есть почти готовый Table Of Contents

    echo $result;

?>