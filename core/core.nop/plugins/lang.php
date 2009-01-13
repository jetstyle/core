<?php

/**
 * Выводит язык сайта
 *
 * en, ru и т.п.
 */

echo isset($tpl->rh->lang) ? $tpl->rh->lang : $tpl->rh->msg_default;

?>
