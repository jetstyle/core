<?php

interface PagerInterface
{
	public function getPages();
	public function setup($currentPage = 1, $total = 0, $perPage = 0, $frameSize = 0);
	public function getLimit();
	public function getOffset();	
}

?>