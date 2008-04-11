<?php
@include(dirname(__FILE__) . '/hyper-cache-config.php');

// From the config file
if (!$hyper_cache_enabled) return;

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') return;

// Do not use or cache pages when a wordpress user is logged on
foreach ($_COOKIE as $n=>$v) 
{ 
    if (substr($n, 0, 13) == 'wordpressuser') return;
}

$hyper_uri = $_SERVER['REQUEST_URI'];

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '?') !== false) return;
if (strpos($hyper_uri, '/wp-admin/') !== false) return;
if (strpos($hyper_uri, '/wp-includes/') !== false) return;
if (strpos($hyper_uri, '/wp-content/') !== false) return;

// Special blocks: the download manager plugin
if (strpos($hyper_uri, '/download/') !== false) return;

// Remove the anchor
$x = strpos($hyper_uri, '#');
if ($x !== false) $hyper_uri = substr($hyper_uri, 0, $x); 


    
$hyper_cache_name = md5($hyper_uri);
$hyper_file = ABSPATH . 'wp-content/hyper-cache/' . $hyper_cache_name . '.dat';

if (file_exists($hyper_file))
{
    $hyper_data = unserialize(file_get_contents($hyper_file));
    if ($hyper_cache_timeout == null) $hyper_cache_timeout = 60;
    if ($hyper_data != null && ($hyper_data['time'] > time()-($hyper_cache_timeout*60)) && $hyper_data['html'] != '') 
    {
        header('Content-Type: text/html;charset=UTF-8');
        echo $hyper_data['html'];
        echo '<!-- -->';
        flush();
        die();
    }
}

ob_start('hyper_cache_callback');


function hyper_cache_callback($buffer)
{
global $hyper_file;
  //$name = md5($_SERVER['REQUEST_URI']);
  
  $data['uri'] = $_SERVER['REQUEST_URI'];
  $data['referer'] = $_SERVER['HTTP_REFERER'];
  $data['time'] = time();
  $data['html'] = $buffer;
  
  $file = fopen($hyper_file, 'w');
  fwrite($file, serialize($data));
  fclose($file);
  
  return $buffer;
}

?>
