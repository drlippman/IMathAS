<?php
require_once("../filter/filter.php");
echo "<div class=breadcrumb>$breadcrumbbase $titlesImp</div>";

echo '<div style="padding-left:10px; padding-right: 10px;">';
echo filter($text);
echo '</div>';

if (isset($from)) {
    echo "<div class=right><a href=\"course?cid=$courseId\">Back</a></div>\n";
} else if ($fcid>0) {
    echo "<div class=right><a href=\"{$_SERVER['HTTP_REFERER']}\">Back</a></div>\n";
} else {
    echo "<div class=right><a href=\"public?cid=$courseId\">Return to the Public Course Page</a></div>\n";
}