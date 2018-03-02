<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../assessment/displayq2.php");
require("../assessment/testutil.php");
$assessver = 2;

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Test Question";
$asid = 0;

	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {
	//data manipulation here
	$useeditor = 1;
	if (isset($_GET['seed'])) {
		$seed = Sanitize::onlyInt($_GET['seed']);
		$attempt = 0;
	} else if (!isset($_POST['seed']) || isset($_POST['regen'])) {
		$seed = rand(0,10000);
		$attempt = 0;
	} else {
		$seed = Sanitize::onlyInt($_POST['seed']);
		$attempt = $_POST['attempt']+1;
	}
	if (isset($_GET['onlychk']) && $_GET['onlychk']==1) {
		$onlychk = 1;
	} else {
		$onlychk = 0;
	}
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		$formn = Sanitize::encodeStringForJavascript($_GET['formn']);
		$loc = Sanitize::encodeStringForJavascript($_GET['loc']);
		if (isset($_GET['checked']) || isset($_GET['usecheck'])) {
			$chk = "&checked=0";
		} else {
			$chk = '';
		}
		if ($onlychk==1) {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','$loc',true);";
		} else {
		  $page_onlyChkMsg = "var prevnext = window.opener.getnextprev('$formn','$loc');";
		}
	}

	
	$lastanswers = array();
	$scores = array();
	$rawscores = array();
	$qn = 27;  //question number to use during testing
	$lastanswers[$qn] = '';
	$rawscores[$qn] = -1;
	$scores[$qn] = -1;

	if (isset($_POST['seed'])) {
		list($score,$rawscores[$qn]) = scoreq($qn,$_GET['qsetid'],$_POST['seed'],$_POST['qn'.$qn],$attempt-1);
		$scores[$qn] = $score;
		//DB $lastanswers[0] = stripslashes($lastanswers[0]);
		$page_scoreMsg =  "<p>Score on last answer: ".Sanitize::encodeStringForDisplay($score)."/1</p>\n";
	} else {
		$page_scoreMsg = "";
		$_SESSION['choicemap'] = array();
	}
  $cid = Sanitize::courseId($_GET['cid']);
	$page_formAction = "testquestion.php?cid=$cid&qsetid=".Sanitize::encodeUrlParam($_GET['qsetid']);

	if (isset($_POST['usecheck'])) {
		$page_formAction .=  "&checked=".Sanitize::encodeUrlParam($_GET['usecheck']);
	} else if (isset($_GET['checked'])) {
		$page_formAction .=  "&checked=".Sanitize::encodeUrlParam($_GET['checked']);
	}
	if (isset($_GET['formn'])) {
		$page_formAction .=  "&formn=".Sanitize::encodeUrlParam($_GET['formn']);
		$page_formAction .=  "&loc=".Sanitize::encodeUrlParam($_GET['loc']);
	}
	if (isset($_GET['onlychk'])) {
		$page_formAction .=  "&onlychk=".Sanitize::encodeUrlParam($_GET['onlychk']);
	}
	if (isset($_GET['fixedseeds'])) {
		$page_formAction .=  "&fixedseeds=1";
	}

	//DB $query = "SELECT imas_users.email,imas_questionset.* ";
	//DB $query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id='{$_GET['qsetid']}'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_assoc($result);
	$query = "SELECT imas_users.email,imas_questionset.* ";
	$query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$_GET['qsetid']));
	$line = $stm->fetch(PDO::FETCH_ASSOC);

	$lastmod = date("m/d/y g:i a",$line['lastmoddate']);

	if (isset($CFG['AMS']['showtips'])) {
		$showtips = $CFG['AMS']['showtips'];
	} else {
		$showtips = 1;
	}
	if (isset($CFG['AMS']['eqnhelper'])) {
		$eqnhelper = $CFG['AMS']['eqnhelper'];
	} else {
		$eqnhelper = 0;
	}

	//DB $query = "SELECT imas_libraries.name,imas_users.LastName,imas_users.FirstName FROM imas_libraries,imas_library_items,imas_users  WHERE imas_libraries.id=imas_library_items.libid AND imas_library_items.ownerid=imas_users.id AND imas_library_items.qsetid='{$_GET['qsetid']}'";
	//DB $resultLibNames = mysql_query($query) or die("Query failed : " . mysql_error());
	$resultLibNames = $DBH->prepare("SELECT imas_libraries.name,imas_users.LastName,imas_users.FirstName FROM imas_libraries,imas_library_items,imas_users  WHERE imas_libraries.id=imas_library_items.libid AND imas_libraries.deleted=0 AND imas_library_items.deleted=0 AND imas_library_items.ownerid=imas_users.id AND imas_library_items.qsetid=:qsetid");
	$resultLibNames->execute(array(':qsetid'=>$_GET['qsetid']));
}

