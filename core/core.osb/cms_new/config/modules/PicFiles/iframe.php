<?
if(!$this->rh->GetVar('id','integer'))
{
	if(!$this->rh->getVar('_new'))
	{
		$res = $this->rh->db->queryOne("
			SELECT id FROM ".$this->rh->project_name."_picfiles_topics
			ORDER BY _order ASC
		");
		if($res['id'])
		{
			$this->rh->redirect($this->rh->url.'do/'.$this->module_name.'?id='.$res['id']);
			die();
		}
	}
	$this->class_name = 'Dummy';
}
else 
{
	$this->class_name = 'IFrame';
	$this->url = $this->rh->url.'do/'.$this->module_name.'/pictures?topic_id='.$this->rh->GetVar('id','integer').'&hide_toolbar=1';
}

?>