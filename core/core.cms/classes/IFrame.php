<?php
	
class IFrame  
{	
	var $rh; //ссылка на $rh
	var $config; //ссылка на объект класса ModuleConfig
	
	//for rendering
	var $template = "iframe.html";
	var $store_to = "";
	
	function IFrame( &$config ){
		//base modules binds
		$this->config =& $config;
		$this->rh =& $config->rh;
		//куда класть?
		$this->store_to = "iframe_".$config->module_name;
	}
	
	function Handle(){
		$tpl =& $this->rh->tpl;
		$tpl->set( '__url', $this->config->url );
	
		$tpl = & $this->rh->tpl;

        $wid = $_GET['id'];
		$vis = isset ($_COOKIE["cf" . $wid]) ? ($_COOKIE["cf" . $wid]==="true") : !$this->config->closed_iframe;

		$tpl->set('_id', $wid);
		$tpl->set('_class_name_1', ( $vis === true) ? "visible" : "invisible");
		$tpl->set('_class_name_2', ( $vis === false) ? "visible" : "invisible");

//        var_dump($vis, $tpl->get('_class_name_1'), $tpl->get('_class_name_2'));
		$tpl->set('prefix', $this->prefix);
		$tpl->Parse( $this->template, $this->store_to, true );	

	}
}	
?>
