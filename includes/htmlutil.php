<?php
require_once("sanitize.php");
//IMathAS: HTML utility functions

//$name is the html name&id for the select list
//$valList is an array of strings for the html value tag
//$labelList is an array of strings that are displayed as the select list
//$selectVal is optional, if passed the item in $valList that matches will be output as selected
function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
	echo "<select name=\"$name\" id=\"$name\" ";
	echo (isset($actions)) ? $actions : "" ;
	echo ">\n";
	if (isset($defaultLabel) && isset($defaultVal)) {
		echo "		<option value=\"".Sanitize::encodeStringForDisplay($defaultVal)."\" selected>".Sanitize::encodeStringForDisplay($defaultLabel)."</option>\n";
	}
	for ($i=0;$i<count($valList);$i++) {
		if ((isset($selectedVal)) && (strcmp($valList[$i],$selectedVal)==0)) {
			echo "		<option value=\"".Sanitize::encodeStringForDisplay($valList[$i])."\" selected>".Sanitize::encodeStringForDisplay($labelList[$i])."</option>\n";
		} else {
			echo "		<option value=\"".Sanitize::encodeStringForDisplay($valList[$i])."\">".Sanitize::encodeStringForDisplay($labelList[$i])."</option>\n";
		}
	}
	echo "</select>\n";	
} 

function writeHtmlMultiSelect($name,$valList,$labelList,$selectedVals=array(),$defaultLabel=null) {
	echo "<div class=\"multisel\"><select name=\"{$name}[]\" id=\"$name\">";
	if (isset($defaultLabel)) {
		echo " <option value=\"null\" selected=\"selected\">".Sanitize::encodeStringForDisplay($defaultLabel)."</option>\n";
	}
	if (is_array($valList[0])) {//has a group structure
		$ingrp = false;
		foreach ($valList as $oc) {
			if ($oc[1]==1) {//is group
				if ($ingrp) { echo '</optgroup>';}
                $optionGroupLabel = Sanitize::encodeStringForDisplay($oc[0]);
				echo '<optgroup label="'.$optionGroupLabel.'">';
				$ingrp = true;
			} else {
				echo '<option value="'.Sanitize::encodeStringForDisplay($oc[0]).'">'.Sanitize::encodeStringForDisplay($labelList[$oc[0]]).'</option>';
			}
		}
		if ($ingrp) { echo '</optgroup>';}	
	} else {
		$val = array();
		for ($i=0;$i<count($valList);$i++) {
			$val[$valList[$i]] = $labelList[$i];
			echo "	<option value=\"".Sanitize::encodeStringForDisplay($valList[$i])."\">".Sanitize::encodeStringForDisplay($labelList[$i])."</option>\n";
		}
	}
	echo '</select><input type="button" value="Add Another" onclick="addmultiselect(this,\''.$name.'\')"/>';
	if (count($selectedVals)>0) {
		foreach ($selectedVals as $v) {
			echo '<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span>';
			echo '<input type="hidden" name="'.$name.'[]" value="'.Sanitize::encodeStringForDisplay($v).'"/>'
				.(is_array($valList[0])?Sanitize::encodeStringForDisplay($labelList[$v]):Sanitize::encodeStringForDisplay($val[$v]));
			echo '</div>';
		}		
	}
	echo '</div>';
}

//writeHtmlChecked is used for checking the appropriate radio box on html forms
function writeHtmlChecked ($var,$test,$notEqual=null) {
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
function getHtmlChecked ($var,$test,$notEqual=null) {
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
function writeHtmlSelected ($var,$test,$notEqual=null) {
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
function getHtmlSelected ($var,$test,$notEqual=null) {
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

?>