/******* begin html output ********/
$sessiondata['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet

$useeqnhelper = $eqnhelper;

require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
	$useeditor = 1;
	$brokenurl = $GLOBALS['basesiteurl'] . "/course/savebrokenqflag.php?qsetid=".Sanitize::encodeUrlParam($_GET['qsetid']).'&flag=';
	?>
	<script type="text/javascript">
		var BrokenFlagsaveurl = '<?php echo $brokenurl;?>';
		function submitBrokenFlag(tagged) {
		  url = BrokenFlagsaveurl + tagged;
		  if (window.XMLHttpRequest) {
		    req = new XMLHttpRequest();
		  } else if (window.ActiveXObject) {
		    req = new ActiveXObject("Microsoft.XMLHTTP");
		  }
		  if (typeof req != 'undefined') {
		    req.onreadystatechange = function() {submitBrokenFlagDone(tagged);};
		    req.open("GET", url, true);
		    req.send("");
		  }
		}

		function submitBrokenFlagDone(tagged) {
		  if (req.readyState == 4) { // only if req is "loaded"
		    if (req.status == 200) { // only if "OK"
			    if (req.responseText=='OK') {
				    toggleBrokenFlagmsg(tagged);
			    } else {
				    alert(req.responseText);
				    alert("Oops, error toggling the flag");
			    }
		    } else {
			   alert(" Couldn't save changes:\n"+ req.status + "\n" +req.statusText);
		    }
		  }
		}
		function toggleBrokenFlagmsg(tagged) {
			document.getElementById("brokenmsgbad").style.display = (tagged==1)?"block":"none";
			document.getElementById("brokenmsgok").style.display = (tagged==1)?"none":"block";
			if (tagged==1) {alert("Make sure you also contact the question author or support so they know why you marked the question as broken");}
		}
	</script>
	<?php
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		echo '<p>';
		echo "<script type=\"text/javascript\">";
		echo "var numchked = -1;";
		echo "if (window.opener && !window.opener.closed) {";
		echo $page_onlyChkMsg;
		echo " if (prevnext[0][1]>0){
				  document.write('<a href=\"testquestion.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">Prev</a> ');
			  } else {
				  document.write('Prev ');
			  }
			  if (prevnext[1][1]>0){
				  document.write('<a href=\"testquestion.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">Next</a> ');
			  } else {
				  document.write('Next ');
			  }
			  if (prevnext[2]!=null) {
			  	document.write(' <span id=\"numchked\">'+prevnext[2]+'</span> checked');
				numchked = prevnext[2];
			  }
			  if (prevnext[3]!=null) {
			  	document.write(' '+prevnext[3]+' remaining');
			  }
			}
			</script>";
		echo '</p>';
	}

	if (isset($_GET['checked'])) {
		echo "<p><input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\"Mark Question for Use\" onclick=\"parentcbox.checked=this.checked;togglechk(this.checked)\" ";
		echo "/> Mark Question for Use</p>";
		echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"$loc\");
		  document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  function togglechk(ischk) {
			  if (numchked!=-1) {
				if (ischk) {
					numchked++;
				} else {
					numchked--;
				}
				document.getElementById(\"numchked\").innerHTML = numchked;
			  }
		  }
		  </script>";
	}
	if (isset($_GET['fixedseeds'])) {
		echo "<p id=\"fixedseedbox\" style=\"display:none\">";
		echo "Seed: $seed. <input type=\"checkbox\" name=\"useinfixed\" id=\"useinfixed\" onclick=\"chguseinfixed(this.checked)\" ";
		echo "/> Include in fixed seed list</p>";
		echo '<script type="text/javascript">
		$(function() {
			var dofixed = opener.document.getElementById("fixedseedwrap").style.display;
			if (dofixed!="none") {
				var fixedseedlist = opener.document.getElementById("fixedseeds").value;
				if (fixedseedlist.match(/\b'.$seed.'\b/)) {
					$("#useinfixed").prop("checked",true);
				}
				$("#fixedseedbox").show();
			}
		});
		function chguseinfixed(state) {
			var fixedseedlist = opener.document.getElementById("fixedseeds").value;
			if (state==true) {
				if (!fixedseedlist.match(/\b'.$seed.'\b/)) {
					if (fixedseedlist=="") {
						fixedseedlist = "'.$seed.'";
					} else {
						fixedseedlist += ",'.$seed.'";
					}
				}
			} else {
				fixedseedlist = fixedseedlist.replace(/\b'.$seed.'(,|$)/,"").replace(/,$/,"");
			}
			opener.document.getElementById("fixedseeds").value = fixedseedlist;
		}
		</script>';
	}

	echo $page_scoreMsg;
	echo '<script type="text/javascript"> function whiteout() { e=document.getElementsByTagName("div");';
	echo 'for (i=0;i<e.length;i++) { if (e[i].className=="question") {e[i].style.backgroundColor="#fff";}}}</script>';
	echo "<form method=post enctype=\"multipart/form-data\" action=\"$page_formAction\" onsubmit=\"doonsubmit(this,true,true)\">\n";
	echo "<input type=hidden name=seed value=\"$seed\">\n";
	echo "<input type=hidden name=attempt value=\"$attempt\">\n";

	if (isset($rawscores)) {
		if (strpos($rawscores[$qn],'~')!==false) {
			$colors = explode('~',$rawscores[$qn]);
		} else {
			$colors = array($rawscores[$qn]); //scorestocolors($rawscores,1,0,false);
		}
	} else {
		$colors = array();
	}
	if ($_GET['cid']=="admin") { //trigger debug messages
		$teacherid = "admin";
	}
	displayq($qn,$_GET['qsetid'],$seed,true,true,$attempt,false,false,false,$colors);
	echo "<input type=submit value=\"Submit\"><input type=submit name=\"regen\" value=\"Submit and Regen\">\n";
	echo "<input type=button value=\"White Background\" onClick=\"whiteout()\"/>";
	echo "<input type=button value=\"Show HTML\" onClick=\"document.getElementById('qhtml').style.display='';\"/>";
	echo "</form>\n";

	echo '<code id="qhtml" style="display:none">';
	$message = displayq($qn,$_GET['qsetid'],$seed,false,false,0,true);
	$message = printfilter(forcefiltergraph($message));
	$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
	$message = str_replacE('`','\`',$message);
	echo htmlentities($message);
	echo '</code>';

	if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
		printf("<p>Question id: %s.  ", Sanitize::encodeStringForDisplay($_GET['qsetid']));//<a href=\"$imasroot/msgs/msglist.php?add=new&cid={$CFG['GEN']['sendquestionproblemsthroughcourse']}&to={$line['ownerid']}&title=Problem%20with%20question%20id%20{$_GET['qsetid']}\" target=\"_blank\">Message owner</a> to report problems</p>";
		echo "<a href=\"$imasroot/msgs/msglist.php?add=new&cid={$CFG['GEN']['sendquestionproblemsthroughcourse']}&";
		echo "quoteq=".Sanitize::encodeUrlParam("0-{$_GET['qsetid']}-{$seed}-reperr-{$assessver}")."\" target=\"reperr\">Message owner</a> to report problems</p>";
	} else {
		echo "<p>Question id: ".Sanitize::encodeStringForDisplay($_GET['qsetid']).".  <a href=\"mailto:".Sanitize::emailAddress($line['email'])
            ."?subject=" . Sanitize::encodeUrlParam("Problem with question id " . $_GET['qsetid'])
			. "\">E-mail owner</a> to report problems</p>";
	}
	printf("<p>Description: %s</p><p>Author: %s</p>", Sanitize::encodeStringForDisplay($line['description']),
        Sanitize::encodeStringForDisplay($line['author']));
	echo "<p>Last Modified: $lastmod</p>";
	if ($line['deleted']==1) {
		echo '<p class=noticetext>This question has been marked for deletion.  This might indicate there is an error in the question. ';
		echo 'It is recommended you discontinue use of this question when possible</p>';
	}
	if ($line['replaceby']>0) {
		echo '<p class=noticetext>This message has been marked as deprecated, and it is recommended you use question ID '.$line['replaceby'].' instead.  You can find this question ';
		echo 'by searching all libraries with the ID number as the search term</p>';
	}

	echo '<p id="brokenmsgbad" class=noticetext style="display:'.(($line['broken']==1)?"block":"none").'">This message has been marked as broken.  This indicates ';
	echo 'there might be an error with this question.  Use with caution.  <a href="#" onclick="submitBrokenFlag(0);return false;">Unmark as broken</a></p>';
	echo '<p id="brokenmsgok" style="display:'.(($line['broken']==0)?"block":"none").'"><a href="#" onclick="submitBrokenFlag(1);return false;">Mark as broken</a> if there appears to be an error with the question.</p>';

	echo '<p>'._('License').': ';
	$license = array('Copyrighted','IMathAS Community License','Public Domain','Creative Commons Attribution-NonCommercial-ShareAlike','Creative Commons Attribution-ShareAlike');
	echo $license[$line['license']];
	if ($line['otherattribution']!='') {
		echo '<br/>Other Attribution: '.Sanitize::encodeStringForDisplay($line['otherattribution']);
	}
	echo '</p>';

	echo '<p>Question is in these libraries:';
	echo '<ul>';
	//DB while ($row = mysql_fetch_row($resultLibNames)) {
	while ($row = $resultLibNames->fetch(PDO::FETCH_NUM)) {
		echo '<li>'.Sanitize::encodeStringForDisplay($row[0]);
		if ($myrights==100) {
			printf(' (%s, %s)', Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
		}
		echo '</li>';
	}
	echo '</ul></p>';

	if ($line['ancestors']!='') {
		echo "<p>Derived from: ".Sanitize::encodeStringForDisplay($line['ancestors']);
		if ($line['ancestorauthors']!='') {
			echo '<br/>Created by: '.Sanitize::encodeStringForDisplay($line['ancestorauthors']);
		}
		echo "</p>";
	} else if ($line['ancestorauthors']!='') {
		echo '<p>Derived from work by: '.Sanitize::encodeStringForDisplay($line['ancestorauthors']).'</p>';
	}
	if ($myrights==100) {
		echo '<p>UniqueID: '.Sanitize::encodeStringForDisplay($line['uniqueid']).'</p>';
	}
}
require("../footer.php");

?>
