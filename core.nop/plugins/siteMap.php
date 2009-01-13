<?php 
$base_url = RequestInfo::$baseUrl.'tpl/';

$siteMap = Locator::get('tpl')->getSiteMap();

if (is_array($siteMap) && !empty($siteMap))
{
	echo '<ul>';
	foreach( $siteMap as $key => $map ) if ($key){  
	  $href = $base_url.$key;
	  echo ( '<li><a href="'.$href.'">'.$siteMap[$key]['name'].'</a></li>');
	}
	echo '</ul>';
}
?>