<?php
	
class IFrame  
{	
	protected $config; //ссылка на объект класса ModuleConfig
	
	//for rendering
	protected $template = "iframe.html";

	public function __construct( &$config )
	{
		$this->config =& $config;
	}
	
	public function handle()
	{
		TemplateEngine::getInstance()->set( '__url', $this->config->url );
	}
	
	public function getHtml()
	{
		return TemplateEngine::getInstance()->parse( $this->template );
	}
}	
?>