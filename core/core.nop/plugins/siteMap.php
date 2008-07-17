<?php 
$base_url =$rh->base_url.'tpl/';

echo '<ul>';
foreach( $rh->site_map as $key => $map ) if ($key){  
  $href = $base_url.$key;
  echo ( '<li><a href="'.$href.'">'.$rh->site_map[$key]['name'].'</a></li>');
}
echo '</ul>';

#echo ( '<small><a href="'.$tpl->rh->base_url.'tests/templates_test/'.'">Проверить все шаблоны на ошибки парсинга</a></small>');

?>
