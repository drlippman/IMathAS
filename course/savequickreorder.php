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


 $stm = $DBH->prepare("SELECT itemorder,blockcnt FROM imas_courses WHERE id=:id");
 $stm->execute(array(':id'=>$cid));
 list($itemorder,$blockcnt) = $stm->fetch(PDO::FETCH_NUM);
 $items = unserialize($itemorder);

 $order = json_decode($_POST['order'], true);
 $newblocks = [];
 $newitems = additems2($order);

 if (md5($itemorder)!=$_POST['checkhash']) {
 	 echo '0:'._('Error: Items have changed in the course, perhaps in another window, since this page was loaded. Refresh the page to load changes and try again.');
 	 exit;
 } else if (countitems($items) != countitems($newitems) - count($newblocks)) {
 	 echo '0:'._('Error: Some item data was not sent correctly. Please try again.');
 	 exit;
 }

 $DBH->beginTransaction();

  foreach ($_POST as $id=>$val) {
	 if ($id=="order" || trim($val)=='') { continue;}
	 $type = $id[0];
	 $typeid = substr($id,1);
	 if ($type=="I") {
		 $stm = $DBH->prepare("UPDATE imas_inlinetext SET title=:title WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':title'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 } else if ($type=="L") {
		 $stm = $DBH->prepare("UPDATE imas_linkedtext SET title=:title WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':title'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 } else if ($type=="A") {
		 $stm = $DBH->prepare("UPDATE imas_assessments SET name=:name WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 } else if ($type=="F") {
		 $stm = $DBH->prepare("UPDATE imas_forums SET name=:name WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 } else if ($type=="W") {
		 $stm = $DBH->prepare("UPDATE imas_wikis SET name=:name WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 } else if ($type=="D") {
		 $stm = $DBH->prepare("UPDATE imas_drillassess SET name=:name WHERE id=:id AND courseid=:cid");
		 $stm->execute(array(':name'=>$val, ':id'=>$typeid, ':cid'=>$cid));
	 }
 }

 $itemlist = serialize($newitems);
 $stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
 $stm->execute(array(':itemorder'=>$itemlist, ':blockcnt'=>$blockcnt, ':id'=>$cid));
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

function additems2($arr) {
  global $items, $blockcnt, $newblocks;
  $outitems = array();
  foreach ($arr as $id=>$item) {
    if (strpos($item['id'],'-')!==false) { //is block
      $blocktree = explode('-',$item['id']);
      $sub = $items;
      for ($i=1;$i<count($blocktree)-1;$i++) {
        $sub = $sub[$blocktree[$i]-1]['items'];
      }

      $block = $sub[$blocktree[count($blocktree)-1]-1];
      if (!empty($_POST['B' . $item['id']])) {
        $block['name'] = htmlentities($_POST['B' . $item['id']]);
      }
      if (!empty($item['children'])) {
        $block['items'] = additems2($item['children']);
      } else {
        $block['items'] = array();
      }
      $outitems[] = $block;
    } else if (substr($item['id'],0,8)=='newblock') {
        $newblockrefid = substr($item['id'], 8);
        if (isset($_POST['NB' . $newblockrefid])) {
            $title = htmlentities($_POST['NB' . $newblockrefid]);
        } else {
            $title = _('New Block');
        }
        $block = array(
            'name' => $title,
            'id' => $blockcnt,
            'startdate' => time() + 60*60,
            'enddate' => time() + 7*24*60*60,
            'avail' => 1,
            'SH' => 'HO0',
            'colors' => '',
            'public' => 0,
            'innav' => 0,
            'fixedheight' => 0,
            'grouplimit' => [],
            'items' => []
        );
        $newblocks[] = $blockcnt;
        $blockcnt++;
        if (!empty($item['children'])) {
            $block['items'] = additems2($item['children']);
        } else {
            $block['items'] = array();
        }
        $outitems[] = $block;
    } else {
      $outitems[] = $item['id'];
    }
  }
  return $outitems;
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
 $openblocks = array_merge($openblocks, $newblocks);
 $plblist = implode(',',$prevloadedblocks);
 $oblist = implode(',',$openblocks);

 quickview($newitems,0);

?>
