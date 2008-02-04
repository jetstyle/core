<?php

$this->useClass('models/DBTreeModel');
class BasicContent extends DBTreeModel
{
	var $fields = array('id', 
		'mode',
		'title', 'title_pre', 
		/*
		'lead', 'lead_pre', 
		 */

		'text', 'text_pre', 
		'_path', '_parent', '_level', '_left', '_right');

	var $fields_info = array(
		array( 'name' => '_level',			 'source' => '_level',				'order' => 'DESC',),

		array( 'name' => 'title',			 'source' => 'title',				'lang' => NULL,),
		array( 'name' => 'title',			 'source' => 'eng_title',			'lang' => 'en',),

		array( 'name' => 'title_pre',		 'source' => 'title_pre',			'lang' => NULL,),
		array( 'name' => 'title_pre',		 'source' => 'eng_title_pre',		'lang' => 'en',),

		/*
		array( 'name' => 'lead',			 'source' => 'lead',					'lang' => NULL,),
		array( 'name' => 'lead',			 'source' => 'eng_lead',			'lang' => 'en',),

		array( 'name' => 'lead_pre',		 'source' => 'lead_pre',			'lang' => NULL,),
		array( 'name' => 'lead_pre',		 'source' => 'eng_lead_pre',		'lang' => 'en',),
		 */

		array( 'name' => 'text',			 'source' => 'text',					'lang' => NULL,),
		array( 'name' => 'text',			 'source' => 'eng_text',			'lang' => 'en',),

		array( 'name' => 'text_pre',		 'source' => 'text_pre',			'lang' => NULL,),
		array( 'name' => 'text_pre',		 'source' => 'eng_text_pre',		'lang' => 'en',),

	);
//	var $table = 'content';
	var $where = '_state = 0';
	var $order = array('_level'=>'DESC'/*,'id'=>'DESC'*/);

}


?>
