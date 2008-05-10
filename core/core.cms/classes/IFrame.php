<?php
	
class IFrame  
{	
	protected $rh; //������ �� $rh
	protected $config; //������ �� ������ ������ ModuleConfig
	
	//for rendering
	protected $template = "iframe.html";

	public function __construct( &$config ){
		//base modules binds
		$this->config =& $config;
		$this->rh =& $config->rh;
	}
	
	public function handle()
	{
		$this->rh->tpl->set( '__url', $this->config->url );
	}
	
	public function getHtml()
	{
		return $this->rh->tpl->parse( $this->template );
	}
}	
?>