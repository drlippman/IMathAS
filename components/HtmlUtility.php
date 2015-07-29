<?php

namespace app\components;

use Yii;
use yii\base\Component;

class HtmlUtility extends Component
{
    public static function writeHtmlSelect($name, $valList, $labelList, $selectedVal = null, $defaultLabel = null, $defaultVal = null, $actions = null)
    {
        echo "<select name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "";
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
            echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i = 0; $i < count($valList); $i++) {
            if ((isset($selectedVal)) && ($valList[$i] == $selectedVal)) {
                echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
            } else {
                echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo "</select>\n";
    }

    public static function writeHtmlMultiSelect($name, $valList, $labelList, $selectedVals = array(), $defaultLabel = null)
    {
        echo "<div class=\"multisel\"><select name=\"{$name}[]\" id=\"$name\">";
        if (isset($defaultLabel)) {
            echo " <option value=\"null\" selected=\"selected\">$defaultLabel</option>\n";
        }
        if (is_array($valList[0])) {//has a group structure
            $ingrp = false;
            foreach ($valList as $oc) {
                if ($oc[1] == 1) {//is group
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
            for ($i = 0; $i < count($valList); $i++) {
                $val[$valList[$i]] = $labelList[$i];
                echo "	<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo '</select><input type="button" value="Add Another" onclick="addmultiselect(this,\'' . $name . '\')"/>';
        if (count($selectedVals) > 0) {
            foreach ($selectedVals as $v) {
                echo '<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span>';
                echo '<input type="hidden" name="' . $name . '[]" value="' . $v . '"/>' . (is_array($valList[0]) ? $labelList[$v] : $val[$v]);
                echo '</div>';
            }
        }
        echo '</div>';
    }

//writeHtmlChecked is used for checking the appropriate radio box on html forms
    public static function writeHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                echo "checked ";
            }
        } else {
            if ($var == $test) {
                echo "checked ";
            }
        }
    }

//writeHtmlChecked is used for checking the appropriate radio box on html forms
    public static function getHtmlChecked($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                return "checked ";
            }
        } else {
            if ($var == $test) {
                return "checked ";
            }
        }
    }

//writeHtmlSelected is used for selecting the appropriate entry in a select item
    public static function writeHtmlSelected($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                echo 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                echo 'selected="selected"';
            }
        }
    }

//writeHtmlSelected is used for selecting the appropriate entry in a select item
    public static function getHtmlSelected($var, $test, $notEqual = null)
    {
        if ((isset($notEqual)) && ($notEqual == 1)) {
            if ($var != $test) {
                return 'selected="selected"';
            }
        } else {
            if ($var == $test) {
                return 'selected="selected"';
            }
        }
    }

}