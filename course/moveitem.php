<?php 
//IMathAS: Move item dialog
//(c) 2017 David Lippman

require("../init.php");

$cid = Sanitize::courseId($_GET['cid']);
if (!isset($teacherid)) {
	echo 'You must be a teacher to access this page';
	exit;
}                       
//$itemtomove will be imas_items.id or 'B'.block['id']
$itemtomove = $_REQUEST['item'];
$curblock = $_REQUEST['block'];
if (!preg_match('/^B?[0-9]+$/',$itemtomove) || !preg_match('/^[0-9\-]+$/',$curblock)) {
	echo 'Invalid item or block format';
	exit;	
}

$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));

if (isset($_POST['newblock'])) {
	if (!preg_match('/^[0-9\-]+$/',$_POST['newblock'])) {
		echo 'Invalid block format';
		exit;	
	}
	if (!preg_match('/^[0-9a-zA-Z\-]+$/',$_POST['moveafter'])) {
		echo 'Invalid move dest format';
		exit;	
	}
	$blocktree = explode('-',$curblock);
	$sub =& $items;
	for ($i=1;$i<count($blocktree)-1;$i++) {
		$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	if (count($blocktree)>1) {
		$curblockitems =& $sub[$blocktree[$i]-1]['items'];
	} else {
		$curblockitems =& $sub;
	}
	
	$blocktree = explode('-',$_POST['newblock']);
	$sub =& $items;
	for ($i=1;$i<count($blocktree)-1;$i++) {
		$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	if (count($blocktree)>1) {
		$newblockitems =& $sub[$blocktree[$i]-1]['items'];
	} else {
		$newblockitems =& $sub;
	}
	$itemloc = -1;
	foreach ($curblockitems as $k=>$blockitem) {
		if (is_array($blockitem)) {
			if ('B'.$blockitem['id'] == $itemtomove) {
				$itemloc = $k;
				break;
			}
		} else if ($blockitem == $itemtomove) {
			$itemloc = $k;
			break;
		}
	}
	if ($itemloc == -1) {
		echo 'Item to move could not be found';
		exit;
	}
	//store the item
	$actualitem = $curblockitems[$k];
	//remove from array
	array_splice($curblockitems,$k,1);
	
	$moveafter = $_POST['moveafter'];
	$newloc = -1;
	if ($moveafter=='top') {
		$newloc = 0;
	} else {
		foreach ($newblockitems as $k=>$blockitem) {
			if (is_array($blockitem)) {
				if ('B'.$blockitem['id'] == $moveafter) {
					$newloc = $k+1;
					break;
				}
			} else if ($blockitem == $moveafter) {
				$newloc = $k+1;
				break;
			}
		}
	}
	if ($newloc == -1) {
		echo 'Location to insert at could not be found';
		exit;
	}
	if (is_array($actualitem)) {
		array_splice($newblockitems,$newloc,0,array($actualitem));
	} else {
		array_splice($newblockitems,$newloc,0,$actualitem);
	}
	
	$itemlist = serialize($items);
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemlist, ':id'=>$cid));
	
	echo 'OK';
	exit;
}

$briefitems = array();
$itemtomovename = "";
function makebrief($items,&$briefitems) {
	global $itemtomove, $itemtomovename;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			$newblock = array();
			$newblock['name'] = $item['name'];
			$newblock['id'] = $item['id'];
			if ($itemtomove == "B".$item['id']) {
				$itemtomovename = $item['name'];
			}
			$newblock['items'] = array();
			makebrief($item['items'], $newblock['items']);
			$briefitems[] = $newblock;
		} else {
			$briefitems[] = $item;
		}
	}
}
makebrief($items, $briefitems);




$itemdata = array();
$query = "SELECT 'Assessment',id,name FROM imas_assessments WHERE courseid=:cid1 UNION ";
$query .= "SELECT 'InlineText',id,title FROM imas_inlinetext WHERE courseid=:cid2 UNION ";
$query .= "SELECT 'LinkedText',id,title FROM imas_linkedtext WHERE courseid=:cid3 UNION ";
$query .= "SELECT 'Forum',id,name FROM imas_forums WHERE courseid=:cid4 UNION ";
$query .= "SELECT 'Wiki',id,name FROM imas_wikis WHERE courseid=:cid5 UNION ";
$query .= "SELECT 'Drill',id,name FROM imas_drillassess WHERE courseid=:cid6";
$stm = $DBH->prepare($query);
$stm->execute(array(':cid1'=>$cid, ':cid2'=>$cid, ':cid3'=>$cid, ':cid4'=>$cid, ':cid5'=>$cid, ':cid6'=>$cid));

while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if (!isset($itemdata[$row[0]])) { $itemdata[$row[0]] = array(); }
	$itemdata[$row[0]][$row[1]] = $row[2];
}

//store item.id => array(item type, item name/title)
$iteminfo = array();
$stm = $DBH->prepare("SELECT id,itemtype,typeid FROM imas_items WHERE courseid=:cid");
$stm->execute(array(':cid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[1]=='Calendar') {
		$iteminfo[$row[0]] = array($row[1], _('Calendar'));
	} else {
		$iteminfo[$row[0]] = array($row[1], $itemdata[$row[1]][$row[2]]);
	}
}

if ($itemtomove{0} != 'B') {
	$itemtomovename = $iteminfo[$itemtomove][1];
} else {
	$itemtomovename = $itemtomovename;
}

$flexwidth = true;
$nologo = true;
$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/moveitem.js"></script>';
$placeinhead .= '<script type="text/javascript">
  var blockinfo = '.json_encode($briefitems).';
  var iteminfo = '.json_encode($iteminfo).';
  var item = "'.$itemtomove.'";
  var block = "'.$curblock.'";
  </script>';
$placeinhead .= '<style type="text/css"> select { max-width: 100%;} </style>';
require("../header.php");

?>
<div id="headerforms" class="pagetitle">
 <h2>Move Item</h2>
</div>
<p>Moving <b><?php echo Sanitize::encodeStringForDisplay($itemtomovename); ?></b></p>
<p>
<label for="blockselect">Move this item into block</label> <br/>
<select id="blockselect" name="blockselect"></select>
</p>

<p><label for="itemselect">after item</label> <br/>
<select id="itemselect" name="itemselect"></select>
</p>

<p>
<button type="button" onclick="cancelmove()" class="secondarybtn">Cancel</button> 
<button type="button" onclick="moveitem()">Move</button></p>

<p class="noticetext" id="error"></p>
<?php
require("../footer.php");
?>
