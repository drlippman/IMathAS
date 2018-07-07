<?php
 //IMathAS:  Save page reordering from Quick View
 //(c) 2008 David Lippman
 require("../init.php");

 if (!isset($teacherid)) {
	 echo "Must be a teacher to access this page";
	 exit;
 }
 if (!isset($_POST['order']) || !isset($_GET['cid'])) {
	 echo "Cannot access this page directly";
	 exit;
 }
 $cid = Sanitize::courseId($_GET['cid']);
 $order = $_POST['order'];
 $DBH->beginTransaction();
 $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
 $stm->execute(array(':id'=>$cid));
 $itemorder = $stm->fetchColumn(0);
 $items = unserialize($itemorder);
 if (md5($itemorder)!=$_POST['checkhash']) {
 	 echo '0:'._('Error: Items have changed in the course, perhaps in another window, since this page was loaded. Refresh the page to load changes and try again.');
 	 exit;
 } else if (countitems($items) != countitems(additems($order))) {
 	 echo '0:'._('Error: Some item data was not sent correctly. Please try again.');
 	 exit;
 }

  foreach ($_POST as $id=>$val) {
	 if ($id=="order" || trim($val)=='') { continue;}
	 $type = $id{0};
	 $typeid = substr($id,1);
	 if ($type=="I") {
		 $stm = $DBH->prepare("UPDATE imas_inlinetext SET title=:title WHERE id=:id");
		 $stm->execute(array(':title'=>$val, ':id'=>$typeid));
	 } else if ($type=="L") {
		 $stm = $DBH->prepare("UPDATE imas_linkedtext SET title=:title WHERE id=:id");
		 $stm->execute(array(':title'=>$val, ':id'=>$typeid));
	 } else if ($type=="A") {
		 $stm = $DBH->prepare("UPDATE imas_assessments SET name=:name WHERE id=:id");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid));
	 } else if ($type=="F") {
		 $stm = $DBH->prepare("UPDATE imas_forums SET name=:name WHERE id=:id");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid));
	 } else if ($type=="W") {
		 $stm = $DBH->prepare("UPDATE imas_wikis SET name=:name WHERE id=:id");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid));
	 } else if ($type=="D") {
		 $stm = $DBH->prepare("UPDATE imas_drillassess SET name=:name WHERE id=:id");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid));
	 } else if ($type=="B") {
		$blocktree = explode('-',$typeid);
		$existingid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$sub[$existingid]['name'] = $val;
	 }
 }

 $newitems = array();

 $newitems = additems($order);
 $itemlist = serialize($newitems);
 $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
 $stm->execute(array(':itemorder'=>$itemlist, ':id'=>$cid));
 $DBH->commit();

 function countitems($arr) {
 	$n = 0;
 	foreach ($arr as $v) {
 		if (is_array($v)) {
 			$n++;
 			$n += countitems($v['items']);
 		} else {
 			$n++;
 		}
 	}
 	return $n;
 }
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
 echo '1,'.md5($itemlist).':';

 require("courseshowitems.php");
 $openblocks = Array(0);
 $prevloadedblocks = array(0);
 if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
 if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
 $plblist = implode(',',$prevloadedblocks);
 $oblist = implode(',',$openblocks);

 quickview($newitems,0);

?>
