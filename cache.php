<?php

global $hyper_cache_stop;
$hyper_cache_stop = false;

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') return false;

// Try to avoid enabling the cache if sessions are managed with request parameters and a session is active
if (defined(SID) && SID != '') return false;

$hyper_uri = $_SERVER['REQUEST_URI'];

if (!$hyper_cache_cache_qs && strpos($hyper_uri, '?') !== false) return false;

if (strpos($hyper_uri, 'robots.txt') !== false) return false;

// Checks for rejected url
if ($hyper_cache_reject !== false)
{
    foreach($hyper_cache_reject as $uri)
    {
        if (substr($uri, 0, 1) == '"')
        {
            if ($uri == '"' . $hyper_uri . '"') return false;
        }
        if (substr($hyper_uri, 0, strlen($uri)) == $uri) return false;
    }
}

if ($hyper_cache_reject_agents !== false)
{
    $hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    foreach ($hyper_cache_reject_agents as $hyper_a)
    {
        if (strpos($hyper_agent, $hyper_a) !== false) return false;
    }
}

// Do nested cycles in this order, usually no cookies are specified
if ($hyper_cache_reject_cookies !== false)
{
    foreach ($hyper_cache_reject_cookies as $hyper_c)
    {
        foreach ($_COOKIE as $n=>$v)
        {
            if (substr($n, 0, strlen($hyper_c)) == $hyper_c) return false;
        }
    }
}

// Do not use or cache pages when a wordpress user is logged on

foreach ($_COOKIE as $n=>$v)
{
// If it's required to bypass the cache when the visitor is a commenter, stop.
    if ($hyper_cache_comment && substr($n, 0, 15) == 'comment_author_')
    {
        hyper_cache_stats('commenter');
        return false;
    }

    // SHIT!!! This test cookie makes to cache not work!!!
    if ($n == 'wordpress_test_cookie') continue;
    // wp 2.5 and wp 2.3 have different cookie prefix, skip cache if a post password cookie is present, also
    if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' || substr($n, 0, 12) == 'wp-postpass_')
    {
        return false;
    }
}

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '/wp-admin/') !== false || strpos($hyper_uri, '/wp-includes/') !== false || strpos($hyper_uri, '/wp-content/') !== false )
{
    return false;
}

$hyper_uri = $_SERVER['HTTP_HOST'] . $hyper_uri;

hyper_cache_log('URI: ' . $hyper_uri);

// The name of the file with html and other data
$hyper_cache_name = md5($hyper_uri);
$hc_file = dirname(__FILE__) . '/cache/' . hyper_mobile_type() . $hyper_cache_name . '.dat';

hyper_cache_log('Cache file: ' . $hc_file);

if (!file_exists($hc_file))
{
    hyper_cache_log('Cache file do not exists');
    hyper_cache_start(false);
    return;
}

hyper_cache_log('Cache file exists');
$hc_file_time = @filectime($hc_file);
$hc_file_age = time() - $hc_file_time;

if ($hc_file_age > $hyper_cache_timeout)
{
    hyper_cache_log('Cached file is too old');
    hyper_cache_start();
    return;
}

$hc_invalidation_time = @filectime(dirname(__FILE__) . '/invalidation.dat');
if ($hc_invalidation_time && $hc_file_time < $hc_invalidation_time)
{
    hyper_cache_log('Invalidation file is newer than cache file');
    hyper_cache_start();
    return;
}

// Load it and check is it's still valid
$hyper_data = @unserialize(file_get_contents($hc_file));

if (!$hyper_data)
{
    hyper_cache_log('Unable to deserialize');
    hyper_cache_start();
    return;
}

if ($hyper_data['type'] == 'home' || $hyper_data['type'] == 'archive')
{
    hyper_cache_log('Archive page type: ' . $hyper_data['type']);

    $hc_invalidation_archive_file =  @filectime(dirname(__FILE__) . '/invalidation-archive.dat');
    if ($hc_invalidation_archive_file && $hc_file_time < $hc_invalidation_archive_file)
    {
        hyper_cache_log('Archive invalidation file is newer than cache file');
        hyper_cache_start();
        return;
    }
}

