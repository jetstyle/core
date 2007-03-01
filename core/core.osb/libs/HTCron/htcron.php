<?php
/* 
////////////////////////////////////////////////////////////////////////
// HTCron (Cron-like "daemon" for HTTP-tasks)                         //
// v. 0.45                                                            //
// supported: MZ1.4+, MSIE5+                                          //
//                                                                    //
// (c) Roman "Kukutz" Ivanov & Yulia Shabuno, 2003                    //
//                                                                    //
// http://wiki.oversite.ru/htcron  mailto:thingol@mail.ru             //
//                                                                    //
////////////////////////////////////////////////////////////////////////

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. The name of the author may not be used to endorse or promote products
   derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Должен инклюдиться в основной скрипт и вызываться через htcCycle()
либо через gif (см. index.html).

Пример вызова:

 ob_end_flush();
 flush();
 include("htcron.php");
 $db = array(
  "host"=>"localhost",
  "user"=>"npj",
  "password"=>"pwd",
  "database"=>"npj",
 );
 htcCycle($db, "r0_npz");

*/

//Параметры вызова htcCycle:
//  $db - объект совместимый с ADODB               // есть также поддержка работы без ADODB: тогда 
//  $table - имя cron-таблицы                      // $db должен содержать associative array с параметрами 
                                                   // доступа к БД (host, user, password, database)
//Структура cron-таблицы:
//  id - идентификатор работы
//  spec - строка в крон-синтаксисе
//  command - URL, соответствующий запуску задания
//  last - время последнего запуска

function htcCycle(&$db, $table) 
{
 //вынимаем из БД cron-таблицу
 if (is_array($db)) 
 {
   $cn = mysql_connect($db["host"],$db["user"],$db["password"]);
   mysql_select_db($db["database"],$cn);
   $rs = mysql_query("select * from ".$table);
   $arr = array();
   while ($row = mysql_fetch_assoc($rs)) 
     $arr[] = $row;
 } 
 else 
 {
   $rs = $db->Execute("select * from ".$table);
   $arr = $rs->GetArray();
 }

 //запускаем все необходимые задания
 if ($rs) {
  foreach ($arr as $npz)
  {
    if (htcDue((int)$npz["last"], time(), $npz["spec"])) 
    {
     htcRun($npz["command"], $npz["id"]);
     if (is_array($db)) 
       mysql_query("update ".$table." set last='".time()."' where id=".$npz["id"]);
     else
       $db->Execute("update ".$table." set last='".time()."' where id=".$npz["id"]);
    }
  } 
 }
}


//запуск задания. Задание = URL.
function htcRun($url, $npzid) 
{
   $uri=parse_url($url);
   $fp = fsockopen($uri["host"], 80, $errno, $errstr, 6);
   if($fp) {
     fputs($fp, "GET ".$uri["path"].($uri["query"]?"?".$uri["query"]."&npzid=".$npzid:"?npzid=".$npzid).
                 " HTTP/1.0\r\nHost: ".$uri["host"]."\r\nUser-Agent: htCron/0.43\r\n\r\n");
     socket_set_timeout($fp, 3);
     fgets($fp,1024);
     fclose($fp);
   }
}


//Проверка условия $cron исходя из даты последнего запуска $last и текущего времени $now.
function htcDue($last, $now, $cron)
{
// $last: last time at which the command was completed
// $now:  the reference time, usually the current time stamp
// $cron: the specifier in the usual crontab format
// returns TRUE if a timestamp exists between $last and $now fulfilling the $cron criteria.
// returns FALSE otherwise

 $NextRun = _GetNextRun($last,$cron);
 if ($NextRun==-1) return false;
 if ($NextRun<=$now) return true;
 return false;
}

function _GetNextRun($PrevRun, $CronString)
{
 $key_names = array("minutes", "hours", "mday", "mon", "wday");
 $last_value = array(59, 23, 31, 12, 6);
 $first_value = array(0, 0, 1, 1, 0);


 $prevdate = getdate($PrevRun);

 $elements = preg_split ("/[\s]+/", $CronString);
 for ($i = 0; $i <= 4; $i++){
   $lists[$key_names[$i]] = _UnWrapList($elements[$i], $first_value[$i], $last_value[$i], $prevdate[$key_names[$i]]);
 } 

 for($year=$prevdate["year"];$year<=$prevdate["year"]+10;$year++){
   reset($lists["mon"]);
   while (list(,$mon) = each($lists["mon"])){
     if (   $year==$prevdate["year"] 
         && $mon < $prevdate["mon"] ) continue;
     reset($lists["mday"]);
     while(list(,$mday) = each($lists["mday"])){
       if (   $year==$prevdate["year"] 
           && $mon ==$prevdate["mon"]
           && $mday < $prevdate["mday"]) continue;
       if (! checkdate (  $mon, $mday, $year)) continue;
       list(,,,,$wday) = array_values(getdate(mktime (0,0,0,$mon,$mday,$year)) );
       if (! in_array($wday ,$lists["wday"])) continue;

       reset($lists["hours"]);
       while(list(,$hours) = each($lists["hours"])){
         if (   $year==$prevdate["year"] 
             && $mon ==$prevdate["mon"]
             && $mday==$prevdate["mday"]
             && $hours < $prevdate["hours"]) continue;

         reset($lists["minutes"]);
         while(list(,$minutes) = each($lists["minutes"])){
           if (   $year==$prevdate["year"] 
               && $mon ==$prevdate["mon"]
               && $mday==$prevdate["mday"]
               && $hours == $prevdate["hours"]
               && $minutes <= $prevdate["minutes"] ) continue;

           return mktime ($hours,$minutes,0,$mon,$mday,$year);
         }
       }
     }
   }
 }
 //NEXT RUN - NEVER OR MORE THAN AFTER 10 YEARS
 return -1; 
}

function _UnWrapList($String,$FirstElement,$LastElement,$UseAsElement)
{
  $lst = array();
  //explode list
  $items = explode(",", $String);
  while (list (, $item) = each ($items)) {
    //get diapasone and delimeter from list item
    list ($diapasone, $delimeter) = explode("/", $item);      
    if (is_null($delimeter)) $delimeter = 1;
    //get first and last from diapasone
    list ($from, $to) = explode("-", $diapasone);      
    if (is_null($to)) $to = $from;
    if ($from=="*") $from = $FirstElement;
    if ($to=="*") $to = $LastElement;
    for ($i = max($from,$FirstElement); $i<=min($LastElement, $to); $i++){
      if ($i % $delimeter == $UseAsElement % $delimeter){
        $lst[] = $i;
      }

    }
  }
  $lst = array_unique($lst);
  sort($lst);
  return $lst;
}


?>