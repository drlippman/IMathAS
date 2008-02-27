<?php
 //IMathAS:  Save page reordering from Quick View
 //(c) 2008 David Lippman
 require("../validate.php");
 
 if (!isset($teacherid)) {
	 echo "Must be a teacher to access this page";
	 exit;
 }
 if (!isset($_GET['order']) || !isset($_GET['cid'])) {
	 echo "Cannot access this page directly";
	 exit;
 }
 $cid = $_GET['cid'];
 $order = $_GET['order'];
 
 $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
 $result = mysql_query($query) or die("Query failed : " . mysql_error());
 $items = unserialize(mysql_result($result,0,0));
 
 $newitems = array();

 function additems($list) {
	 global $items;
	 $outarr = array();
	 $list = substr($list,1,-1);
	 $i = 0; $nd = 0; $last = 0;
	 $listarr = array();
	 while ($i<strlen($list)) {
		 if ($list[$i]=='[') {
			 $nd++;
		 } else if($list[$i]==']') {
			 $nd--;
		 } else if ($list[$i]==',' && $nd==0) {
			$listarr[] = substr($list,$last,$i-$last);
			$last = $i+1;
		 }
		 $i++;
	 }
	 $listarr[] = substr($list,$last);
	 foreach ($listarr as $it) {
		 if (strpos($it,'-')!==false) { //is block
			 $pos = strpos($it,':');
			 if ($pos===false) {
				 $pts[0] = $it;
			 } else {
				 $pts[0] = substr($it,0,$pos);
				 $pts[1] = substr($it,$pos+1);
			 }
			 
			 $blocktree = explode('-',$pts[0]);
			 $sub = $items;
			 for ($i=1;$i<count($blocktree)-1;$i++) {
				 $sub = $sub[$blocktree[$i]-1]['items'];
			 }
			 $block = $sub[$blocktree[count($blocktree)-1]-1];
			 unset($block['items']);
			 if ($pos===false) {
				 $block['items'] = array();
			 } else {
				 $subarr = additems($pts[1]);
				 $block['items'] = $subarr;
			 }
			 $outarr[] = $block;
		 } else { //regular item
			 $outarr[] = $it;
		 }
		 
	 }
	 return $outarr;
 }
 
 $newitems = additems($order);
 $itemlist = addslashes(serialize($newitems));
 $query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='$cid'";
 mysql_query($query) or die("Query failed : " . mysql_error());
 echo "OK"; 
?>
