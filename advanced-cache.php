<?php
@include(dirname(__FILE__) . '/hyper-cache-config.php');

// From the config file
if (!$hyper_cache_enabled) return false;

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') return false;

// Try to avoid enabling the cache if sessions are managed with request parameters and a session is active
if (defined(SID) && SID != '') return false;

$hyper_uri = $_SERVER['REQUEST_URI'];

if ($hyper_cache_urls == 'default' && strpos($hyper_uri, '?') !== false) return false;

// Checks for rejected url
if ($hyper_cache_reject)
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

if (count($hyper_cache_reject_agents) > 0)
{
    $hyper_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    foreach ($hyper_cache_reject_agents as $hyper_a)
    {
        if (strpos($hyper_agent, $hyper_a) !== false) return false;
    }
}

// Do not use or cache pages when a wordpress user is logged on
foreach ($_COOKIE as $n=>$v) 
{
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

// The name of the file with html and other data
$hyper_cache_name = md5($hyper_uri);
$hyper_file = ABSPATH . 'wp-content/hyper-cache/' . hyper_mobile_type() . $hyper_cache_name . '.dat';

// The file is present?
if (is_file($hyper_file)) 
{
    if (!$hyper_cache_timeout || (time() - filectime($hyper_file)) < $hyper_cache_timeout*60)
    {
        // Load it and check is it's still valid
        $hyper_data = unserialize(file_get_contents($hyper_file));

        // Protect against broken cache files    
        if ($hyper_data != null)
        {

            if ($hyper_data['location'])
            {
                header('Location: ' . $hyper_data['location']);
                flush;
                die();
            }
            
            if ($hyper_data['status'] == 404)
            {
                header("HTTP/1.1 404 Not Found");
                $hyper_data = unserialize(file_get_contents(ABSPATH . 'wp-content/hyper-cache/404.dat'));
            }
        
            if (array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER))
            {
                $if_modified_since = strtotime(preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]));
                if ($if_modified_since >= filectime($hyper_file))
                {
                    header("HTTP/1.0 304 Not Modified");
                    flush();
                    die();
                }
            }


            header('Last-Modified: ' . date("r", filectime($hyper_file)));
            
            header('Content-Type: ' . $hyper_data['mime']);
        
            // Send the cached html
            if ($hyper_cache_gzip && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && strlen($hyper_data['gz']) > 0)
            {
              header('Content-Encoding: gzip');
              echo $hyper_data['gz'];
            }
            else 
            {
              if (strlen($hyper_data['html']) > 0)
              {
                  echo $hyper_data['html'];
              }
              else
              {
                echo gzdecode($hyper_data['gz']);               
              }
            }
            flush();
            hyper_cache_clean();
            die();
        }
    }
}

// Now we start the caching, but we remove the cookie which stores the commenter data otherwise the page will be generated
// with a pre compiled comment form...
foreach ($_COOKIE as $n=>$v ) 
{
    if (substr($n, 0, 14) == 'comment_author') 
    {
        unset($_COOKIE[$n]);
    }
}

ob_start('hyper_cache_callback');

// From here Wordpress starts to process the request

// Called whenever the page generation is ended
function hyper_cache_callback($buffer) 
{
    global $hyper_cache_charset, $hyper_cache_home, $hyper_cache_redirects, $hyper_redirect, $hyper_file, $hyper_cache_compress, $hyper_cache_name, $hyper_cache_gzip;

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
        
    // Clean up a it the html, this is a energy saver plugin!
    if ($hyper_cache_compress)
    {
        $buffer = hyper_cache_compress($buffer);
    }
    
    $buffer .= '<!-- hyper cache: ' . $hyper_cache_name . ' -->';    
    
    $data['html'] = $buffer;
    
    if ($hyper_cache_gzip && function_exists('gzencode')) 
    {
        $data['gz'] = gzencode($buffer);
    }

    if (is_404())
    {
        if (!file_exists(ABSPATH . 'wp-content/hyper-cache/404.dat'))
        {
            $file = fopen(ABSPATH . 'wp-content/hyper-cache/404.dat', 'w');
            fwrite($file, serialize($data));
            fclose($file);            
        }
        unset($data['html']);
        unset($data['gz']);
        $data['status'] = 404;
    }
        
    hyper_cache_write($data);

    return $buffer;
}

function hyper_cache_write(&$data)
{
    global $hyper_file, $hyper_cache_storage;
    
    $data['uri'] = $_SERVER['REQUEST_URI'];
    $data['referer'] = $_SERVER['HTTP_REFERER'];
    $data['time'] = time();   
    $data['host'] = $_SERVER['HTTP_HOST'];
    $data['agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    if ($hyper_cache_storage == 'minimize')
    {
        unset($data['html']);
    }
    
    $file = fopen($hyper_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);  

    header('Last-Modified: ' . date("r", filectime($hyper_file)));

}

function hyper_cache_compress(&$buffer)
{
    $buffer = ereg_replace("[ \t]+", ' ', $buffer);
    $buffer = ereg_replace("[\r\n]", "\n", $buffer);
    $buffer = ereg_replace(" *\n *", "\n", $buffer);
    $buffer = ereg_replace("\n+", "\n", $buffer);
    $buffer = ereg_replace("\" />", "\"/>", $buffer);
    $buffer = ereg_replace("<tr>\n", "<tr>", $buffer);
    $buffer = ereg_replace("<td>\n", "<td>", $buffer);
    $buffer = ereg_replace("<ul>\n", "<ul>", $buffer);
    $buffer = ereg_replace("</ul>\n", "</ul>", $buffer);
    $buffer = ereg_replace("<p>\n", "<p>", $buffer);
    $buffer = ereg_replace("</p>\n", "</p>", $buffer);
    $buffer = ereg_replace("</li>\n", "</li>", $buffer);
    $buffer = ereg_replace("</td>\n", "</td>", $buffer);   

    return $buffer;
}


function hyper_mobile_type()
{
    global $hyper_cache_mobile, $hyper_cache_mobile_agents;
    
    if (!$hyper_cache_mobile || !$hyper_cache_mobile_agents) return '';
    
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

    if (!$hyper_cache_clean_interval) return;
    if (rand(1, 10) != 5) return;
    
    $time = time();
    $file = ABSPATH . 'wp-content/hyper-cache/last-clean.dat';
    if (file_exists($file) && ($time - filectime($file) < $hyper_cache_clean_interval*60)) return;
    
    touch(ABSPATH . 'wp-content/hyper-cache/last-clean.dat');
    
    $path = ABSPATH . 'wp-content/hyper-cache';
    if ($handle = @opendir($path)) 
    {
        while ($file = readdir($handle)) 
        {
            if ($file == '.' || $file == '..') continue;
            
            $t = filectime($path . '/' . $file);
            if ($time - $t > $hyper_cache_timeout) @unlink($path . '/' . $file);
        }
        closedir($handle);    
    }
}

if (!function_exists('gzdecode')) 
{
	function gzdecode ($data) 
    {
	    $flags = ord(substr($data, 3, 1));
	    $headerlen = 10;
	    $extralen = 0;

	    $filenamelen = 0;
	    if ($flags & 4) {
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
	    if ($unpacked === FALSE)

        $unpacked = $data;
	    return $unpacked;
    }
}

?>
