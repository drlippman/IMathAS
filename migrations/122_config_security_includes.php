<?php
/*
 * Add a require_once("includes/security.php") to the top of /config.php for security related includes.
 */

$configFileLocation = __DIR__ . '/../config.php';

// config.php.dist already contains the new variable.
if (!file_exists($configFileLocation)) {
	return true;
}

$originalConfig = file_get_contents(__DIR__ . '/../config.php');

if (preg_match('/require_once.*\includes\/security.php/', $originalConfig)) {
	echo "<p style='color: green;'><b>config.php</b> is already updated. Doing nothing.<br/>Migration completed successfully.</p>";
	return true;
}

if (!is_writable($configFileLocation)) {
	?>
    <p style='color: red;'>Unable to write to file: <?php echo $configFileLocation ?></p>
    <p>Please ensure your <b>config.php</b> is writable, or you may manually add the following to the top of your
        <b>config.php</b> file, after the copyright notice:
    <div style="display: table-cell;">
        <pre style="border: 1px solid; padding: 5px;">require_once(__DIR__ . "/includes/security.php");</pre>
    </div>
	<?php

	return false;
}

// Config
$searchFor = "/^(<\?php)/";
$newRequire = "require_once(__DIR__ . \"/includes/security.php\");";

// Insert the new require line.
$updatedConfig = preg_replace($searchFor, "$0\n$newRequire\n", $originalConfig, 1);

// Write the updated config.
file_put_contents(__DIR__ . '/../config.php', $updatedConfig);

// Done!
return true;

