<?php
    /*

    COUNT magic     {{!units 10 pages}} {{!units total items}}
    -----------

    Плагин-магик, который пишет "страниц" в нужном падеже, 
    пользуясь функциями messageset
    
    ОТЛИЧИЕ ОТ COUNT: выводится подпись без цифры

    ==================================================== v.0 (kuso@npj)

    $params:   "0"         => число или имя переменной $tpl->Get()
               "1"         => идентификатор (optional, default="items"

    */

    $count = $params[0];
    if (!is_numeric($count)) $count = $tpl->Get($count);

    $item_name = $params[1];
    if (!$item_name) $item_name = "items";

    echo $tpl->msg->NumberString( $count, $item_name ); 

?>