<?php

$options = get_option('hyper');

@include(dirname(__FILE__) . '/languages/en_US.php');
if (!$options['_notranslation'] && WPLANG != '') @include(dirname(__FILE__) . '/languages/' . WPLANG . '.php');

function hyper_request($name, $default=null) 
{
    if (!isset($_POST[$name]))
    {
        return $default;
    }

    if (get_magic_quotes_gpc())
    {
        return hyper_stripslashes($_POST[$name]);
    }
    else
    {
        return $_POST[$name];
    }
}

function hyper_stripslashes($value) 
{
    $value = is_array($value)?array_map('hyper_stripslashes', $value):stripslashes($value);
    return $value;
}

function hyper_field_checkbox($name, $label='', $tips='', $attrs='') {
    global $options;

    echo '<th scope="row">';
    echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
    echo '<td><input type="checkbox" ' . $attrs . ' name="options[' . $name . ']" value="1" ' . ($options[$name]!= null?'checked':'') . '/>';
    echo ' ' . $tips;
    echo '</td>';
}

function hyper_field_text($name, $label='', $tips='', $attrs='') {
    global $options;
    if (strpos($attrs, 'size') === false) $attrs .= 'size="30"';
    echo '<th scope="row">';
    echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
    echo '<td><input type="text" ' . $attrs . ' name="options[' . $name . ']" value="' .
    htmlspecialchars($options[$name]) . '"/>';
    echo ' ' . $tips;
    echo '</td>';
}

function hyper_field_textarea($name, $label='', $tips='', $attrs='') {
    global $options;

    if (strpos($attrs, 'cols') === false) $attrs .= 'cols="70"';
    if (strpos($attrs, 'rows') === false) $attrs .= 'rows="5"';

    echo '<th scope="row">';
    echo '<label for="options[' . $name . ']">' . $label . '</label></th>';
    echo '<td><textarea wrap="off" ' . $attrs . ' name="options[' . $name . ']">' .
    htmlspecialchars($options[$name]) . '</textarea>';
    echo '<br />' . $tips;
    echo '</td>';
}

$installed = is_dir(ABSPATH . 'wp-content/hyper-cache') && is_file(ABSPATH . 'wp-content/advanced-cache.php') &&
@filesize(ABSPATH . 'wp-content/advanced-cache.php') == @filesize(ABSPATH . 'wp-content/plugins/hyper-cache/advanced-cache.php');


if ($installed && isset($_POST['clear'])) 
{
    hyper_cache_invalidate();
}


