<?php
/*
    TYPOGRAFICA
    -----------

    ������-���������, ������������ ������������� ������.
    ��������� �� ������� http://pixel-apes.com/typografica

    ����������:  * ��� ������������� ������ � CMS
                 * ��� ��������� ������ �������
                 * �������-������� ��� ��������� ������ � ��������

    ==================================================== v.0 (kuso@npj)

    $params:   "_", "0"    => ������������� �����
*/

    // text ���� �� ����������, ������� ��� ��� Rockette
    if (!is_array($params)) $params = array("_"=>$params);
    $text = $params["_"]?$params["_"]:$params[0];

    if ($text == "") return;

    Finder::useLib("typografica", "classes/typografica");

    $typo = &new Typografica( $rh );

    $ret = $typo->correct($text);

    echo $ret;


?>