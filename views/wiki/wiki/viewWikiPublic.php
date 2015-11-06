<?php
require_once("../filter/filter.php");
if ($wikiData['groupsetid']>0 || $wikiData['avail']==0 || ($wikiData['avail']==1 && ($now < $wikiData['startdate'] || $now > $wikiData['enddate']))) {
    echo "This wiki is not currently available for viewing";
    exit;
}

echo "<div class=breadcrumb>$breadcrumbbase View Wiki</div>";
echo '<div id="headerviewwiki" class="pagetitle"><h2>'.$wikiData['name'].'</h2></div>';

echo '<div style="padding-left:10px; padding-right: 10px; border: 1px solid #000;">';
echo filter($text);
echo '</div>';

echo "<div class=right><a href=\"public?cid=$courseId\">Return to Public Course Page</a></div>\n";