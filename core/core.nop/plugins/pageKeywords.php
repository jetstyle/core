<?php
if (!Locator::exists('controller')) return;
$controller = Locator::get('controller');
echo $controller['meta_keywords'];
?>