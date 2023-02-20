<?php

require("../init.php");

if (empty($cid)) {
    echo "call as util/fixforums.php?cid=### with your course ID to fix";
    exit;
}
if (!isset($teacherid)) {
    echo "must be teacher";
    exit;
}

$stm = $DBH->prepare('SELECT ancestors FROM imas_courses WHERE id=?');
$stm->execute([$cid]);
$sourcecid = explode(',', $stm->fetchColumn(0))[0];

$stm = $DBH->prepare('SELECT id,name FROM imas_forums WHERE courseid=?');
$stm->execute([$cid]);
$forums = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $forums[$row['id']] = $row['name'];
}

$stm = $DBH->prepare('SELECT id,name FROM imas_forums WHERE courseid=?');
$stm->execute([$sourcecid]);
// will map sourcecid forum ID to destcid forum ID
$forummap = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $destfid = array_search($row['name'], $forums);
    if ($destfid !== false) {
        $forummap[$row['id']] = $destfid;
    }
}

$upd = $DBH->prepare('UPDATE imas_assessments SET posttoforum=? WHERE id=?');

$stm = $DBH->prepare('SELECT id,posttoforum FROM imas_assessments WHERE courseid=?');
$stm->execute([$cid]);
$assessforums = [];
$remapped = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (isset($forummap[$row['posttoforum']])) {
        $upd->execute([$forummap[$row['posttoforum']], $row['id']]);
        $remapped++;
    }    
}

echo "$remapped forums fixed";
