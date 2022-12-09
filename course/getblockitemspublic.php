<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman

   require("../init_without_validate.php");
   $ispublic = true;
   $_SESSION = ['mathdisp'=>1, 'graphdisp'=>1, 'useed'=>1];
   
   $cid = Sanitize::courseId($_GET['cid']);
   require("../filter/filter.php");

   $stm = $DBH->prepare("SELECT name,itemorder,allowunenroll,msgset,UIver FROM imas_courses WHERE id=:id");
   $stm->execute(array(':id'=>$cid));
   $line = $stm->fetch(PDO::FETCH_ASSOC);
   if ($line == null) {
	   echo "Course does not exist. \n";
	   exit;
   }
   $pagetitle = $line['name'];
   $items = unserialize($line['itemorder']);
   $courseUIver = $line['UIver'];

   require("courseshowitems.php");

   //if ($_GET['folder']!='0') {
   $blockispublic = false;
   if (strpos($_GET['folder'],'-')!==false) {

	   $now = time();
	   $blocktree = explode('-',$_GET['folder']);
	   $backtrack = array();
	   for ($i=1;$i<count($blocktree);$i++) {
        if (!isset($items[$blocktree[$i]-1])) {
            $_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
        }
		$backtrack[] = array($items[$blocktree[$i]-1]['name'],implode('-',array_slice($blocktree,0,$i+1)));
		if ($items[$blocktree[$i]-1]['public']==1) {
			$blockispublic = true;
		}
		if (!isset($teacherid) && $items[$blocktree[$i]-1]['avail']<2 && $items[$blocktree[$i]-1]['SH'][0]!='S' &&($now<$items[$blocktree[$i]-1]['startdate'] || $now>$items[$blocktree[$i]-1]['enddate'] || $items[$blocktree[$i]-1]['avail']=='0')) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
		$items = $items[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	   }
   }
   if (!$blockispublic) {
	   echo "Content not public";
	   exit;
   }

   $openblocks = Array(0);
   if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]);}
   if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);} else {$prevloadedblocks = array();}
   if (in_array($_GET['folder'],$prevloadedblocks)) { $firstload = false;} else {$firstload = true;}

   //$oblist = implode(',',$openblocks);
   //echo "<script>\n";
   //echo "  oblist += ',$oblist';\n";
   //echo "</script>\n";


   if (count($items)>0) {

	   showitems($items,$_GET['folder'],$blockispublic);

   }
   if ($firstload) {
	   echo "<script>document.cookie = 'openblocks-$cid=' + oblist;</script>\n";
   }
   function tzdate($string,$time) {
	  global $tzoffset;
	  //$dstoffset = date('I',time()) - date('I',$time);
	  //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
	  $serveroffset = date('Z') + $tzoffset*60;
	  return date($string, $time-$serveroffset);
	  //return gmdate($string, $time-60*$tzoffset);
  }


?>
