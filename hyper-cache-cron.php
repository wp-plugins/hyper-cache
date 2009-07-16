<?php
@include(dirname(__FILE__) . '/hyper-cache-config.php');

$action = $_GET['action'];
if (!$action || !$_GET['key'] || $_GET['key'] != $hyper_cache_cron_key)
{
    sleep(3);
    die('no valid call');
}

if ($action == 'invalidate')
{
    $path = dirname(__FILE__) . '/hyper-cache';
    if ($handle = @opendir($path))
    {
        while ($file = readdir($handle))
        {
            if ($file != '.' && $file != '..')
            {
                @unlink($path . '/' . $file);
            }
        }
        closedir($handle);
    }
    else
    {
        die('ko');
    }
    die('ok');
}

die('unknown action');
?>