if ($installed && isset($_POST['save'])) 
{
    $options = hyper_request('options');

    if ($options['timeout'] == '' || !is_numeric($options['timeout']))
    {
        $options['timeout'] = 60;
    }

    if ($options['clean_interval'] == '' || !is_numeric($options['clean_interval']))
    {
        $options['clean_interval'] = 0;
    }

    $buffer = "<?php\n";
    $buffer .= '$hyper_cache_charset = "' . get_option('blog_charset') . '"' . ";\n";
    $buffer .= '$hyper_cache_enabled = ' . ($options['enabled']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_compress = ' . ($options['compress']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_timeout = ' . $options['timeout'] . ";\n";
    $buffer .= '$hyper_cache_cron_key = \'' . $options['cron_key'] . "';\n";
    $buffer .= '$hyper_cache_get = ' . ($options['get']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_redirects = ' . ($options['redirects']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_mobile = ' . ($options['mobile']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_feed = ' . ($options['feed']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_home = ' . ($options['home']?'true':'false') . ";\n";
    //$buffer .= '$hyper_cache_folder = \'' . $options['folder'] . "';\n";
    if (function_exists('gzencode'))
    {
        $buffer .= '$hyper_cache_gzip = ' . ($options['gzip']?'true':'false') . ";\n";
        $buffer .= '$hyper_cache_storage = \'' . $options['storage'] . "';\n";
    }
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
    if ($options['timeout'] == '')
    {
        $options['timeout'] = 60;
    }
    if ($options['clean_interval'] == '')
    {
        $options['clean_interval'] = 1440;
    }
    if ($options['mobile_agents'] == '')
    {
        $options['mobile_agents'] = "elaine/3.0\niphone\nipod\npalm\neudoraweb\nblazer\navantgo\nwindows ce\ncellphone\nsmall\nmmef20\ndanger\nhiptop\nproxinet\nnewt\npalmos\nnetfront\nsharp-tq-gx10\nsonyericsson\nsymbianos\nup.browser\nup.link\nts21i-10\nmot-v\nportalmmm\ndocomo\nopera mini\npalm\nhandspring\nnokia\nkyocera\nsamsung\nmotorola\nmot\nsmartphone\nblackberry\nwap\nplaystation portable\nlg\nmmp\nopwv\nsymbian\nepoc";
    }
}

?>
<div class="wrap">
    <form method="post">
        <h2>Hyper Cache</h2>

<?php
if (!$installed)
{
    echo '<div class="alert error" style="margin-top:10px;"><p>';
    echo $hyper_labels['not_activated'];
    echo '</p></div>';
}
?>

<?php
if (!defined('WP_CACHE') ) {
    echo '<div class="alert error" style="margin-top:10px;"><p>';
    echo $hyper_labels['wp_cache_not_enabled'];
    echo "<pre>define('WP_CACHE', true);</pre>";
    echo '</p></div>';
}
?>
        <p>To have more information about Hyper Cache read the <a href="http://www.satollo.com/english/wordpress/hyper-cache">official plugin page</a>
        or write me to info@satollo.com.</p>
        

        <p>Consider a <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2545483">DONATION</a> to support this plugin or
        if it <strong>saved you a lot of money</strong> avoiding expensive hosting providers to sustain your blog traffic...</p>

        <p>
            My other plugins:
            <a href="http://www.satollo.com/english/wordpress/post-layout">Post Layout</a>,
            <a href="http://www.satollo.com/english/wordpress/feed-layout">Feed Layout</a>,
        </p>

        <p>Check the advanced options if you are using the Global Translator Plugin or if you are NOT using
        permalinks (eg. IIS hosted blogs).</p>

        <h3><?php echo $hyper_labels['configuration']; ?></h3>
        <table class="form-table">
            <tr valign="top">
                <?php hyper_field_checkbox('_notranslation', $hyper_labels['_notranslation']); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_checkbox('enabled', $hyper_labels['activate']); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_text('timeout', $hyper_labels['timeout'], $hyper_labels['timeout_desc'], 'size="5"'); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_text('clean_interval', $hyper_labels['clean_interval'], $hyper_labels['clean_interval_desc'], 'size="5"'); ?>
            </tr>

            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['expire_type']; ?></label></th>
                <td>
                    <select name="options[expire_type]">
                        <option value="post_strictly" <?php echo ($options['expire_type'] == 'post_strictly')?'selected':''; ?>>Single pages strictly</option>
                        <option value="post" <?php echo ($options['expire_type'] == 'post')?'selected':''; ?>>Single pages</option>
                        <option value="all" <?php echo ($options['expire_type'] == 'all')?'selected':''; ?>>All</option>
                        <option value="none" <?php echo ($options['expire_type'] == 'none')?'selected':''; ?>>None</option>
                    </select><br />
                    <?php echo $hyper_labels['expire_type_desc']; ?>
                </td>
            </tr>
            <tr valign="top">
                <?php hyper_field_checkbox('feed', $hyper_labels['feed'], $hyper_labels['feed_desc']); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_checkbox('compress', $hyper_labels['compress_html'], $hyper_labels['compress_html_desc']); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_checkbox('mobile', $hyper_labels['mobile']); ?>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['mobile_agents']; ?></label></th>
                <td>
                    <textarea wrap="off" rows="5" cols="70" name="options[mobile_agents]"><?php echo htmlspecialchars($options['mobile_agents']); ?></textarea>
                    <br />
                    <?php echo $hyper_labels['mobile_agents_desc']; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['gzip']; ?></label></th>
                <td>
                    <?php if (function_exists('gzencode')) { ?>
                    <input type="checkbox" name="options[gzip]" value="1" <?php echo $options['gzip']!=null?'checked':''; ?> />
                    <br />
                    <?php echo $hyper_labels['gzip_desc']; ?>
                    <?php } else { ?>
                    <?php echo $hyper_labels['gzip_nogzencode_desc']; ?>
                    <?php } ?>
                </td>
            </tr>

            <!--
<tr valign="top">
<?php hyper_field_text('folder', $hyper_labels['folder'], $hyper_labels['folder_desc'], 'size="5"'); ?>
</tr>
            -->
            <tr valign="top">
                <th scope="row"><?php echo $hyper_labels['count']; ?></th>
                <td><?php echo hyper_count(); ?></td>
            </tr>
        </table>


        <h3><?php echo $hyper_labels['advanced_options']; ?></h3>
        <table class="form-table">
            <tr valign="top">
                <?php hyper_field_checkbox('home', $hyper_labels['home'], $hyper_labels['home_desc']); ?>
            </tr>
            <tr valign="top">
                <?php hyper_field_checkbox('redirects', $hyper_labels['redirects'], $hyper_labels['redirects_desc']); ?>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['storage']; ?></label></th>
                <td>
                    <?php if (function_exists('gzencode')) { ?>
                    <select name="options[storage]">
                        <option value="default" <?php echo ($options['storage'] == 'default')?'selected':''; ?>>Default</option>
                        <option value="minimize" <?php echo ($options['storage'] == 'minimize')?'selected':''; ?>>Minimize the disk space</option>
                    </select>
                    <?php } else { ?>
                    <?php echo $hyper_labels['storage_nogzencode_desc']; ?>
                    <?php } ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['urls_analysis']; ?></label></th>
                <td>
                    <select name="options[urls]">
                        <option value="default" <?php echo ($options['urls'] == 'default')?'selected':''; ?>><?php echo $hyper_labels['urls_analysis_default']; ?></option>
                        <option value="full" <?php echo ($options['urls'] == 'full')?'selected':''; ?>><?php echo $hyper_labels['urls_analysis_full']; ?></option>
                        <!--
<option value="removeqs" <?php echo ($options['removeqs'] == 'full')?'selected':''; ?>><?php echo $hyper_labels['urls_analysis_removeqs']; ?></option>
                        -->
                    </select>
                    <br />
                    <?php echo $hyper_labels['urls_analysis_desc']; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label><?php echo $hyper_labels['reject']; ?></label></th>
                <td>
                    <textarea wrap="off" rows="5" cols="70" name="options[reject]"><?php echo htmlspecialchars($options['reject']); ?></textarea>
                    <br />
                    <?php echo $hyper_labels['reject_desc']; ?>

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
                <th scope="row"><label><?php echo $hyper_labels['reject_agents']; ?></label></th>
                <td>
                    <textarea wrap="off" rows="5" cols="70" name="options[reject_agents]"><?php echo htmlspecialchars($options['reject_agents']); ?></textarea>
                    <br />
                    <?php echo $hyper_labels['reject_agents_desc']; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label>Cookies that bypass Hyper Cache</label></th>
                <td>
                    <textarea wrap="off" rows="5" cols="70" name="options[reject_cookies]"><?php echo htmlspecialchars($options['reject_cookies']); ?></textarea>
                    <br />
                    One per line insert cookie names that, if present, bypass the cache. The names specifies will
                    match if there is at least one cookie name <strong>starting</strong> with such name.
                    <?php if (defined('FBC_APP_KEY_OPTION')) { ?>
                    <br />
                    It seems you have <strong>Facebook Connect</strong> plugin installed. Add this cookie name to make it works
                    with Hyper Cache:<br />
                    <strong><?php echo get_option(FBC_APP_KEY_OPTION); ?>_user</strong>
                    <? } ?>

                </td>
            </tr>
            <tr valign="top">
                <?php hyper_field_text('cron_key', $hyper_labels['cron_key'], $hyper_labels['cron_key_desc'], 'size="30"'); ?>
            </tr>

        </table>


        <p class="submit">
            <input class="button" type="submit" name="save" value="<?php echo $hyper_labels['save']; ?>">
            <input class="button" type="submit" name="clear" value="<?php echo $hyper_labels['clear']; ?>">
        </p>
    </form>
</div>
