<?php
if (!Locator::exists('controller')) return;

$controller = Locator::get('controller');
if($controller['meta_title'])
{
	echo strip_tags($controller['meta_title']);
}
else
{
	echo strip_tags($controller['title']);
	//echo Config::get('project_title');
}
?>