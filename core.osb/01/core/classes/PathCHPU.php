<?
  $this->UseClass('Path',0);
  
class PathCHPU extends Path {
  
  var $NODE_TRAIL = array(); //массив последних числовых вхождений в адрес
  var $path_to_node; //ЧПУ-адрес узла

  function Handle($path_str){
    $rh =& $this->rh;
    
    //хэндлер указан явно?
    if($rh->GLOBALS['page']) return;
    
    $this->path_orig = $path_str;
    $B = explode('/',rtrim($path_str,'/'));
    
    //стартовая страница?
    if($path_str=='') return;
    
    //проверяем хэндлеры
    //в корне и в подпапках
/*
 * This portion of code:
 *
    $_found = $this->_CheckFName($B[0],$path_str);
    if(!$_found)
      $_found = $this->_CheckFName($B[0].'/'.$B[1],$path_str);
 *
 * has been replaced on that code by Geronimo (01-06-2005):
 */
    if (!in_array('..', $B))
    {
      for ($i = count($B); $i > 0; $i--)
      {
        if (!is_numeric($B[$i-1]) && $B[$i-1]{0} != '_' && ($_found = $this->_CheckFName(implode('/', array_slice($B, 0, $i)), $path_str)))
          break;
      }
    }
/* That code above recursive scans handler's directory (from deep to root)
 * for appropriate handler (appropriate handler name should not be a number)
 */
    
    //ищем узел контента
    if( !$_found ){
      //отрезаем последние числовые вхождения
/*** Following block removed by Geronimo (29-08-2005) (нахуя это ваще тут было?)
      //zharik: кому надо - переносите в классы выше
      while( (integer)$B[ count($B)-1 ] )
        array_unshift($this->NODE_TRAIL,array_pop($B));
***/
      //запоминаем имя узла
      $rh->GLOBALS['page'] = 'content';
      $this->path_to_node = implode('/',$B);
    }
    
  }
}
  
?>