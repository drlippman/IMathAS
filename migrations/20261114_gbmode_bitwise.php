<?php
// Convert imas_gbscheme.defgbmode from the old decimal-digit format to a plain bitfield.
// See includes/gbmode.php for the new layout.
//
// NOT idempotent: the converted values can't be distinguished from old ones.
// Order matters: change the default first (harmless if repeated), then convert,
// so that a failure never leaves a half-converted table that a rerun would convert again.

require_once __DIR__ . '/../includes/gbmode.php';

// ALTER implicitly commits in MySQL, so do it outside the transaction
$query = "ALTER TABLE `imas_gbscheme` ALTER `defgbmode` SET DEFAULT " . GBMODE_DEFAULT;
$res = $DBH->query($query);
if ($res === false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	return false;
}

$DBH->beginTransaction();

$query = "UPDATE imas_gbscheme SET defgbmode = " . gbmodeLegacyToBitsSql('defgbmode');
$res = $DBH->query($query);
if ($res === false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo '<p style="color: green;">✓ converted defgbmode to bitfield format</p>';
if (isset($CFG['GBS']['defgbmode']) && !isset($CFG['GBS']['gbmode'])) {
	echo '<p>Note: $CFG[\'GBS\'][\'defgbmode\'] in config.php is still in the old format.  It will be converted automatically, but you may want to replace it with $CFG[\'GBS\'][\'gbmode\'] = ' . gbmodeFromLegacy($CFG['GBS']['defgbmode']) . ';</p>';
}

return true;
