<?php
/**
 * Класс отладки
 * @author lunatic lunatic@jetstyle.ru
 */
class Debug
{
	static protected $log = array();
	static protected $_milestone;
	static protected $milestone;
	static protected $prefix = "<div class='debug-div' id='debug-div'>Trace log:";
	static protected $postfix = "</div><div class='debug-cont' id='debug-cont'></div>";
	static protected $canGetMemoryUsage = false;
	static protected $mark = array();
	static protected $inc = 1;
	static protected $categories = array();
	
	/**
    * Инициализация
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
    * работа с временными отметками
    */
	static protected function _getmicrotime()
	{
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}


	/**
    * Формирование лога
    *
    * @param $prefix
    * @param $separator
    * @param $postfix
    * @return HTML
    */
	static public function getHtml()
	{
		self::trace( "<b>log flushed</b>");
		
		$out = '';
		$out .= self::getStyles();
		$out .= self::$prefix;
		
		// parse categories
		$out .= '<div class="debug-categories">';
		$out .= '<span class="selected-" id="debug-category-all" onclick="JSDebug.showCategory(this, \'all\');">All</span>';
		foreach(self::$categories AS $title => $count)
		{
			$out .= '<span class="debug-clickable" onclick="JSDebug.showCategory(this, \''.str_replace("'", "\'", $title).'\');" >'.$title.'</span>';
		}
		$out .= '</div>';
		
		$out .= '<ul id="debug-all">';
		foreach (self::$log AS $item)
		{
			$out .= self::constructHtmlItem($item);
		}
		$out .= '</ul>';
		
		$out .= self::$postfix;
		$out .= self::getJavascript();
		
		self::$log = array();
		return $out;
	}
	
	
	/**
	 * Добавление записи в лог
	 *
	 * @param string $title
	 * @param string $category категория записи
	 * @param string $label отметка, поставленная функцией mark(), от нее будет считаться время выполнения
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
			'category' => $category, 
			'title' => $title,
			'text' => $text,
		);
		if($category)
		{
			self::$categories[$category]++;
		}
		self::$mark['auto'] = $m;
	}

	/**
	 * Ставим контрольную отметку
	 *
	 * @param string $label
	 */
	static public function mark($label = 'auto')	
	{
		self::$mark[$label] = self::_getmicrotime();
	}

	/**
	 * умереть, тихо или громко
	 */ 
	static public function halt( $flush = 1 )
	{
		header("Content-Type: text/html; charset=windows-1251");
		if ($flush) echo self::$getHtml();
		die("К сожалению, произошла ошибка.");
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
				
		return '<li type="'.$item['category'].'">'.$out.'</li>';
	}
	
	static protected function getId()
	{
		return 'debug'.self::$inc++;
	}
	
	static protected function getStyles()
	{
		$txt = '<style type="text/css">
			.debug-div {font-size: 16px;}
			.debug-clickable {cursor: pointer; }
			.debug-cont {position: absolute; background-color: #CACACA; border: 1px solid black; padding: 5px; font-size: 16px;}
			.debug-cont td {border-right: 1px solid grey; border-bottom: 1px solid grey; padding: 3px;}
			.debug-categories {padding: 5px;}
			.debug-categories span {padding: 2px; margin: 2px;}
			.debug-categories span.selected- {background-color: #CACACA;} 
		</style>';
		
		return $txt;
	}
	
	static protected function getJavascript()
	{
		$txt = '<script language="javascript">
					JSDebug = function(){
						this.cont = document.getElementById("debug-cont");
						this.allItems = document.getElementById("debug-all");
						this.selectedId = null;
						this.selectedCategory = document.getElementById("debug-category-all");
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
					JSDebug.prototype.showCategory = function(el, t)
					{
						if(JSDebug.selectedCategory)
						{
							JSDebug.selectedCategory.className = "debug-clickable";
						}
						el.className = "selected-";
						JSDebug.selectedCategory = el;
						
						for(var i=0; i < JSDebug.allItems.childNodes.length; i++)
						{
							if(t == "all")
							{
								JSDebug.allItems.childNodes[i].style.display = "";
							}
							else
							{
								if(JSDebug.allItems.childNodes[i].type == t)
								{
									JSDebug.allItems.childNodes[i].style.display = "";
								}
								else
								{
									JSDebug.allItems.childNodes[i].style.display = "none";
								}
							}
						}
					}
					var JSDebug = new JSDebug();
				</script>';
		
		return $txt;
	}
}
?>