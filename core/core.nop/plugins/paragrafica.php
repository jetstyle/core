<?php
/*
    PARAGRAFICA
    -----------

    ������-���������, ������������ ������ ������ ������ <p>
    �������� ���-�� �����:
    <a name="p1249-1"></a><p...>
    <a name="h1249-1"></a><hX..>
    ����� ����, �������� �� ��������� TableOfContents.
    ��������� -- �� �������� �������

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

    $rh->useLib("typografica", "classes/paragrafica");

    // we got pure HTML on input.
    $para = &new paragrafica( &$this->rh );
    $result = $para->correct($text);

    // NB: � $para->toc � ��� ���� ����� ������� Table Of Contents

    echo $result;

?>