<?php
  // прошиваем локали, чтобы у нас всё работало с case-sensitivity
  setlocale(LC_CTYPE, array("ru_RU.CP1251","ru_SU.CP1251","ru_RU.KOI8-r","ru_RU","russian","ru_SU","ru"));

  // 2kukutz@npj: напиши здесь, зачем нужна эта строка
  // lucky@npj: http://ru.php.net/manual/ru/function.setcookie.php#48838
  header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');

  // kuso@npj: спорная строка, но мне она нравится:
  error_reporting (E_ALL ^ E_NOTICE );
  
  ob_start("ob_gzhandler");

  require_once('libs/core.nop/classes/ConfigProcessor.php');
  require_once('libs/core.nop/classes/RequestHandler.php');
  require_once('web/classes/controllers/RedarmyRequestHandler.php');

  //site controller, builds site environment
  $rh =& new RedarmyRequestHandler('libs/core.nop/config.php');

  //handles the request
  echo $rh->Handle();

  //TODO: wtf
  $rh->End();

  ob_end_flush();
?>
