<?
/*
    ListObjectTree -- ��������� ListObject, ���������� ���, ��� ������� ������ ��� ������, ������� ������ � �������
  
  ---------

  * &Parse( $tpl_root, $store_to=false, $append=false ) -- ��������� ������ �� ��������� ��������
			���������� �� KistObject::Prase() ������� ������ ������

	������������, ��� $DATA, ��� ��� id=>record, 
	������������� ������ $CHILDREN - ������ id �������� ��� ������� ����
  
=============================================================== v.1 (Zharik)
*/
	
	$this->UseClass('ListObject');
  
class ListObjectTree extends ListObject {
  
  function &Parse( $tpl_root, $store_to=false, $append=false ){
		$tpl =& $this->rh->tpl;
		
		//tpl vars
		$this->tpl_root				=	$tpl_root;
    $this->tpl_item				= $this->tpl_root."_Item";
		
		//shortcuts
		$CHILDREN =& $this->CHILDREN;
		$ITEMS =& $this->DATA;
		
/*		$this->rh->debug->Trace_R($this->DATA,0,"ITEMS");
		$this->rh->debug->Trace_R($this->CHILDREN,1,"CHILDREN");*/
		
		/* deep search */
		$STACK = array();
		//put root
		$A =& $CHILDREN[0];
		for($i=count($A)-1;$i>=0;$i--) $STACK[] = $A[$i];
		//main loop
		while(count($STACK)){
			$this->loop_index = $node_id = array_pop($STACK);
			//put children
			$A = $CHILDREN[$node_id];
			for($i=count($A)-1;$i>=0;$i--) $STACK[] = $A[$i];
			//parse item
			$result .= $this->ParseOne();
		}
		
		//store result may be
		if($store_to) $tpl->Assign( $store_to, $result, $append );
		
		//free misc handlres
		$tpl->Free(array(
			$this->tpl_item,
		));
		
		return $result;
	}
	
} 
  
?>