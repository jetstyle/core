function WrapperSwitch(id,visible, subclass)
{
  document.getElementById('module_div_opened_'+id).className = visible  ? 'visible '+(subclass!="" ? subclass : "") : 'invisible'; 
  document.getElementById('module_div_closed_'+id).className = !visible ? 'visible '+(subclass!="" ? subclass : "") : 'invisible'; 

  document.cookie = "c"+id+"=" + ( visible ? 0 : 1 );
}