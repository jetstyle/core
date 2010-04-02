<?php

interface ModuleInterface
{
	public function handle();
	public function insert( $postData=array() );
	public function update();
        public function load();
        public function getHtml();
}

?>