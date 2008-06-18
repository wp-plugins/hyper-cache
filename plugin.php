<?php
/*
Plugin Name: Hyper Cache
Plugin URI: http://www.satollo.com/english/wordpress/hyper-cache
Description: Hyper Cache is an extremely aggressive cache for WordPress.
Version: 1.0.9
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


add_action('activate_hyper-cache/plugin.php', 'hyper_activate');
function hyper_activate() {
    if (!file_exists(ABSPATH . '/wp-content/hyper-cache')) {
        mkdir(ABSPATH . '/wp-content/hyper-cache', 0766);
    }
	hyper_cache_invalidate();
	
    // Write the advanced-cache.php (so we grant it's the correct version)
    $buffer = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
    $file = fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    
    fwrite($file, $buffer);
    fclose($file);	
}


add_action('deactivate_hyper-cache/plugin.php', 'hyper_deactivate');
function hyper_deactivate() {
	delete_option('hyper');

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

function hyper_cache_invalidate($force=false) 
{
	global $hyper_options;
	
	if (!$force && $hyper_options['not_expire_on_actions']) return;
	
	$path = ABSPATH . 'wp-content/' . time();
	rename(ABSPATH . 'wp-content/hyper-cache', $path);
	hyper_delete_path( $path );
	mkdir(ABSPATH . 'wp-content/hyper-cache', 0766);
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


if ( $hyper_options['cache'] && !$hyper_options['not_expire_on_actions']) 
{
	// Posts
	add_action('publish_post', 'hyper_cache_invalidate', 0);
	add_action('edit_post', 'hyper_cache_invalidate', 0);
	add_action('delete_post', 'hyper_cache_invalidate', 0);
	add_action('publish_phone', 'hyper_cache_invalidate', 0);
	
	// Coment ID is received
	add_action('trackback_post', 'hyper_cache_invalidate', 0);
	add_action('pingback_post', 'hyper_cache_invalidate', 0);
	add_action('comment_post', 'hyper_cache_invalidate', 0);
	add_action('edit_comment', 'hyper_cache_invalidate', 0);
	add_action('wp_set_comment_status', 'hyper_cache_invalidate', 0);
	
	// No post_id is available
	add_action('delete_comment', 'hyper_cache_invalidate', 0);
	add_action('switch_theme', 'hyper_cache_invalidate', 0);
}
?>
