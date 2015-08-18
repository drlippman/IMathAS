<?php

namespace app\components;


use app\models\Exceptions;
use app\models\Questions;
use Yii;
use yii\base\Component;

class AssessmentUtility extends Component
{
   public static  function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
        echo "<select class='form-control' name=\"$name\" id=\"$name\" ";
        echo (isset($actions)) ? $actions : "" ;
        echo ">\n";
        if (isset($defaultLabel) && isset($defaultVal)) {
            echo "		<option value=\"$defaultVal\" selected>$defaultLabel</option>\n";
        }
        for ($i=0;$i<count($valList);$i++) {
            if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
                echo "		<option value=\"$valList[$i]\" selected>$labelList[$i]</option>\n";
            } else {
                echo "		<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo "</select>\n";
    }

    public static function writeHtmlMultiSelect($name,$valList,$labelList,$selectedVals=array(),$defaultLabel=null) {
        echo "<div class=\"multisel\"><select class='form-control' name=\"{$name}[]\" id=\"$name\">";
        if (isset($defaultLabel)) {
            echo " <option value=\"null\" selected=\"selected\">$defaultLabel</option>\n";
        }
        if (is_array($valList[0])) {//has a group structure
            $ingrp = false;
            foreach ($valList as $oc) {
                if ($oc[1]==1) {//is group
                    if ($ingrp) { echo '</optgroup>';}
                    echo '<optgroup label="'.htmlentities($oc[0]).'">';
                    $ingrp = true;
                } else {
                    echo '<option value="'.$oc[0].'">'.$labelList[$oc[0]].'</option>';
                }
            }
            if ($ingrp) { echo '</optgroup>';}
        } else {
            $val = array();
            for ($i=0;$i<count($valList);$i++) {
                $val[$valList[$i]] = $labelList[$i];
                echo "	<option value=\"$valList[$i]\">$labelList[$i]</option>\n";
            }
        }
        echo '</select><br><input type="button" value="Add Another" onclick="addmultiselect(this,\''.$name.'\')"/>';
        if (count($selectedVals)>0) {
            foreach ($selectedVals as $v) {
                echo '<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span>';
                echo '<input type="hidden" name="'.$name.'[]" value="'.$v.'"/>'.(is_array($valList[0])?$labelList[$v]:$val[$v]);
                echo '</div>';
            }
        }
        echo '</div>';
    }

//writeHtmlChecked is used for checking the appropriate radio box on html forms
    public static function writeHtmlChecked ($var,$test,$notEqual=null)
    {
        if ((isset($notEqual)) && ($notEqual==1)) {
            if ($var!=$test) {
                echo "checked ";
            }
        } else {
            if ($var==$test) {
                echo "checked ";
            }
        }
    }

//writeHtmlChecked is used for checking the appropriate radio box on html forms
    public static function getHtmlChecked ($var,$test,$notEqual=null) {
        if ((isset($notEqual)) && ($notEqual==1)) {
            if ($var!=$test) {
                return "checked ";
            }
        } else {
            if ($var==$test) {
                return "checked ";
            }
        }
    }

//writeHtmlSelected is used for selecting the appropriate entry in a select item
    public static function writeHtmlSelected ($var,$test,$notEqual=null) {
        if ((isset($notEqual)) && ($notEqual==1)) {
            if ($var!=$test) {
                echo 'selected="selected"';
            }
        } else {
            if ($var==$test) {
                echo 'selected="selected"';
            }
        }
    }

//writeHtmlSelected is used for selecting the appropriate entry in a select item
    public static function getHtmlSelected ($var,$test,$notEqual=null) {
        if ((isset($notEqual)) && ($notEqual==1)) {
            if ($var!=$test) {
                return 'selected="selected"';
            }
        } else {
            if ($var==$test) {
                return 'selected="selected"';
            }
        }
    }

    public static function createItemOrder($key,$countCourseDetails,$parent,$blockList) {
        global $toolset;
        if (($toolset&4)==4) {
            return '';
        }
        $key = $key+1;
        if($parent != '0'){
            $html = "<select class=\"mvsel inside-mvsel \" id=\"$parent-$key\" onchange=\"moveitem($key,'$parent')\">\n";
        }
        else{
        $html = "<select class=\"mvsel\" id=\"$parent-$key\" onchange=\"moveitem($key,'$parent')\">\n";
        }
        for ($i = 1; $i <= $countCourseDetails; $i++) {
            $html .= "<option value=\"$i\" ";
            if ($i==$key) {
                $html .= "SELECTED";
            }
            $html .= ">$i</option>\n";
        }
        for ($i=0; $i<count($blockList); $i++) {
            if ($key!=$blockList[$i]) {
                $html .= "<option value=\"B-{$blockList[$i]}\">" . sprintf(_('Into %s'),$blockList[$i]) . "</option>\n";
            }
        }
        if ($parent!='0') {
            $html .= '<option value="O-' . $parent . '">' . _('Out of Block') . '</option>';
        }
        $html .= "</select>\n";
        echo $html;
    }

    public static function parsedatetime($date,$time) {
        global $tzoffset, $tzname;
        preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$date,$dmatches);
        preg_match('/(\d+)\s*:(\d+)\s*(\w+)/',$time,$tmatches);
        if (count($tmatches)==0) {
            preg_match('/(\d+)\s*([a-zA-Z]+)/',$time,$tmatches);
            $tmatches[3] = $tmatches[2];
            $tmatches[2] = 0;
        }
        $tmatches[1] = $tmatches[1]%12;
        if($tmatches[3]=="pm") {$tmatches[1]+=12; }
        //$tmatches[2] += $tzoffset;
        //return gmmktime($tmatches[1],$tmatches[2],0,$dmatches[1],$dmatches[2],$dmatches[3]);
        if ($tzname=='') {
            $serveroffset = date('Z')/60 + $tzoffset;
            $tmatches[2] += $serveroffset;
        }
        return mktime($tmatches[1],$tmatches[2],0,$dmatches[1],$dmatches[2],$dmatches[3]);
    }
}

