<?php
/*
Plugin Name: Hyper Cache
Plugin URI: http://www.satollo.com/english/wordpress/hyper-cache
Description: Hyper Cache is an extremely aggressive cache for WordPress even for mobile blogs. After an upgrade, DEACTIVATE, REACTIVATE and RECONFIGURE. ALWAYS!
Version: 2.0.0
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

// On activation, we try to create files and directories. If something goes wrong
// (eg. for wrong permission on file system) the options page will give a
// warning.
add_action('activate_hyper-cache/plugin.php', 'hyper_activate');
function hyper_activate() 
{
    @mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);

    $buffer = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
    $file = @fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    if ($file)
    {
        fwrite($file, $buffer);
        fclose($file);	
    }
}


add_action('deactivate_hyper-cache/plugin.php', 'hyper_deactivate');
function hyper_deactivate() 
{
	@unlink(ABSPATH . 'wp-content/advanced-cache.php');
	@unlink(ABSPATH . 'wp-content/hyper-cache-config.php');

    // We can safely delete the hyper-cache directory, is not more used at this time.
    hyper_delete_path(ABSPATH . 'wp-content/hyper-cache');
}

add_action('admin_head', 'hyper_admin_head');
function hyper_admin_head() 
{
	add_options_page('Hyper Cache', 'Hyper Cache', 'manage_options', 'hyper-cache/options.php');
}

// Completely invalidate the cache. The hyper-cache directory is renamed
// with a random name and re-created to be immediately available to the cache
// system. Then the renamed directory is removed.
// If the cache has been already invalidated, the function doesn't anything.
function hyper_cache_invalidate() 
{
	global $hyper_options, $hyper_invalidated;
	
    //hyper_log("Called global invalidate");
    if ($hyper_invalidated) 
    {
        //hyper_log("Already invalidated");
        return;
    }
	
    $hyper_invalidated = true;
	$path = ABSPATH . 'wp-content/' . time();
	rename(ABSPATH . 'wp-content/hyper-cache', $path);
	mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);
	hyper_delete_path($path);
}

function hyper_cache_invalidate_post($post_id) 
{
    //hyper_log("Called post invalidate for post id $post_id");
    hyper_delete_by_post($post_id);
}

function hyper_cache_invalidate_post_status($new, $old, $post) 
{
    global $hyper_invalidated;
    
    $post_id = $post->ID;
    //hyper_log("Called post status invalidate for post id $post_id from $old to $new");

    // The post is going online or offline
    if ($new != 'publish' && $old != 'publish') return;

    //hyper_log("Start global invalidation");
    
    if ($hyper_invalidated) //hyper_log("Already invalidated");
    if ($hyper_invalidated) return;
        
    $hyper_invalidated = true;
	$path = ABSPATH . 'wp-content/' . time();
	rename(ABSPATH . 'wp-content/hyper-cache', $path);
	mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);
	hyper_delete_path($path);    
}

function hyper_cache_invalidate_comment($comment_id, $status=1) 
{
    //hyper_log("Called comment invalidate for comment id $comment_id and status $status");
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


// Delete files in the cache based only on a post id. Only the post cached
// page is deleted (with its mobile versions) and the home page of the blog.
// If the cache has been already invalidated, of the specified post has already
// been invalidate the function return doing nothing. This behaviour protect
// from multi invalidation caused by actions fired by WP.
// The invalidation doesn't take place is the post is not in "publish" status.
function hyper_delete_by_post($post_id)
{
    global $hyper_invalidated_post_id, $hyper_invalidated;
    
    if ($hyper_invalidated) 
    {
        //hyper_log("Already invalidated");
        return;
    }
    if ($hyper_invalidated_post_id == $post_id) 
    {
        //hyper_log("Already invalidated post id $post_id");  
        return;
    }
    
    $post = get_post($post_id);
    //hyper_log("Post status " . $post->post_status);
    if ($post->post_status != 'publish') 
    {
        return;
    }
    $hyper_invalidated_post_id = $post_id;
    
    // Post invalidation
    $link = get_permalink($post_id);
    //$link = substr($link, strpos($link, '/', 7));
    $link = substr($link, 7);
    $file = md5($link);

    @unlink(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat');
    
    // Home invalidation
    $link = substr(get_option('home'), 7) . '/';
    $file = md5($link);

    @unlink(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat');
    @unlink(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat');
}

// Completely remove a directory and it's content.
function hyper_delete_path($path) 
{
    if ($path == null) return;
    
	if ($handle = opendir($path)) 
    {
		while ($file = readdir($handle)) 
        {
			if ($file != '.' && $file != '..') 
            {
				unlink($path . '/' . $file);
			}
		}
		closedir($handle);
		@rmdir($path);
	}
}

// Counts the number of file in to the hyper cache directory to give an idea of
// the number of pages cached.
function hyper_count() 
{
    $count = 0;
    //if (!is_dir(ABSPATH . 'wp-content/hyper-cache')) return 0;
	if ($handle = opendir(ABSPATH . 'wp-content/hyper-cache')) 
    {
		while ($file = readdir($handle)) 
        {
			if ($file != '.' && $file != '..') 
            {
				$count++;
			}
		}
		closedir($handle);
	}
    return $count;
}

// Intercepts the action that can trigger a cache invalidation if the cache system is enabled
// and invalidation is asked for actions (if is not only based on cache timeout)
if ($hyper_options['enabled'] && $hyper_options['expire_type'] != 'none')
{
    
    // We need to invalidate everything for those actions because home page, categories pages, 
    // tags pages are affected and generally if we use plugin that print out "latest posts"
    // or so we cannot know if a deleted post appears on every page.
    add_action('switch_theme', 'hyper_cache_invalidate', 0);
    add_action('delete_post', 'hyper_cache_invalidate', 0);
    
	// When a post is modified and we want to expire only it's page we listen for
    // post edit (called everytime a post is modified, even if a comment is added) and
    // the status change. We invalidate the single post if it's status is publish, we 
    // invalidate all the cache if the status change from publish to "not publish" or
    // from "not publish" to publish. These two cases make a post to appear or disappear
    // anc can affect home, categories, single pages with a posts list, ...
    if ($hyper_options['expire_type'] == 'post')
    {
        add_action('edit_post', 'hyper_cache_invalidate_post', 0);
        add_action('transition_post_status', 'hyper_cache_invalidate_post_status', 0, 3);
    }
    else
    {    
        // If a complete invalidation is required, we do it on post edit.
        add_action('edit_post', 'hyper_cache_invalidate', 0);
    }

    // When a comment is received, and it's status is approved, the reference
    // post is modified, but even other pages can be affected (last comments list,
    // comment count and so on). We don't care about the latter situation when
    // the expire type is configured to invalidate the single post page.
    // Surely some of those hooks are redundant. When a new comment is added (and
    // approved) the action "edit_post" is fired (llok at the code before). I need
    // to deeply check this code BUT the plugin is protected from redundant invalidations.
    if ($hyper_options['expire_type'] == 'post')
    {    
        add_action('comment_post', 'hyper_cache_invalidate_comment', 10, 2);
        add_action('edit_comment', 'hyper_cache_invalidate_comment', 0);
        add_action('wp_set_comment_status', 'hyper_cache_invalidate_comment', 0);
        add_action('delete_comment', 'hyper_cache_invalidate_comment', 0);
    }
    else 
    {
        add_action('comment_post', 'hyper_cache_invalidate', 0);
        add_action('edit_comment', 'hyper_cache_invalidate', 0);
        add_action('wp_set_comment_status', 'hyper_cache_invalidate', 0);
        add_action('delete_comment', 'hyper_cache_invalidate', 0);
    }
}


// Capture and register if a redirect is sent back from WP, so the cache
// can cache (or ignore) it. Redirects were source of problems for blogs
// with more than one host name (eg. domain.com and www.domain.com) comined
// with the use of Hyper Cache.
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
