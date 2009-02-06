<?php
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
