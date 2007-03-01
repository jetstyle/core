<?
/*
  Класс обёртки для нескольких модулей. 
  Может включать в себя другие классы обёртки... Дух захватывает 8))
*/
  
class Wrapper {
  
  //templates
  var $template; //возьмём из конфига
  var $store_to;
  var $store_to_tree = "___tree"; //куда класть вывод дерева
  var $store_to_form = "___form"; //куда класть вывод формы
  
  var $wrapped_count = 0; //число дочерних модулей
  var $CONFIGS = array(); //массив конфигов дочерних модулей
  var $MODULES = array(); //массив объектов дочерних модулей
  
  function Wrapper( &$config ){
    //base modules binds
    $this->config =& $config;
    $this->rh =& $config->rh;
    //sublings
    $this->wrapped_count = $n = count($config->WRAPPED);
    for($i=0;$i<$n;$i++){
      $this->CONFIGS[$i] = $config;
      $this->CONFIGS[$i]->Read( $this->config->WRAPPED[$i] );
      $this->MODULES[$i] =& $this->CONFIGS[$i]->InitModule();
    }
    //templates
    $this->template = $config->template;
    $this->store_to = $config->GetPassed()."_".$config->module_name;
  }
  
  function Handle(){
    $config =& $this->config;
    $tpl =& $this->rh->tpl;
    //перенаправляем вывод агрегированных объектов
    //и вообще адаптируем модули к аггрегированному состоянию
    $_mode = $this->config->GetPassed();
    for($i=$this->wrapped_count-1;$i>=0;$i--){
      $module =& $this->MODULES[$i];
//      $module->store_to = $_mode."_".$module->config->GetPassed();
      $module->store_to = '__wrapped_'.$i;//'__'.$module->config->GetPassed();
      $module->prefix = $config->module_name.$module->store_to.'_';
      //?? перенести эту логику в сами модули?
      //?? да и вообще, логика несколько не ясна...
//      $module->_href_template = ( $this->_href_template )? $this->_href_template : $this->rh->path_rel."do/".$this->config->module_name.'/'.$_mode.'?';
      $module->Handle();
      $tpl->assign( 'wid_'.$i, $this->config->module_name.'_'.$i );
      $tpl->assign( '_wtitle_'.$i, $config->module_title.": ".$this->rh->MODES_RUS[$module->config->GetPassed()] );
    }
    //общий вывод
    $this->rh->tpl->Parse( $this->template, $this->store_to, true );
    //чистим временные шаблонные переменные за собой
    for($i=$this->wrapped_count-1;$i>=0;$i--){
      $tpl_var = '__wrapped_'.$i;
      if( $this->store_to!=$tpl_var )
        $tpl->free( $tpl_var );
    }
  }
  
} 
?>