<?php
// Javascript utils
global $allowedmacros;
array_push($allowedmacros,"mchoiceTarget");

function mchoiceTarget($ins,$outs,$q,$part) {
	$targets="<span id='target_NA' style='display: inline'>type of function you have chosen</span>"
	for ($i=0..$l) {
	  $targets .= "<span id='target_$ins[$i]' style='display: none;'>$outs[$i]</span>";
	}
	$setVisibility="document.getElementById('target_NA').style.display=(val == 'NA')?'inline':'none';";
	for ($i=0..$l) {
	   $setVisibility .= "document.getElementById('target_$ins[$i]').style.display=(val == '$ins[$i]')?'inline':'none';"
	}
	$targets.="<script>
	function getInput (cid,part) {
		let ref='qn'+(cid*1000+part).toString();
	  let x=document.getElementById(ref);
	  x.onchange = function () {
		alert(this.options[this.selectedIndex].innerHTML);
		let val=this.options[this.selectedIndex].innerHTML;
		$setVisibility
	  }
	}
	getInput($q,$part);
	 </script>";
	return $targets;
}
?>