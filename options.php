<?php

$options = get_option('hyper');

if (!$options['notranslation'])
{
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('hyper-cache', 'wp-content/plugins/' . $plugin_dir, $plugin_dir);
}

$installed = is_dir(ABSPATH . 'wp-content/hyper-cache') && is_file(ABSPATH . 'wp-content/advanced-cache.php') &&
@filesize(ABSPATH . 'wp-content/advanced-cache.php') == @filesize(ABSPATH . 'wp-content/plugins/hyper-cache/advanced-cache.php');

if ($installed && isset($_POST['clean']))
{
    hyper_cache_invalidate();
}


if ($installed && isset($_POST['save'])) 
{
    if (!check_admin_referer()) die('No hacking please');
    
    $options = stripslashes_deep($_POST['options']);

    if (!is_numeric($options['timeout'])) $options['timeout'] = 60;
    $options['timeout'] = (int)$options['timeout'];

    if (!is_numeric($options['clean_interval'])) $options['clean_interval'] = 60;
    $options['clean_interval'] = (int)$options['clean_interval'];

    $buffer = "<?php\n";
    $buffer .= '$hyper_cache_charset = "' . get_option('blog_charset') . '"' . ";\n";
    $buffer .= '$hyper_cache_enabled = true' . ";\n";
    $buffer .= '$hyper_cache_stats = ' . ($options['stats']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_comment = ' . ($options['comment']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_compress = ' . ($options['compress']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_timeout = ' . $options['timeout'] . ";\n";
    $buffer .= '$hyper_cache_cron_key = \'' . $options['cron_key'] . "';\n";
    $buffer .= '$hyper_cache_get = ' . ($options['get']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_redirects = ' . ($options['redirects']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_mobile = ' . ($options['mobile']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_feed = ' . ($options['feed']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_cache_qs = ' . ($options['cache_qs']?'true':'false') . ";\n";

    $buffer .= '$hyper_cache_home = ' . ($options['home']?'true':'false') . ";\n";
    //$buffer .= '$hyper_cache_folder = \'' . $options['folder'] . "';\n";

    if ($options['gzip']) $options['store_compressed'] = 1;
    
    $buffer .= '$hyper_cache_gzip = ' . ($options['gzip']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_store_compressed = ' . ($options['store_compressed']?'true':'false') . ";\n";
    
    $buffer .= '$hyper_cache_urls = \'' . $options['urls'] . "';\n";
    $buffer .= '$hyper_cache_folder = \'' . ABSPATH . 'wp-content/hyper-cache' . "';\n";
    $buffer .= '$hyper_cache_clean_interval = ' . $options['clean_interval'] . ";\n";

    if (trim($options['reject']) != '')
    {
        $options['reject'] = str_replace(' ', "\n", $options['reject']);
        $options['reject'] = str_replace("\r", "\n", $options['reject']);
        $buffer .= '$hyper_cache_reject = array(';
        $reject = explode("\n", $options['reject']);
        $options['reject'] = '';
        foreach ($reject as $uri)
        {
            $uri = trim($uri);
            if ($uri == '') continue;
            $buffer .= "\"" . addslashes(trim($uri)) . "\",";
            $options['reject'] .= $uri . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }

    if (trim($options['reject_agents']) != '')
    {
        $options['reject_agents'] = str_replace(' ', "\n", $options['reject_agents']);
        $options['reject_agents'] = str_replace("\r", "\n", $options['reject_agents']);
        $buffer .= '$hyper_cache_reject_agents = array(';
        $reject_agents = explode("\n", $options['reject_agents']);
        $options['reject_agents'] = '';
        foreach ($reject_agents as $uri)
        {
            $uri = trim($uri);
            if ($uri == '') continue;
            $buffer .= "\"" . addslashes(strtolower(trim($uri))) . "\",";
            $options['reject_agents'] .= $uri . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }

    if (trim($options['reject_cookies']) != '')
    {
        $options['reject_cookies'] = str_replace(' ', "\n", $options['reject_cookies']);
        $options['reject_cookies'] = str_replace("\r", "\n", $options['reject_cookies']);
        $buffer .= '$hyper_cache_reject_cookies = array(';
        $reject_cookies = explode("\n", $options['reject_cookies']);
        $options['reject_cookies'] = '';
        foreach ($reject_cookies as $c)
        {
            $c = trim($c);
            if ($c == '') continue;
            $buffer .= "\"" . addslashes(strtolower(trim($c))) . "\",";
            $options['reject_cookies'] .= $c . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }

    if (trim($options['mobile_agents']) != '')
    {
        $options['mobile_agents'] = str_replace(',', "\n", $options['mobile_agents']);
        $options['mobile_agents'] = str_replace("\r", "\n", $options['mobile_agents']);
        $buffer .= '$hyper_cache_mobile_agents = array(';
        $mobile_agents = explode("\n", $options['mobile_agents']);
        $options['mobile_agents'] = '';
        foreach ($mobile_agents as $uri)
        {
            $uri = trim($uri);
            if ($uri == '') continue;
            $buffer .= "\"" . addslashes(strtolower(trim($uri))) . "\",";
            $options['mobile_agents'] .= $uri . "\n";
        }
        $buffer = rtrim($buffer, ',');
        $buffer .= ");\n";
    }

    $buffer .= '?>';
    $file = fopen(ABSPATH . 'wp-content/hyper-cache-config.php', 'w');
    fwrite($file, $buffer);
    fclose($file);
    update_option('hyper', $options);
} 
else 
{
    if ($options['mobile_agents'] == '')
    {
        $options['mobile_agents'] = "elaine/3.0\niphone\nipod\npalm\neudoraweb\nblazer\navantgo\nwindows ce\ncellphone\nsmall\nmmef20\ndanger\nhiptop\nproxinet\nnewt\npalmos\nnetfront\nsharp-tq-gx10\nsonyericsson\nsymbianos\nup.browser\nup.link\nts21i-10\nmot-v\nportalmmm\ndocomo\nopera mini\npalm\nhandspring\nnokia\nkyocera\nsamsung\nmotorola\nmot\nsmartphone\nblackberry\nwap\nplaystation portable\nlg\nmmp\nopwv\nsymbian\nepoc";
    }
}

?>
<div class="wrap">

<h2>Hyper Cache</h2>

<?php if (!$installed) { ?>
    <div class="alert error" style="margin-top:10px;">
    <?php _e('Hyper Cache is NOT correctly installed: some files or directories have not been created. Check if the wp-content directory is writable and remove any advanced-cache.php file into it. Deactivate and reactivate the plugin.'); ?>
    </div>
<?php } ?>

<?php if (!defined('WP_CACHE') || !WP_CACHE) { ?>
    <div class="alert error" style="margin-top:10px;">
    <?php _e('The WordPress cache system is not enabled! Please, activate it adding the line of code below in the file wp-config.php.'); ?>
    <pre>define('WP_CACHE', true);</pre>
    </div>
<?php } ?>

<div style="padding: 10px; background-color: #E0EFF6; border: 1px solid #006">
    <?php printf(__('<strong>And if this plugin stops to work?</strong><br />Hyper Cache required a lot of effort to be developed and
    I\'m pretty sure is giving you a good, even if invisible, service.
    Probably Hyper Cache makes you save money with your hosting provider using a
    basic and cheap hosting plan intead of a bigger and expensive one.
    <br />So, why not consider a <a href="%s"><strong>donation</strong></a>?', 'hyper-cache'),
    'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2545483'); ?>
</div>

<p>
    <?php printf(__('You can find more details about configurations and working mode
    on <a href="%s">Hyper Cache official page</a>.', 'hyper-cache'),
    'http://www.satollo.net/plugins/hyper-cache'); ?>
</p>

<p>
    <?php _e('Other interesting plugins:'); ?>
    <a href="http://www.satollo.net/plugins/post-layout">Post Layout</a>,
    <a href="http://www.satollo.net/plugins/postacards">Postcards</a>,
    <a href="http://www.satollo.net/plugins/comment-notifier">Comment Notifier</a>,
    <a href="http://www.satollo.net/plugins/comment-image">Comment Image</a>.
</p>

<form method="post">
<?php wp_nonce_field(); ?>

<h3><?php _e('Cache status', 'hyper-cache'); ?></h3>
<table class="form-table">
<tr valign="top">
    <th><?php _e('Cached page count', 'hyper-cache'); ?></th>
    <td><?php echo hyper_count(); ?></td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="clean" value="<?php _e('Clean the cache', 'hyper-cache'); ?>">
</p>


<h3><?php _e('Statistics', 'hyper-cache'); ?></h3>

<table class="form-table">
<tr valign="top">
    <th><?php _e('Enable statistics collection', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[stats]" value="1" <?php echo $options['stats']?'checked':''; ?>/>
        <br />
        <?php _e('Very experimental and not really efficient,
        but can be useful to check how the cache works.', 'hyper-cache'); ?>
        <?php _e('Many .txt files willbe created inside the wp-content folder,
        you can safely delete them if you need.', 'hyper-cache'); ?>
    </td>
</tr>
</table>

<?php if ($options['stats']) { ?>

<?php
$hit_304 = @filesize(ABSPATH . 'wp-content/hyper-cache-304.txt');
$hit_404 = @filesize(ABSPATH . 'wp-content/hyper-cache-404.txt');
$hit_gzip = @filesize(ABSPATH . 'wp-content/hyper-cache-gzip.txt');
$hit_plain = @filesize(ABSPATH . 'wp-content/hyper-cache-plain.txt');
$hit_wp = @filesize(ABSPATH . 'wp-content/hyper-cache-wp.txt');
$hit_commenter = @filesize(ABSPATH . 'wp-content/hyper-cache-commenter.txt');
$total = (float)($hit_304 + $hit_404 + $hit_gzip + $hit_plain + $hit_wp + 1);
?>

<p>
<?php _e('Below are statitics about requests Hyper Cache can handle and the ratio between the
requests served by Hyper Cache and the ones served by WordPress.', 'hyper-cache'); ?>

<?php _e('Requests that bypass the cache due to configurations are not counted because they are
explicitely not cacheable.', 'hyper-cache'); ?>
</p>
<table cellspacing="5">
<tr><td>Cache hits</td><td><div style="width:<?php echo (int)(($total-$hit_wp)/$total*300); ?>px; background-color: #0f0; float: left;">&nbsp;</div> <?php echo (int)(($total-$hit_wp)/$total*100); ?>%</td></tr>
<tr><td>Cache misses</td><td><div style="width:<?php echo (int)(($hit_wp)/$total*300); ?>px; background-color: #f00; float: left;">&nbsp;</div> <?php echo (int)(($hit_wp)/$total*100); ?>%</td></tr>
</table>

<p><?php _e('Detailed data broken up on different types of cache hits'); ?></p>

<table cellspacing="5">
<tr><td>Total requests handled</td><td><?php echo $total; ?></td></tr>
<tr><td>304 responses</td><td><div style="width:<?php echo (int)(($hit_304)/$total*300); ?>px; background-color: #339; float: left;">&nbsp;</div><?php echo (int)($hit_304/$total*100); ?>% (<?php echo $hit_304; ?>)</td></tr>
<tr><td>404 responses</td><td><div style="width:<?php echo (int)(($hit_404)/$total*300); ?>px; background-color: #33b; float: left;">&nbsp;</div><?php echo (int)($hit_404/$total*100); ?>% (<?php echo $hit_404; ?>)</td></tr>
<tr><td>Compressed pages served</td><td><div style="width:<?php echo (int)(($hit_gzip)/$total*300); ?>px; background-color: #33d; float: left;">&nbsp;</div><?php echo (int)($hit_gzip/$total*100); ?>% (<?php echo $hit_gzip; ?>)</td></tr>
<tr><td>Plain pages served</td><td><div style="width:<?php echo (int)(($hit_plain)/$total*300); ?>px; background-color: #33f; float: left;">&nbsp;</div><?php echo (int)($hit_plain/$total*100); ?>% (<?php echo $hit_plain; ?>)</td></tr>
<tr><td>Not in cache</td><td><div style="width:<?php echo (int)(($hit_wp)/$total*300); ?>px; background-color: #f00; float: left;">&nbsp;</div><?php echo (int)($hit_wp/$total*100); ?>% (<?php echo $hit_wp; ?>)</td></tr>
</table>

<?php } ?>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>

<h3><?php _e('Configuration'); ?></h3>

<table class="form-table">

<tr valign="top">
    <th><?php _e('Cached pages timeout', 'hyper-cache'); ?></th>
    <td>
        <input type="text" size="5" name="options[timeout]" value="<?php echo htmlspecialchars($options['timeout']); ?>"/>
        (<?php _e('minutes', 'hyper-cache'); ?>)
        <br />
        <?php _e('Minutes a cached page is valid and served to users. A zero value means a cached page is
        valid forever.', 'hyper-cache'); ?>
        <?php _e('If a cached page is older than specified value (expired) it is no more used and
        will be regenerated on next request of it.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Cache autoclean', 'hyper-cache'); ?></th>
    <td>
        <input type="text" size="5" name="options[clean_interval]" value="<?php echo htmlspecialchars($options['clean_interval']); ?>"/>
        (<?php _e('minutes', 'hyper-cache'); ?>)
        <br />
        <?php _e('Frequency of the autoclean process which removes to expired cached pages to free
        disk space.', 'hyper-cache'); ?>
        <?php _e('Set lower or equals of timeout above. If set to zero the autoclean process never
        runs.', 'hyper-cache'); ?>
        <?php _e('If timeout is set to zero, autoclean never runs, so this value has no meaning', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Cache invalidation mode', 'hyper-cache'); ?></th>
    <td>
        <select name="options[expire_type]">
            <option value="all" <?php echo ($options['expire_type'] == 'all')?'selected':''; ?>><?php _e('All cached pages', 'hyper-cache'); ?></option>
            <option value="post" <?php echo ($options['expire_type'] == 'post')?'selected':''; ?>><?php _e('Modified pages and home page', 'hyper-cache'); ?></option>
            <option value="post_strictly" <?php echo ($options['expire_type'] == 'post_strictly')?'selected':''; ?>><?php _e('Only modified pages', 'hyper-cache'); ?></option>
            <option value="none" <?php echo ($options['expire_type'] == 'none')?'selected':''; ?>><?php _e('Nothing', 'hyper-cache'); ?></option>
        </select>
        <br />
        <?php _e('"Invalidation" is the process of deleting cached pages when they are no more valid.', 'hyper-cache'); ?>
        <?php _e('Invalidation process is started when blog contents are modified (new post, post update, new comment,...) so
        one or more cached pages need to be refreshed to get that new content.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Disable cache for commenters', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[comment]" value="1" <?php echo $options['comment']?'checked':''; ?>/>
        <br />
        <?php _e('When users leave comments, WordPress show pages with their comments even if in moderation
        (and not visible to others) and pre-fills the comment form.', 'hyper-cache'); ?>
        <?php _e('If you want to keep those features, enable this option.', 'hyper-cache'); ?>
        <?php _e('The caching system will be less efficient but the blog more usable.'); ?>

    </td>
</tr>

<tr valign="top">
    <th><?php _e('Feeds caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[feed]" value="1" <?php echo $options['feed']?'checked':''; ?>/>
        <br />
        <?php _e('When enabled the blog feeds will be cache as well.', 'hyper-cache'); ?>
        <?php _e('Usually this options has to be left unchecked but if your blog is rather static,
        you can enable it and have a bit more efficiency', 'hyper-cache'); ?>
    </td>    
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>

<h3><?php _e('Configuration for mobile devices', 'hyper-cache'); ?></h3>
<table class="form-table">
<tr valign="top">
    <th><?php _e('Detect mobile devices', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[mobile]" value="1" <?php echo $options['mobile']?'checked':''; ?>/>
        <br />
        <?php _e('When enabled mobile devices will be detected and the cached page stored under different name.', 'hyper-cache'); ?>
        <?php _e('This makes blogs with different themes for mobile devices to work correctly.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Mobile agent list', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="4" cols="70" name="options[mobile_agents]"><?php echo htmlspecialchars($options['mobile_agents']); ?></textarea>
        <br />
        <?php _e('One per line mobile agents to check for when a page is requested.', 'hyper-cache'); ?>
        <?php _e('The mobile agent string is matched against the agent a device is sending to the server.', 'hyper-cache'); ?>
    </td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>


<h3><?php _e('Compression', 'hyper-cache'); ?></h3>

<?php if (!function_exists('gzencode')) { ?>

<p><?php _e('Your hosting space has not the "gzencode" function, so no compression options are available.', 'hyper-cache'); ?></p>

<?php } else { ?>

<table class="form-table">
<tr valign="top">
    <th><?php _e('Enable compression', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[gzip]" value="1" <?php echo $options['gzip']?'checked':''; ?> />
        <br />
        <?php _e('When possible the page will be sent compressed to save bandwidth.', 'hyper-cache'); ?>
        <?php _e('Only the textual part of a page can be compressed, not images, so a photo
        blog will consume a lot of bandwidth even with compression enabled.', 'hyper-cache'); ?>
        <?php _e('Leave the options disabled if you note malfunctions, like blank pages.', 'hyper-cache'); ?>
        <br />
        <?php _e('If you enable this option, the option below will be enabled as well.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Disk space usage', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[store_compressed]" value="1" <?php echo $options['store_compressed']?'checked':''; ?> />
        <br />
        <?php _e('Enable this option to minimize disk space usage.', 'hyper-cache'); ?>
        <?php _e('The cache will be a little less performant.', 'hyper-cache'); ?>
        <?php _e('Leave the options disabled if you note malfunctions, like blank pages.', 'hyper-cache'); ?>
    </td>
</tr>
</table>
<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>
<?php } ?>


<h3><?php _e('Advanced options', 'hyper-cache'); ?></h3>

<table class="form-table">
<tr valign="top">
    <th><?php _e('Translation', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[notranslation]" value="1" <?php echo $options['notranslation']?'checked':''; ?>/>
        <br />
        <?php _e('DO NOT show this panel translated.', 'hyper-cache'); ?>
    </td>
</tr>
<tr valign="top">
    <th><?php _e('HTML optimization', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[compress]" value="1" <?php echo $options['compress']?'checked':''; ?>/>
        <br />
        <?php _e('Try to optimize the generated HTML.','hyper-cache'); ?>
        <?php _e('Be sure to extensively verify it it works with your theme on different browsers!', 'hyper-cache'); ?>
        <?php _e('NO MORE EFFECTIVE DUE TO COMPATIBILITY PROBLEMS', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Home caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[home]" value="1" <?php echo $options['home']?'checked':''; ?>/>
        <br />
        <?php _e('DO NOT cache the home page so it is always fresh.','hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Redirect caching', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[redirects]" value="1" <?php echo $options['redirects']?'checked':''; ?>/>
        <br />
        <?php _e('Cache WordPress redirects.', 'hyper-cache'); ?>
        <?php _e('WordPress sometime sends back redirects that can be cached to avoid further processing time.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('URL with parameters', 'hyper-cache'); ?></th>
    <td>
        <input type="checkbox" name="options[cache_qs]" value="1" <?php echo $options['cache_qs']?'checked':''; ?>/>
        <br />
        <?php _e('Cache requests with query string (parameters).', 'hyper-cache'); ?>
        <?php _e('This option has to be enabled for blogs which have post URLs with a question mark on them.', 'hyper-cache'); ?>
        <?php _e('This option is disabled by default because there is plugins which use
        URL parameter to perform specific action that cannot be cached', 'hyper-cache'); ?>
        <?php _e('For who is using search engines friendly permalink format is safe to
        leave this option disabled, no performances will be lost.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('URI to reject', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject]"><?php echo htmlspecialchars($options['reject']); ?></textarea>
        <br />
        <?php _e('Write one URI per line, each URI has to start with a slash.', 'hyper-cache'); ?>
        <?php _e('A specified URI will match the requested URI if the latter starts with the former.', 'hyper-cache'); ?>
        <?php _e('If you want to specify a stric matching, surround the URI with double quotes.', 'hyper-cache'); ?>

        <?php
        $languages = get_option('gltr_preferred_languages');
        if (is_array($languages))
        {
            echo '<br />';
            $home = get_option('home');
            $x = strpos($home, '/', 8); // skips http://
            $base = '';
            if ($x !== false) $base = substr($home, $x);
            echo 'It seems you have Global Translator installed. The URI prefixes below can be added to avoid double caching of translated pages:<br />';
            foreach($languages as $l) echo $base . '/' . $l . '/ ';
        }
        ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Agents to reject', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject_agents]"><?php echo htmlspecialchars($options['reject_agents']); ?></textarea>
        <br />
        <?php _e('Write one agent per line.', 'hyper-cache'); ?>
        <?php _e('A specified agent will match the client agent if the latter contains the former. The matching is case insensitive.', 'hyper-cache'); ?>
    </td>
</tr>

<tr valign="top">
    <th><?php _e('Cookies matching', 'hyper-cache'); ?></th>
    <td>
        <textarea wrap="off" rows="5" cols="70" name="options[reject_cookies]"><?php echo htmlspecialchars($options['reject_cookies']); ?></textarea>
        <br />
        <?php _e('Write one cookie name per line.', 'hyper-cache'); ?>
        <?php _e('When a specified cookie will match one of the cookie names sent bby the client the cache stops.', 'hyper-cache'); ?>
        <?php if (defined('FBC_APP_KEY_OPTION')) { ?>
        <br />
        <?php _e('It seems you have Facebook Connect plugin installed. Add this cookie name to make it works
        with Hyper Cache:', 'hyper-cache'); ?>
        <br />
        <strong><?php echo get_option(FBC_APP_KEY_OPTION); ?>_user</strong>
        <?php } ?>

    </td>
</tr>

</table>

<p class="submit">
    <input class="button" type="submit" name="save" value="<?php _e('Update'); ?>">
</p>
</form>
</div>
