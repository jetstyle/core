<?php
/*
Semi-automatic Translit PHP class tests.
*/

require("translit.php");

$arr = array(
 "WebРазработка/Мимо",
 "Смотрите зайцы -- нас много",
 "Привет, Родина!",
 "ЙухХа",
 "Ыхыхых It's English text",
 "Пьянь",
 "(){}----____",
 "Madonna - Свежия Песенки",
 "5+8-4=9",
 "Въезд ГЛЯНЬ, ВЪЕЗД",
 "Установка mod_perl",
 "Проверка__двери неразумной",
 "Проверка_ дверцы",
 "Кровать устала _ь",
 "test__bed",
 "test_ bed",
 "test__ __bed",
 "a_-_b-_-c",
 "a - b _ c",
);

echo "<h2>UrlTranslit</h2>";

foreach ($arr as $str)
{
 echo "'".$str."' <font color=gray>becomes</font> '".Translit::UrlTranslit($str, TR_ALLOW_SLASHES)."'<br>";
}

echo "<h2>Supertag</h2>";

foreach ($arr as $str)
{
 echo "'".$str."' <font color=gray>becomes</font> '".($tr = Translit::Supertag($str, TR_ALLOW_SLASHES))."'. <font color=gray>Supertag(UrlTranslit)==Supertag</font> ".($tr==Translit::Supertag(Translit::UrlTranslit($str, TR_ALLOW_SLASHES), TR_ALLOW_SLASHES)?"<font color=green>pass</font>":"<font color=red>fail</font>")."<br>";
}

echo "<h2>BiDi</h2>";

foreach ($arr as $str)
{
 $str = preg_replace("/[^\- _0-9a-zA-Z\xC0-\xFFёЁ\/]/", "", $str);
 echo "'".$str."' <font color=gray>becomes</font> '".($tr=Translit::BiDiTranslit($str, TR_ENCODE, TR_ALLOW_SLASHES))."' <font color=gray>then</font> '".($retr=Translit::BiDiTranslit($tr, TR_DECODE, TR_ALLOW_SLASHES))."', <font color=gray>and it</font> ".($str==$retr?"<font color=green>pass</font>":"<font color=red>fail</font>")."<br>";
 $str = preg_replace("/[^\- _0-9a-zA-Z\xC0-\xFFёЁ]/", "", $str);
 echo "'".$str."' <font color=gray>becomes</font> '".($tr=Translit::BiDiTranslit($str, TR_ENCODE, TR_NO_SLASHES))."' <font color=gray>then</font> '".($retr=Translit::BiDiTranslit($tr, TR_DECODE, TR_NO_SLASHES))."', <font color=gray>and it</font> ".($str==$retr?"<font color=green>pass</font>":"<font color=red>fail</font>")."<br>";
}

?>