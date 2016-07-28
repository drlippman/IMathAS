<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman
   require("../config.php");
   require("courseshowitems.php");
   $ispublic = true;

   $cid = $_GET['cid'];
   require("../filter/filter.php");
   //DB $query = "SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,topbar,cploc FROM imas_courses WHERE id='$cid'";
   //DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
   //DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
   $stm = $DBH->prepare("SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,topbar,cploc FROM imas_courses WHERE id=:id");
   $stm->execute(array(':id'=>$cid));
   $line = $stm->fetch(PDO::FETCH_ASSOC);
   if ($line == null) {
	   echo "Course does not exist. \n";
	   exit;
   }
   $hideicons = $line['hideicons'];
   $graphicalicons = ($line['picicons']==1);
   $pagetitle = $line['name'];
   $items = unserialize($line['itemorder']);


   //if ($_GET['folder']!='0') {
   $blockispublic = false;
   if (strpos($_GET['folder'],'-')!==false) {

	   $now = time();
	   $blocktree = explode('-',$_GET['folder']);
	   $backtrack = array();
	   for ($i=1;$i<count($blocktree);$i++) {
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
