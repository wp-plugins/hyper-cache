<?php
//if (function_exists('load_plugin_textdomain')) {
//    load_plugin_textdomain('hyper-cache', 'wp-content/plugins/hyper-cache');
//}

include(ABSPATH . 'wp-content/plugins/hyper-cache/en_US.php');
if (WPLANG) include(ABSPATH . 'wp-content/plugins/hyper-cache/' . WPLANG . '.php');

function hyper_request( $name, $default=null ) {
    if ( !isset($_POST[$name]) ) {
    	return $default;
    }
    if ( get_magic_quotes_gpc() ) {
    	return hyper_stripslashes($_POST[$name]);
    }
    else {
    	return $_POST[$name];
    }
}

function hyper_stripslashes($value) {
    $value = is_array($value) ? array_map('hyper_stripslashes', $value) : stripslashes($value);
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

if (isset($_POST['clear'])) {
    hyper_cache_invalidate(true);
}

if (isset($_POST['save'])) {
    $options = hyper_request('options');
    update_option('hyper', $options);

    if (!$options['timeout'] || !is_numeric($options['timeout'])) 
    {
    	$options['timeout'] = 60;
    }
    
    $buffer = "<?php\n";
    $buffer .= '$hyper_cache_enabled = ' . ($options['cache']?'true':'false') . ";\n";
    $buffer .= '$hyper_compress = ' . ($options['compress']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_timeout = ' . $options['timeout'] . ";\n";
    $buffer .= '$hyper_cache_get = ' . ($options['get']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_gzip = ' . ($options['gzip']?'true':'false') . ";\n";
    $buffer .= '?>';
    $file = fopen(ABSPATH . 'wp-content/hyper-cache-config.php', 'w');
    fwrite($file, $buffer);
    fclose($file);

} 
else 
{
    $options = get_option('hyper');
    if (!$options['timeout']) 
    {
    	$options['timeout'] = 60;
    }
}
?>
<div class="wrap">
    <form method="post">
        <h2>Hyper Cache</h2>
        
        <?php
        if (!defined('WP_CACHE') ) {
            echo '<div class="alert error" style="margin-top:10px;"><p>';
            echo $hyper_labels['wp_cache_not_enabled'];
            echo "<pre>define('WP_CACHE', true);</pre>";
            echo '</p></div>';
        }
        ?>

        <h3><?php echo $hyper_labels['configuration']; ?></h3>
        <table class="form-table">
			<tr valign="top">
        		<?php hyper_field_checkbox('cache', $hyper_labels['activate']); ?>
        	</tr>
        	<tr valign="top">
       			<?php hyper_field_text('timeout', $hyper_labels['expire'], $hyper_labels['minutes'], 'size="5"'); ?>
       		</tr>
			
        	<tr valign="top">
       			<?php hyper_field_checkbox('not_expire_on_actions', $hyper_labels['never_expire'], $hyper_labels['never_expire_desc']); ?>
       		</tr>
        	<tr valign="top">
       			<?php hyper_field_checkbox('invalidate_single_posts', $hyper_labels['invalidate_single_posts'], $hyper_labels['invalidate_single_posts_desc']); ?>
       		</tr>            
			<tr valign="top">
        		<?php hyper_field_checkbox('compress', $hyper_labels['compress_html'], $hyper_labels['compress_html_desc']); ?>
        	</tr>
			<tr valign="top">
        		<?php hyper_field_checkbox('gzip', $hyper_labels['gzip_compression'], $hyper_labels['gzip_compression_desc']); ?>
        	</tr>            

			<tr valign="top">
        		<th scope="row"><?php echo $hyper_labels['count']; ?></th>
                <td><?php echo hyper_count(); ?></td>
        	</tr>
        </table>        
        
		<!--
        <h3><?php _e('advanced options', 'hyper-cache'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <?php echo hyper_field_textarea('urls', __('url to reject', 'hyper-cache')); ?>
            </tr>  
            <tr valign="top">
                <?php echo hyper_field_checkbox('get', __('cache get with parameters', 'hyper-cache')); ?>
            </tr>              
        </table>
		-->
        
        <p class="submit">
            <input class="button" type="submit" name="save" value="<?php echo $hyper_labels['save']; ?>">  
            <input class="button" type="submit" name="clear" value="<?php echo $hyper_labels['clear']; ?>">
        </p>      
    </form>
</div>
