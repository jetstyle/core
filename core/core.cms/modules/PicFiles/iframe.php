<?php
$id = intval($this->rh->ri->get('id'));
if(!$id)
{
	if(!$_REQUEST['_new'])
	{
		$res = $this->rh->db->queryOne("
			SELECT id FROM ??picfiles_topics
			ORDER BY _order ASC
		");
		if($res['id'])
		{
			$this->rh->redirect($this->rh->ri->hrefPlus('', array('id' => $res['id'])));
		}
	}
	$this->class_name = 'Dummy';
}
else 
{
	$this->class_name = 'IFrame';
	$this->url = $this->rh->base_url.'do/'.$this->moduleName.'/pictures?topic_id='.$id.'&hide_toolbar=1';
}
?>