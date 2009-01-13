<?php
/**
 *  Link
 *
 */

Finder::useClass("controllers/Controller");
class LinkController extends Controller
{
	function handle()
	{
		Controller::redirect($this->data['link']);
	}
}
?>