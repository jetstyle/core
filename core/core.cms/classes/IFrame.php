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
		$tpl->Parse( $this->template, $this->store_to, true );
		
	}
}	
?>