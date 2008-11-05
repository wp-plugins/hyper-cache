<?php
/*
Plugin Name: Hyper Cache
Plugin URI: http://www.satollo.com/english/wordpress/hyper-cache
Description: Hyper Cache is an extremely aggressive cache for WordPress. After an upgrade, DEACTIVATE and REACTIVATE the plugin.
Version: 1.2.6
Author: Satollo
Author URI: http://www.satollo.com
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

---
Copyright 2008  Satollo  (email : satollo@gmail.com)
---

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

---
Changelog
---

Version 1.2.x 
    - new version with many improvements, maybe not very safe
    
Version 1.1.1
    - added an option to invalidate single post pages
    
Version 1.1
    - fixed behaviour with password protected posts
    - added a bit of html compression (not gzip)
    
Verison 1.0.9 
    - fixed a bug in the "not expire" management
    
Version 1.0.8
	- fixed the "clear cache" that didn't work when "not exipre on actions" was set
	
Version 1.0.7
	- Fixed the mime type for feed
	- Added the "do not expire on actions" option
	
Version 1.0.6
    - German translation by Frank Luef
    - Fixed some not blocking url when installing without the WP_CACHE defined
    - Fixed a message key in the .po files
    
Version 1.0.5
    - Add italian translation
    - Add the "clear the cache" button
    - Add the cache page count

Version 1.0.4
    - Thank you to Amaury Balmer for this version
	- Add french translation
	- Improve options with WordPress 2.5
	- Fix bug with WP 2.5 cookies
	- Minor changes
*/

$hyper_options = get_option('hyper');
$hyper_invalidated = false;
$hyper_invalidated_post_id = null;

add_action('activate_hyper-cache/plugin.php', 'hyper_activate');
function hyper_activate() 
{
    if (!file_exists(ABSPATH . 'wp-content/hyper-cache')) 
    {
        if (!mkdir(ABSPATH . 'wp-content/hyper-cache', 0766)) return new WP_Error('', 'Cannot create the cache directory');
        if (!touch(ABSPATH . 'wp-content/hyper-cache/test.dat')) return new WP_Error('', 'Cannot write into the cache directory');
        $file = time();
        if (!mkdir(ABSPATH . 'wp-content/' . $file, 0766)) return new WP_Error('', 'Cannot create directories in wp-content');
        rmdir(ABSPATH . 'wp-content/' . $file);
    }
	//hyper_cache_invalidate();
	
    // Write the advanced-cache.php (so we grant it's the correct version)
    $buffer = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
    $file = fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    if ($file)
    {
        fwrite($file, $buffer);
        fclose($file);	
    }
    else
    {
        return new WP_Error('', 'Cannot create advanced-cache.php into wp-content directory');    
    }
}


add_action('deactivate_hyper-cache/plugin.php', 'hyper_deactivate');
function hyper_deactivate() 
{
	if (file_exists(ABSPATH . 'wp-content/advanced-cache.php')) unlink(ABSPATH . 'wp-content/advanced-cache.php');
	if (file_exists(ABSPATH . 'wp-content/hyper-cache-config.php')) unlink(ABSPATH . 'wp-content/hyper-cache-config.php');

	if (is_dir(ABSPATH . 'wp-content/hyper-cache'))
	{
		$path = ABSPATH . 'wp-content/' . time();
		rename(ABSPATH . 'wp-content/hyper-cache', $path);

		hyper_delete_path( $path );
	}
}

add_action('admin_head', 'hyper_admin_head');
function hyper_admin_head() {
	add_options_page('Hyper Cache', 'Hyper Cache', 'manage_options', 'hyper-cache/options.php');
}

function hyper_cache_invalidate_publish_post() 
{
    hyper_log("Called global invalidate for publish_post");
    hyper_cache_invalidate();
}

function hyper_cache_invalidate($force=false) 
{
	global $hyper_options, $hyper_invalidated;
	
    hyper_log("Called global invalidate");
    if ($hyper_invalidated) hyper_log("Already invalidated");
    if ($hyper_invalidated) return;
    
	//if (!$force && $hyper_options['not_expire_on_actions']) return;
	if (!$force) return;
	
    $hyper_invalidated = true;
	$path = ABSPATH . 'wp-content/' . time();
	rename(ABSPATH . 'wp-content/hyper-cache', $path);
	mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);
	hyper_delete_path( $path );
}

function hyper_cache_invalidate_post($post_id) 
{
    hyper_log("Called post invalidate for post id $post_id");
    hyper_delete_by_post($post_id);
}

function hyper_cache_invalidate_post_status($new, $old, $post) 
{
    global $hyper_invalidated;
    
    $post_id = $post->ID;
    hyper_log("Called post status invalidate for post id $post_id from $old to $new");

    // The post is going online or offline
    if ($new != 'publish' && $old != 'publish') return;

    hyper_log("Start global invalidation");
    
    if ($hyper_invalidated) hyper_log("Already invalidated");
    if ($hyper_invalidated) return;
        
    $hyper_invalidated = true;
	$path = ABSPATH . 'wp-content/' . time();
	rename(ABSPATH . 'wp-content/hyper-cache', $path);
	mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);
	hyper_delete_path($path);    
}

