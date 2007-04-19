<?
/*
    ListObject( &$rh, &$DATA, $cache_id="", $cache_class="" ) -- ������� ����� ������ �� �������
      - $data     -- ������ ������ ��� sql-������, �� ������� ���������� ���������
      - $cache_id -- ������������� �����������, ��� ���������� �� ����, 
                      � ��� �� ��������� ���� ���������� � ������ ����� $DATA - sql-������
      - $cache_class -- ����������� ��� �����������, ���� �� ������������, �� ������ get_class($this)
  
  ---------
  * &Parse( $tpl_root, $store_to, $append ) -- ��������� �� ��������� ��������
      - $tpl_root    -- �������� �������� ������, ����� ��� "file.html:List"
      - $store_to    -- ���� �����������, �� ��������� ����� ����������� � ���������� ������ � ����� ������
                        ���� === true, �� ���������� ��������� ����� ����, ��� ��� ����� ������
      - $append      -- ���� �������� $store_to, �� ��������� �� ������� �������� ����������, � ������������ � �����
  
  * ParseOne() -- ������ ������� ������� � ������, ����������� � ������ ������ ���������
  
  // ����������
  
  * $loop_index -- ������ ������� ������� � ������
  * $loop_start -- ������, ������� � �������� ����� �������
  * $loop_step -- ��������� ������� ��� �������� ������
  * $loop_max -- ������������ ����� �������� ��� ��������
  * $loop_total -- ����� ����� ��������� � ������, ������� ������
  
  * $this->implode -- � ����� ������ ������ ���������� ��� *_Item  *_Separator  *_Item  *_Separator  *_Item
  * $issel_function; -- ��� �������, ������� ���������, � ����� �������� ��������� ������ �������
                    ��������� ��� � ���������, ���������� ������� � �������� $tpl_item;
                    ����� �������� ��� array( ������, ����� )
  
  * $tpl_root -- �������� ������ ������
  * $tpl_empty - ������ ������� ������
  * $tpl_item -- ������ �������� ������
  * $tpl_separator -- ������ ����������� ����� ����������
  
  * $ASSIGN_FIELDS -- ������ ����� �������� ������, ������� ����������� ����� $tpl->Assign, ���� ���� ����������� ���
  * $EVOLUTORS -- ��� ���������� [����=>��� �������] ��� [����=>array(������,�����)],
                    �������/����� ��������� ���� �������� - ������ �� $this, ���������� ������,
                    ��������� ������ �������/������ ������������� � ��������� ���������� '_'.����
  
=============================================================== v.2 (Zharik)
*/
  
  $this->UseClass('Obj');
  
class ListObject extends Obj {
  
  var $rh;
  var $DATA = array();
  
  var $cache_class;
  var $cache_id;
  
  var $loop_index = 0;
  var $loop_start = 0;
  var $loop_step = 1;
  var $loop_max = 1000;
  var $loop_total = 0;
  
  var $implode = false;
  var $issel_function;
  
  var $tpl_root;
  var $tpl_empty;
  var $tpl_item;
  var $tpl_separator;
  
  var $ASSIGN_FIELDS = array();
  var $EVOLUTORS = array();
  
  function ListObject( &$rh, &$DATA, $cache_id="", $cache_class="" ){
    $this->rh = $rh;
    
    //try from cache
    if( $cache_id ){
      $this->cache_id = $cache_id;
      $this->cache_class = ($cache_class)? $cache_class : get_class($this) ;
      $this->DATA =& $this->rh->Restore( $this->cache_class, $this->cache_id );
    }else{
      
      //try from DB
      if( is_string($DATA) ){
        $this->rh->db->execute( $DATA );
        $this->DATA =& $this->rh->db->GetArray();
        if( $this->cache_id ) $this->rh->cache->Store( $this->cache_class, $this->cache_id );
      }else 
        //direct reference
        $this->DATA =& $DATA;
    }
    
    //bind data
    if( !is_array($this->DATA) )
      $this->rh->debug->Error("List: \$DATA provided is not an array");
    
  }
  
  function &Parse( $tpl_root, $store_to=false, $append=false ){
    $tpl =& $this->rh->tpl;
    
    //tpl vars
    $this->tpl_root       = $tpl_root;
    $this->tpl_empty      = $this->tpl_root."_Empty";
    $this->tpl_item       = $this->tpl_root."_Item";
    $this->tpl_separator  = $this->tpl_root."_Separator";
    
    //resolve handler
    //���, ��� ������ $store_to ������ ���������� ���������
    /*if( $store_to=="" ){
      $A = explode(":",$this->tpl_root);
      $store_to = $A[ count($A)-1 ]."_Item";
    }*/
    
    //empty case
    if ( count($this->DATA) == 0 ) 
      return $tpl->Parse( $this->tpl_empty, $store_to, $append );
    
    //test _Separatore somehow
    //$this->implode = ??
    
    //loop
    $this->loop_total = count($this->DATA);
    if($this->implode){
      
      //implode mode
      for(
        $this->loop_index = $this->loop_start;
        $this->loop_index < $this->loop_total && $this->loop_index < $this->loop_max;
        $this->loop_index += $this->loop_step
      )
        $DATA[] = $this->ParseOne();
      $result = implode( $tpl->Parse( $this->tpl_separator ), $DATA );
      
    }else{
      //simple mode
      for(
        $this->loop_index = $this->loop_start;
        $this->loop_index < $this->loop_total && $this->loop_index < $this->loop_max;
        $this->loop_index += $this->loop_step
      ){
          $result .= $this->ParseOne();
      }
    }
    
    //store result may be
    if($store_to) $tpl->Assign( $store_to, $result, $append );
    
    //free misc handlres
    $tpl->Free(array(
      $this->tpl_empty,
      $this->tpl_item,
      $this->tpl_separator,
    ));
    
    return $result;
  }
  
  function ParseOne(){
    $tpl =& $this->rh->tpl;
    
    //assign misc values
    $tpl->Assign( "_index", $this->loop_index );
    
    //get current item (array)
    $ITEM =& $this->DATA[ $this->loop_index ];
    
    //resolve $FIELDS array
    if( count($this->ASSIGN_FIELDS)==0 )
      $FIELDS = array_keys($ITEM);
    else
      $FIELDS =& $this->ASSIGN_FIELDS;
    
    //assign from $ITEM
    $N = count($FIELDS);
    for( $i=0; $i<$N; $i++ )
      $tpl->Assign( "_".$FIELDS[$i], $ITEM[ $FIELDS[$i] ] );
    
    //assign evolutors
    foreach($this->EVOLUTORS as $field=>$func){
      $handler = '_'.$field;
      if( is_array($func) ){
        //object and method supported
        $_func = $func[1];
        $tpl->Assign( $handler, $func[0]->$_func($this) );
      }else{
        if( $tpl->CheckAction($func) )
          //tpl-action
          $tpl->Assign( $handler, $tpl->Action($func) );
        else
          //php-function
          $tpl->Assign( $handler, $func($this) );
      }
    }
    
    //������� ������ � ����?
    if( !($_suffix = $ITEM["_suffix"] ) ){
      //���������?
      if( !($_suffix = $this->_do($this->isfreezed_function)) )
        //�������?
        $_suffix = $this->_do($this->issel_function);
    }
    
    //������-��������
    $tpl->assign( '__even', $this->loop_index%2 ? '1' : '0' );
    
    return $tpl->Parse( $this->tpl_item.$_suffix );
  }
  
} 
  
?>