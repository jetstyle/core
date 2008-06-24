<?php
#$base_url =$tpl->rh->base_url.'tests/templates/'; 
$base_url =$tpl->rh->base_url;

echo '<ul>';
foreach( $tpl->rh->site_map as $key => $map ) if ($key){  
  $href = $base_url.$key;
  echo ( '<li><a href="/tpl'.$href.'">'.$tpl->rh->site_map[$key]['name'].'</a></li>');
}
echo '</ul>';

#echo ( '<small><a href="'.$tpl->rh->base_url.'tests/templates_test/'.'">Проверить все шаблоны на ошибки парсинга</a></small>');

?>
