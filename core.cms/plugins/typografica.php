<?php
$typo = &Locator::get('typografica');

$typo->settings["dashglue"] = false;
$typo->settings["dashwbr"] = true;

return $typo->correct( $params, false );
?>