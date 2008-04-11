<?php
function hyper_request($name, $default=null) 
{
	if (!isset($_REQUEST[$name])) return $default;
	if (get_magic_quotes_gpc()) return hyper_stripslashes($_REQUEST[$name]);
	else return $_REQUEST[$name];
}

function hyper_stripslashes($value)
{
	$value = is_array($value) ? array_map('hyper_stripslashes', $value) : stripslashes($value);
	return $value;
	
}
function hyper_field_checkbox($name, $label='', $tips='', $attrs='')
{
  global $options;
  echo '<tr><td class="label">';
  echo '<label for="options[' . $name . ']">' . $label . '</label></td>';
  echo '<td><input type="checkbox" ' . $attrs . ' name="options[' . $name . ']" value="1" ' . 
    ($options[$name]!= null?'checked':'') . '/>';
  echo ' ' . $tips;
  echo '</td></tr>';
}

function hyper_field_text($name, $label='', $tips='', $attrs='')
{
  global $options;
  if (strpos($attrs, 'size') === false) $attrs .= 'size="30"';
  echo '<tr><td class="label">';
  echo '<label for="options[' . $name . ']">' . $label . '</label></td>';
  echo '<td><input type="text" ' . $attrs . ' name="options[' . $name . ']" value="' . 
    htmlspecialchars($options[$name]) . '"/>';
  echo ' ' . $tips;
  echo '</td></tr>';
}

function hyper_field_textarea($name, $label='', $tips='', $attrs='')
{
  global $options;
  
  if (strpos($attrs, 'cols') === false) $attrs .= 'cols="70"';
  if (strpos($attrs, 'rows') === false) $attrs .= 'rows="5"';
  
  echo '<tr><td class="label">';
  echo '<label for="options[' . $name . ']">' . $label . '</label></td>';
  echo '<td><textarea wrap="off" ' . $attrs . ' name="options[' . $name . ']">' . 
    htmlspecialchars($options[$name]) . '</textarea>';
  echo '<br /> ' . $tips;
  echo '</td></tr>';
}	

if (isset($_POST['save']))
{
    $options = hyper_request('options');
    update_option('hyper', $options);
    
    // Write the configuration file
    
    if (!file_exists(ABSPATH . '/wp-content/hyper-cache')) 
    {
        mkdir(ABSPATH . '/wp-content/hyper-cache', 0766);
    }
  
    if (!$options['timeout'] || !is_numeric($options['timeout'])) $options['timeout'] = 60;
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
}
else 
{
    $options = get_option('hyper');
    if (!$options['timeout']) $options['timeout'] = 60;
}
?>	
<style type="text/css">
td.label
{
  width: 150px;
  vertical-align: top;
  font-weight: bold;
  text-align: right;
}
td textarea {
    font-family: monospace;
    font-size: 11px;
}
</style>
<div class="wrap">

<form method="post">

<h2>Hyper Cache</h2>
<?php if (!defined('WP_CACHE')) { ?>
<p>The WordPress caching system is not enabled. Pleade add the line:
<pre>
define('WP_CACHE', true);
</pre>
in your wp-config.php file. Thank you.
</p>
<?php } ?>

<h3>Configuration</h3>
<table>
<?php hyper_field_checkbox('cache', 'Cache active?'); ?>
<?php hyper_field_text('timeout', 'Single page cached expire after', '(minutes!)'); ?>
</table>
<p><input type="submit" name="save" value="Save"></p>

</form>
</div>