function hyper_cache_invalidate_comment($comment_id, $status=1) 
{
    hyper_log("Called comment invalidate for comment id $comment_id and status $status");
    if ($status != 1) return;
    hyper_delete_by_comment($comment_id);
    //hyper_cache_invalidate();
}

function hyper_delete_by_comment($comment_id)
{
    $comment = get_comment($comment_id);
    $post_id = $comment->comment_post_ID;
    hyper_delete_by_post($post_id);
}

function hyper_delete_by_post($post_id)
{
    global $hyper_invalidated_post_id, $hyper_invalidated;
    
    if ($hyper_invalidated) 
    {
        hyper_log("Already invalidated");
        return;
    }
    if ($hyper_invalidated_post_id == $post_id) 
    {
        hyper_log("Already invalidated post id $post_id");  
        return;
    }
    
    $post = get_post($post_id);
    hyper_log("Post status " . $post->post_status);
    if ($post->post_status != 'publish') 
    {
        return;
    }
    $hyper_invalidated_post_id = $post_id;
    
    
    $link = get_permalink($post_id);
    //$link = substr($link, strpos($link, '/', 7));
    $link = substr($link, 7);
    $file = md5($link);

    if (file_exists(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat'))
    {
        unlink(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat');
    }
    if (file_exists(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat'))
    {
        unlink(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat');
    }
    if (file_exists(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat'))
    {
        unlink(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat');
    }
    
    // Home invalidation
    $link = substr(get_option('home'), 7) . '/';
    $file = md5($link);

    @unlink(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat');
        
}

function hyper_delete_path( $path = '' ) {
	if ($handle = opendir($path)) {
		while ($file = readdir($handle)) {
			if ($file != '.' && $file != '..') {
				unlink($path . '/' . $file);
			}
		}
		closedir($handle);
		rmdir($path);
	}
}

function hyper_count() {
    $count = 0;
    if (!is_dir(ABSPATH . 'wp-content/hyper-cache')) return 0;
	if ($handle = opendir(ABSPATH . 'wp-content/hyper-cache')) {
		while ($file = readdir($handle)) {
			if ($file != '.' && $file != '..') {
				$count++;
			}
		}
		closedir($handle);
	}
    return $count;
}

// Intercepts the action that can trigger a cache invalidation
if ($hyper_options['enabled'] && $hyper_options['expire_type'] != 'none')
{

    
    // We need to invalidate everything for those actions: home page, categories pages, tags pages are affected
	//add_action('publish_post', 'hyper_cache_invalidate_publish_post', 0);
	//add_action('publish_phone', 'hyper_cache_invalidate', 0);
    add_action('switch_theme', 'hyper_cache_invalidate', 0);
    add_action('delete_post', 'hyper_cache_invalidate', 0);
    
	// Posts
    if ($hyper_options['expire_type'] == 'post')
    {
        add_action('edit_post', 'hyper_cache_invalidate_post', 0);
        add_action('transition_post_status', 'hyper_cache_invalidate_post_status', 0, 3);
    }
    else
    {    
        add_action('edit_post', 'hyper_cache_invalidate', 0);
    }

    // Coment ID is received
    //add_action('trackback_post', 'hyper_cache_invalidate', 0);
    //add_action('pingback_post', 'hyper_cache_invalidate', 0);
    if ($hyper_options['expire_type'] == 'post')
    {    
        add_action('comment_post', 'hyper_cache_invalidate_comment', 10, 2);
        add_action('edit_comment', 'hyper_cache_invalidate_comment', 0);
        add_action('wp_set_comment_status', 'hyper_cache_invalidate_comment', 0);
        
        // No post_id is available
        add_action('delete_comment', 'hyper_cache_invalidate_comment', 0);
    }
    else 
    {
        add_action('comment_post', 'hyper_cache_invalidate', 0);
        add_action('edit_comment', 'hyper_cache_invalidate', 0);
        add_action('wp_set_comment_status', 'hyper_cache_invalidate', 0);
        
        // No post_id is available
        add_action('delete_comment', 'hyper_cache_invalidate', 0);
    }
}

add_filter('redirect_canonical', 'hyper_redirect_canonical', 10, 2);
$hyper_redirect = null;
function hyper_redirect_canonical($redirect_url, $requested_url)
{
    global $hyper_redirect;

    $hyper_redirect = $redirect_url;
    
    return $redirect_url;
}

function hyper_log($text) 
{
	$file = fopen(dirname(__FILE__) . '/plugin.log', 'a');
	fwrite($file, $text . "\n");
	fclose($file);
}
?>
