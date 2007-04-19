<?
/*
    ListObject( &$rh, &$DATA, $cache_id="", $cache_class="" ) -- базовый класс работы со списком
      - $data     -- массив списка или sql-строка, по которой получается рекордсет
      - $cache_id -- идентификатор кэширования, для извлечения из кэша, 
                      а так же помещения туда результата в случае когда $DATA - sql-строка
      - $cache_class -- псевдокласс для кэширования, если не предоставлен, то берётся get_class($this)
  
  ---------
  * &Parse( $tpl_root, $store_to, $append ) -- отпарсить по коллекции шаблонок
      - $tpl_root    -- корневая шаблонка списка, задаём как "file.html:List"
      - $store_to    -- если установлено, то результат также сохраняется в переменную домена с таким именем
                        если === true, то записывает результат прямо туда, где был задан шаблон
      - $append      -- если непустое $store_to, то результат не стирает значение переменной, а дописывается в конец
  
  * ParseOne() -- парсит текущую позицию в списке, перегружать в случае особых изращений
  
  // переменные
  
  * $loop_index -- индекс текущей позиции в списке
  * $loop_start -- индекс, начиная с которого будем парсить
  * $loop_step -- инкремент индекса при переборе списка
  * $loop_max -- максимальное число итераций при парсинге
  * $loop_total -- общее число элементов в списке, который парсим
  
  * $this->implode -- в таком случае список собирается как *_Item  *_Separator  *_Item  *_Separator  *_Item
  * $issel_function; -- имя функции, которая проверяет, с каким шаблоном рендерить данную позицию
                    параметры как у эволютора, возвращает суффикс к значению $tpl_item;
                    можно задавать как array( объект, метод )
  
  * $tpl_root -- корневой шаблон списка
  * $tpl_empty - шаблон пустого списка
  * $tpl_item -- шаблон элемента списка
  * $tpl_separator -- шаблон разделителя между элементами
  
  * $ASSIGN_FIELDS -- массив полей элемента списка, которые прогоняются через $tpl->Assign, если пуст прогоняются все
  * $EVOLUTORS -- хэш эволюторов [ключ=>имя функции] или [ключ=>array(объект,метод)],
                    функция/метод принимают один аргумент - ссылка на $this, возвращают строку,
                    результат работа функции/метода привязывается к шаблонной переменной '_'.ключ
  
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
    //нет, при пустом $store_to просто возвращать результат
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
    
    //суффикс указан в поле?
    if( !($_suffix = $ITEM["_suffix"] ) ){
      //заморожен?
      if( !($_suffix = $this->_do($this->isfreezed_function)) )
        //текущий?
        $_suffix = $this->_do($this->issel_function);
    }
    
    //чётный-нечётный
    $tpl->assign( '__even', $this->loop_index%2 ? '1' : '0' );
    
    return $tpl->Parse( $this->tpl_item.$_suffix );
  }
  
} 
  
?>