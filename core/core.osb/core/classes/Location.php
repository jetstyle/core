<?php

class Location
{

  function redirect($url, $code = 302) 
  {
    if (!headers_sent())
    {
      if ($code == 303) 
      {
        preg_match('/^HTTP\/(\d)\.(\d)/', $_SERVER['SERVER_PROTOCOL'], $m);
        if (intval($m[1])*10+intval($m[2]) < 11)
            $code=302;
      }
      switch ($code)
      {
        case 301 : header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently'); break;
        case 302 : header($_SERVER['SERVER_PROTOCOL'].' 302 Found'); break;
        case 303 : header($_SERVER['SERVER_PROTOCOL'].' 303 See Other'); break;
        default : header($_SERVER['SERVER_PROTOCOL'].' 302 Found');
      }

      $url_parts = parse_url($url);
      $url = Location::makeUrl(
        (isset($url_parts['query'])) ? $url_parts['query'] : '',
        (isset($url_parts['path'])) ? $url_parts['path'] : null,
        (isset($url_parts['host'])) ? $url_parts['host'] : null,
        (isset($url_parts['scheme'])) ? $url_parts['scheme'] : null
      );

      if (!strpos($url, '://'))
        $url = 'http://'.$_SERVER['HTTP_HOST'].$url;

      header('Location: '.$url);
      echo '<html><head><title>Not redirected</title></head><body>'.
         '<p>Your browser does not redirected automaticaly, please follow this link: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a></p>.'.
         '</body></html>';
      exit();
    }
    else
    {
      echo '<html><head><title>Unable to redirect</title></head><body>'.
         '<p>Unable to redirect automatically, please follow this link: <a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a>.</p>'.
         '</body></html>';
      exit();
    }
  }

  function arrayToUrlQuery($a, $_prefix = null)
  {
      $s = '';
      foreach ($a as $k => $v)
      {
          if (is_array($v))
          {
              if (!empty($v))
                  $a[$k] = Location::arrayToUrlQuery($v, $k);
              else
                  unset($a[$k]);
          }
          else
              if ($_prefix == null)
                  $a[$k] = urlencode($k).'='.urlencode($v);
              else
                  $a[$k] = $_prefix.'%5B'.urlencode($k).'%5D='.urlencode($v);
      }
      return implode('&', $a);
  }

  /**
   * Add or update variables in $url_query, $vars - is associative array, 
   * where key is variable name
   */
  function urlSetVars($url_query, $vars) 
  {
      $url_var_pairs = explode('&', $url_query);
      $url_vars = array();
      for ($i = 0, $m = count($url_var_pairs); $i < $m; $i++) 
      {
          if (($n = strpos($url_var_pairs[$i], '=')) !== false)
          {
              $k = urldecode(substr($url_var_pairs[$i], 0, $n));
              $v = urldecode(substr($url_var_pairs[$i], $n+1));
              if (($n2 = strpos($k, '[')) !== false && $k{strlen($k)-1} == ']')
              {
                $k = substr($k, 0, $n2);
                $keys_path = explode('][', substr($k, $n));
                for ($i2 = 0, $m2 = count($keys_path); $i2 < $m2; $i2++)
                {
                  if ($keys_path[$i2] != '' && !is_numeric($keys_path[$i2]))
                    $keys_path[$i2] = '\''.addslashes($keys_path[$i2]).'\'';
                }
                eval('$url_vars[$k]['.implode($keys_path).'] = $v;');
              }
              else
                $url_vars[$k] = $v;
          }
      }
      foreach ($vars as $k => $v)
      {
        $url_vars[$k] = $v;
      }
      return Location::arrayToUrlQuery($url_vars);
  }

