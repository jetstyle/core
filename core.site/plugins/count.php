<?php
    /*

    COUNT magic     {{!count 10 pages}} {{!count total items}}
    -----------

    ѕлагин-магик, который пишет "10 страниц" в нужном падеже, 
    пользу€сь функци€ми messageset

    ==================================================== v.0 (kuso@npj)

    $params:   "0"         => число или им€ переменной $tpl->Get()
               "1"         => идентификатор (optional, default="items"

    */

    $count = $params[0];
    if (!is_numeric($count)) $count = $tpl->get($count);

    $item_name = isset($params[1]) ? $params[1] : false;
    if (!$item_name) $item_name = "items";

    echo $count."&nbsp;".Locator::get("msg")->numberString( $count, $item_name );
?>
