<?php

if ($isgroup) {
    $addr .= '&grp='.$groupid;
}
$urlmode = \app\components\AppUtility::urlMode();
$addr2 = $urlmode.$_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\').'/viewwiki.php?revert=ask&cid='.$cid.'&id='.$id.$framed;
if ($isgroup) {
    $addr2 .= '&grp='.$groupid;
}
$placeinhead .= '<script type="text/javascript">var AHAHrevurl = "'.$addr.'"; var reverturl = "'.$addr2.'";</script>';
$placeinhead .= '<style type="text/css"> a.grayout {color: #ccc; cursor: default;} del {color: #f99; text-decoration:none;} ins {color: #6f6; text-decoration:none;} .wikicontent {padding: 10px;}</style>';
if ($isgroup && isset($teacherid)) {
    $placeinhead .= "<script type=\"text/javascript\">";
    $placeinhead .= 'function chgfilter() {';
    $placeinhead .= '  var gfilter = document.getElementById("gfilter").value;';
    $placeinhead .= "  window.location = \"viewwiki.php?cid=$cid&id=$id$framed&grp=\"+gfilter;";
    $placeinhead .= '}';
    $placeinhead .= '</script>';
}