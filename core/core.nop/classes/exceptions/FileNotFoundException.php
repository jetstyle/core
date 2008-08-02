<?php

class FileNotFoundException extends JSException
{
	protected $filename = '';
	
	public function setFilename($v)
	{
		$this->filename = $v;
	}
	
	public function getFilename()
	{
		return $this->filename;
	}
	
//	protected $text = '';
//	
//	public function __construct($msg, $text, $searchHistory = array()) 
//	{
//		$this->searchHistory = $searchHistory;
//		return parent::__construct($msg);
//	}
//
//	public function __toString() 
//	{
//		$ret = __CLASS__ . ": " . $this->codes_names[$this->code] . ": {$this->message}\n";
//		
//		$ret .= $this->buildSearchHistory();
//		
//
//		if ($this->code==1)
//		{
//			$traces = $this->getTrace();
//
//			$needle = $this->file_name;
//			
//			foreach ($traces as $k=>$trace)
//			{
//				
//				if ($can_seek && ( $trace['function']=='Parse' || $trace['function']=='_constructValue' ) && !$this->in_args($needle, $trace['args']) )
//				{					
//					//не выводить бэктрейс
//					$this->no_trace = true;
//					
//					$from = $traces[$k]['args'][0];
//					$file_source = $this->rh->tpl_root_dir.$this->rh->tpl_skin."/templates/".preg_replace("/\.html:.*/", ".html", $from);
//					if(file_exists($file_source))
//					{
//						$contents = file($file_source);
//					}
//					else
//					{
//						$contents = array();
//					}
//					$contents = str_replace($needle, "<font color='red'>".$needle."</font>", $contents);
//
//				  	$pret = "<br><div style='background-color:#DDDDDD'> ";
//				  	$pret .= "<span style='float:left'><b>Parsed from: <a href='#' onclick='document.getElementById(\"exc_".$k."\").style.display= (document.getElementById(\"exc_".$k."\").style.display==\"\" ? \"none\" : \"\" ); document.getElementById(\"exc_".$k."\").style.backgroundColor=\"#EEEEEE\"; return false;'>".$from."</a></b></span>
//				  	<span style='float:right'>".$file_source."</span>
//				  	</div>";
//				  	$pret .= "<div style='display:none' id=\"exc_".$k."\"><br>" . nl2br(implode("\n", $contents)) . "</div></p>";
//					
//					break;
//				}
//				
//				if ( $this->in_args($needle, $trace['args']) )
//				{
//					$can_seek = true;	
//				}
//			} 
//			
//		}
//
//		/**
//		 * На случай если в site_map.php в HTML:body указывает на несуществующий шаблон
//		 */
//		if(empty($files) && $this->html_body)
//		{
//			//не выводить бектрейс
//			$this->no_trace = true;
//			$file_source = $this->rh->tpl_root_dir.$this->rh->tpl_skin."/site_map.php";
//			$contents = file($file_source);
//			$contents = str_replace($needle, "<font color='red'>".$needle."</font>", $contents);
//			$from = "site_map";
//		  	$pret = "<br><div style='background-color:#DDDDDD'> ";
//		  	$pret .= "<span style='float:left'><b>Sitemapped from: <a href='#' onclick='document.getElementById(\"exc_".$k."\").style.display= (document.getElementById(\"exc_".$k."\").style.display==\"\" ? \"none\" : \"\" ); document.getElementById(\"exc_".$k."\").style.backgroundColor=\"#EEEEEE\"; return false;'>".$from."</a></b></span>
//		  	<span style='float:right'>".$file_source."</span>
//		  	</div>";
//		  	$pret .= "<div style='display:none' id=\"exc_".$k."\"><br>" . nl2br(implode("\n", $contents)) . "</div></p>";
//		}
//
//		return $ret . $pret;
//	}
//	
//	protected function in_args($needle, $args)
//	{
//		$ret = false;
//		foreach ($args as $arg)
//		{
//			if (is_array($arg))
//			{
//				foreach ($arg as $ar_key=>$ar)
//				{
//					if ($this->equalTemplates($needle, $ar))
//					{
//						$ret = true;	
//						if ($ar_key == "HTML:body")
//							$this->html_body=true;
//						break;		
//					}
//				}
//			}
//			else if ($this->equalTemplates($needle, $arg))
//			{
//				$ret = true;	
//				break;	
//			}
//		}
//		return $ret;
//	}
//	
//	protected function equalTemplates($needle, $compare, $dump=false)
//	{
//		$ret = false;
//		
//		if (is_object($compare))
//		{
//			return $ret;
//		}
//		
//		$c1 = strcmp($needle, $compare);
//		$c2 = strcmp('@'.$needle.'.html', $compare);
//		$c3 = strcmp($needle.'.html', $compare);
//		$ret = $c1==0 || $c2==0 || $c3==0;
//		/*
//		if ($dump)
//			echo '<br>['.($c1.' || '.$c2.' || '.$c3.' ('.$ret.')');
//			*/
//			
//		return $ret;	
//	}
//	
//	protected function buildSearchHistory()
//	{
//		if(empty($this->searchHistory))
//		{
//			return '';
//		}
//		
//		$out = '<div style="margin-top: 15px;"><b>Search history:</b></div>';
//		$out .= '<div class="search-history">';
//
//		foreach($this->searchHistory AS $k => $v)
//		{
//			$out .= '<div>'.$k.' - '.str_replace($this->rh->project_dir, '', $v).'</div>';
//		}
//		
//		$out .= '</div>';
//		return $out;
//	}
	
}

?>