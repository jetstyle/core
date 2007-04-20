<?
    
    if ($this->rh->getVar('id'))
    {
	$this->class_name = 'IFrame';
	$this->url = $this->rh->url.'do/'.$this->module_name.'/pictures?topic_id='.$this->rh->GetVar('id','integer').'&hide_toolbar=1';
	}
    else
        $this->class_name="Dummy";
?>