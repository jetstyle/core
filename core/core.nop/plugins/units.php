<?php
    /*

    COUNT magic     {{!units 10 pages}} {{!units total items}}
    -----------

    ������-�����, ������� ����� "�������" � ������ ������, 
    ��������� ��������� messageset
    
    ������� �� COUNT: ��������� ������� ��� �����

    ==================================================== v.0 (kuso@npj)

    $params:   "0"         => ����� ��� ��� ���������� $tpl->Get()
               "1"         => ������������� (optional, default="items"

    */

    $count = $params[0];
    if (!is_numeric($count)) $count = $tpl->Get($count);

    $item_name = $params[1];
    if (!$item_name) $item_name = "items";

    echo $tpl->msg->NumberString( $count, $item_name ); 

?>