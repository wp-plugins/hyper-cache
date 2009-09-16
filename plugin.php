<?php
/*
Plugin Name: Hyper Cache
Plugin URI: http://www.satollo.net/plugins/hyper-cache
Description: Hyper Cache is a features rich cache system WordPress. If you do an auto upgrade via WordPress, you need only to reconfigure the cache, if you upgrade manually be sure to deactivate the plugin before upload the new files. Version 2.5.0 has been widely changed, look for the new invalidation options and configure as you prefer.
Version: 2.5.1
Author: Satollo
Author URI: http://www.satollo.net
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.

---
Copyright 2008  Satollo  (email : info@satollo.net)
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

See the readme.txt.

*/

$hyper_options = get_option('hyper');
$hyper_invalidated = false;
$hyper_invalidated_post_id = null;

// On activation, we try to create files and directories. If something goes wrong
// (eg. for wrong permission on file system) the options page will give a
// warning.
add_action('activate_hyper-cache/plugin.php', 'hyper_activate');
function hyper_activate() {
    @mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);

    $buffer = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
    $file = @fopen(ABSPATH . 'wp-content/advanced-cache.php', 'wb');
    if ($file) {
        fwrite($file, $buffer);
        fclose($file);
    }
}


add_action('deactivate_hyper-cache/plugin.php', 'hyper_deactivate');
function hyper_deactivate() {
    @unlink(ABSPATH . 'wp-content/advanced-cache.php');
    @unlink(ABSPATH . 'wp-content/hyper-cache-config.php');

    // We can safely delete the hyper-cache directory, is not more used at this time.
    hyper_delete_path(ABSPATH . 'wp-content/hyper-cache');
}

add_filter("plugin_action_links_hyper-cache/plugin.php", 'hyper_plugin_action_links');
function hyper_plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=hyper-cache/options.php">' . __( 'Settings' ) . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_action('admin_menu', 'hyper_admin_menu');
function hyper_admin_menu() {
    add_options_page('Hyper Cache', 'Hyper Cache', 'manage_options', 'hyper-cache/options.php');
}

// Completely invalidate the cache. The hyper-cache directory is renamed
// with a random name and re-created to be immediately available to the cache
// system. Then the renamed directory is removed.
// If the cache has been already invalidated, the function doesn't anything.
function hyper_cache_invalidate()
{
    global $hyper_invalidated;

    hyper_log("hyper_cache_invalidate> Called");

    if ($hyper_invalidated) {
        hyper_log("hyper_cache_invalidate> Cache already invalidated");
        return;
    }

    if (!touch(ABSPATH . 'wp-content/hyper-cache-invalidation.dat'))
    {
        hyper_log("hyper_cache_invalidate> Unable to touch invalidation.dat");
    }
    else
    {
        hyper_log("hyper_cache_invalidate> Touched invalidation.dat");
    }

    $hyper_invalidated = true;

}

/**
 * Invalidates a single post and eventually the home and archives if
 * required.
 */
function hyper_cache_invalidate_post($post_id)
{
    global $hyper_invalidated_post_id;
    
    hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Called");

    if ($hyper_invalidated_post_id == $post_id) {
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Post was already invalidated");
        return;
    }

    $options = get_option('hyper');

    if ($options['expire_type'] == 'none')
    {
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Invalidation disabled");
        return;
    }

    if ($options['expire_type'] == 'post')
    {
        $post = get_post($post_id);

        $link = get_permalink($post_id);
        $link = substr($link, 7);
        $file = md5($link);

        @unlink(ABSPATH . 'wp-content/hyper-cache/' . $file . '.dat');
        @unlink(ABSPATH . 'wp-content/hyper-cache/pda' . $file . '.dat');
        @unlink(ABSPATH . 'wp-content/hyper-cache/iphone' . $file . '.dat');

        $hyper_invalidated_post_id = $post_id;

        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Post invalidated");

        if ($options['archive']) {

            hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Archive invalidation required");

            if (!touch(ABSPATH . 'wp-content/hyper-cache-invalidation-archive.dat'))
            {
                hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Unable to touch invalidation-archive.dat");
            }
            else
            {
                hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Touched invalidation-archive.dat");
            }
        }
        return;
    }

    if ($options['expire_type'] == 'all')
    {
        hyper_log("hyper_cache_invalidate_post(" . $post_id . ")> Full invalidation");
        hyper_cache_invalidate();
        return;
    }
}


// Completely remove a directory and it's content.
function hyper_delete_path($path) {
    if ($path == null) return;
    $handle = @opendir($path);
    if ($handle) {
        while ($file = readdir($handle)) {
            if ($file != '.' && $file != '..') {
                @unlink($path . '/' . $file);
            }
        }
        closedir($handle);
    //@rmdir($path);
    }
}

// Counts the number of file in to the hyper cache directory to give an idea of
// the number of pages cached.
function hyper_count() {
    $count = 0;
    //if (!is_dir(ABSPATH . 'wp-content/hyper-cache')) return 0;
    if ($handle = @opendir(ABSPATH . 'wp-content/hyper-cache')) {
        while ($file = readdir($handle)) {
            if ($file != '.' && $file != '..') {
                $count++;
            }
        }
        closedir($handle);
    }
    return $count;
}

add_action('switch_theme', 'hyper_cache_invalidate', 0);

add_action('edit_post', 'hyper_cache_invalidate_post', 0);
add_action('delete_post', 'hyper_cache_invalidate_post', 0);


// Capture and register if a redirect is sent back from WP, so the cache
// can cache (or ignore) it. Redirects were source of problems for blogs
// with more than one host name (eg. domain.com and www.domain.com) comined
// with the use of Hyper Cache.
add_filter('redirect_canonical', 'hyper_redirect_canonical', 10, 2);
$hyper_redirect = null;
function hyper_redirect_canonical($redirect_url, $requested_url) {
    global $hyper_redirect;

    $hyper_redirect = $redirect_url;

    return $redirect_url;
}

function hyper_log($text) {
//    $file = fopen(dirname(__FILE__) . '/plugin.log', 'a');
//    fwrite($file, $text . "\n");
//    fclose($file);
}

?>
