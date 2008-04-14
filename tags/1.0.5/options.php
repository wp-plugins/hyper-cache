<?php
if (function_exists('load_plugin_textdomain')) {
    load_plugin_textdomain('hyper-cache', 'wp-content/plugins/hyper-cache');
}

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
    hyper_cache_invalidate();
}

if (isset($_POST['save'])) {
    $options = hyper_request('options');
    update_option('hyper', $options);

    // Write the configuration file
    if (!file_exists(ABSPATH . '/wp-content/hyper-cache')) {
        mkdir(ABSPATH . '/wp-content/hyper-cache', 0766);
    }

    if (!$options['timeout'] || !is_numeric($options['timeout'])) {
    	$options['timeout'] = 60;
    }
    
    $buffer = "<?php\n";
    $buffer .= '$hyper_cache_enabled = ' . ($options['cache']?'true':'false') . ";\n";
    $buffer .= '$hyper_cache_timeout = ' . $options['timeout'] . ";\n";
    $buffer .= '?>';
    $file = fopen(ABSPATH . 'wp-content/hyper-cache-config.php', 'w');
    fwrite($file, $buffer);
    fclose($file);

    // Write the advanced-cache.php (so we grant it's the correct version)
    $buffer = file_get_contents(dirname(__FILE__) . '/advanced-cache.php');
    $file = fopen(ABSPATH . 'wp-content/advanced-cache.php', 'w');
    
    fwrite($file, $buffer);
    fclose($file);
} else {
    $options = get_option('hyper');
    if (!$options['timeout']) {
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
            _e('not_enabled', 'hyper-cache');
            echo "<pre>define('WP_CACHE', true);</pre>";
            echo '</p></div>';
        }
        ?>

        <h3><?php _e('configuration', 'hyper-cache'); ?></h3>
        <table class="form-table">
			<tr valign="top">
        		<?php hyper_field_checkbox('cache', __('active', 'hyper-cache')); ?>
        	</tr>
        	<tr valign="top">
       			<?php hyper_field_text('timeout', __('expire', 'hyper-cache'), __('minutes', 'hyper-cache'), 'size="5"'); ?>
       		</tr>
        </table>
        
        <table class="form-table">
			<tr valign="top">
        		<th scope="row"><?php _e('count', 'hyper-cache'); ?></th>
                <td><?php echo hyper_count(); ?></td>
        	</tr>
        </table>        
        
        <p>
            <input class="button" type="submit" name="save" value="<?php _e('save', 'hyper-cache'); ?>">  
            <input class="button" type="submit" name="clear" value="<?php _e('clear', 'hyper-cache'); ?>">
        </p>      
    </form>
</div>