  /**
   * Removes variables in $varlist from $url_query
   */
  function urlUnsetVars($url_query, $varlist) 
  {
      $url_var_pairs = explode('&', $url_query);
      $url_vars = array();
      for ($i = 0, $m = count($url_var_pairs); $i < $m; $i++) 
      {
          if (($n = strpos($url_var_pairs[$i], '=')) !== false)
          {
              $k = urldecode(substr($url_var_pairs[$i], 0, $n));
              $v = urldecode(substr($url_var_pairs[$i], $n+1));
              if (($n2 = strpos($k, '[')) !== false && $k{strlen($k)-1} == ']')
              {
                $k = substr($k, 0, $n2);
                $keys_path = explode('][', substr($k, $n));
                for ($i2 = 0, $m2 = count($keys_path); $i2 < $m2; $i2++)
                {
                  if ($keys_path[$i2] != '' && !is_numeric($keys_path[$i2]))
                    $keys_path[$i2] = '\''.addslashes($keys_path[$i2]).'\'';
                }
                eval('$url_vars[$k]['.implode($keys_path).'] = $v;');
              }
              else
                $url_vars[$k] = $v;
          }
      }
      foreach ($varlist as $k)
      {
        if (isset($url_vars[$k]))
          unset($url_vars[$k]);
      }
      return Location::arrayToUrlQuery($url_vars);

//      foreach ($varlist as $k)
//      {
//        $url_query = preg_replace('/(^|&)'.preg_quote($k).'(%5B[^&]*%5D)*=[^&]*/', '', $url_query, 1);
//        if (strlen($url_query) > 0 && $url_query{0} == '&')
//          $url_query = substr($url_query, 1);
//      }
//      return $url_query;

  }

  /**
   * Returns path (short URI without scheme, host, port, user and password)
   *
   *  @param  string      URL-query, like "var1=value1&var2=value2"
   *  @param  string      Path on the server "/path/to/document.html"
   *  @param  string      Anchor, after the hashmark #
   *
   * All parameters are optional, if no parameters passed - URL of current page
   * returned. You may specify one or more parameters to replace some parts for
   * your needs. If you, for example, need to change host but leave current
   * url-query and path - use null value for these params.
   *
   */
  function makePath($query = null, $path = null, $anchor = null)
  {
      /* parse current uri and split it into parts in $urlParts */
      $urlParts = parse_url($_SERVER['REQUEST_URI']);
      if (!isset($urlParts['query']))
          $urlParts['query'] = '';
      if ($query !== null)
          $urlParts['query'] = $query;
      if ($path !== null)
          $urlParts['path'] = $path;

      $url = $urlParts['path'] .
          ((!empty($urlParts['query'])) ? '?' . $urlParts['query'] : '') .
          ((!empty($anchor)) ? '#' . $anchor : '');
      return $url;
  }

  /**
   * Returns URL.
   *
   *  @param  string      URL-query, like "var1=value1&var2=value2"
   *  @param  string      Path on the server "/path/to/document.html"
   *  @param  string      Host: domain name or IP-address
   *  @param  string      Protocol, actually "http" or "https"
   *  @param  string      Anchor, after the hashmark #
   *  @param  string      User name
   *  @param  string      Password
   *
   * All parameters are optional, if no parameters passed - URL of current page
   * returned. You may specify one or more parameters to replace some parts for
   * your needs. If you, for example, need to change host but leave current
   * url-query and path - use null value for these params.
   *
   */
  function makeUrl($query = null, $path = null, $host = null, $scheme = null, $anchor = null, $user = null, $pass = null)
  {
      /* parse current uri and split it into parts in $urlParts */
      $urlParts = parse_url($_SERVER['REQUEST_URI']);
      /* we use port as part of host field */
      if (isset($urlParts['port']) && isset($urlParts['host']))
      {
          $urlParts['host'] .= ':'.$url['port'];
          unset($urlParts['port']);
      }
      if (!isset($urlParts['query']))
          $urlParts['query'] = '';
      if (!isset($urlParts['scheme']))
          $urlParts['scheme'] = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')?"https":"http");
      if (!isset($urlParts['host']))
          $urlParts['host'] = (!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] :
                                  (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] :
                                      $_SERVER['SERVER_ADDR']) .
                                  (($_SERVER['SERVER_PORT'] != 80) ? ':'.$_SERVER['SERVER_PORT'] : ''));
      if ($query !== null)
          $urlParts['query'] = $query;
      if ($path !== null)
          $urlParts['path'] = $path;
      if ($host !== null)
          $urlParts['host'] = $host;
      if ($scheme !== null)
          $urlParts['scheme'] = $scheme;

      $url = $urlParts['scheme'] . '://' .
          (($user !== null || $pass !== null) ? (($user !== null) ? $user : '') . ':' .
          (($pass !== null) ? $pass : '').'@' : '') .
          $urlParts['host'] . $urlParts['path'] .
          ((!empty($urlParts['query'])) ? '?' . $urlParts['query'] : '') .
          ((!empty($anchor)) ? '#' . $anchor : '');
      return $url;
  }
}

?>