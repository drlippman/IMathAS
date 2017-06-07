<?php

/*
 * Create a table for storing PHP session data.
 *
 * This needs to happen before updating config.php, as the next step requires this table to exist.
 */

$DBH->beginTransaction();

$query = 'CREATE TABLE IF NOT EXISTS `php_sessions` (
	`id` varchar(32) NOT NULL,
	`access` int(10) unsigned DEFAULT NULL,
	`data` text,
	PRIMARY KEY (`id`),
	INDEX (`access`)
) ENGINE=InnoDB';

$res = $DBH->query($query);
if ($res === false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

$DBH->commit();

echo "<p style='color: green;'>✓ Database updated.</p>";


/*
 * Ensure config.php is updated.
 */

$configFileLocation = __DIR__ . '/../config.php';

// config.php.dist already contains the new variable.
if (!file_exists($configFileLocation)) {
	return true;
}

$originalConfig = file_get_contents($configFileLocation);

if (preg_match('/require_once.*\/includes\/session.php/', $originalConfig)) {
	echo "<p style='color: green;'>✓ config.php is already updated.</p>";
} else {
	?>
    <p style='color: red;'>Manual update required for file: <?php echo $configFileLocation ?></p>
    <p>
        Add the following <span style="color: #fc7933;">to your <b>config.php</b> file</span>, after the database
        connection has been established.
    </p>
    <p>
        This should be near the end of the file. See <b>config.php.dist</b> for an example.
    </p>
    <p>
    <div style="display: table-cell;">
        <pre style="border: 1px solid; padding: 5px;">require_once(__DIR__ . "/includes/session.php");</pre>
    </div>
	<?php

	return false;
}


return true;

