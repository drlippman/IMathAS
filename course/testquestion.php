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
$pagetitle = _("Test Question");
$asid = 0;

	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = _("You need to log in as a teacher to access this page");
} else {
	//data manipulation here
	$useeditor = 1;
  $qsetid = Sanitize::onlyInt($_GET['qsetid']);
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
		list($score,$rawscores[$qn]) = scoreq($qn,$qsetid,$_POST['seed'],$_POST['qn'.$qn],$attempt-1);
		$scores[$qn] = $score;
		$page_scoreMsg =  "<p>"._("Score on last answer: ").Sanitize::encodeStringForDisplay($score)."/1</p>\n";
	} else {
		$page_scoreMsg = "";
		$_SESSION['choicemap'] = array();
	}
  $cid = Sanitize::courseId($_GET['cid']);
	$page_formAction = "testquestion.php?cid=$cid&qsetid=".Sanitize::encodeUrlParam($qsetid);

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
	$query = "SELECT imas_users.email,imas_questionset.* ";
	$query .= "FROM imas_users,imas_questionset WHERE imas_users.id=imas_questionset.ownerid AND imas_questionset.id=:id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$qsetid));
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
	$resultLibNames = $DBH->prepare("SELECT imas_libraries.name,imas_users.LastName,imas_users.FirstName FROM imas_libraries,imas_library_items,imas_users  WHERE imas_libraries.id=imas_library_items.libid AND imas_libraries.deleted=0 AND imas_library_items.deleted=0 AND imas_library_items.ownerid=imas_users.id AND imas_library_items.qsetid=:qsetid");
	$resultLibNames->execute(array(':qsetid'=>$qsetid));
}

/******* begin html output ********/
$_SESSION['coursetheme'] = $coursetheme;
$flexwidth = true; //tells header to use non _fw stylesheet

$useeqnhelper = $eqnhelper;
$useOldassessUI = true;

require("../assessment/header.php");

