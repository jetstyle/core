<?php

if($rh->page->config['meta_title'])
{
	echo $rh->page->config['meta_title'];
}
else
{
	if($rh->page->config['title_short'])
	{
		echo strip_tags($rh->page->config['title_short']).' &mdash; ';
	}
	echo Config::get('project_title');
}

?>