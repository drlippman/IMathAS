<?php

namespace app\components;

use Yii;
use yii\base\Component;

class AssessmentUtility extends Component
{
    public static function writeHtmlSelect($name, $valList, $labelList, $selectedVal = null, $defaultLabel = null, $defaultVal = null, $actions = null)
    {
        echo "<select class='form-control' name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "";
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
            echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($valList); $i++) {
            echo "<option value='" . $valList[$i] . "'";
            if ((isset($selectedVal)) && ($valList[$i] == $selectedVal)) {
                echo " selected";
            }
            echo ">$labelList[$i]</option>\n";
        }
        echo "</select>\n";
    }

    public static function writeHtmlMultiSelect($name, $valList, $labelList, $selectedVals = array(), $defaultLabel = null)
    {
        echo "<div class=\"multisel\"><select class='form-control' name=\"{$name}[]\" id=\"$name\">";
        if (isset($defaultLabel)) {
            echo " <option value=\"null\" selected=\"selected\">$defaultLabel</option>\n";
        }
        if (is_array($valList[0])) { //has a group structure
            $ingrp = false;
            foreach ($valList as $oc) {
                if ($oc[1] == AppConstant::NUMERIC_ONE) { //is group
                    if ($ingrp) {
                        echo '</optgroup>';
                    }
                    echo '<optgroup label="' . htmlentities($oc[0]) . '">';
                    $ingrp = true;
                } else {
                    echo '<option value="' . $oc[0] . '">' . $labelList[$oc[0]] . '</option>';
                }
            }
            if ($ingrp) {
                echo '</optgroup>';
            }
        } else {
            $val = array();
            for ($i = AppConstant::NUMERIC_ZERO; $i < count($valList); $i++) {
                $val[$valList[$i]] = $labelList[$i];
                echo "	<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo '</select><br><input type="button" value="Add Another" onclick="addmultiselect(this,\'' . $name . '\')"/>';
        if (count($selectedVals) > AppConstant::NUMERIC_ZERO) {
            foreach ($selectedVals as $v) {
                echo '<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span>';
                echo '<input type="hidden" name="' . $name . '[]" value="' . $v . '"/>' . (is_array($valList[0]) ? $labelList[$v] : $val[$v]);
                echo '</div>';
            }
        }
        echo '</div>';
    }

/*
 * writeHtmlChecked is used for checking the appropriate radio box on html forms
 */
    public static function writeHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == AppConstant::NUMERIC_ONE)) {
            if ($var != $test) {
                echo "checked ";
            }
        } else {
            if ($var == $test) {
                echo "checked ";
            }
        }
    }

/*
 * writeHtmlChecked is used for checking the appropriate radio box on html forms
 */
    public static function getHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == AppConstant::NUMERIC_ONE)) {
            if ($var != $test) {
                return "checked ";
            }
        } else {
            if ($var == $test) {
                return "checked ";
            }
        }
    }
/*
 *writeHtmlSelected is used for selecting the appropriate entry in a select item
 */

    public static function writeHtmlSelected($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && (self::isNumericOne($notEqual))) {
            if ($var != $test) {
                echo 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                echo 'selected="selected"';
            }
        }
    }
/*
 *writeHtmlSelected is used for selecting the appropriate entry in a select item
 */
    public static function getHtmlSelected($var, $test, $notEqual = null)
    {
        $retStr = "";
        if ((isset($notEqual)) && (self::isNumericOne($notEqual))) {
            if ($var != $test) {
                return 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                return 'selected="selected"';
            }
        }
    }

    public static function createItemOrder($key, $countCourseDetails, $parent, $blockList)
    {
        global $toolset;
        if (($toolset & AppConstant::NUMERIC_FOUR) == AppConstant::NUMERIC_FOUR) {
            return '';
        }
        $key = $key + AppConstant::NUMERIC_ONE;
        if ($parent != AppConstant::ZERO_VALUE) {

            $html = "<select class=\"mvsel inside-mvsel \" id=\"$parent-$key\" onchange=\"moveitem($key,'$parent')\">\n";
        } else {
            $html = "<select class=\"mvsel\" id=\"$parent-$key\" onchange=\"moveitem($key,'$parent')\">\n";
        }
        for ($i = AppConstant::NUMERIC_ONE; $i <= $countCourseDetails; $i++) {
            $html .= "<option value=\"$i\" ";
            if ($i == $key) {
                $html .= "SELECTED";
            }
            $html .= ">$i</option>\n";
        }
        for ($i = AppConstant::NUMERIC_ZERO; $i < count($blockList); $i++) {
            if ($key != $blockList[$i]) {
                $html .= "<option value=\"B-{$blockList[$i]}\">" . sprintf(_('Into %s'), $blockList[$i]) . "</option>\n";
            }
        }
        if ($parent != AppConstant::ZERO_VALUE) {
            $html .= '<option value="O-' . $parent . '">' . _('Out of Block') . '</option>';
        }
        $html .= "</select>\n";
        echo $html;
    }

    public static function parsedatetime($date, $time)
    {
        global $tzoffset, $tzname;
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/', $date, $dmatches);
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/', $time, $tmatches);
        if (count($tmatches) == AppConstant::NUMERIC_ZERO) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/', $time, $tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = AppConstant::NUMERIC_ZERO;
        }
        $tmatches[1] = $tmatches[1] % AppConstant::NUMERIC_TWELVE;
        if ($tmatches[3] == "pm") {
            $tmatches[1] += AppConstant::NUMERIC_TWELVE;
        }
        if ($tzname == '') {
            $serveroffset = date('Z') / AppConstant::MINUTE + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1], $tmatches[2], AppConstant::NUMERIC_ZERO, $dmatches[1], $dmatches[2], $dmatches[3]);
    }

    public static function isNumericOne($notEqual)
    {
        return $notEqual == AppConstant::NUMERIC_ONE;
    }

    public static function getquestionlicense($row) {
        global $CFG, $sendfrom;
        $license = 'This question was written by '.$row['author'];
        if ($row['ancestorauthors']!='') {
            $license .= ', derived from work by '.$row['ancestorauthors'];
        }
        if ($row['license']==0) {
            $license .= '. This work is copyrighted, or contains copyright material.';
        } else if ($row['license']==1) {
            $license .= '. This work is licensed under the <a href="http://www.imathas.com/communitylicense.html">IMathAS Community License (GPL + CC-BY)</a>.<br/>';
            $license .= 'The code that generated this question can be obtained by instructors by ';
            if (isset($CFG['GEN']['meanstogetcode'])) {
                $license .= $CFG['GEN']['meanstogetcode'];
            } else {
                global $sendfrom;
                $license .= 'emailing '.$sendfrom;
            }
        } else if ($row['license']==2) {
            $license .= '. This work has been placed in the <a href="https://creativecommons.org/publicdomain/zero/1.0/">Public Domain</a>. ';
        } else if ($row['license']==3) {
            $license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike</a> license. ';
        } else if ($row['license']==4) {
            $license .= '. This work, both code and generated output, is licensed under the <a href="http://creativecommons.org/licenses/by-sa/4.0/">Creative Commons Attribution-ShareAlike</a> license. ';
        }
        if ($row['otherattribution']!='') {
            $license .= '. '.$row['otherattribution'];
        }
        return $license;
    }
}