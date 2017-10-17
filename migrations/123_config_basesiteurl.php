<?php
/*
 * Notify the user to update their /config.php file with $GLOBALS['basesiteurl'].
 */

$configFileLocation = __DIR__ . '/../config.php';

// config.php.dist already contains the new variable.
if (!file_exists($configFileLocation)) {
	return true;
}

$originalConfig = file_get_contents(__DIR__ . '/../config.php');

if (preg_match('/\$GLOBALS\[\'basesiteurl\'\]/', $originalConfig)) {
	echo "<p style='color: green;'><b>config.php</b> is already updated. Doing nothing.<br/>Migration completed successfully.</p>";
	return true;
} else {
	?>
    <p style='color: red;'>Manual update required for file: <?php echo $configFileLocation ?></p>
    <p>Add the following <span style="color: #fc7933;">to your <b>config.php</b> file, after "$imasroot = ...":</span>
    <div style="display: table-cell;">
    <pre style="border: 1px solid; padding: 5px;">//base site url - use when generating full URLs to site pages.
$httpmode = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https')
    ? 'https://' : 'http://';
$GLOBALS['basesiteurl'] = $httpmode . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $imasroot;</pre>
    </div>
	<?php

	return false;
}

