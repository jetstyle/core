<?php
/*
	 ListObject( &$rh, &$DATA ) -- ������� ����� ������ �� �������
		- $data     -- ������ ������

  ---------
  * &Parse( $tpl_root, $store_to, $append ) -- ��������� �� ��������� ��������
		- $tpl_root    -- �������� �������� ������, ����� ��� "file.html:List"
		- $store_to    -- ���� �����������, �� ��������� ����� ����������� � ���������� ������ � ����� ������
								���� === true, �� ���������� ��������� ����� ����, ��� ��� ����� ������
		- $append      -- ���� �������� $store_to, �� ��������� �� ������� �������� ����������, � ������������ � �����

  // ����������

  * $loop_index -- ������ ������� ��������
  * $loop_total -- ����� ����� ��������� � ������, ������� ������

  * $this->implode -- � ����� ������ ������ ���������� ��� *_Item  *_Separator  *_Item  *_Separator  *_Item

  * $tpl_root -- �������� ������ ������
  * $tpl_empty - ������ ������� ������
  * $tpl_item -- ������ �������� ������
  * $tpl_separator -- ������ ����������� ����� ����������

  * $list_store_to -- ����� ����� � �������� ������� ������, ���� ������� ���������� ������

  // ��������������� ��������� ����������
  ��� ������� ����:
	 _Num -- ����� ������� �������, ���������� � 1
	 _Even -- ������� ��������, = $this->loop_index%2
  ��� ��������� �������:
	 _ItemCount -- ����� ��������� � ������

=============================================================== v.3 (Zharik)
 */

class ListObject {

	var $rh;  //������ �� ������ ���� RequestHandler
	var $tpl; //�� ��, �� �� TemplateEngine
	var $ITEMS = array(); //������ �� ������ ��������, �� ������� ����� ������
	var $EVOLUTORS = array();

	var $loop_index = 0;   // ������� ����� ��������
	var $loop_total = 0;   // ����� ���� ��������, ������� ����� �������

	var $implode = false;

	var $item_store_to = '*';

	var $tpl_root;
	var $tpl_empty;
	var $tpl_item;
	var $tpl_separator;

	var $list_store_to = "_";

	function ListObject( &$rh, &$ITEMS ){
		$this->rh =& $rh;
		$this->tpl=& $rh->tpl;
		$this->ITEMS =& $ITEMS;
	}

	function Set (&$ITEMS ){
		$this->ITEMS =& $ITEMS;
	}

	function Parse( $tpl_root, $store_to=false, $append=false ){
		$tpl =& $this->tpl;

		$this->rh->debug->Trace( 'ListObject::Parse $tpl_root='.$tpl_root );

		//tpl vars
		$this->tpl_root       = $tpl_root;
		$this->tpl_empty      = $this->tpl_root."_Empty";
		if (!isset($this->tpl_item)) $this->tpl_item       = $this->tpl_root."_Item";
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
		$tpl =& $this->rh->tpl;

		//assign misc values
		$tpl->Set( "_index", $this->loop_index );

		//get current item (array)
		$ITEM =& $this->ITEMS[ $this->loop_index ];

		//resolve $FIELDS array
	 /*
	 if( count($this->ASSIGN_FIELDS)==0 )
		$FIELDS = array_keys($ITEM);
	 else
		$FIELDS =& $this->ASSIGN_FIELDS;

	 //assign from $ITEM
	 $N = count($FIELDS);
	 for( $i=0; $i<$N; $i++ )
		$tpl->Assign( "_".$FIELDS[$i], $ITEM[ $FIELDS[$i] ] );
	  */
		//assign evolutors
		foreach($this->EVOLUTORS as $field=>$func)
		{
			$handler = '_'.$field;
		/* lucky@npj: ����� ���� �����
		if( is_array($func) )
		{
		  //object and method supported
		  $_func = $func[1];
		  $out = $func[0]->$_func($this) ;

		}
		else
		{
		  / *
		  if( $tpl->CheckAction($func) )
			 //tpl-action
			 $tpl->Assign( $handler, $tpl->Action($func) );
		  else
				* /
			 //php-function
			 $out = $func($this);
		}
		 */
			$topic = array(&$this);
			if( is_callable($func) )
			{
				//object and method supported
				$out = call_user_func_array($func, $topic);

			}
			$ITEM[$handler] = $out;
		}

		//������� ������ � ����?
		if( !($_suffix = $ITEM["_suffix"] ) )
		{
			//���������?
			if( !($_suffix = $this->_do($this->isfreezed_function)) )
				//�������?
				$_suffix = $this->_do($this->issel_function);
		}

		//������-��������
		//$tpl->assign( '__even', $this->loop_index%2 ? '1' : '0' );

		$tpl->setRef($this->item_store_to, $ITEM);

		return $tpl->Parse( $this->tpl_item.$_suffix );
	}

	function _do($action){
		if(!$action) return null;
		/* lucky@npj: �����
		if( is_array($action) ){
			$obj =& $action[0];
			$method = $action[1];
			return $obj->$method($this);
		}else	return $action($this);
		 */
		$topic = array(&$this); // ������ ���������� ������
		if (is_callable($action)) return call_user_func_array($action, $topic);
		//FIXME: � ��������, ������������ ��������. Raise error?
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
