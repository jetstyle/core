<?
  $this->UseClass('Path',0);
  
class PathCHPU extends Path {
  
  var $NODE_TRAIL = array(); //������ ��������� �������� ��������� � �����
  var $path_to_node; //���-����� ����

  function Handle($path_str){
    $rh =& $this->rh;
    
    //������� ������ ����?
    if($rh->GLOBALS['page']) return;
    
    $this->path_orig = $path_str;
    $B = explode('/',rtrim($path_str,'/'));
    
    //��������� ��������?
    if($path_str=='') return;
    
    //��������� ��������
    //� ����� � � ���������
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
    
    //���� ���� ��������
    if( !$_found ){
      //�������� ��������� �������� ���������
/*** Following block removed by Geronimo (29-08-2005) (����� ��� ���� ��� ����?)
      //zharik: ���� ���� - ���������� � ������ ����
      while( (integer)$B[ count($B)-1 ] )
        array_unshift($this->NODE_TRAIL,array_pop($B));
***/
      //���������� ��� ����
      $rh->GLOBALS['page'] = 'content';
      $this->path_to_node = implode('/',$B);
    }
    
  }
}
  
?>