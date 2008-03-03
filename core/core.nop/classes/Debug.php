<?php
/**
 * ����� �������
 * @author lunatic lunatic@jetstyle.ru
 */
class Debug
{
	static protected $log = array();
	static protected $_milestone;
	static protected $milestone;
	static protected $prefix = "<div class='debug-div' id='debug-div'>Trace log:<ul><li>";
	static protected $separator = "</li><li>";
	static protected $postfix = "</li></ul></div><div class='debug-cont' id='debug-cont'></div>";
	static protected $canGetMemoryUsage = false;
	static protected $mark = array();
	static protected $inc = 1;
	
	/**
    * �������������
    *
    */
	static public function init()
	{
		self::$milestone = self::_getmicrotime();
		self::$_milestone = self::$milestone;
		self::$mark['auto'] = self::$milestone;
		self::$canGetMemoryUsage = function_exists('memory_get_usage');
		self::trace("<b>log started.</b>");
	}

	/**
    * ������ � ���������� ���������
    */
	static protected function _getmicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}


	/**
    * ������������ ����
    *
    * @param $prefix
    * @param $separator
    * @param $postfix
    * @return HTML
    */
	static public function getHtml()
	{
		$out = '';
		self::trace( "<b>log flushed</b>");
		$out .= self::getStyles();
		$out .= self::$prefix;
		
		foreach (self::$log AS $item)
		{
			$out .= self::constructHtmlItem($item);
			$out .= self::$separator;
		}
		
		$out .= self::$postfix;
		$out .= self::getJavascript();
		self::$log = array();
		return $out;
	}
	
	
	/**
	 * ���������� ������ � ���
	 *
	 * @param string $title
	 * @param string $category ��������� ������
	 * @param string $label �������, ������������ �������� mark(), �� ��� ����� ��������� ����� ����������
	 */
	static public function trace( $title, $category = null, $label = null, $text = '')
	{
		$m = self::_getmicrotime();
		$diff = $m - self::$_milestone;
		if ($label)	
		{
			$diff1 = $m - self::$mark[$label];
			unset(self::$mark[$label]);
		}
		else
		{
			$diff1 = $m - self::$mark['auto'];
		}

		if (self::$canGetMemoryUsage)
		{
			$memory = number_format((memory_get_usage() / 1024));
		}
		
		self::$log[] = array(
			'time' => $diff,
			'diffTime' => $diff1,
			'memory' => $memory,
			'title' => $title,
			'text' => $text,
		);
		self::$mark['auto'] = $m;
	}

	/**
	 * ������ ����������� �������
	 *
	 * @param string $label
	 */
	static public function mark($label = 'auto')	
	{
		self::$mark[$label] = self::_getmicrotime();
	}

	/**
	 * �������, ���� ��� ������
	 */ 
	static public function halt( $flush = 1 )
	{
		header("Content-Type: text/html; charset=windows-1251");
		if ($flush) echo self::$getHtml();
		die("� ���������, ��������� ������.");
	}
	
	static public function setPrefix($d)
	{
		self::$prefix = $d;
	}
	
	static public function setSeparator($d)
	{
		self::$separator = $d;
	}
	
	static public function setPostfix($d)
	{
		self::$postfix($d);
	}
	
	static protected function constructHtmlItem($item)
	{
		$out = '';
		
		$out .= sprintf("[%0.4f] ",$item['time']);
		$out .= sprintf("[%0.4f] ",$item['diffTime']);
		
		if($item['memory'])
		{
			$out .= '['.$item['memory'].' kb]';
		}
		
		$out .= '&nbsp;&nbsp;';
		
		if($item['text'])
		{
			$out .= '<span class="debug-clickable" onclick="JSDebug.show(this)" id="'.self::getId().'">'.$item['title'].'</span><span style="display: none;">'.$item['text'].'</span>';
		}
		else
		{
			$out .= $item['title'];
		}
		
		return $out;
	}
	
	static protected function getId()
	{
		return 'debug'.self::$inc++;
	}
	
	static protected function getStyles()
	{
		$txt = '<style type="text/css">
			.debug-div {font-size: 150%;}
			.debug-clickable {cursor: pointer; }
			.debug-cont {position: absolute; background-color: #CACACA; border: 1px solid black; padding: 5px; font-size: 150%;}
			.debug-cont td {border-right: 1px solid grey; border-bottom: 1px solid grey; padding: 3px;}
		</style>';
		
		return $txt;
	}
	
	static protected function getJavascript()
	{
		$txt = '<script language="javascript">
					JSDebug = function(){
						this.cont = document.getElementById("debug-cont");
						this.selectedId = null;
					}
					JSDebug.prototype.show = function(el)
					{
						if(el && el.nextSibling)
						{
							JSDebug.showPopup(el, el.nextSibling.innerHTML);
						}
					}
					JSDebug.prototype.showPopup = function(el, txt)
					{
						if(JSDebug.selectedId == el.id)
						{
							JSDebug.cont.style.display = "none";
							JSDebug.selectedId = null;
							return;
						}
						var c = JSDebug.getPosition(el);
						JSDebug.cont.style.left = c.x + "px";
						JSDebug.cont.style.top = (c.y + el.offsetHeight) + "px";
						JSDebug.cont.innerHTML = txt;
						JSDebug.cont.style.display = "";
						JSDebug.selectedId = el.id;
					}
					JSDebug.prototype.getPosition = function(e)
					{
						var left = 0;
						var top  = 0;
						
						while (e.offsetParent){
							left += e.offsetLeft + (e.currentStyle?(parseInt(e.currentStyle.borderLeftWidth)).NaN0():0);
							top  += e.offsetTop  + (e.currentStyle?(parseInt(e.currentStyle.borderTopWidth)).NaN0():0);
							e     = e.offsetParent;
						}
				
						left += e.offsetLeft + (e.currentStyle?(parseInt(e.currentStyle.borderLeftWidth)).NaN0():0);
						top  += e.offsetTop  + (e.currentStyle?(parseInt(e.currentStyle.borderTopWidth)).NaN0():0);
				
						return {x:left, y:top};
					}
					var JSDebug = new JSDebug();
				</script>';
		
		return $txt;
	}
}
?>