<?php
/*
   Наследует от: Arrows


   ArrowsMore (  &$rh, $from='', $where='', $page_size=0, $frame_size=0 )  -- Класс для орагнизации страничной прокрутки
      - $rh -- ссылка на $rh
      - $from, $where, $page_size, $frame_size -- см. Setup()
      Здесь же восстанавливается ->current_page из запроса
  
  ---------

  Лучше тем, что использует паттерн поисковых систем.
  Портирован с Manifesto
  
=============================================================== v.0 (kuso@npj)
*/
$this->UseClass("Arrows");

class ArrowsMore extends Arrows
{
   var $block_page_size  = false;
   var $block_frame_size = false;

   // внутренние переменные
   var $_pagesize;
   var $_pageno;
   var $_pageframesize;
   var $_pageframeno;
   var $_itemcount; 

   // здесь логика выбора правильной страницы и плавающего фрейма
   function _Calculate()
   {
     // defaults:
     $this->_pagesize = $this->page_size;
     $this->_pageframesize = $this->frame_size;
     $this->_pageno = 1;
     $this->_pageframeno = 1;               
     $this->_itemcount = $this->record_count;

     // adjust size
     if (!$this->block_page_size) 
       if ($this->Get( $this->prefix."pagesize" ))
        $this->_pagesize = $this->Get( $this->prefix."pagesize" );
     if (!$this->block_frame_size) 
       if ($this->Get( $this->prefix."framesize" ))
         $this->_pageframesize = $this->Get( $this->prefix."framesize" );

     // set counts
     if ($this->_itemcount && $this->_pagesize)
     {
       if ($this->_itemcount > $this->_pagesize)
       {   
         $this->_pagecount = ceil( $this->_itemcount / $this->_pagesize );
       }
       else
       {
       	 $this->_pagecount = 1;
       }
       if ($this->_pageframesize)
       if ($this->_pagecount > $this->_pageframesize) 
         $this->_pageframecount = ceil( $this->_pagecount / $this->_pageframesize );
     } 

     // adjust positions
     $this->_pageno = $this->current_page; 
    
     if ($this->_pageno <= 0) $this->_pageno = 1;
     if ($this->_pageno > $this->_pagecount) $this->_pageno = $this->_pagecount;
     if ($this->_pageframesize)
      $this->_pageframeno = floor(($this->_pageno-1) / $this->_pageframesize +1);
     else 
      $this->_pageframeno = 1;
     if ($this->_pageframesize)
     if ($this->_pageframeno > $this->_pageframesize) $this->_pageframeno = $this->_pageframesize;     
   }

   // вспомогательные функции
   function _GetItemCount()       { return $this->_itemcount; }
   function _GetPageCount()       { return $this->_pagecount; }
   function _GetPageSize()        { return $this->_pagesize; }
   function _GetPageNo()          { return $this->_pageno; }
   function _GetPageFrameCount()  { return $this->_pageframecount; }
   function _GetPageFrameSize()   { return $this->_pageframesize; }
   function _GetPageFrameNo()     { return $this->_pageframeno; }

   function Limit()
   {
     $this->_Restore();
     return ($this->_pagesize?$this->_pagesize:-1);
   }
   
   function Offset()
   {
     $this->_Restore();
     return ($this->_pagesize?($this->_pagesize*($this->_pageno-1)):-1);
   }

