<?php
//IMathAS:  Displays a single question for preview/testing
//(c) 2006 David Lippman
	require("../validate.php");
	if ($myrights<20) {
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	require("../assessment/header.php");
	if (!isset($_POST['seed']) || isset($_POST['regen'])) {
		$seed = rand(0,10000);
		$attempt = 0;
	} else {
		$seed = $_POST['seed'];
		$attempt = $_POST['attempt']+1;
	}
	if (isset($_GET['onlychk']) && $_GET['onlychk']==1) {
		$onlychk = 1;
	} else {
		$onlychk = 0;
	}
	if (isset($_GET['formn']) || isset($_GET['checked'])) {
		echo '<p>';
	}
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		$formn = $_GET['formn'];
		$loc = $_GET['loc'];
		if (isset($_GET['checked']) || isset($_GET['usecheck'])) {
			$chk = "&checked=0";
		} else {
			$chk = '';
		}
		echo "<script type=\"text/javascript\">";
		echo "if (window.opener && !window.opener.closed) {";
		if ($onlychk==1) {
		  echo "var prevnext = window.opener.getnextprev('$formn','{$_GET['loc']}',true);";
		} else {
		  echo "var prevnext = window.opener.getnextprev('$formn','{$_GET['loc']}');";	
		}
		echo "	  if (prevnext[0][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$_GET['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">Prev</a> ');
			  } else {
				  document.write('Prev ');
			  }
			  if (prevnext[1][1]>0){
				  document.write('<a href=\"testquestion.php?cid={$_GET['cid']}$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">Next</a> ');
			  } else {
				  document.write('Next ');
			  }
			}
			</script>";
	}
	
	if (isset($_GET['checked'])) {
		echo "<input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\"Mark Question for Use\" onclick=\"parentcbox.checked=this.checked\" ";
		//if ($_GET['checked']==1) {
		//	echo "checked=\"checked\"";
		//}
		echo "/> Mark Question for Use";
		echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"{$_GET['loc']}\");
		  document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  </script>";
	}
	if (isset($_GET['formn']) || isset($_GET['checked'])) {
		echo '</p>';
	}
	require("../assessment/displayq2.php");
	if (isset($_POST['seed'])) {
		$score = scoreq(0,$_GET['qsetid'],$_POST['seed'],$_POST['qn0']);
		echo "<p>Score on last answer: $score/1</p>\n";
	}
	
	echo "<form method=post action=\"testquestion.php?cid={$_GET['cid']}&qsetid={$_GET['qsetid']}";
	if (isset($_POST['usecheck'])) {
		echo "&checked=".$_GET['usecheck'];
	} else if (isset($_GET['checked'])) {
		echo "&checked=".$_GET['checked'];
	}
	if (isset($_GET['formn'])) {
		echo "&formn=".$_GET['formn'];
		echo "&loc=".$_GET['loc'];
	}
	if (isset($_GET['onlychk'])) {
		echo "&onlychk=".$_GET['onlychk'];
	}
	echo "\" onsubmit=\"doonsubmit()\">\n";

	echo "<input type=hidden name=seed value=\"$seed\">\n";
	echo "<input type=hidden name=attempt value=\"$attempt\">\n";
	unset($lastanswers);
	displayq(0,$_GET['qsetid'],$seed,true,$attempt);
	echo "<input type=submit value=\"Submit\"><input type=submit name=\"regen\" value=\"Submit and Regen\">\n";
	
	echo "</form>\n";
	
	$query = "SELECT imas_users.email,imas_questionset.author,imas_questionset.description,imas_questionset.lastmoddate ";
	$query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id='{$_GET['qsetid']}'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$email = mysql_result($result,0,0);
	$author = mysql_result($result,0,1);
	$descr = mysql_result($result,0,2);
	
	echo "<p>Question id: {$_GET['qsetid']}.  <a href=\"mailto:$email\">E-mail owner</a> to report problems</p>";
	
	echo "<p>Description: $descr</p><p>Author: $author</p>";
	$lastmod = date("m/d/y g:i a",mysql_result($result,0,3));
	echo "<p>Last Modified: $lastmod</p>";
	require("../footer.php");
?>
	