// Valid cache file check ends here

if ($hyper_data['location'])
{
    hyper_cache_log('Was a redirect to ' . $hyper_data['location']);
    header('Location: ' . $hyper_data['location']);
    flush();
    die();
}

// It's time to serve the cached page
if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER))
{
    $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
    if ($if_modified_since >= $hc_file_time)
    {
        header("HTTP/1.0 304 Not Modified");
        flush();
        hyper_cache_stats('304');
        die();
    }
}

// Now serve the real content

header('Last-Modified: ' . date("r", @filectime($hyper_file)));
header('Content-Type: ' . $hyper_data['mime']);
if ($hyper_data['status'] == 404) header("HTTP/1.1 404 Not Found");

hyper_cache_log('Encoding accepted: ' . $_SERVER['HTTP_ACCEPT_ENCODING']);

// Send the cached html
if ($hyper_cache_gzip && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && strlen($hyper_data['gz']) > 0)
{
    hyper_cache_log('Gzip encoding accepted, serving compressed data');
    header('Content-Encoding: gzip');
    echo $hyper_data['gz'];
    hyper_cache_stats('gzip');
}
else
{
    // No compression accepted, check if we have the plain html or
    // decompress the compressed one.
    if ($hyper_data['html'])
    {
        hyper_cache_log('Serving plain data');
        //header('Content-Length: ' . strlen($hyper_data['html']));
        echo $hyper_data['html'];
    }
    else
    {
        hyper_cache_log('decoding compressed data (length: ' . strlen($hyper_data['gz']) . ')');
        $buffer = hyper_cache_gzdecode($hyper_data['gz']);
        if ($buffer === false) echo 'Error retriving the content';
        else echo $buffer;
    }
    hyper_cache_stats('plain');
}
flush();
hyper_cache_clean();
die();


function hyper_cache_start($delete=true)
{
    global $hc_file;
    
    if ($delete) @unlink($hc_file);
    foreach ($_COOKIE as $n=>$v )
    {
        if (substr($n, 0, 14) == 'comment_author')
        {
            unset($_COOKIE[$n]);
        }
    }
    hyper_cache_stats('wp');
    ob_start('hyper_cache_callback');
}

// From here Wordpress starts to process the request

// Called whenever the page generation is ended
function hyper_cache_callback($buffer)
{
    global $hyper_cache_stop, $hyper_cache_charset, $hyper_cache_home, $hyper_cache_redirects, $hyper_redirect, $hc_file, $hyper_cache_name, $hyper_cache_gzip;

    if ($hyper_cache_stop) return $buffer;

    // WP is sending a redirect
    if ($hyper_redirect)
    {
        if ($hyper_cache_redirects)
        {
            $data['location'] = $hyper_redirect;
            hyper_cache_write($data);
        }
        return $buffer;
    }

    if (is_home() && $hyper_cache_home)
    {
        return $buffer;
    }

    if (is_feed() && !$hyper_cache_feed)
    {
        return $buffer;
    }

    if (is_home()) $data['type'] = 'home';
    else if (is_feed()) $data['type'] = 'feed';
    else if (is_archive()) $data['type'] = 'archive';
    else if (is_single()) $data['type'] = 'single';
    else if (is_page()) $data['type'] = 'page';
    $buffer = trim($buffer);

    // Can be a trackback or other things without a body. We do not cache them, WP needs to get those calls.
    if (strlen($buffer) == 0) return '';

    if (!$hyper_cache_charset) $hyper_cache_charset = 'UTF-8';

    if (is_feed())
    {
        $data['mime'] = 'text/xml;charset=' . $hyper_cache_charset;
    }
    else
    {
        $data['mime'] = 'text/html;charset=' . $hyper_cache_charset;
    }

    $buffer .= '<!-- hyper cache: ' . $hyper_cache_name . ' ' . date('y-m-d h:i:s') .' -->';

    $data['html'] = $buffer;

    if (is_404()) $data['status'] = 404;

    hyper_cache_write($data);

    return $buffer;
}

