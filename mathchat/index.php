<?php
  //MathChat
  //(c) 2008 David Lippman

require("../config.php");
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }
$svgimgurl = "$imasroot/filter/graph/svgimg.php";
$svgdloc = "$imasroot/javascript/d.svg";
$editorloc = "$imasroot/editor";
//session_start();
//$sessionid = session_id();

$now = time();
//check for session
//$query = "SELECT * FROM mc_sessions WHERE sessionid='$sessionid'";
//$result = mysql_query($query) or die("Query failed : " . mysql_error());
//if (mysql_num_rows($result)==0) {
if (isset($_GET['isactive'])) { //request to see if room is being used
	$on = $now - 15;
	$query = "SELECT name FROM mc_sessions WHERE mc_sessions.room='{$_GET['isactive']}' AND lastping>$on";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo mysql_num_rows($result);
	exit;
}
if (!isset($_GET['userid'])) {
	if (empty($_REQUEST['uname']) || empty($_REQUEST['room'])) {
		echo "No identity provided.  Quitting";
		exit;
	}
	//no session exists yet.  Assume we were sent info.  
	//TODO for general use - write login page
	$uname = $_REQUEST['uname'];
	$room = $_REQUEST['room'];
	$mathdisp = $_REQUEST['mathdisp'];
	$graphdisp = $_REQUEST['graphdisp'];
	//$query = "INSERT INTO mc_sessions (sessionid,name,room,mathdisp,graphdisp,lastping) ";
	//$query .= "VALUES ('$sessionid','$uname','$room','$mathdisp','$graphdisp',$now)";
	$query = "INSERT INTO mc_sessions (name,room,mathdisp,graphdisp,lastping) ";
	$query .= "VALUES ('$uname','$room','$mathdisp','$graphdisp',$now)";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$mcsession['userid'] = mysql_insert_id();
	$mcsession['name'] = $uname;
	$mcsession['room'] = $room;
	$mcsession['mathdisp'] = $mathdisp;
	$mcsession['graphdisp'] = $graphdisp;
	$roomname = $_REQUEST['roomname'];
	
	$old = time() - 24*60*60; //1 day old
	$query = "DELETE FROM mc_msgs WHERE time<$old";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "DELETE FROM mc_sessions WHERE lastping<$old";
	mysql_query($query) or die("Query failed : " . mysql_error());
	//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'].'?userid='.$mcsession['userid']);
	//exit;
} else {
	/*if (!empty($_REQUEST['uname']) && !empty($_REQUEST['room'])) {
		$query = "UPDATE mc_sessions SET name='{$_REQUEST['uname']}',room='{$_REQUEST['room']}' WHERE sessionid='$sessionid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$_SESSION['roomname'] = $_REQUEST['roomname'];
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		exit;
	}*/
	$query = "SELECT * FROM mc_sessions WHERE userid='{$_GET['userid']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$mcsession = mysql_fetch_array($result, MYSQL_ASSOC);	
}
$mcsession['useed'] = 1;

if (isset($_POST['addtxt'])) {
	$query = "INSERT INTO mc_msgs (userid,msg,time) VALUES ";
	$query .= "('{$mcsession['userid']}','{$_POST['addtxt']}',$now)";
	mysql_query($query) or die("Query failed : " . mysql_error());
}
if (isset($_REQUEST['update'])) {
	$query = "SELECT mc_sessions.name,mc_msgs.msg,mc_msgs.id FROM mc_sessions ";
	$query .= "JOIN mc_msgs ON mc_sessions.userid=mc_msgs.userid ";
	$lastmsg = intval($_REQUEST['update']);
	if ($_REQUEST['update']==0) {
		$last = $now - 5*60;
		$query .= "WHERE mc_sessions.room='{$mcsession['room']}' AND mc_msgs.time > $last ";
	} else {
		$query .= "WHERE mc_sessions.room='{$mcsession['room']}' AND mc_msgs.id > $lastmsg ";
	}
	$query .= "ORDER BY mc_msgs.time";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo '{"msgs":[';
	$i =0;
	while ($row = mysql_fetch_row($result)) {
		if ($i>0) {echo ',';}
		$i++;
		//echo '<div class="msg" id="'.$row[2].'"><div class="user">'.$row[0].'</div>';
		//echo '<div class="txt">'.$row[1].'</div></div>';
		echo '{"id": '.$row[2].',"user": "'. str_replace(array('\\','/','"',), array('\\\\','\/','\"'), $row[0]).'",';
		echo '"msg":"'.str_replace(array('\\','/','"',), array('\\\\','\/','\"'), $row[1]).'"}';
		
	}
	echo '],"users":[';
	$query = "UPDATE mc_sessions SET lastping=$now WHERE userid='{$mcsession['userid']}'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	$on = $now - 15;
	//echo '<div id="userlist">';
	$query = "SELECT name FROM mc_sessions WHERE mc_sessions.room='{$mcsession['room']}' AND lastping>$on ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$i =0;
	while ($row = mysql_fetch_row($result)) {
		//echo $row[0].'<br/>';
		if ($i>0) {echo ',';}
		$i++;
		echo '"'.str_replace(array('\\','/','"',), array('\\\\','\/','\"'), $row[0]).'"';
		
	}
	echo ']}';
	//JSON this so we can check for non-new message ids?
	//$obj = str_replace(array('\\','/','"',), array('\\\\','\/','\"'), $obj);
        //return '"' . $obj . '"';
        //{ "msgs": [{"id": 5, "user"="name", "msg": "the text escaped},
        //	     { blah blha }],
        //  "users": [ "name","name","name"]
	//echo '</div>';
	
} else {
	$placeinhead = '<script type="text/javascript">if (typeof window.onload == "function") { var existing = window.onload; window.onload = function() { existing(); updatemsgs();};} else { window.onload = updatemsgs;}';
	$placeinhead .= 'var postback = "'.$urlmode.$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'].'?userid='.$mcsession['userid'] .'";</script>';
	$useeditor = "addtxt";
	require("header.php");
?>
<img id="loading" src="loading.gif" />
<div id="title">Math Chat - <?php echo $roomname;?></div>
<div id="msgbody">
</div>
<div id="users"><b>Users:</b><div id="userscontent"></div></div>
<div id="inputarea">
<table>
<tr>
<td>
<textarea name="addtxt" id="addtxt" rows="5" cols="80"></textarea>
</td><td>
<input type="button" value="Post" onclick="posttxt()"/>
</td></tr>
</table>
</div>
<?php
	require("footer.php");
}

?>
