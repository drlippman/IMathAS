<?php

//Add imas_teacher_audit_log table
$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_lti_keys` (
  `id` int(10) AUTO_INCREMENT PRIMARY KEY,
  `key_set_url` varchar(2000) NOT NULL,
  `kid` varchar(254) NOT NULL,
  `alg` varchar(254) NOT NULL,
  `publickey` TEXT NOT NULL,
  `privatekey` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  INDEX `keyinf` ( key_set_url(100), kid)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'CREATE TABLE `imas_lti_platforms` (
  `id` int(10) AUTO_INCREMENT PRIMARY KEY,
  `client_id` varchar(254) NOT NULL,
  `issuer` varchar(254) NOT NULL,
  `auth_login_url` varchar(2000) NOT NULL,
  `auth_token_url` varchar(2000) NOT NULL,
  `key_set_url` varchar(2000) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  INDEX `isscli` (`issuer`,`client_id`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'CREATE TABLE `imas_lti_deployments` (
  `id` int(10) AUTO_INCREMENT PRIMARY KEY,
  `platform` int(10) UNSIGNED NOT NULL,
  `deployment` varchar(254) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  INDEX `platdep` (`platform`,`deployment`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'CREATE TABLE `imas_lti_groupassoc` (
  `deploymentid` INT(10) NOT NULL,
  `groupid` int(10) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`deploymentid`,`groupid`),
  INDEX (`groupid`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'CREATE TABLE `imas_lti_tokens` (
  `platformid` INT(10) UNSIGNED NOT NULL,
  `scopes` varchar(254) NOT NULL,
  `token` TEXT NOT NULL,
  `expires` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`platformid`,`scopes`),
  INDEX (`expires`)
) ENGINE=InnoDB;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = "CREATE INDEX `time` ON imas_ltinonces(`time`)";
$res = $DBH->query($query);
if ($res===false) {
  echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
}


$DBH->commit();
echo '<p>LTI 1.3 tables created</p>';

return true;