function hyper_cache_write(&$data)
{
    global $hc_file, $hyper_cache_store_compressed;

    $data['uri'] = $_SERVER['REQUEST_URI'];

    // Look if we need the compressed version
    if ($hyper_cache_store_compressed)
    {
        $data['gz'] = gzencode($data['html']);
        if ($data['gz']) unset($data['html']);
    }
    $file = fopen($hc_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);

    header('Last-Modified: ' . date("r", @filectime($hc_file)));
}

function hyper_mobile_type()
{
    global $hyper_cache_mobile, $hyper_cache_mobile_agents;

    if (!isset($hyper_cache_mobile) || $hyper_cache_mobile_agents === false) return '';

    $hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    //$hyper_agents = explode(',', "elaine/3.0,iphone,ipod,palm,eudoraweb,blazer,avantgo,windows ce,cellphone,small,mmef20,danger,hiptop,proxinet,newt,palmos,netfront,sharp-tq-gx10,sonyericsson,symbianos,up.browser,up.link,ts21i-10,mot-v,portalmmm,docomo,opera mini,palm,handspring,nokia,kyocera,samsung,motorola,mot,smartphone,blackberry,wap,playstation portable,lg,mmp,opwv,symbian,epoc");
    foreach ($hyper_cache_mobile_agents as $hyper_a)
    {
        if (strpos($hyper_agent, $hyper_a) !== false)
        {
            if (strpos($hyper_agent, 'iphone') || strpos($hyper_agent, 'ipod'))
            {
                return 'iphone';
            }
            else
            {
                return 'pda';
            }
        }
    }
    return '';
}

function hyper_cache_clean()
{
    global $hyper_cache_timeout, $hyper_cache_clean_interval;
    $invalidation_time = @filectime(ABSPATH . 'wp-content/plugins/hyper-cache/invalidation.dat');
    if (!$hyper_cache_clean_interval || (!$hyper_cache_timeout && !$invalidation_time)) return;

    if (rand(1, 20) != 1) return;

    hyper_cache_log('start cleaning');

    $time = time();
    $file = ABSPATH . 'wp-content/plugins/hyper-cache/last-clean.dat';
    $last_clean_time = @filectime($file);
    if ($last_clean_time && ($time - $last_clean_time < $hyper_cache_clean_interval)) return;

    touch($file);

    $path = dirname(__FILE__) . '/cache';
    $handle = @opendir($path);
    if ($handle)
    {
        $count = 0;
        while ($file = readdir($handle))
        {
            if ($file == '.' || $file == '..') continue;
            $t = @filectime($path . '/' . $file);
            if ($time - $t > $hyper_cache_timeout || ($invalidation_time && $t < $invalidation_time))
            {
                @unlink($path . '/' . $file);
                $count++;
                if ($count > 100) break;
            }
        }
        closedir($handle);
    }
    hyper_cache_log('end cleaning');
}

function hyper_cache_gzdecode ($data)
{
    hyper_cache_log('gzdecode called with data length ' + strlen($data));

    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;

    $filenamelen = 0;
    if ($flags & 4)
    {
        $extralen = unpack('v' ,substr($data, 10, 2));

        $extralen = $extralen[1];
        $headerlen += 2 + $extralen;
    }
    if ($flags & 8) // Filename

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 16) // Comment

        $headerlen = strpos($data, chr(0), $headerlen) + 1;
    if ($flags & 2) // CRC at end of file

        $headerlen += 2;
    $unpacked = gzinflate(substr($data, $headerlen));
    if ($unpacked === false) hyper_cache_log('unable to unpack!!!');
    return $unpacked;
}


function hyper_cache_log($text)
{
//    $file = fopen(dirname(__FILE__) . '/hyper_cache.log', 'a');
//    if (!$file) return;
//    fwrite($file, $_SERVER['REMOTE_ADDR'] . ' ' . date('Y-m-d H:i:s') . ' ' . $text . "\n");
//    fclose($file);
}

function hyper_cache_stats($type)
{
    global $hyper_cache_stats;

    if (!$hyper_cache_stats) return;
    $file = fopen(dirname(__FILE__) . '/stats/hyper-cache-' . $type . '.txt', 'a');
    if (!$file) return;
    fwrite($file, 'x');
    fclose($file);
}
?>
