<?php
/*
	 ListObject( &$rh, &$DATA ) -- базовый класс работы со списком
		- $data     -- массив списка

  ---------
  * &Parse( $tpl_root, $store_to, $append ) -- отпарсить по коллекции шаблонок
		- $tpl_root    -- корневая шаблонка списка, задаём как "file.html:List"
		- $store_to    -- если установлено, то результат также сохраняется в переменную домена с таким именем
								если === true, то записывает результат прямо туда, где был задан шаблон
		- $append      -- если непустое $store_to, то результат не стирает значение переменной, а дописывается в конец

  // переменные

  * $loop_index -- индекс текущей итерации
  * $loop_total -- общее число элементов в списке, который парсим

  * $this->implode -- в таком случае список собирается как *_Item  *_Separator  *_Item  *_Separator  *_Item

  * $tpl_root -- корневой шаблон списка
  * $tpl_empty - шаблон пустого списка
  * $tpl_item -- шаблон элемента списка
  * $tpl_separator -- шаблон разделителя между элементами

  * $list_store_to -- точка входа в корневом шаблоне списка, куда кладётся собственно список

  // вспомогательные шаблонные переменные
  для каждого ряда:
	 _Num -- номер текущей позиции, начинается с 1
	 _Even -- признак чётности, = $this->loop_index%2
  для корневого шаблона:
	 _ItemCount -- число элементов в списке

=============================================================== v.3 (Zharik)
 */

class ListObject 
{
	var $tpl; //то же, но на TemplateEngine
	var $ITEMS = array(); //ссылка на массив объектов, по которым нужно бегать
	var $EVOLUTORS = array();

	var $loop_index = 0;   // текущий номер итерации
	var $loop_total = 0;   // общее чисо итераций, которое нужно сделать

	var $implode = false;

	var $item_store_to = '*';

	var $tpl_root;
	var $tpl_empty;
	var $tpl_item;
	var $tpl_separator;

	var $list_store_to = "_";

	function __construct( &$ITEMS ){
		$this->tpl=&Locator::get('tpl');
		$this->ITEMS =& $ITEMS;
	}

	function Set (&$ITEMS ){
		$this->ITEMS =& $ITEMS;
	}

	function Parse( $tpl_root, $store_to=false, $append=false ){
		$tpl =& $this->tpl;

		Debug::trace( 'ListObject::Parse $tpl_root='.$tpl_root );

		//tpl vars
		$this->tpl_root       = $tpl_root;
		$this->tpl_empty      = $this->tpl_root."_Empty";

		//very, very impressive
		//if (!isset($this->tpl_item)) $this->tpl_item       = $this->tpl_root."_Item";
		$this->tpl_item = $this->tpl_root."_Item";
		$this->tpl_separator  = $this->tpl_root."_Sep";
		//ItemCount
		$this->loop_total = count($this->ITEMS);
		$tpl->set( '_ItemCount', $this->loop_total );
		$tpl->Set( $this->list_store_to, "" );

		//empty case
		if ( count($this->ITEMS) == 0 )
			return $tpl->Parse( $this->tpl_empty, $store_to, $append );

		//loop
		$result = "";
		$this->loop_index = 0;
		$this->loop_max = count(array_keys($this->ITEMS));
		foreach( $this->ITEMS as $k=>$r ) 
		{
			//this row

			$tpl->Set( '_Num', $this->loop_index+1 );
			$tpl->Set( '_Even', $this->loop_index%2 );
			$out[] = $this->parseOne();
		/*
		$tpl->SetRef( '*', $r );
		$result.=$tpl->Parse( $this->tpl_item );
		if( $this->implode && $this->loop_index < $this->loop_max-1 )
		  $result .= $tpl->Parse( $this->tpl_separator );
		 */

			//index by hands
			$this->loop_index++;
		}

		$result = implode( $this->implode ? $tpl->Parse( $this->tpl_separator ) : "" , $out   );
		$tpl->Set( $this->list_store_to, $result );
		//free misc handlers
		$tpl->Free(array(
			$this->tpl_empty,
			$this->tpl_item,
			$this->tpl_separator,
		));

		return $tpl->Parse( $tpl_root, $store_to, $append );
	}

	function parseOne()
	{
		$tpl =& $this->tpl;

		//assign misc values
		$tpl->Set( "_index", $this->loop_index );

		//get current item (array)
		$ITEM =& $this->ITEMS[ $this->loop_index ];
		
		$tpl->setRef($this->item_store_to, $ITEM);
		
		//assign evolutors
		foreach($this->EVOLUTORS as $field=>$func)
		{
			$handler = '_'.$field;
		
			$topic = array(&$this);
			if( is_callable($func) )
			{
				//object and method supported
				$out = call_user_func_array($func, $topic);

			}
			$ITEM[$handler] = $out;
		}

		//суффикс указан в поле?
		if( !($_suffix = $ITEM["_suffix"] ) )
		{
			//заморожен?
			if( !($_suffix = $this->_do($this->isfreezed_function)) )
				//текущий?
				$_suffix = $this->_do($this->issel_function);
		}

		return $tpl->parse( $this->tpl_item.$_suffix );
	}

	function _do($action){
		if(!$action) return null;
		/* lucky@npj: проще
		if( is_array($action) ){
			$obj =& $action[0];
			$method = $action[1];
			return $obj->$method($this);
		}else	return $action($this);
		 */
		$topic = array(&$this); // список аргументов экшена
		if (is_callable($action)) return call_user_func_array($action, $topic);
		//FIXME: В принципе, неправильный аргумент. Raise error?
		return NULL;
	}

	function getListIds()
	{
		if (!$this->all_ids)   
		{
			foreach ($this->ITEMS as $item)   
			{
				$this->all_ids[] = $item['id'];   
			}
		}
		return $this->all_ids;
	}
	}

?>
