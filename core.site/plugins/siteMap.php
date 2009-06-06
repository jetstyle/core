<?php 
$base_url = RequestInfo::$baseUrl.Router::linkTo('Tpl').'/';

$siteMap = Locator::get('tpl')->getSiteMap();

if (is_array($siteMap) && !empty($siteMap))
{
	echo '<ul>';
	foreach( $siteMap as $key => $map ) 
	{
		if ($key)
		{  
			$href = $base_url.$key;
			$name = $siteMap[$key]['name'];
			echo ( '<li><a href="'.$href.'">'.$name.'</a></li>');
			if (is_array($map['views']))
			{
				echo '<ul>';
				foreach ($map['views'] AS $viewKey => $viewData)
				{
					if (is_numeric($viewKey))
					{
						$subkey = $viewData;
					}
					else
					{
						$subkey = $viewKey;
					}
				
					$href = $base_url.$key.'/'.$subkey;
					if (is_array($viewData) && $viewData['name'])
					{
						$name = $viewData['name'];
					}
					else
					{
						$name = $subkey;
					}
					
					echo ( '<li><a href="'.$href.'">'.$name.'</a></li>');
				}
				echo '</ul>';
			}
		}
	}
	echo '</ul>';
}
?>