<?php
/**
 * 
 * @uses: Locator::get("controller")
 *
 * Ex:
 * $ical=new IcalView();
 * $ical->setModel( $aDBModel );    или $rss->setData( $array );
 * $ical->getHtml();
 */
class IcalView
{
    private $data = array();
    
    private $model;
    
    private $limit = 20;
    
    public function setData($data)
    {
        $this->data=$data;    
    }
    
    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getHtml()
    {
        if (empty($this->data))
    	    $this->data = $this->model->load();

	    foreach ($this->data as $i=>$data)
	    {
	        $vevents[] = $this->writeEvent( $data);
	    }

        $out = "BEGIN:VCALENDAR\r\n";
        $out.= "VERSION:1.0\r\n";
        $out.= "PRODID:-//".Config::get("project_name")."//EN\r\n";
        $out.= "METHOD:PUBLISH\r\n";
        $out.= implode("",$vevents);
        $out.= "END:VCALENDAR\r\n";

        return $out;
    }

    /**
     * * $row["inserted_unixtime"] - дата+время начала события в unixtime
     * $row["inserted_end_unixtime"] - дата+время конца события в unixtime
     * $row["duration"] - продолжительность событя в часах (по-умолчанию 1)
     */

    protected function writeEvent(  $row ) {
            
            $dtstart =  gmdate('Ymd', $row["inserted_unixtime"])."T".gmdate('His', $row["inserted_unixtime"])."Z";//;


            if ( ! $row["duration"] )
                $duration = "PT1H";
            else
                $duration = "PT".$row["duration"]."H";

            $uid = md5($dtstart.$summary.$row["id"] )."@".Config::get("project_name");
            $event = array(
                    "BEGIN"=>"VEVENT",
                    "UID"=>$uid,
                    //"DTSTAMP"=>gmdate('Ymd')."T".gmdate('His')."Z",
                    "DTSTART"=>$dtstart,
                    "DURATION"=>$duration, //1h
//                    "DTEND"=>$dtend,
                    "SUMMARY"=> iconv("cp1251", "utf-8", $row["title"]),
                    "END"=>"VEVENT"
            );

            if ( $row["inserted_end_unixtime"] )
                $event["DTEND"] =  gmdate('Ymd', $row["inserted_end_unixtime"])."T".gmdate('His', $row["inserted_end_unixtime"])."Z";
            //

            $vevent = "";
            foreach ($event as $k=>$v)
                $vevent .= $k.":".$v."\r\n";
                               
            
            
            return $vevent;
    }

}


