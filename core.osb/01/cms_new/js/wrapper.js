function WrapperSwitch(id,visible){
  document.getElementById('module_div_opened_'+id).className = visible ? 'visible' : 'invisible'; 
  document.getElementById('module_div_closed_'+id).className = !visible ? 'visible' : 'invisible'; 
  document.cookie = "c"+id+"=" + ( visible ? 0 : 1 );
}