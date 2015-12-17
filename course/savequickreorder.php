<?php
 //IMathAS:  Save page reordering from Quick View
 //(c) 2008 David Lippman
 require("../validate.php");
 
 if (!isset($teacherid)) {
	 echo "Must be a teacher to access this page";
	 exit;
 }
 if (!isset($_POST['order']) || !isset($_GET['cid'])) {
	 echo "Cannot access this page directly";
	 exit;
 }
 $cid = $_GET['cid'];
 $order = $_POST['order'];
 
  foreach ($_POST as $id=>$val) {
	 if ($id=="order") { continue;}
	 $type = $id{0};
	 $typeid = substr($id,1);
	 if ($type=="I") {
		 $query = "UPDATE imas_inlinetext SET title='$val' WHERE id='$typeid'";
	 } else if ($type=="L") {
		 $query = "UPDATE imas_linkedtext SET title='$val' WHERE id='$typeid'";
	 } else if ($type=="A") {
		 $query = "UPDATE imas_assessments SET name='$val' WHERE id='$typeid'";
	 } else if ($type=="F") {
		 $query = "UPDATE imas_forums SET name='$val' WHERE id='$typeid'";
	 } else if ($type=="W") {
		 $query = "UPDATE imas_wikis SET name='$val' WHERE id='$typeid'";
	 } else if ($type=="D") {
		 $query = "UPDATE imas_drillassess SET name='$val' WHERE id='$typeid'";
	 } else if ($type=="B") {
		 $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		 $result = mysql_query($query) or die("Query failed : " . mysql_error());
		 $itemsforblock = unserialize(mysql_result($result,0,0));
		$blocktree = explode('-',$typeid);
		$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		$sub =& $itemsforblock;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$sub[$existingid]['name'] = stripslashes($val);
		$itemorder = addslashes(serialize($itemsforblock));
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid';";
	 }
	 mysql_query($query) or die("Query failed : " . mysql_error());
 }
 
 $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
 $result = mysql_query($query) or die("Query failed : " . mysql_error());
 $items = unserialize(mysql_result($result,0,0));
 
 $newitems = array();

 $newitems = additems($order);
 $itemlist = addslashes(serialize($newitems));
 $query = "UPDATE imas_courses SET itemorder='$itemlist' WHERE id='$cid'";
 mysql_query($query) or die("Query failed : " . mysql_error());
 

 
 
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
 echo "OK"; 
 require("courseshowitems.php");
 $openblocks = Array(0);
 $prevloadedblocks = array(0);
 if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
 if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
 $plblist = implode(',',$prevloadedblocks);
 $oblist = implode(',',$openblocks);
	
 quickview($newitems,0);
?>
