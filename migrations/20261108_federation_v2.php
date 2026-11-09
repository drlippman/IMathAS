<?php

// Federation v2: sync-base, staging and pull-session tables.
// See federation-plan.md.
// imas_federation_pulls from migration 126 was never used in production
// and is replaced with a new structure.

$DBH->beginTransaction();

$queries = array();

// longer shared secrets
$queries[] = "ALTER TABLE `imas_federation_peers` MODIFY `secret` VARCHAR(64) NOT NULL";

// pull sessions: one row per pull started against a peer
$queries[] = "DROP TABLE IF EXISTS `imas_federation_pulls`";
$queries[] = "CREATE TABLE `imas_federation_pulls` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`peerid` INT(10) UNSIGNED NOT NULL,
	`starttime` INT(10) UNSIGNED NOT NULL,
	`endtime` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 in progress, 1 complete, 2 abandoned',
	`stage` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 libs, 1 questions, 2 library items',
	`lastcursor` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'lastmoddate:id of last record received in current stage',
	`startcursors` TEXT NOT NULL COMMENT 'JSON: per-stage cursor this pull started from',
	`stats` TEXT NOT NULL COMMENT 'JSON: counts',
	INDEX (`peerid`, `starttime`)
) ENGINE = InnoDB ROW_FORMAT=DYNAMIC COMMENT = 'Federation pull sessions'";

// sync base: state of each item the last time it was in sync with a peer
// type: 1 library, 2 question, 3 library item
// uid: uniqueid for libraries/questions; "libuid:questionuid" for library items
$queries[] = "CREATE TABLE `imas_federation_items` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`peerid` INT(10) UNSIGNED NOT NULL,
	`type` TINYINT(1) UNSIGNED NOT NULL,
	`uid` VARCHAR(48) NOT NULL,
	`remote_lastmod` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`local_lastmod` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'local lastmoddate when last in sync',
	`content_hash` CHAR(40) NOT NULL DEFAULT '',
	UNIQUE KEY `peeritem` (`peerid`, `type`, `uid`)
) ENGINE = InnoDB ROW_FORMAT=DYNAMIC COMMENT = 'Federation sync base'";

// staged incoming changes awaiting admin review
// kind: 1 new, 2 update, 3 conflict, 4 delete, 5 delete-vs-edit
// status: 0 pending, 1 approved/applied, 2 rejected, 3 superseded
$queries[] = "CREATE TABLE `imas_federation_staged` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`peerid` INT(10) UNSIGNED NOT NULL,
	`pullid` INT(10) UNSIGNED NOT NULL,
	`type` TINYINT(1) UNSIGNED NOT NULL,
	`uid` VARCHAR(48) NOT NULL,
	`kind` TINYINT(1) UNSIGNED NOT NULL,
	`status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
	`parentstageid` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'for a question pulled as a dependency: staged id of a question that references it',
	`remote_lastmod` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`content_hash` CHAR(40) NOT NULL DEFAULT '',
	`staged` INT(10) UNSIGNED NOT NULL,
	`resolved` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`payload` MEDIUMTEXT NOT NULL COMMENT 'JSON of incoming record',
	INDEX `peerstatus` (`peerid`, `status`, `kind`),
	INDEX `peeritem` (`peerid`, `type`, `uid`),
	INDEX (`pullid`),
	INDEX (`parentstageid`)
) ENGINE = InnoDB ROW_FORMAT=DYNAMIC COMMENT = 'Federation staged changes'";

foreach ($queries as $query) {
	$res = $DBH->query($query);
	if ($res === false) {
		echo "<p>Query failed: ($query) : " . implode(' ', $DBH->errorInfo()) . "</p>";
		if ($DBH->inTransaction()) { $DBH->rollBack(); }
		return false;
	}
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Federation v2 tables added.</p>";

return true;