if ($overwriteBody==1) {
	echo $body;
} else { //DISPLAY BLOCK HERE
	$useeditor = 1;
	$brokenurl = $GLOBALS['basesiteurl'] . "/course/savebrokenqflag.php?qsetid=".Sanitize::encodeUrlParam($qsetid).'&flag=';
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
				    alert("<?php echo _('Oops, error toggling the flag'); ?>");
			    }
		    } else {
			   alert("<?php echo _('Couldn\'t save changes:'); ?>\n"+ req.status + "\n" +req.statusText);
		    }
		  }
		}
		function toggleBrokenFlagmsg(tagged) {
			document.getElementById("brokenmsgbad").style.display = (tagged==1)?"block":"none";
			document.getElementById("brokenmsgok").style.display = (tagged==1)?"none":"block";
			if (tagged==1) {alert("<?php echo _('Make sure you also contact the question author or support so they know why you marked the question as broken'); ?>");}
		}

		$(window).on('beforeunload', function() {
			if (window.opener && !window.opener.closed  && window.opener.sethighlightrow) {
				window.opener.sethighlightrow(-1);
			}
		});
	</script>
	<?php
	if (isset($_GET['formn']) && isset($_GET['loc'])) {
		echo '<p>';
		echo "<script type=\"text/javascript\">";
		echo "var numchked = -1;";
		echo "if (window.opener && !window.opener.closed && window.opener.sethighlightrow && window.opener.getnextprev) {";
		echo " window.opener.sethighlightrow(\"$loc\"); ";
		echo $page_onlyChkMsg;
		echo " if (prevnext[0][1]>0){
				  document.write('<a href=\"testquestion.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[0][0]+'&qsetid='+prevnext[0][1]+'\">"._("Prev")."</a> ');
			  } else {
				  document.write('"._("Prev")." ');
			  }
			  if (prevnext[1][1]>0){
				  document.write('<a href=\"testquestion.php?cid=$cid$chk&formn=$formn&onlychk=$onlychk&loc='+prevnext[1][0]+'&qsetid='+prevnext[1][1]+'\">"._("Next")."</a> ');
			  } else {
				  document.write('"._("Next")." ');
			  }
			  if (prevnext[2]!=null) {
			  	document.write(' <span id=\"numchked\">'+prevnext[2]+'</span> "._("checked")."');
				numchked = prevnext[2];
			  }
			  if (prevnext[3]!=null) {
			  	document.write(' '+prevnext[3]+' "._("remaining")."');
			  }
			}
			</script>";
		echo '</p>';
	}

	if (isset($_GET['checked'])) {
		echo "<p id=usecheckwrap><input type=\"checkbox\" name=\"usecheck\" id=\"usecheck\" value=\""._("Mark Question for Use")."\" onclick=\"parentcbox.checked=this.checked;togglechk(this.checked)\" ";
		echo "/> "._("Mark Question for Use")."</p>";
		echo "
		  <script type=\"text/javascript\">
		  var parentcbox = opener.document.getElementById(\"$loc\");
		  if (!parentcbox) {
		  	$('#usecheckwrap').hide();
		  } else {
		  	$('#usecheckwrap').show();
		  	document.getElementById(\"usecheck\").checked = parentcbox.checked;
		  }
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
	echo "<input type=hidden name=attempt value=\"" . Sanitize::onlyInt($attempt) . "\">\n";

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
	displayq($qn,$qsetid,$seed,true,true,$attempt,false,false,false,$colors);
	echo "<input type=submit value=\""._("Submit")."\"><input type=submit name=\"regen\" value=\""._("Submit and Regen")."\">\n";
	echo "<input type=button value=\""._("White Background")."\" onClick=\"whiteout()\"/>";
	echo "<input type=button value=\""._("Show HTML")."\" onClick=\"document.getElementById('qhtml').style.display='';\"/>";
	echo "</form>\n";

	echo '<code id="qhtml" style="display:none">';
	$message = displayq($qn,$qsetid,$seed,false,false,0,true);
	$message = printfilter($message);
	$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
	$message = str_replacE('`','\`',$message);
	echo htmlentities($message);
	echo '</code>';

	if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
		$sendtype = 'msg';
		$sendtitle = (_('Message owner'));
		$sendcid = $CFG['GEN']['sendquestionproblemsthroughcourse'];
	} else {
		$sendtype = 'email';
		$sendtitle = _('Email owner');
		$sendcid = $cid;
	}
	if (isset($CFG['GEN']['qerrorsendto'])) {
		if (is_array($CFG['GEN']['qerrorsendto'])) {
			if (empty($CFG['GEN']['qerrorsendto'][3])) { //if not also sending to owner
				$sendtype = $CFG['GEN']['qerrorsendto'][1];
			}
			$sendtitle = $CFG['GEN']['qerrorsendto'][2];
		} else {
			$sendtype = 'email';
			$sendtitle = _('Contact support');
		}
	}

	printf("<p>"._("Question ID:")." %s.  ", Sanitize::encodeStringForDisplay($qsetid));
	echo '<span class="small subdued">'._('Seed:').' '.Sanitize::onlyInt($seed) . '.</span> ';
  if ($line['ownerid'] == $userid) {
    echo '<a href="moddataset.php?cid='. Sanitize::courseId($sendcid) . '&id=' . Sanitize::onlyInt($qsetid).'" target="_blank">';
    echo _('Edit Question') . '</a>';
  } else {
    echo "<a href=\"#\" onclick=\"GB_show('$sendtitle','$imasroot/course/sendmsgmodal.php?sendtype=$sendtype&cid=" . Sanitize::courseId($sendcid) . '&quoteq='.Sanitize::encodeUrlParam("0-{$qsetid}-{$seed}-reperr-{$assessver}"). "',800,'auto')\">$sendtitle</a> "._("to report problems");
  }
  echo '</p>';

	printf("<p>"._("Description:")." %s</p><p>"._("Author:")." <span class='pii-full-name'>%s</span></p>",
        Sanitize::encodeStringForDisplay($line['description']),
        Sanitize::encodeStringForDisplay($line['author']));
	echo "<p>"._("Last Modified:")." $lastmod</p>";
	if ($line['deleted']==1) {
		echo '<p class=noticetext>'._('This question has been marked for deletion.  This might indicate there is an error in the question. ');
		echo _('It is recommended you discontinue use of this question when possible').'</p>';
	}
	if ($line['replaceby']>0) {
	  echo '<p class=noticetext>'.sprintf(_('This message has been marked as deprecated, and it is recommended you use question ID %s instead.  You can find this question by searching all libraries with the ID number as the search term'),$line['replaceby']).'</p>';
	}

	echo '<p id="brokenmsgbad" class=noticetext style="display:'.(($line['broken']==1)?"block":"none").'">'._('This question has been marked as broken.  This indicates there might be an error with this question.  Use with caution.').'  <a href="#" onclick="submitBrokenFlag(0);return false;">'._('Unmark as broken').'</a></p>';
	//echo '<p id="brokenmsgok" style="display:'.(($line['broken']==0)?"block":"none").'"><a href="#" onclick="submitBrokenFlag(1);return false;">Mark as broken</a> if there appears to be an error with the question.</p>';

	echo '<p>'._('License').': ';
	$license = array('Copyrighted','IMathAS Community License','Public Domain','Creative Commons Attribution-NonCommercial-ShareAlike','Creative Commons Attribution-ShareAlike');
	echo $license[$line['license']];
	if ($line['otherattribution']!='') {
		echo '<br/>'._('Other Attribution: ').Sanitize::encodeStringForDisplay($line['otherattribution']);
	}
	echo '</p>';

	echo '<p>'._('Question is in these libraries:');
	echo '<ul>';
	while ($row = $resultLibNames->fetch(PDO::FETCH_NUM)) {
		echo '<li>'.Sanitize::encodeStringForDisplay($row[0]);
		if ($myrights==100) {
			printf(' (<span class="pii-full-name">%s, %s</span>)',
                Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
		}
		echo '</li>';
	}
	echo '</ul></p>';

	if ($line['ancestors']!='') {
		echo "<p>"._("Derived from:")." ".Sanitize::encodeStringForDisplay($line['ancestors']);
		if ($line['ancestorauthors']!='') {
			echo '<br/>'._('Created by: ').Sanitize::encodeStringForDisplay($line['ancestorauthors']);
		}
		echo "</p>";
	} else if ($line['ancestorauthors']!='') {
		echo '<p>'._('Derived from work by: ').Sanitize::encodeStringForDisplay($line['ancestorauthors']).'</p>';
	}
	if ($myrights==100) {
		echo '<p>'._('UniqueID: ').Sanitize::encodeStringForDisplay($line['uniqueid']).'</p>';
	}
  echo '<p>'._('Testing using the old interface.');
  echo ' <a href="testquestion2.php?cid='.$cid.'&qsetid='.$qsetid.'">';
  echo _('Test in new interface').'</a></p>';
}
require("../footer.php");

?>
