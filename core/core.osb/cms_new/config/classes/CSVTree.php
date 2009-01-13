<?
/*
	CSVTree - разбор csv-файла в дерево с данными
	=======

	$DATA -- массив, каждый элемент $t:
    $t["father"]      -- индекс в $DATA родительской рубрики
    $t["children"]    -- массив индексов дочерних рубрик
    $t["data"]        -- массив записей данных, содержащихся в этой рубрике
    											один элемент - одна запись
    $t["level"]       -- уровень вложенности данной рубрики
    $t["title"]       -- название данной рубрики
		
*/

class CSVTree{

	var $rh; //ссылка на $rh

	var $DATA = array(); //сложный массив с данными

	var $delimiter = ";";

	function CSVTree(&$rh){
		$this->rh =& $rh;
	}

	function Parse($file_name){
		
		$fp = fopen($file_name,"r");

    /* обрабатываем данные */
    $t[0]["father"] = -1; //no father for fiction topic
    $t[0]["level"] = 0;
    $ct = 0;       //current topic
    $tn = 0;       //index of last topic
    $t_cnt = 0;

    while($r=fgetcsv($fp,1000,$this->delimiter)){
     while(count($r) && $r[count($r)-1]=="") array_pop($r);     //strip empty cells
     if($r[0]==""){
     	$t[$ct]["data"][] = array_slice($r,1);  //attach data
     	$t_cnt++;
     }
     //a new topic
     else{
       //find a proper father for a new topic
       if($r[0]<=$t[$ct]["level"])
         while($t[$ct]["level"]>=$r[0]) $ct = $t[$ct]["father"];

       //attach a new topic to the proper father
       $t[$ct]["children"][] = ++$tn;
       $t[$tn]["father"] = $ct;
       $ct = $tn;
       $t[$ct]["level"] = $r[0];      
       for($i=1;$i<count($r);$i++) if($r[$i]!="") $t[$ct]["title"] .= $r[$i]." ";    
     }
    }

    fclose($fp);

    //линкуем результат
    $this->DATA =& $t;
	}
}

?>