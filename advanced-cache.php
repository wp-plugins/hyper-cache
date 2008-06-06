<?php
@include( dirname(__FILE__) . '/hyper-cache-config.php' );

// From the config file
if (!$hyper_cache_enabled) {
	return false;
}

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
	return false;
}

// Do not use or cache pages when a wordpress user is logged on
foreach ( (array) $_COOKIE as $n => $v ) {
    // wp 2.5 and wp 2.3 have different cookie prefix
    if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' ) {
    	return false;
    }
}

$hyper_uri = stripslashes($_SERVER['REQUEST_URI']);

if (!$hyper_cache_get && strpos($hyper_uri, '?') !== false) return false;

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '/wp-admin/') !== false ||
	strpos($hyper_uri, '/wp-includes/') !== false ||
	strpos($hyper_uri, '/wp-content/') !== false ) {
	return false;
}


// Special blocks: the download manager plugin
/*
if (strpos($hyper_uri, '/download/') !== false) {
	return false;
}
*/

// Remove the anchor
$x = strpos($hyper_uri, '#');
if ($x !== false) {
	$hyper_uri = substr($hyper_uri, 0, $x);
}

$hyper_cache_name = md5($hyper_uri);
$hyper_file = ABSPATH . 'wp-content/hyper-cache/' . $hyper_cache_name . '.dat';

if ( is_file($hyper_file) ) {
    $hyper_data = unserialize( file_get_contents($hyper_file) );
    
    // Default timeout
    if ($hyper_cache_timeout == null) {
    	$hyper_cache_timeout = 60;
    }
    
    if ($hyper_data != null && ($hyper_data['time'] > time()-($hyper_cache_timeout*60)) && $hyper_data['html'] != '') {
		if ($hyper_data['mime'] == '')
		{
			header('Content-Type: text/html;charset=UTF-8');
		}
		else {
			header('Content-Type: ' . $hyper_data['mime']);
		}
        echo $hyper_data['html'];
        echo '<!-- hyper cache -->';
        flush();
        die();
    }
}

// Now we start the caching, but we remove the cookie which stores the comment for data
foreach ( (array) $_COOKIE as $n => $v ) {
    if (substr($n, 0, 14) == 'comment_author') {
    	unset($_COOKIE[$n]);
    }
}

ob_start('hyper_cache_callback');
function hyper_cache_callback($buffer) {
    global $hyper_file;

    $data['uri'] = $_SERVER['REQUEST_URI'];
    $data['referer'] = $_SERVER['HTTP_REFERER'];
    $data['time'] = time();
	if (is_feed()) {
		$data['mime'] = 'text/xml;charset=UTF-8';
	} else {
		$data['mime'] = 'text/html;charset=UTF-8';
	}	
    $data['html'] = $buffer;

    $file = fopen($hyper_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);

    return $buffer;
}
?>
