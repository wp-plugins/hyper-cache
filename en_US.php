<?php

$hyper_labels['wp_cache_not_enabled'] = "The wordPress cache system is not enabled. Please, activate it adding the line of code below in the file wp-config.php. Thank you!";
$hyper_labels['configuration'] = "Configuration";
$hyper_labels['activate'] = "Activate the cache?";
$hyper_labels['timeout'] = "Expire a cached page after";
$hyper_labels['timeout_desc'] = "minutes (set to zero to never expire)";
$hyper_labels['count'] = "Total cached pages (cached redirect is counted too)";
$hyper_labels['save'] = "Save";
//$hyper_labels['store'] = "Store pages as";
//$hyper_labels['folder'] = "Cache folder";
$hyper_labels['gzip'] = "Gzip compression";
$hyper_labels['gzip_desc'] = "Send gzip compressed pages to enabled browsers";
$hyper_labels['clear'] = "Clear the cache";
$hyper_labels['compress_html'] = "Optimize HTML";
$hyper_labels['compress_html_desc'] = "Try to optimize the HTML removing unuseful spaces. Do not use if you are using &lt;pre&gt; tags in the posts";
$hyper_labels['redirects'] = "Cache the WP redirects";
$hyper_labels['redirects_desc'] = "Can give problems with some configuration. Try and hope.";
$hyper_labels['mobile'] = "Detetect and cache for mobile devices";
$hyper_labels['clean_interval'] = "Autoclean every";
$hyper_labels['clean_interval_desc'] = "minutes (set to zero to disable)";
$hyper_labels['not_activated'] = "Hyper Cache is NOT correctly installed: some files or directories have not been created. Check if the wp-content directory is writable and remove any advanced-cache.php file into it. Deactivate and reactivate the plugin.";
$hyper_labels['expire_type'] = "What cached pages to delete on events";
$hyper_labels['expire_type_desc'] = "<b>none</b>: the cache never delete the cached page on events (comments, new posts, and so on)<br />";
$hyper_labels['expire_type_desc'] .= "<b>single pages</b>: the cached pages relative to the post modified (by the editor or when a comment is added) plus the home page. New published posts invalidate all the cache.<br />";
$hyper_labels['expire_type_desc'] .= "<b>single pages strictly</b>: as 'single pages' but without to invalidate all the cache on new posts publishing.<br />";
$hyper_labels['expire_type_desc'] .= "<b>all</b>: all the cached pages (the blog is always up to date)<br />";
$hyper_labels['expire_type_desc'] .= "Beware: when you use 'single pages strictly', a new post will appear on home page, but not on category and tag pages. If you use the 'last posts' widget/feature on sidebar it won't show updated.";
$hyper_labels['advanced_options'] = "Advanced options";
$hyper_labels['reject'] = "URI to reject";
$hyper_labels['reject_desc'] = "One per line. When a URI (eg. /video/my-new-performance) starts with one of the listed lines, it won't be cached.";

$hyper_labels['home'] = "Do not cache the home";
$hyper_labels['home_desc'] = "Enabling this option, the home page and the subsequent pages for older posts will not be cached.";

$hyper_labels['feed'] = "Cache the feed?";
$hyper_labels['feed_desc'] = "Usually not, so we are sure to feed always an updated feed even if we do a strong cache of the web pages";

?>
