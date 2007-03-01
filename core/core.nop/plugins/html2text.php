<?php
    /*

    HTML-2-TEXT
    -----------

    Плагин-форматтер, который преобразует кусок HTML-разметки, 
    превращает его в plain text-версию, при этом пытаясь сохранить
    какое-то подобие тому, что из себя представлял текст ранее.

    Используют:  * при отправке text only почты
                 * при выводе заголовков страницы (?)

    ==================================================== v.0 (kuso@npj)

    $params:   "_", "0"    => форматируемый текст
               "nolinks"   => не вставлять ссылки

    */

    // text берём из параметров, которые нам даёт Rockette
    $text = $params["_"]?$params["_"]:$params[0];


    $text = str_replace("\n", " ", $text);
    $text = str_replace("\r", " ", $text);

    $nohtml = 
              preg_replace("/<\/table>/i", "\n\n", 
              preg_replace("/<\/h.>/i", "\n\n", 
              preg_replace("/<\/div>/i", "\n", 
              preg_replace("/<br.*?>/i", "\n", 
              preg_replace("/<hr.*?>/i", "\n\n----------------------------\n\n", 
              str_replace("</strong>", "**", 
              str_replace("<strong>", "**", 
              str_replace("<li>", "<br />  * ", 
              preg_replace("/^\s+/im", "", 
              preg_replace("/\s+/i", " ", 
              preg_replace( '/(&.*?;)|['.chr(127).'-'.chr(167).chr(169).'-'.chr(183).chr(185).'-'.chr(191).']/', '#', 
              preg_replace( '/<a([^>]*?)href=(\"|\'|)([^\"\' ]*)([^>]*)>(.*?)<\/a>/i', //"
                             $params["nolinks"]?'$5':'$5 ( $3 )', 
//              preg_replace( '/<a([^>]*)name=>/i', '', 
              preg_replace( '/<a([^>]*)><img([^>]*)><\/a>/i', '', 
              preg_replace( '/<style>.*?<\/style>/i', '', 
              preg_replace( '/(&(quot|laquo|raquo|\#0?147|\#0?148|\#0?171|\#0?187);)|'.
                              chr(147).'|'.chr(148).'|'.chr(171).'|'.chr(187).'/', '"', //"
              preg_replace( '/(&(trade|\#0?153);)|'.chr(153).'/', '(tm)', //"
              preg_replace( '/(&(copy|\#0?169);)|'.chr(169).'/', '(c)', //"
              preg_replace( '/(&(reg|\#0?174);)|'.chr(174).'/', '(R)', //"
              preg_replace( '/(&(ndash|\#0?150);)|'.chr(150).'/', '-', 
              preg_replace( '/(&(mdash|\#0?151);)|'.chr(151).'/', '--', 
              preg_replace( '/(&(nbsp|\#0?160);)|'.chr(160).'/', ' ', 
                $text 
              )))
              ))))))))))))))))));

    $nohtml = preg_replace( "/<[^>]+>/i", "", $nohtml );
    $nohtml = preg_replace( '/([^ ]+) \( \1 \)/i', '$1', $nohtml ); // delete http://npj.ru ( http://npj.ru )

    echo $nohtml;
?>