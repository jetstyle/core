<?php
if (!Locator::exists('controller')) return;

$controller = Locator::get('controller');
if($controller['meta_title'])
{
	echo $controller['meta_title'];
}
else
{
	if($controller['title_short'])
	{
		echo strip_tags($controller['title_short']).' &mdash; ';
	}
	echo Config::get('project_title');
}
?>