   // парсинг
   function Parse( $template, $store_to="", $append=false )
   {
     $this->_Restore();
     $this->rh->debug->Trace('Arrows::Parse - $current_page='.$this->current_page.', $record_count='.$this->record_count);

     $tpl = &$this->tpl;
 
     $no     = $this->_GetPageNo();
     $size   = $this->_GetPageSize();
     $count  = $this->_GetPageCount();

     $fno    = $this->_GetPageFrameNo();
     $fsize  = $this->_GetPageFrameSize();
     $fcount = $this->_GetPageFrameCount();
     if (!$fsize) $fsize = $count*20+20;

     // Общее для всей листалки
     $tpl->Load( array(
      "_RecordCount"       => $this->_GetItemCount(),
      "_PageNo"      => $no,
      "_PageSize"    => $size,
      "_PageCount"   => $count,

      "_HaveFirst" => 1,                       "Href_First" => $this->HrefPlus( "", $this->varname , 1 ),
      "_HaveLast"  => $count,                  "Href_Last"  => $this->HrefPlus( "", $this->varname , $count ),
      "_HaveNext"  => ($count>$no)?($no+1):"", "Href_Next"  => $this->HrefPlus( "", $this->varname , $no+1 ),
      "_HavePrev"  => ($no>1)?($no-1):"",      "Href_Prev"  => $this->HrefPlus( "", $this->varname , $no-1 ),

      /* поддержка фреймов не реализуется пока
      "PageFrameNo"      => $fno,
      "PageFrameSize"    => $fsize,
      "PageFrameCount"   => $fcount,
      "PageFrameNext"  => ($fcount>$fno)?($fno+1):"", "Link:PageFrameNext" => $this->HrefPlus( "", $this->varname , $fno+1 ),
      "PageFramePrev"  => ($fno>1)?($fno-1):"",       "Link:PageFramePrev" => $this->HrefPlus( "", $this->varname , $fno-1 ),
       */
                     ) );

     // Делаем список страниц
     $pages = array();

     if ($this->page_frame_slip)
     {
       $i = $no-$fsize/2;
       $endi = $i+$fsize;
       if ($i<1) 
       { $i=1; $endi = $fsize; }
       if ($endi > $this->_pagecount) 
       { $i=$this->_pagecount-$fsize; 
         if ($i<1) $i=1;
         $endi = $this->_pagecount; 
       }
     }
     else
     {
       $i = ($fno-1)*$fsize;
       $endi = $i+$fsize;
       if ($i<1) { $i=1; $endi = $fsize; }
       if ($endi > $this->_pagecount) $endi = $this->_pagecount;
     }

     for (; $i<=$endi; $i++)
     {
       $pages[$i]["IsCurrent"] = $i == $this->_pageno;
       $pages[$i]["PageNo"] = $i;
       $pages[$i]["Href"] = $this->HrefPlus( "", $this->varname, $i);
       /* commented, потому что кажется неиспользуемым
       $pages[$i]["_First"] = ($i-1)*$size;
       $pages[$i]["_Last"]  = ($i)*$size;
       if ($pages[$i]["_Last"] > $this->_itemcount) $pages[$i]["_Last"] = $this->item_count;
       */
     }

     // Делаем список окон
     // -> пока для этого стоит пользоваться оригинальным Arrows

     // рендерим страницы
     $tpl->loop( $pages, $template."_List", $this->list_store_to, false, $this->implode );
     //обёртка
     return  $tpl->parse( $template, $store_to, $append );
     
   }

	function getTplData()
	{
		$t = array();
		$t['page'] = $this->_GetPageNo();
		$t['page_count'] = $this->_GetPageCount();
		$t['page_size'] = $this->_GetPageSize();
		$t['item_count'] = $this->_GetItemCount();
		$t['item'] = $this->Offset() + 1;
		$t['item_last'] = $this->Offset() + $t['page_size'];

		if ($t['page_count'] > $t['page'])
		{
			$t['page_next'] = $t['have_next'] = $t['page'] + 1;
			$t['next_href'] =  $this->rh->ri->HrefPlus('', array($this->varname => $t['page_next']) );
		}
		
		if ($t['page'] > 1)
		{
			$t['page_prev'] = $t['have_prev'] = $t['page'] - 1;
			$t['prev_href'] =
			  $this->rh->ri->HrefPlus('', array( $this->varname => $t['page_prev']) );
		}

		foreach (range(1, $t['page_count']) as $i)
		{
			$p = array();
			$p['page'] = $i;
			$p['href'] = $this->rh->ri->HrefPlus('', array( $this->varname => $p['page']) );
			if ($i == $t['page']) $p['selected'] = True;
			$t['pages'][$i] = $p; 
		}
		return $t;
	}

}


?>