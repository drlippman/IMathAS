<?php
//IMathAS:  Main course page
//(c) 2006 David Lippman

	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		$urlmode = 'https://';
	} else {
		$urlmode = 'http://';
	}
	/*** master php includes *******/
	require("../config.php");
	require("../i18n/i18n.php");
	require("courseshowitems.php");
	$ispublic = true;
	$cid = $_GET['cid'];
	//DB $query = "SELECT name,theme,itemorder,hideicons,picicons,allowunenroll,msgset,chatset,topbar,cploc FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$stm = $DBH->prepare("SELECT name,theme,itemorder,hideicons,picicons,allowunenroll,msgset,topbar,cploc FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	if ($line == null) {
		echo "Course cannot be found";
		exit;
	}
	$coursename = $line['name'];
	$coursetheme = $line['theme'];
	$hideicons = $line['hideicons'];
	$graphicalicons = ($line['picicons']==1);
	$pagetitle = $line['name'];
	$items = unserialize($line['itemorder']);

	if (!isset($_GET['folder']) || $_GET['folder']=='') {
		$_GET['folder'] = '0';
	}
	$blockispublic = false;
	if ($_GET['folder']!='0') {

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
		if (!$blockispublic) {
			$_GET['folder'] = 0;
			$items = unserialize($line['itemorder']);
			unset($backtrack);
			unset($blocktree);
			break;
		}
	}

	$jsAddress1 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/public.php?cid={$_GET['cid']}";
	$jsAddress2 = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

	$openblocks = Array(0);
	$prevloadedblocks = array(0);
	if (isset($_COOKIE['openblocks-'.$cid]) && $_COOKIE['openblocks-'.$cid]!='') {$openblocks = explode(',',$_COOKIE['openblocks-'.$cid]); $firstload=false;} else {$firstload=true;}
	if (isset($_COOKIE['prevloadedblocks-'.$cid]) && $_COOKIE['prevloadedblocks-'.$cid]!='') {$prevloadedblocks = explode(',',$_COOKIE['prevloadedblocks-'.$cid]);}
	$plblist = implode(',',$prevloadedblocks);
	$oblist = implode(',',$openblocks);

	$curBreadcrumb = $breadcrumbbase;
	if (isset($backtrack) && count($backtrack)>0) {
		$curBreadcrumb .= "<a href=\"public.php?cid=$cid&folder=0\">$coursename</a> ";
		for ($i=0;$i<count($backtrack);$i++) {
			$curBreadcrumb .= "&gt; ";
			if ($i!=count($backtrack)-1) {
				$curBreadcrumb .= "<a href=\"public.php?cid=$cid&folder={$backtrack[$i][1]}\">";
			}
			//DB $curBreadcrumb .= stripslashes($backtrack[$i][0]);
			$curBreadcrumb .= $backtrack[$i][0];
			if ($i!=count($backtrack)-1) {
				$curBreadcrumb .= "</a>";
			}
		}
		$curname = $backtrack[count($backtrack)-1][0];
		if (count($backtrack)==1) {
			$backlink =  "<span class=right><a href=\"public.php?cid=$cid&folder=0\">Back</a></span><br class=\"form\" />";
		} else {
			$backlink = "<span class=right><a href=\"public.php?cid=$cid&folder=".$backtrack[count($backtrack)-2][1]."\">Back</a></span><br class=\"form\" />";
		}
	} else {
		$curBreadcrumb .= $coursename;
		$curname = $coursename;
	}

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js\"></script>";
require("../header.php");
?>
<script>
	var getbiaddr = 'getblockitemspublic.php?cid=<?php echo $cid ?>&folder=';
	var oblist = '<?php echo $oblist ?>';
	var plblist = '<?php echo $plblist ?>';
	var cid = '<?php echo $cid ?>';
</script>
<div class=breadcrumb>
		<?php echo $curBreadcrumb ?>
		<div class=clear></div>
</div>
<?php
 echo "<h2>$curname</h2>\n";
 if (count($items)>0) {
	 showitems($items,$_GET['folder'],$blockispublic);
 }

 echo "<hr/>This is the publicly accessible content from a course on $installname.  There may be additional content available by <a href=\"course.php?cid=$cid\">logging in</a>";
require("../footer.php");

 function tzdate($string,$time) {
	  global $tzoffset;
	  //$dstoffset = date('I',time()) - date('I',$time);
	  //return gmdate($string, $time-60*($tzoffset+60*$dstoffset));
	  $serveroffset = date('Z') + $tzoffset*60;
	  return date($string, $time-$serveroffset);
	  //return gmdate($string, $time-60*$tzoffset);
  }
