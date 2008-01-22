<?
	//базовый стартап
    
	include($this->FindScript('scripts','startup', 1));

	//корзина
	$this->UseClass('Trash');
	$this->trash =& new Trash($this);
			
	$res = $this->db->query('SELECT name, value FROM '.$this->project_name.'_config WHERE name IN("project_title", "admin_email")');
	if(is_array($res) && !empty($res))
	{
		foreach($res AS $r)
		{
			config_replace($this, $r['name'],  $r['value']);
		}
		$this->front_end->project_title = $this->project_title;
		$this->project_title = $this->project_title . ': CMS';
	}
		
  //шаблонные переменные для вставки htmlarea
	$this->tpl->assign('fe_/',$this->front_end->path_rel);
	$this->tpl->assign('fe_images',$this->front_end->path_rel.$this->project_name.'/'.$this->front_end->skin.'/images/');
	$this->tpl->assign('fe_css',$this->front_end->path_rel.$this->project_name.'/css/');
	$this->tpl->assign('fe_js',$this->front_end->path_rel.$this->project_name.'/js/');
	
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
