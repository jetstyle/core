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
		
		if (("js" == $compile && Config::get('compress_js')) || ("css" == $compile && Config::get('compress_css')))
		{
			$compressedName = '';
			foreach ($tpl->CONNECT[$compile] AS $fileName)
			{
				if (file_exists($tpl->getSkinDir().'/'.$compile.'/'.$fileName.'.'.$compile))
				{
					$compressedName .= '|'.filemtime($tpl->getSkinDir().'/'.$compile.'/'.$fileName.'.'.$compile).'|'.$fileName.'|';
				}
			}
			$compressedName = md5($compressedName);
			
			if (!file_exists(Config::get('cache_dir').'/'.$compile.'/'.$compressedName.'.'.$compile))
			{
				$result = '';
				$skinDir = $tpl->getSkinDir();
				foreach ($tpl->CONNECT[$compile] AS $filename)
				{
					if (file_exists($skinDir.'/'.$compile.'/'.$filename.'.'.$compile))
					{
						$result .= file_get_contents($skinDir.'/'.$compile.'/'.$filename.'.'.$compile);
					}
				}
				
				if ('js' == $compile)
				{
					Finder::useClass('JSMin');
					$result = JSMin::minify($result);
				}
				elseif ('css' == $compile)
				{
					Finder::useClass('CSSMin');
					$result = CSSMin::minify($result);
				}
								
				if (!is_dir(Config::get('cache_dir').'/'.$compile))
				{
					mkdir(Config::get('cache_dir').'/'.$compile, 0775, true);
					$htaccess = "RewriteEngine on\r\nRewriteBase ".RequestInfo::$baseUrl.'cache/'.Config::get('app_name').'/'.$compile."\r\n";
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
					$htaccess .= "RewriteCond %{HTTP_USER_AGENT} !Safari\r\n";
					$htaccess .= "RewriteCond %{HTTP_USER_AGENT} !Konqueror\r\n";
					$htaccess .= "RewriteRule ^(.*\.".$compile.")$ $1.gz\r\n";
					$htaccess .= "AddEncoding gzip .gz\r\n";
					$htaccess .= "Header set ExpiresActive On\r\n";
					$htaccess .= "Header set ExpiresDefault \"access plus 10 years\"\r\n";
					file_put_contents(Config::get('cache_dir').'/'.$compile.'/.htaccess', $htaccess);
				}
				
				file_put_contents(Config::get('cache_dir').'/'.$compile.'/'.$compressedName.'.'.$compile, $result);
				file_put_contents(Config::get('cache_dir').'/'.$compile.'/'.$compressedName.'.'.$compile.'.gz', gzencode($result, 9));
			}
			
			$fname = array('path' => RequestInfo::$baseUrl.'cache/'.Config::get('app_name').'/'.$compile, 'file' => $compressedName);
			$tpl->set("*",$fname);
			$str = $tpl->parse($template."_path");
		}
		else
		{
			$projDir = Config::get('project_dir');
			$projDir = rtrim($projDir, '/\\');
			
      foreach( $tpl->CONNECT[$compile] as $fname )
			{
				$tplAdd = '';
				
		    $_fname = Finder::findScript($compile, $fname, 0, 1, $compile);
		    		    
		    if ($_fname)
		    {
		    	$_fname = str_replace($projDir, '', $_fname );
		    	$_fname = ltrim($_fname, '/\\');
		    	$_fname = (Config::exists('front_end_path') ? Config::get('front_end_path') : Config::get('base_url')).$_fname;
		    	$fname = $_fname;
		    	$tplAdd = '_path';
		    }
				
				$tpl->set("_", $fname);
				$str .= $tpl->parse($template.$tplAdd);
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
	
	if (!is_array($tpl->CONNECT))
	{
		$tpl->CONNECT = array();
	}
	
	if (!is_array($tpl->CONNECT[$ext]))
	{
		$tpl->CONNECT[$ext] = array();
	}
	
	if( !in_array($fname, $tpl->CONNECT[$ext]) )
	{
		if ($params['unshift'])
		{
			array_unshift($tpl->CONNECT[$ext], $fname);
		}
		else
		{
			$tpl->CONNECT[$ext][] = $fname;
		}
	}
	elseif ($params['unshift'])
	{
		$key = array_search($fname, $tpl->CONNECT[$ext]);
		if ($key)
		{
			unset($tpl->CONNECT[$ext][$key]);
			array_unshift($tpl->CONNECT[$ext], $fname);
		}
	}
}
?>