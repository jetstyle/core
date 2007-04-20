<?
	//базовый стартап
    
	include($this->FindScript('scripts','startup', 1));

	//корзина
	$this->UseClass('Trash');
	$this->trash =& new Trash($this);
	
  //шаблонные переменные для вставки htmlarea
	$this->tpl->assign('fe_/',$this->front_end->path_rel);
	$this->tpl->assign('fe_images',$this->front_end->path_rel.$this->front_end->skin.'/images/');
	$this->tpl->assign('fe_css',$this->front_end->path_rel.'css/');
	$this->tpl->assign('fe_js',$this->front_end->path_rel.'js/');
	
	//логирование
	if( $this->trace_logs )
    {
		$this->UseClass('Logs');
		$this->logs =& new Logs($this);
	}else{
		$this->UseClass('LogsDummy');
		$this->logs =& new LogsDummy($this);
	}
   
?>
