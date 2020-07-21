<?php
if ($coursetheme=='otbsreader.css') {
	$nologo = true;
}
if (!isset($flexwidth) && ($coursetheme=='default_nm.css' || $coursetheme=='wamap_fw.css' || $coursetheme=='wamap.css' ) && !isset($loadinginfoheader)) {
	$smallheaderlogo = '<img src="'.$imasroot.'/img/collapse.gif"/>';
?>
<div id="headercontent" role="navigation" aria-label="System Navigation">
<div id="headerrightlinks">
<?php
$usernameinheader = false;
if (isset($userid)) {
	if ($myrights > 5) {
		echo "&nbsp;<br/><a href=\"#\" onclick=\"GB_show('Account Settings','$imasroot/forms.php?action=chguserinfo&greybox=true',800,'auto')\" title=\"Account Settings\"><span id=\"myname\">$userfullname</span> <img style=\"vertical-align:top\" src=\"$imasroot/img/gears.png\" alt=\"\"/></a>";
	} else {
		echo '&nbsp;<br/><span id="myname">'.$userfullname.'</span>';
	}
}
echo '</div>';
echo '<div id="headerbarlogo"><a href="'.$imasroot.'/index.php"><img src="'.$imasroot.'/vcrp/img/vcrp-logo-2016.png" alt="VCRP" height="40"/></a>';
?>
<span id="headermidlinks">
<?php
if (isset($userid)) {
	echo "<a href=\"$imasroot/index.php\">Home</a> | ";
	echo '<a href="#" onclick="jQuery(\'#homemenu\').css(\'left\',jQuery(this).offset().left+\'px\');mopen(\'homemenu\',0)" onmouseout="mclosetime()">Meine Kurse <img src="'.$imasroot.'/img/smdownarrow.png" style="vertical-align:middle" alt=""/></a> | ';

	if (isset($teacherid)) {
		echo "<a href=\"$imasroot/help.php?section=coursemanagement\">Help</a> ";
	} else {
		echo "<a href=\"$imasroot/help.php?section=usingimas\">Dokumentation</a> ";
	}
	echo "| <a href=\"$imasroot/actions.php?action=logout\">Abmelden</a>";
	echo '</span>';
	echo '<div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()"></div>';
} else {
	echo '</span>';
}

echo '</div>'; //headerbarlogo
?>
<div id="headermenu">
  <a href="#" tabindex=0 id="topnavmenu" aria-expanded="false" aria-controls="headermobilemenulist">
      <img src="<?php echo $imasroot;?>/img/menu.png" alt="Options" class="mida"/>
  </a>
</div>
<?php
echo '</div>'; //headercontent
?>
<div class="headermobilemenu">
  <ul id="headermobilemenulist" role="navigation" aria-labelledby="topnavmenu" aria-hidden="true">
<!--
	<li>
		<a href="<?php echo $imasroot;?>/index.php" class="trackprepped">Home</a>
	</li>
-->
	<?php
	 	if ($myrights>0) {
			echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
		}
	?>
	<!--
  	<li>
		<a href="<?php echo $imasroot;?>/actions.php?action=logout" class="trackprepped">Abmelden</a>
	</li>
	-->
  </ul>
</div>
<?php
$nologo = true;
$haslogout = true;
} else if (isset($CFG['GEN']['hidedefindexmenu'])) {
	unset($CFG['GEN']['hidedefindexmenu']);
}
?>
