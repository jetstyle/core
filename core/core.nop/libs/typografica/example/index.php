<?php echo "<";?>?xml version="1" encoding="windows-1251"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
  <head>
    <title>Typo/para-grafica example</title>
    <style>
      textarea { display:block; width:100% }
      label    { cursor:pointer; cursor:hand; }
      .result  { margin: 20px 0; padding:10px; background:#eeeeee }
      p.auto { margin:2px; padding:2px; border:1px dashed #888888 }
    </style>
  </head>
  <body onload=''>

    <h1>Typo[para]grafica example</h1>

    <form action="?" method="post">

      <textarea cols="60" rows="10" name="data"><?php

      _Quotes( &$_POST );

      $data = '<h1>Header</h1>Как вам "это" -- нравится?<br/><br />'.
              'Я думаю, что не всё, что клеится -- "хорошо". But "people" said: I am good.<br/><br />'.
              '(c)2004 JetStyle, Pixel-Apes';

      if ($_POST["data"]) echo htmlspecialchars($_POST["data"]);
      else 
      echo $data;
      ?></textarea>
      <div>
      <input type="checkbox" <?php echo $_POST["typo"]?'checked="checked"':"";?> id="chk_typo" name="typo" /><label for="chk_typo"><b>typografica</b>: Типографические замены, кавычки и тире</label>
      <div>
      </div>
      <input type="checkbox" <?php echo $_POST["para"]?'checked="checked"':"";?> id="chk_para" name="para" /><label for="chk_para"><b>paragrafica</b>: Обёртка текста в параграфы (&lt;p> вместо переводов строк)</label>
      </div>
      <br />

      <input type="submit" value="Proceed formatting &raquo;" />

    </form>

    <?php
      if ($_POST["data"])
      {
        include ("../classes/typografica.php");
        include ("../classes/paragrafica.php");
        $dummy = new Dummy();
        $typo = new typografica( &$dummy );
        $para = new paragrafica( &$dummy );

        $what = $_POST["data"];

        if ($_POST["typo"])
         $what = $typo->correct( $what );
        if ($_POST["para"])
         $what = $para->correct( $what );


        ?><h3>Result</h3><div class="result"><?php
        echo $what;
        ?></div><?php

      }
    ?>
    <div>
      &copy;2004 <a href="http://pixel-apes.com/typografica">Pixel-Apes</a>
    </div>
 </body>

</html>
<?php

  class Dummy {}

  function _Quotes(&$a)
  {
   if(is_array($a))
    foreach($a as $k => $v)
     if(is_array($v)) _Quotes($a[$k]);
                 else $a[$k] = stripslashes($v);
  }

?>