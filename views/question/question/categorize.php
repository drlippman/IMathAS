<?php
    echo '<div id="headercategorize" class="pagetitle"><h2>Categorize Questions</h2></div>';
    echo "<form method=post action=\"categorize?aid=$aid&cid=$cid&record=true\">";
    echo 'Check: <a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',true);return false;">All</a> ';
    echo '<a href="#" onclick="$(\'input[type=checkbox]\').prop(\'checked\',false);return false;">None</a>';
    echo '<table class="gb"><thead><tr><th></th><th>Description</th><th>Category</th></tr></thead><tbody>';

    foreach($itemarr as $qid) {
        echo "<tr><td><input type=\"checkbox\" id=\"c$qid\"/></td>";
        echo "<td>{$descriptions[$qid]}</td><td>";
        echo "<select id=\"$qid\" name=\"$qid\" class=\"qsel\">";
        echo "<option value=\"0\" ";
        if ($category[$qid] == 0) {
            echo "selected=1";
        }
        echo ">Uncategorized or Default</option>\n";
        if (count($outcomes)>0) {
            echo '<optgroup label="Outcomes"></optgroup>';
        }
        $ingrp = false;
        $issel = false;
        foreach ($outcomes as $oc) {
            if ($oc[1]==1) {//is group
                if ($ingrp) {
                    echo '</optgroup>';
                }
                echo '<optgroup label="'.htmlentities($oc[0]).'">';
                $ingrp = true;
            } else {
                echo '<option value="'.$oc[0].'" ';
                if ($category[$qid] == $oc[0]) {
                    echo "selected=1"; $issel = true;
                }
                echo '>'.$outcomenames[$oc[0]].'</option>';
            }
        }
        if ($ingrp) {
            echo '</optgroup>';
        }
        echo '<optgroup label="Libraries">';
        foreach ($questionlibs[$qid] as $qlibid) {
            echo "<option value=\"{$libnames[$qlibid]}\" ";
            if ($category[$qid] == $libnames[$qlibid] && !$issel) {
                echo "selected=1"; $issel= true;
            }
            echo ">{$libnames[$qlibid]}</option>\n";
        }
        echo '</optgroup><optgroup label="Custom">';
        foreach ($extracats as $cat) {
            echo "<option value=\"$cat\" ";
            if ($category[$qid] == $cat && !$issel) {
                echo "selected=1";$issel = true;
            }
            echo ">$cat</option>\n";
        }
        echo '</optgroup>';
        echo "</select></td></tr>\n";
    }
    echo "</tbody></table>\n";
    if (count($outcomes)>0) {
        echo '<p>Apply outcome to selected: <select id="masssel">';
        $ingrp = false;
        $issel = false;
        foreach ($outcomes as $oc) {
            if ($oc[1]==1) {//is group
                if ($ingrp) { echo '</optgroup>';}
                echo '<optgroup label="'.htmlentities($oc[0]).'">';
                $ingrp = true;
            } else {
                echo '<option value="'.$oc[0].'">'.$outcomenames[$oc[0]].'</option>';
            }
        }
        if ($ingrp) {
            echo '</optgroup>';
        }
        echo '</select> <input type="button" value="Assign" onclick="massassign()"/></p>';
    }
    echo "<p>Select first listed library for all uncategorized questions: <input type=button value=\"Quick Pick\" onclick=\"quickpick()\"></p>\n";
    echo "<p>Add new category to lists: <input type=type id=\"newcat\" size=40> ";
    echo "<input type=button value=\"Add Category\" onclick=\"addcategory()\"></p>\n";
    echo '<p><input type=submit value="Record Categorizations"> and return to the course page.  <input type="button" class="secondarybtn" value="Reset" onclick="resetcat()"/></p>';
    echo "</form>\n";
?>