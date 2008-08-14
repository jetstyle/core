<?php
//@_/connect.html
/*

Connect
-------

���������� � ������ �������� ����� �� ������� ������� - js � css.

� ���������� ������ �������� � ���� <HEAD> ��������� ����:
<script type="text/javascript" language="Javascript" >{{_}}</script>
<script type="text/javascript" language="Javascript" src="{{js}}{{_}}.js"></script>

��� ������� �������������:

1. {{!connect news.css}} ��� {{!connect news.js}}
- ����������, ��� ������ ����� ����� ������������ � <HEAD> ��������.

- ��� ��������:
{{!connect news.css path="custompath"}}
{{!connect news.css lib="wikiedit"}}

2. {{!connect compile=css}} ��� {{!connect compile=js}}
- ���������� ����� ��������������� ������ ��� <HEAD>, ��� ���� �������� ����������� ���������.

-------

$params:
0 - ��� ����� ��� �����������, ���� �������������� ������� � {{js}} ��� {{css}}
"compile" - ���� ����������

������ ������ � $tpl:
$tpl->CONNECT = array(
"js"=>array("",...),
"css"=>array("",...),
);

*/

$str = "";

$compile = isset($params["compile"]) ? $params["compile"] : false;

if ( $compile )
{
	//����������� �����������
	if ( isset($tpl->CONNECT[$compile]) && is_array($tpl->CONNECT[$compile]) )
	{
		$template = "_/connect.html:".$compile;

		if (1 == 2 AND ("js" == $compile && Config::get('compress_js')) || ("css" == $compile && Config::get('compress_css')))
		{
//			$compressedName = '';
//			foreach ($tpl->CONNECT[$compile] AS $fileName)
//			{
//				if (file_exists($tpl->getSkinDir().'/'.$compile.'/'.$fileName.'.'.$compile))
//				{
//					$compressedName .= '|'.filemtime($tpl->getSkinDir().'/'.$compile.'/'.$fileName.'.'.$compile).'|'.$fileName.'|';
//				}
//			}
//			$compressedName = md5($compressedName);
//			
//			if (!file_exists(Config::get('cache_dir').'/'.$compile.'/'.$compressedName.'.'.$compile))
//			{
//				$result = '';
//				
//				foreach ($tpl->CONNECT[$compile] AS $filename)
//				{
//					if (file_exists($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$filename.'.'.$compile))
//					{
//						$result .= file_get_contents($rh->app_dir.'skins/'.$rh->tpl_skin.'/'.$compile.'/'.$filename.'.'.$compile);
//					}
//				}
//				
//				if ('js' == $compile)
//				{
//					$result = JSMin::minify($result);
//				}
//				elseif ('css' == $compile)
//				{
//					$result = CSSMin::minify($result);
//				}
//								
//				if (!is_dir($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile))
//				{
//					mkdir($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile, 0775, true);
//					$htaccess = "RewriteEngine on\r\n";
//					if ('js' == $compile)
//					{
//						$htaccess .= "AddType application/x-javascript .gz\r\n";
//						$htaccess .= "AddType application/x-javascript .js\r\n";
//					}
//					elseif ('css' == $compile)
//					{
//						$htaccess .= "AddType text/css .gz\r\n";
//						$htaccess .= "AddType text/css .css\r\n";
//					}
//					$htaccess .= "RewriteRule ^(.*\.gz)$ $1 [L]\r\n";
//					$htaccess .= "RewriteCond %{HTTP:Accept-Encoding} gzip\r\n";
//					$htaccess .= "RewriteRule ^(.*\.".$compile.")$ $1.gz\r\n";
//					$htaccess .= "AddEncoding gzip .gz\r\n";
//					$htaccess .= "Header set ExpiresActive On\r\n";
//					$htaccess .= "Header set ExpiresDefault \"access plus 10 years\"\r\n";
//					file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/.htaccess', $htaccess);
//				}
//				
//				file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/'.$compressedName.'.'.$compile, $result);
//				file_put_contents($rh->project_dir.'cache/'.$rh->app_name.'/'.$compile.'/'.$compressedName.'.'.$compile.'.gz', gzencode($result, 9));
//			}
//			
//			$fname = array('path' => $rh->base_url.'cache/'.$rh->app_name.'/'.$compile, 'file' => $compressedName);
//			$tpl->set("*",$fname);
//			$str = $tpl->parse($template."_path");
		}
		else
		{
			foreach( $tpl->CONNECT[$compile] as $fname )
			{
				$tpl->set("_",$fname);
				$str .= $tpl->parse($template);
			}
		}
	}
	echo $str;
}
else
{
	//����������� ��� ����������
	$A = explode(".",$params[0]);
	$ext = array_pop($A);
	$fname = implode(".",$A);

	if( !isset($tpl->CONNECT[$ext]) || !is_array($tpl->CONNECT[$ext]) || !in_array($fname,$tpl->CONNECT[$ext]) )
	{
			$tpl->CONNECT[$ext][] = $fname;
	}
}
?>