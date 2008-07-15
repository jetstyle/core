<?php
//@_/connect.html
/*

Connect
-------

Подключает в начало страницы линки на внешние ресурсы - js и css.

В результате работы получаем в тэге <HEAD> вхождения типа:
<script type="text/javascript" language="Javascript" >{{_}}</script>
<script type="text/javascript" language="Javascript" src="{{js}}{{_}}.js"></script>

Два вариант использования:

1. {{!connect news.css}} или {{!connect news.js}}
- запоминает, что данные файлы нужно прилинковать в <HEAD> страницы.

- ещё варианты:
{{!connect news.css path="custompath"}}
{{!connect news.css lib="wikiedit"}}

2. {{!connect compile=css}} или {{!connect compile=js}}
- генерирует набор соответствующий линков для <HEAD>, при этом избегает дублирующих вхождений.

-------

$params:
0 - имя файла для прилинковки, файл предполагается лежащим в {{js}} или {{css}}
"compile" - флаг компиляции

Хранит данные в $tpl:
$tpl->CONNECT = array(
"js"=>array("",...),
"css"=>array("",...),
);

*/

$str = "";

$compile = isset($params["compile"]) ? $params["compile"] : false;

if ( $compile )
{
	//компилируем накопленное
	if ( isset($tpl->CONNECT[$compile]) && is_array($tpl->CONNECT[$compile]) )
	{
		$template = "_/connect.html:".$compile;

		if ( ("js" == $compile && $rh->compress_js) || ("css" == $compile && $rh->compress_css))
		{
			$compressedName = '';
			foreach ($tpl->CONNECT[$compile] AS $fileName)
			{
				if (file_exists($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$fileName.'.'.$compile))
				{
					$compressedName .= '|'.filemtime($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$fileName.'.'.$compile).'|'.$fileName.'|';
				}
			}
			$compressedName = md5($compressedName);
			
			if (!file_exists($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/'.$compressedName.'.'.$compile))
			{
				$result = '';
				
				foreach ($tpl->CONNECT[$compile] AS $filename)
				{
					if (file_exists($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$filename.'.'.$compile))
					{
						$result .= file_get_contents($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$filename.'.'.$compile);
					}
				}
				
				if ('js' == $compile)
				{
					$result = JSMin::minify($result);
				}
				elseif ('css' == $compile)
				{
					$result = CSSMin::minify($result);
				}
								
				if (!is_dir($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile))
				{
					mkdir($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile, 0775, true);
					$htaccess = "RewriteEngine on\r\n";
					if ('js' == $compile)
					{
						$htaccess .= "AddType application/x-javascript .gz\r\n";
						$htaccess .= "AddType application/x-javascript .js\r\n";
					}
					elseif ('css' == $compile)
					{
						$htaccess .= "AddType text/css .gz\r\n";
						$htaccess .= "AddType text/css .css\r\n";
					}
					$htaccess .= "RewriteRule ^(.*\.gz)$ $1 [L]\r\n";
					$htaccess .= "RewriteCond %{HTTP:Accept-Encoding} gzip\r\n";
					$htaccess .= "RewriteRule ^(.*\.".$compile.")$ $1.gz\r\n";
					$htaccess .= "AddEncoding gzip .gz\r\n";
					$htaccess .= "Header set ExpiresActive On\r\n";
					$htaccess .= "Header set ExpiresDefault \"access plus 10 years\"\r\n";
					file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/.htaccess', $htaccess);
				}
				
				file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/'.$compressedName.'.'.$compile, $result);
				file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/'.$compressedName.'.'.$compile.'.gz', gzencode($result, 9));
			}
			
			$fname = array('path' => $rh->base_url.'cache/'.$rh->app_name.'/'.$compile, 'file' => $compressedName);
			$tpl->set("*",$fname);
			$str = $tpl->parse($template."_path");
		}
		else
		{
			foreach( $tpl->CONNECT[$compile] as $fname )
			{
				if (!is_array( $fname )) // просто файл в текущей шкуре
				{
					$tpl->set("_",$fname);
					$str .= $tpl->parse($template);
				}
				else // файл с произвольным путём
				{
					$tpl->set("*",$fname);
					$str .= $tpl->parse($template."_path");
				}
			}
		}
	}
	echo $str;
}
else
{
	//накапливаем для компиляции
	$A = explode(".",$params[0]);
	$ext = array_pop($A);
	$fname = implode(".",$A);

	if( !isset($tpl->CONNECT[$ext]) || !is_array($tpl->CONNECT[$ext]) || !in_array($fname,$tpl->CONNECT[$ext]) )
	{
		if (isset($params["lib"])) // если файл находится в либе
		{
			$params["path"] = $rh->lib_href_part."/".$params["lib"];
		}

		if (!isset($params["path"])) // просто файл в текущей шкуре
		{
			$tpl->CONNECT[$ext][] = $fname;
		}
		else // файл с произвольным путём
		{
			$tpl->CONNECT[$ext][] = array( "file" => $fname, "path" => rtrim($ri->Href($params["path"]),"/") );
		}
	}
}
?>