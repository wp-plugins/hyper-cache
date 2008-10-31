<?php
@include(dirname(__FILE__) . '/hyper-cache-config.php');

// From the config file
if (!$hyper_cache_enabled) return false;

// Do not cache post request (comments, plugins and so on)
if ($_SERVER["REQUEST_METHOD"] == 'POST') return false;

// Do not use or cache pages when a wordpress user is logged on
foreach ($_COOKIE as $n=>$v) 
{
    // wp 2.5 and wp 2.3 have different cookie prefix, skip cache if a post password cookie is present, also
    if (substr($n, 0, 14) == 'wordpressuser_' || substr($n, 0, 10) == 'wordpress_' || substr($n, 0, 12) == 'wp-postpass_') 
    {
        return false;
    }
}

$hyper_uri = stripslashes($_SERVER['REQUEST_URI']);

// Do not cache WP pages, even if those calls typically don't go throught this script
if (strpos($hyper_uri, '/wp-admin/') !== false || strpos($hyper_uri, '/wp-includes/') !== false || strpos($hyper_uri, '/wp-content/') !== false ) 
{
    return false;
}

// The name of the file with html and other data
$hyper_cache_name = md5($hyper_uri);
$hyper_file = ABSPATH . 'wp-content/hyper-cache/' . $hyper_cache_name . '.dat';

// The file is present?
if (is_file($hyper_file)) 
{
    // Load it and check is it's still valid
    $hyper_data = unserialize(file_get_contents($hyper_file));

    // Default timeout
    if ($hyper_cache_timeout == null) 
    {
        $hyper_cache_timeout = 60;
    }

    if ($hyper_data != null && ($hyper_data['time'] > time()-($hyper_cache_timeout*60))) 
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
        
        header('Content-Type: ' . $hyper_data['mime']);
        
        // Send the cached html
        if ($hyper_cache_gzip && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false && $hyper_data['gz'])
        {
          header('Content-Encoding: gzip');
          echo $hyper_data['gz'];
        }
        else 
        {
          echo $hyper_data['html'];
        }
        
        flush();
        die();
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
    global $hyper_redirect, $hyper_file, $hyper_compress, $post, $hyper_cache_name, $hyper_cache_gzip;
    
    // A bug? May be WP call the "canonical_redirect" hook even when no redirect is really issued. If the
    // uri equals the WP redirect, we ignore it.
    if ($hyper_redirect == $_SERVER['REQUEST_URI']) $hyper_redirect = null;
    
    if (!$hyper_redirect && strlen($buffer) == 0) return '';
    
    $data['uri'] = $_SERVER['REQUEST_URI'];
    $data['referer'] = $_SERVER['HTTP_REFERER'];
    $data['time'] = time();
    if (false && $hyper_redirect)
    {
        $data['location'] = $hyper_redirect;
    }
    else 
    {
        if (is_feed()) 
        {
            $data['mime'] = 'text/xml;charset=UTF-8';
        } 
        else 
        {
            $data['mime'] = 'text/html;charset=UTF-8';
        }
        
        // Clean up a it the html, this is a energy saver plugin!
        if ($hyper_compress)
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
    
    }
    

    
    $file = fopen($hyper_file, 'w');
    fwrite($file, serialize($data));
    fclose($file);

    return $buffer;
}
?>
