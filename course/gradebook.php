<?php
//IMathAS:  Display gradebook (main view and detailed view)
//(c) 2006 David Lippman
	require("../validate.php");
	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	
	$cid = $_GET['cid'];
	//gbmode
	//links: 0 reg, 1 cat totals
	//PT's: 0 none, 2 show
	//dates: 0 all, 4 current (instructor only)
	//totals side: 0 right, 8 left
	//don'tcount: 0 show, 16 hide
	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else {
		$query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gbmode = mysql_result($result,0,0);
	}
	$nopracticet = (($gbmode&2)==0);
	$curonly = (($gbmode&4)==4);
	$hidenc = (($gbmode&16)==16);
	if (isset($_GET['catfilter'])) {
		$catfilter = $_GET['catfilter'];
		$sessiondata[$cid.'catfilter'] = $catfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'catfilter'])) {
		$catfilter = $sessiondata[$cid.'catfilter'];
	} else {
		$catfilter = -1;
	}
	if ($isteacher && isset($_GET['stu'])) {
		$stu = $_GET['stu'];
	} else {
		$stu = 0;
	}
		
	if (isset($_GET['export']) && $_GET['export']=="true") {
		list($gb) = gbtable(false);
		header('Content-type: text/csv');
		header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
		foreach ($gb as $gbline) {
			$line = '';
			foreach ($gbline as $val) {
				 # remove any windows new lines, as they interfere with the parsing at the other end 
				  $val = str_replace("\r\n", "\n", $val); 
				  $val = str_replace("\n", " ", $val);
				  $val = str_replace(array("<BR>",'<br>','<br/>'), ' ',$val);
				  $val = str_replace("&nbsp;"," ",$val);
			
				  # if a deliminator char, a double quote char or a newline are in the field, add quotes 
				  if(ereg("[\,\"\n\r]", $val)) { 
					  $val = '"'.str_replace('"', '""', $val).'"'; 
				  }
				  $line .= $val.',';
			}
			# strip the last deliminator 
			$line = substr($line, 0, -1); 
			$line .= "\n";
			echo $line;
		}
		exit;
	}
	if (isset($_GET['emailgb'])) {
		if ($_GET['emailgb']=="ask") {
			if (isset($_POST['email'])) {
				$_GET['emailgb'] = $_POST['email'];
			} else {
				echo "<html><body><form method=post action=\"gradebook.php?stu=$stu&cid=$cid&gbmode=$gbmode&emailgb=ask\">";
				echo "Email Gradebook To: <input type=text name=\"email\" /> <input type=submit value=\"Email\"/>";
				echo "</form></body></html>";
				exit;
			}
		}
		list($gb) = gbtable(false);
		$line = '';
		foreach ($gb as $gbline) {
			
			foreach ($gbline as $val) {
				 # remove any windows new lines, as they interfere with the parsing at the other end 
				  $val = str_replace("\r\n", "\n", $val); 
				  $val = str_replace("\n", " ", $val);
				  $val = str_replace("<BR>", " ",$val);
				  $val = str_replace("<br/>", " ",$val);
				  $val = str_replace("&nbsp;"," ",$val);
			
				  # if a deliminator char, a double quote char or a newline are in the field, add quotes 
				  if(ereg("[\,\"\n\r]", $val)) { 
					  $val = '"'.str_replace('"', '""', $val).'"'; 
				  }
				  $line .= $val.',';
			}
			# strip the last deliminator 
			$line = substr($line, 0, -1); 
			$line .= "\n";
		}
		$boundary = '-----=' . md5( uniqid ( rand() ) );
		$message = "--".$boundary . "\n";
		$message .= "Content-Type: text/csv; name=\"Gradebook\"\n";
		$message .= "Content-Transfer-Encoding: base64\n";
		$message .= "Content-Disposition: attachment; filename=\"gradebook.csv\"\n\n";
		$content_encode = chunk_split(base64_encode($line));
		$message .= $content_encode . "\n";
		$message .= "--" . $boundary . "--\n";
		$headers  = "From: $sendfrom\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";
		if ($_GET['emailgb']=="me") {
			$query = "SELECT email FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$_GET['emailgb'] = mysql_result($result,0,0);
		}
		if ($_GET['emailgb']!='') {
			mail($_GET['emailgb'], "Gradebook for $coursename", $message, $headers);
			echo "<b>Gradebook Emailed</b>";
		}
		
	}
	if (isset($_GET['clearattempt']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearattempt']=="confirmed") {
			
			$query = "DELETE FROM imas_assessment_sessions";// WHERE id='{$_GET['asid']}'";
			$query .= getasidquery($_GET['asid']);
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			unset($_GET['asid']);
			unset($agroupid);
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
			} else {
				$pers = 'student';
			}
			echo "<p>Are you sure you want to clear this $pers's assessment attempt?  This will make it appear the $pers never tried the assessment, and the $pers will receive a new version of the assessment.</p>";
			echo "<p><input type=button onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&clearattempt=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['breakfromgroup']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['breakfromgroup']=="confirmed") {
			$query = "SELECT count(id) FROM imas_assessment_sessions WHERE agroupid='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_result($result,0,0)>1) { //was group creator and others in group; need to move to new id
				$query = "SELECT id,agroupid,userid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$row = mysql_fetch_row($result);
				$oldgroupid = $row[1];
				$thisuserid = $row[2];
				$query = "SELECT userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
				$query .= "FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$row = mysql_fetch_row($result);
				$insrow = "'".implode("','",addslashes_deep($row))."'";
				$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
				$query .= "VALUES ($insrow)";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$newasid = mysql_insert_id();
				$query = "DELETE FROM imas_assessment_sessions WHERE id='{$_GET['asid']}' LIMIT 1";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$query = "SELECT sessionid,sessiondata FROM imas_sessions WHERE userid='$thisuserid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$tmpsessdata = unserialize(base64_decode($row[1]));
					if ($tmpsessdata['sessiontestid']==$_GET['asid']) {
						$tmpsessdata['sessiontestid'] = $newasid;
						$tmpsessdata['groupid'] = 0;
						$tmpsessdata = base64_encode(serialize($tmpsessdata));
						$query = "UPDATE imas_sessions SET sessiondata='$tmpsessdata' WHERE sessionid='{$row[0]}'";
						mysql_query($query) or die("Query failed : $query:" . mysql_error());
					}
				}
				$_GET['asid'] = $newasid;
			} else {
				$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE id='{$_GET['asid']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		} else {
			echo "<p>Are you sure you want to separate this student from their current group?</p>";
			echo "<p><input type=button onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&breakfromgroup=confirmed'\" value=\"Really Separate\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearscores']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearscores']=="confirmed") {
			$whereqry = getasidquery($_GET['asid']);
			$query = "SELECT seeds FROM imas_assessment_sessions $whereqry";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$seeds = explode(',',mysql_result($result,0,0));
			
			$scores = array_fill(0,count($seeds),-1);
			$attempts = array_fill(0,count($seeds),0);
			$lastanswers = array_fill(0,count($seeds),'');
			$scorelist = implode(",",$scores);
			$attemptslist = implode(",",$attempts);
			$lalist = implode("~",$lastanswers);
			$bestscorelist = implode(',',$scores);
			$bestattemptslist = implode(',',$attempts);
			$bestseedslist = implode(',',$seeds);
			$bestlalist = implode('~',$lastanswers);
			
			$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
			$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestseeds='$bestseedslist',bestlastanswers='$bestlalist' ";
			$query .= $whereqry;//"WHERE id='{$_GET['asid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			unset($_GET['asid']);
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
			} else {
				$pers = 'student';
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this assessment?</p>";
			echo "<p><input type=button onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&clearscores=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearq']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['confirmed']=="true") {
			$whereqry = getasidquery($_GET['asid']);
			
			$query = "SELECT attempts,lastanswers,scores,bestscores,bestattempts,bestlastanswers FROM imas_assessment_sessions $whereqry"; //WHERE id='{$_GET['asid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			
			$scores = explode(",",$line['scores']);
			$attempts = explode(",",$line['attempts']);
			$lastanswers = explode("~",$line['lastanswers']);
			$bestscores = explode(",",$line['bestscores']);
			$bestattempts = explode(",",$line['bestattempts']);
			$bestlastanswers = explode("~",$line['bestlastanswers']);
			
			$clearid = $_GET['clearq'];
			if ($clearid!=='' && is_numeric($clearid) && isset($scores[$clearid])) {
				$scores[$clearid] = -1;
				$attempts[$clearid] = 0;
				$lastanswers[$clearid] = '';
				$bestscores[$clearid] = -1;
				$bestattempts[$clearid] = 0;
				$bestlastanswers[$clearid] = '';
				
				$scorelist = implode(",",$scores);
				$attemptslist = implode(",",$attempts);
				$lalist = addslashes(implode("~",$lastanswers));
				$bestscorelist = implode(',',$scores);
				$bestattemptslist = implode(',',$attempts);
				$bestlalist = addslashes(implode('~',$lastanswers));
				
				$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
				$query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist' ";
				$query .= $whereqry; //"WHERE id='{$_GET['asid']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			} else {
				echo "<p>Error.  Try again.</p>";
			}
			unset($_GET['asid']);
			unset($_GET['clearq']);
			
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
			} else {
				$pers = 'student';
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this question?</p>";
			echo "<p><input type=button onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&clearq={$_GET['clearq']}&confirmed=true'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	
	
	if (!isset($_GET['asid']) && ($isteacher || $istutor) && $stu==0) { //not doing grade detail, show full instructor view
		if ((isset($_POST['submit']) && ($_POST['submit']=="E-mail" || $_POST['submit']=="Message"))|| isset($_GET['masssend']))  {
			$calledfrom='gb';
			include("masssend.php");
		}
		if ((isset($_POST['submit']) && $_POST['submit']=="Make Exception") || isset($_GET['massexception'])) {
			$calledfrom='gb';
			include("massexception.php");
		}
		if ((isset($_POST['submit']) && $_POST['submit']=="Unenroll") || (isset($_GET['action']) && $_GET['action']=="unenroll" )) {
			$calledfrom='gb';
			$curBreadcrumb = "<a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}\">Gradebook</a> &gt; Confirm Change";
			$pagetitle = "Unenroll Students";
			include("unenroll.php");
			include("../footer.php");
			exit;
		}
		$pagetitle = "Gradebook";
		if ($isteacher) {
			$placeinhead = "<script type=\"text/javascript\">function lockcol() { \n";
			$placeinhead .= " var cont = document.getElementById(\"tbl-container\");\n";
			$placeinhead .= " if (cont.style.overflow == \"auto\") {\n";
			$placeinhead .= "   cont.style.height = \"auto\"; cont.style.overflow = \"visible\"; cont.style.border = \"0px\";";
			$placeinhead .= "document.getElementById(\"myTable\").className = \"gb\"; document.cookie = 'gblhdr-$cid=0';";
			$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Lock headers\"; } else {";
			$placeinhead .= " cont.style.height = \"75%\"; cont.style.overflow = \"auto\"; cont.style.border = \"1px solid #000\";\n";
			$placeinhead .= "document.getElementById(\"myTable\").className = \"gbl\"; document.cookie = 'gblhdr-$cid=1'; ";
			$placeinhead .= "  document.getElementById(\"lockbtn\").value = \"Unlock headers\"; }";
			$placeinhead .= "} ";
			$placeinhead .= 'function chgfilter() { ';
			$placeinhead .= '       var cat = document.getElementById("filtersel").value; ';
			$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=$gbmode";
			
			$placeinhead .= "       var toopen = '$address&catfilter=' + cat;\n";
			$placeinhead .= "  	window.location = toopen; \n";
			$placeinhead .= "}\n";
			$placeinhead .= 'function chgtoggle() { ';
			$placeinhead .= '	var altgbmode = document.getElementById("toggleview").value; ';
			$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid=$cid&gbmode=";
			$placeinhead .= "	var toopen = '$address' + altgbmode;\n";
			$placeinhead .= "  	window.location = toopen; \n";
			$placeinhead .= "}\n";
			$placeinhead .= "function chkAll(frm, arr, mark) {  for (i = 0; i <= frm.elements.length; i++) {   try{     if(frm.elements[i].name == arr) {  frm.elements[i].checked = mark;     }   } catch(er) {}  }}";
			$placeinhead .= "</script>\n";
			$placeinhead .= "<style type=\"text/css\"> table.gb { margin: 0px; } </style>";
		
		}
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; Gradebook</div>";
		echo "<form method=post action=\"gradebook.php?cid=$cid&gbmode=$gbmode\">";
		
		echo "<span class=\"hdr1\">Grade Book</span>";
		if ($isteacher) {
			//echo " <input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"Lock headers\"/> (IE only)\n";
		}
		if ($isteacher) {
			echo "<div class=cpmid>";
			echo "<a href=\"addgrades.php?cid=$cid&gbmode=$gbmode&gbitem=new&grades=all\">Add Offline Grade</a> | ";
			echo "Export to <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&export=true\">File</a>, ";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&emailgb=me\">My Email</a>, or <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&emailgb=ask\">Other Email</a> | ";
			echo "<a href=\"gbsettings.php?gbmode=$gbmode&cid=$cid\">Gradebook Settings</a> | ";
			echo "<a href=\"gradebook.php?cid=$cid&gbmode=$gbmode&stu=-1\">Averages</a> | ";
			echo "<a href=\"gbcomments.php?cid=$cid&gbmode=$gbmode&stu=0\">Comments</a><br/> ";
			echo "<input type=\"button\" id=\"lockbtn\" onclick=\"lockcol()\" value=\"Lock headers\"/> (IE) |  \n";
			echo 'Filter Items: <select id="filtersel" onchange="chgfilter()">';
			echo '<option value="-1" ';
			if ($catfilter==-1) {echo "selected=1";}
			echo '>All</option>';
			echo '<option value="0" ';
			if ($catfilter==0) { echo "selected=1";}
			echo '>Default</option>';
			$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo '<option value="'.$row[0].'"';
				if ($catfilter==$row[0]) {echo "selected=1";}
				echo '>'.$row[1].'</option>';
			}
			echo '<option value="-2" ';
			if ($catfilter==-2) {echo "selected=1";}
			echo '>Category Totals</option>';
			echo '</select> | ';
			
			echo 'Toggle: <select id="toggleview" onchange="chgtoggle()">';
			echo '<option value="-1">Select toggle</option>';
			if ($nopracticet) {
				$altgbmode = $gbmode+2;
				echo "<option value=\"$altgbmode\">Practice Test: hidden -> shown</option>";
			} else {
				$altgbmode = $gbmode-2;
				echo "<option value=\"$altgbmode\">Practice Test: shown -> hidden</option>";
			} 
			if (($gbmode&1)==1) {
				$altgbmode = $gbmode-1;
				echo "<option value=\"$altgbmode\">Links: question breakdown -> edit</option>";
			} else {
				$altgbmode = $gbmode+1;
				echo "<option value=\"$altgbmode\">Links: edit -> question breakdown</option>";
			}
			if ($curonly) {
				$altgbmode = $gbmode-4;
				echo "<option value=\"$altgbmode\">Items shown: available -> all</option>";
			} else {
				$altgbmode = $gbmode+4;
				echo "<option value=\"$altgbmode\">Items shown: all -> available and past</option>";
			}
			if ($hidenc) {
				$altgbmode = $gbmode-16;
				echo "<option value=\"$altgbmode\">Items shown: counted -> all</option>";
			} else {
				$altgbmode = $gbmode+16;
				echo "<option value=\"$altgbmode\">Items shown: all -> counted</option>";
			}
			echo '</select>';
			
			echo "</div>";
		}
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\"> \n";
		echo "With Selected:  <input type=submit name=submit value=\"E-mail\"> <input type=submit name=submit value=\"Message\"> <input type=submit name=submit value=\"Unenroll\"> <input type=submit name=submit value=\"Make Exception\"> ";
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "<div id=\"tbl-container\">";
		echo "<table class=gb id=myTable><thead><tr>"; //<tr><td>Name</td>\n";
		list($gb,$cathdr) = gbtable(true,0,true);
		for ($i=0;$i<count($gb[0]);$i++) {
			echo '<th';
			if ($cathdr[$i]>-1) {
				echo ' class="cat'.$cathdr[$i].'"';
			}
			echo '>'.$gb[0][$i].'</th>';
		}
		//echo "<tr><th scope=\"col\" class=\"locked\">" . implode('</th><th scope="col">',$gb[0]) . "</th></tr></thead>\n";
		echo "</thead><tbody>";
		for ($i=1; $i<count($gb); $i++) {
			if ($i%2!=0) {
				echo "<tr class=even onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='even'\">"; 
			} else {
				echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">"; 
			}
			echo "<td class=\"locked\" scope=\"row\">" . implode('</td><td class=c>',$gb[$i]) . "</td></tr>\n";
		}
		echo "</tbody></table>\n";
		echo "</form>";
		echo "</div>";
		echo "<script type=\"text/javascript\">\n";
		$sarr = implode(",",array_fill(0,count($gb[0]),"'S'"));
		
		if ($isteacher) { //showing averages, so don't sort last row
			echo "initSortTable('myTable',Array($sarr),true,false);\n";
		} else {
			echo "initSortTable('myTable',Array($sarr),true);\n";
		}
		if (isset($_COOKIE['gblhdr-'.$cid]) && $_COOKIE['gblhdr-'.$cid]==1) {
			echo "lockcol();";	
		}
		echo "</script>\n";
		echo "Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit<br/><sup>*</sup> Has feedback\n";
		if ($isteacher) {
			echo "<div class=cp>";
			echo "<a href=\"addgrades.php?cid=$cid&gbmode=$gbmode&gbitem=new&grades=all\">Add Offline Grade</a><br/>";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&export=true\">Export Gradebook</a><br/>";
			echo "Email gradebook to <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&emailgb=me\">Me</a> or <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&emailgb=ask\">to another address</a><br/>";
			echo "<a href=\"gbsettings.php?gbmode=$gbmode&cid=$cid\">Gradebook Settings</a>";
			echo "<div class=clear></div></div>";
		}

		echo "<div class=cp>";
		if ($isteacher) {
			echo 'Filter Items: <select id="filtersel" onchange="chgfilter()">';
			echo '<option value="-1" ';
			if ($catfilter==-1) {echo "selected=1";}
			echo '>All</option>';
			echo '<option value="0" ';
			if ($catfilter==0) { echo "selected=1";}
			echo '>Default</option>';
			$query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo '<option value="'.$row[0].'"';
				if ($catfilter==$row[0]) {echo "selected=1";}
				echo '>'.$row[1].'</option>';
			}
			echo '<option value="-2" ';
			if ($catfilter==-2) {echo "selected=1";}
			echo '>Category Totals</option>';
			echo '</select><br/>';
		}
		if ($nopracticet) {
			$altgbmode = $gbmode+2;
			echo "Practice tests are not displayed (they are not included in total grade).  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Show practice tests</a>\n";
		} else {
			$altgbmode = $gbmode-2;
			echo "Practice tests are being displayed (they are not included in total grade).  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Hide practice tests</a>\n";
		}
		if ($isteacher) {
			if (($gbmode&1)==1) {
				$altgbmode = $gbmode-1;
				echo "<br/>Score links show Category and Question breakdown only.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Switch links to show full test with score editing</a>\n";
			} else {
				$altgbmode = $gbmode+1;
				echo "<br/>Score links show full test with score editing.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Switch links to show category and question breakdown only</a>\n";
			}
			if ($curonly) {
				$altgbmode = $gbmode-4;
				echo "<br/>Showing Past and Available Items only.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Show all items</a>\n";
			} else {
				$altgbmode = $gbmode+4;
				echo "<br/>Showing all items.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Show Past and Available items only</a>\n";
			}
			if ($hidenc) {
				$altgbmode = $gbmode-16;
				echo "<br/>Showing Counted Items only.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Show all items</a>\n";
			} else {
				$altgbmode = $gbmode+16;
				echo "<br/>Showing all items.  <a href=\"gradebook.php?stu=$stu&gbmode=$altgbmode&cid=$cid\">Hide Not Counted items</a>\n";
			}
		}
		echo "<div class=clear></div></div>";
	} else if (!isset($_GET['asid']) && (!$isteacher || $stu!=0)) { //showing student view
		if (!$isteacher) {
			$stu = $userid;
		}
		$pagetitle = "Gradebook";
		require("../header.php");
		if (isset($_GET['from']) && $_GET['from']=="listusers") {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Student Grade Detail</div>\n";
		} else if ($isteacher) {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; Student Detail</div>";
		} else {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; Gradebook</div>";
		}
		if ($stu==-1) {
			echo "<h2>Grade Book Averages</h2>\n";
		} else {
			echo "<h2>Grade Book Student Detail</h2>\n";
		}
		if ($stu>0) { //doing a student
			if (isset($_POST['usrcomments'])) {
				$query = "UPDATE imas_students SET gbcomment='{$_POST['usrcomments']}' WHERE userid='$stu'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				echo "<p>Comment Updated</p>";
			}
			list($gb,$cathdr,$feedbacks) = gbtable(true,$stu);
			echo '<h3>' . strip_tags($gb[1][0]) . '</h3>';
			$query = "SELECT imas_students.gbcomment,imas_users.email FROM imas_students,imas_users WHERE ";
			$query .= "imas_students.userid=imas_users.id AND imas_users.id='$stu' AND imas_students.courseid='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				if ($isteacher) {
					echo '<a href="mailto:'.mysql_result($result,0,1).'">Email</a> | ';
					echo "<a href=\"$imasroot/msgs/msglist.php?cid={$_GET['cid']}&add=new&to=$stu\">Message</a> | ";
					echo "<a href=\"exception.php?cid={$_GET['cid']}&uid=$stu\">Make Exception</a> | ";
					echo "<a href=\"listusers.php?cid={$_GET['cid']}&chgstuinfo=true&uid=$stu\">Change Info</a>";
				}
				$gbcomment = mysql_result($result,0,0);
			} else {
				$gbcomment = '';
			}
			if (trim($gbcomment)!='' || $isteacher) {
				if ($isteacher) {
					echo "<form method=post action=\"gradebook.php?{$_SERVER['QUERY_STRING']}\">";
					echo "<textarea name=\"usrcomments\" rows=3 cols=60>$gbcomment</textarea><br/>";
					echo "<input type=submit value=\"Update Comment\">";
					echo "</form>";
				} else {
					echo "<div class=\"item\">$gbcomment</div>";
				}
			}
		} else { //doing averages
			list($gb,$cathdr) = gbtable(true);
			array_splice($gb,1,count($gb)-2);
			$feedbacks = array();
		}
		echo '<table class=gb>';
		$gb[1][0] = preg_replace('/<[^>]+>/','',$gb[1][0]);
		echo "<thead><tr><th>Item</th><th>Possible</th><th>Grade</th><th>Percent</th>";
		if ($stu>0) {
			echo "<th>Feedback</th>";
		} 
		echo "</tr></thead><tbody>";
		
		for ($i=1;$i<count($gb[0]);$i++) {
			//if ($i%2!=0) {
				echo "<tr class=grid>"; 
			//} else {
			//	echo "<tr class=odd onMouseOver=\"this.className='highlight'\" onMouseOut=\"this.className='odd'\">"; 
			//}
			
			echo '<td';
			if ($cathdr[$i]>-1) {
				echo ' class="cat'.$cathdr[$i].'"';
			} else {
				echo ' class="catdf"';
			}
			echo '>';
			$anpts = explode('<br/>',$gb[0][$i]);
			if (count($anpts)==1) {
				$anpts[] = '';
			}
			$poss = substr($anpts[1],0,strpos($anpts[1],'pt'));
				if ($poss>0) {
					$apct = round(100*floatval(strip_tags($gb[1][$i]))/floatval($poss),1) . '%';
				} else {
					$apct = '';
				}
			echo $anpts[0];
			echo '</td><td>'.$anpts[1];
			echo '</td><td>'.$gb[1][$i].'</td><td>'.$apct.'</td>';
			if ($stu>0) {
				if (isset($feedbacks[$i])) {
					echo "<td>{$feedbacks[$i]}</td>";
				} else {
					echo "<td></td>";
				}
			} 
			echo '</tr>';
		}
		echo '</tbody></table>';
		
		echo "<p>Meanings:  IP-In Progress, OT-overtime, PT-practice test, EC-extra credit, NC-no credit</p>\n";
		
		
	} else if ($_GET['asid']=="new" && $isteacher) {
		$aid = $_GET['aid'];
		$query = "SELECT * FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$adata = mysql_fetch_array($result, MYSQL_ASSOC);
		$questions = explode(",",$adata['itemorder']);
		foreach($questions as $k=>$q) {
			if (strpos($q,'~')!==false) {
				$sub = explode('~',$q);
				if (strpos($sub[0],'|')===false) { //backwards compat
					$questions[$k] = $sub[array_rand($sub,1)];
				} else {
					$grpqs = array();
					$grpparts = explode('|',$sub[0]);
					array_shift($sub);
					if ($grpparts[1]==1) { // With replacement
						for ($i=0; $i<$grpparts[0]; $i++) {
							$grpqs[] = $sub[array_rand($sub,1)];
						}
					} else if ($grpparts[1]==0) { //Without replacement
						shuffle($sub);
						$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
						if ($grpparts[0]>count($sub)) {
							for ($i=count($sub); $i<$grpparts[0]; $i++) {
								$grpqs[] = $sub[array_rand($sub,1)];
							}
						}
					}
					array_splice($questions,$k,1,$grpqs);
				}
			}
		}
		if ($adata['shuffle']&1) {shuffle($questions);}
		
		if ($adata['shuffle']&2) { //all questions same random seed
			if ($adata['shuffle']&4) { //all students same seed
				$seeds = array_fill(0,count($questions),$aid);
				$reviewseeds = array_fill(0,count($questions),$aid+100);
			} else {
				$seeds = array_fill(0,count($questions),rand(1,9999));
				$reviewseeds = array_fill(0,count($questions),rand(1,9999));
			}
		} else {
			if ($adata['shuffle']&4) { //all students same seed
				for ($i = 0; $i<count($questions);$i++) {
					$seeds[] = $aid + $i;
					$reviewseeds[] = $aid + $i;
				}
			} else {
				for ($i = 0; $i<count($questions);$i++) {
					$seeds[] = rand(1,9999);
					$reviewseeds[] = rand(1,9999);
				}
			}
		}

		$scores = array_fill(0,count($questions),-1);
		$attempts = array_fill(0,count($questions),0);
		$lastanswers = array_fill(0,count($questions),'');
		
		$starttime = time();
		
		$qlist = implode(',',$questions);
		$seedlist = implode(',',$seeds);
		$scorelist = implode(',',$scores);
		$attemptslist = implode(',',$attempts);
		$lalist = implode('~',$lastanswers);
		$reviewseedlist = implode(',',$reviewseeds);
		
		$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers,reviewscores,reviewattempts,reviewseeds,reviewlastanswers) ";
		$query .= "VALUES ('{$_GET['uid']}','$aid','$qlist','$seedlist','$scorelist','$attemptslist','$lalist',$starttime,'$scorelist','$attemptslist','$seedlist','$lalist','$scorelist','$attemptslist','$reviewseedlist','$lalist');";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$asid = mysql_insert_id();
															
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&gbmode=$gbmode&cid={$_GET['cid']}&asid=$asid&uid={$_GET['uid']}");
		exit;
	} else if ($_GET['asid']!="average" && (($gbmode&1)==0 || !$isteacher)) { //asid (assessment-session id) is set: show grade detail w/ edit
		if (isset($_GET['update']) && $isteacher) {
			$scores = array();
			$i = 0;
			while (isset($_POST[$i]) || isset($_POST["$i-0"])) {
				$j=0;
				$scpt = array();
				if (isset($_POST["$i-0"])) {

					while (isset($_POST["$i-$j"])) {
						if ($_POST["$i-$j"]!='N/A') {
							$scpt[$j] = $_POST["$i-$j"];
						} else {
							$scpt[$j] = -1;
						}
						$j++;
					}
					$scores[$i] = implode('~',$scpt);
				} else {
					if ($_POST[$i]!='N/A') {
						$scores[$i] = $_POST[$i];
					} else {
						$scores[$i] = -1;
					}
				}
				$i++;
			}
			$scorelist = implode(",",$scores);
			$feedback = $_POST['feedback'];
			$query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
			if (isset($_POST['updategroup'])) {
				$query .= getasidquery($_GET['asid']);
			} else {
				$query .= "WHERE id='{$_GET['asid']}'";
			}

			mysql_query($query) or die("Query failed : $query " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&gbmode=$gbmode&cid={$_GET['cid']}");
			exit;
		}
		$useeditor='review';
		require("../assessment/header.php");
		echo "<style type=\"text/css\">p.tips {	display: none;}\n</style>\n";
		if (isset($_GET['starttime']) && $isteacher) {
			$query = "UPDATE imas_assessment_sessions SET starttime='{$_GET['starttime']}' ";//WHERE id='{$_GET['asid']}'";
			$query .= getasidquery($_GET['asid']);
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		}
		
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> ";
		if ($stu>0) {echo "&gt; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Student Detail</a> ";}
		echo "&gt; Detail</div>";
		echo "<h2>Grade Book Detail</h2>\n";
		$query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$_GET['uid']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";
		
		$teacherreview = $_GET['uid'];
		
		$query = "SELECT imas_assessments.name,imas_assessments.timelimit,imas_assessments.defpoints,";
		$query .= "imas_assessments.deffeedback,imas_assessments.enddate,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line=mysql_fetch_array($result, MYSQL_ASSOC);
		list($testtype,$showans) = explode('-',$line['deffeedback']);
		echo "<h4>{$line['name']}</h4>\n";
		echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
		if ($line['endtime']==0) { 
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "</p>\n";
		}
		$saenddate = $line['enddate'];
		unset($exped);
		$query = "SELECT enddate FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$line['assessmentid']}'";
		$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($r2)>0) {
			$exped = mysql_result($r2,0,0);
			if ($exped>$saenddate) {
				$saenddate = $exped;
			}
		}
		
		if ($isteacher) {
			echo "<p>Due Date: ". tzdate("F j, Y, g:i a",$line['enddate']);
			if (isset($exped) && $exped!=$line['enddate']) {
				echo "<br/>Has exception, with due date: ".tzdate("F j, Y, g:i a",$exped);
			}
			echo "</p>";
		}
		if ($isteacher) {
			if ($line['agroupid']>0) {
				echo "<p>This assignment is linked to a group.  Changes will affect the group unless specified. ";
				echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&breakfromgroup=true\">Separate from Group</a></p>";
			}
		}
		
		if ($isteacher) {echo "<p><a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&clearattempt=true\">Clear Attempt</a> | <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&clearscores=true\">Clear Scores</a></p>\n";}
		
		if (($line['timelimit']>0) && ($line['endtime'] - $line['starttime'] > $line['timelimit'])) {
			$over = $line['endtime']-$line['starttime'] - $line['timelimit'];
			echo "<p>Time limit exceeded by ";
			if ($over > 60) {
				$overmin = floor($over/60);
				echo "$overmin minutes, ";
				$over = $over - $overmin*60;
			}
			echo "$over seconds.<BR>\n";
			$reset = $line['endtime']-$line['timelimit'];
			if ($isteacher) {
				echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&starttime=$reset&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}\">Clear overtime and accept grade</a></p>\n";
			}
		}
		
		$query = "SELECT id,points,withdrawn FROM imas_questions WHERE assessmentid='{$line['assessmentid']}'";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$totalpossible = 0;
		while ($r = mysql_fetch_row($result)) {
			if ($r[1]==9999) {
				$pts[$r[0]] = $line['defpoints'];  //use defpoints
			} else {
				$pts[$r[0]] = $r[1]; //use points from question
			}
			$totalpossible += $pts[$r[0]];
			$withdrawn[$r[0]] = $r[2];
		}
		
		$questions = explode(",",$line['questions']);
		if (isset($_GET['lastver'])) {
			$seeds = explode(",",$line['seeds']);
			$scores = explode(",",$line['scores']);
			$attempts = explode(",",$line['attempts']);
			$lastanswers = explode("~",$line['lastanswers']);
			echo "<p>";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<b>Showing Last Attempts</b> | ";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		} else if (isset($_GET['reviewver'])) {
			$seeds = explode(",",$line['reviewseeds']);
			$scores = explode(",",$line['reviewscores']);
			$attempts = explode(",",$line['reviewattempts']);
			$lastanswers = explode("~",$line['reviewlastanswers']);
			echo "<p>";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}&lastver=1\">Show Last Graded Attempts</a> | ";
			echo "<b>Showing Review Attempts</b>";
			echo "</p>";
		}else {
			$seeds = explode(",",$line['bestseeds']);
			$scores = explode(",",$line['bestscores']);
			$attempts = explode(",",$line['bestattempts']);
			$lastanswers = explode("~",$line['bestlastanswers']);
			echo "<p><b>Showing Scored Attempts</b> | ";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}&lastver=1\">Show Last Attempts</a> | ";
			echo "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&asid={$_GET['asid']}&cid=$cid&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		}
		
		
		require("../assessment/displayq2.php");
		echo '<script type="text/javascript">';
		echo 'function hidecorrect() {';
		echo '   var butn = document.getElementById("hctoggle");';
		echo '   if (butn.value=="Hide Perfect Score Questions") {';
		echo '      butn.value = "Show Perfect Score Questions";';
		echo '      var setdispto = "block";';
		echo '   } else { ';
		echo '      butn.value = "Hide Perfect Score Questions";';
		echo '      var setdispto = "none";';
		echo '   }';
		echo '   var divs = document.getElementsByTagName("div");';
		echo '   for (var i=0;i<divs.length;i++) {';
		echo '     if (divs[i].className=="iscorrect") { ';
		echo '         if (divs[i].style.display=="none") {';
		echo '               divs[i].style.display = "block";';
		echo '         } else { divs[i].style.display = "none"; }';
		echo '     }';
		echo '    }';
		echo '}';
		echo 'function hideNA() {';
		echo '   var butn = document.getElementById("hnatoggle");';
		echo '   if (butn.value=="Hide Not Answered Questions") {';
		echo '      butn.value = "Show Not Answered Questions";';
		echo '      var setdispto = "block";';
		echo '   } else { ';
		echo '      butn.value = "Hide Not Answered Questions";';
		echo '      var setdispto = "none";';
		echo '   }';
		echo '   var divs = document.getElementsByTagName("div");';
		echo '   for (var i=0;i<divs.length;i++) {';
		echo '     if (divs[i].className=="notanswered") { ';
		echo '         if (divs[i].style.display=="none") {';
		echo '               divs[i].style.display = "block";';
		echo '         } else { divs[i].style.display = "none"; }';
		echo '     }';
		echo '    }';
		echo '}';
		echo '</script>';
		echo '<input type=button id="hctoggle" value="Hide Perfect Score Questions" onclick="hidecorrect()" />';
		echo ' <input type=button id="hnatoggle" value="Hide Not Answered Questions" onclick="hideNA()" />';
		echo "<form id=\"mainform\" method=post action=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&update=true\">\n";
		$total = 0;
		for ($i=0; $i<count($questions);$i++) {
			echo "<div ";
			if (getpts($scores[$i])==$pts[$questions[$i]]) {
				echo 'class="iscorrect"';	
			} else if ($scores[$i]==-1) {
				echo 'class="notanswered"';	
			} else {
				echo 'class="iswrong"';
			}
			echo '>';
			list($qsetid,$cat) = getqsetid($questions[$i]);
			if ($isteacher || ($testtype=="Practice" && $showans!="N") || ($testtype!="Practice" && (($showans=="I"  && !in_array(-1,$scores))|| ($showans!="N" && time()>$saenddate)))) {$showa=true;} else {$showa=false;}
			displayq($i,$qsetid,$seeds[$i],$showa,false,$attempts[$i]);
			echo '</div>';
			
			if ($scores[$i]==-1) { $scores[$i]="NA";} else {$total+=getpts($scores[$i]);}
			echo "<div class=review>Question ".($i+1).": ";
			if ($withdrawn[$questions[$i]]==1) {
				echo "<span class=\"red\">Question Withdrawn</span> ";
			}
			list($pt,$parts) = printscore($scores[$i]);
			if ($isteacher && $parts=='') { 
				echo "<input type=text size=4 name=\"$i\" value=\"$pt\">";
			} else {
				echo $pt;
			}
			if ($parts!='') {
				if ($isteacher) {
					echo " (parts: ";
					$prts = explode(', ',$parts);
					for ($j=0;$j<count($prts);$j++) {
						echo "<input type=text size=2 name=\"$i-$j\" value=\"{$prts[$j]}\"> ";
					}
					echo ")";
				} else {
					echo " (parts: $parts)";
				}
			}
			echo " out of {$pts[$questions[$i]]} in {$attempts[$i]} attempt(s)\n";
			if ($isteacher) {
				$laarr = explode('##',$lastanswers[$i]);
				if (count($laarr)>1) {
					echo "<br/>Previous Attempts:";
					$cnt =1;
					for ($k=0;$k<count($laarr)-1;$k++) {
						if ($laarr[$k]=="ReGen") {
							echo ' ReGen ';
						} else {
							echo "  <b>$cnt:</b> " . strip_tags($laarr[$k]);
							$cnt++;
						}
					}
				}
			}
			if ($isteacher) {
				echo " <a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}\">Use in Msg</a>";
				echo " &nbsp; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&clearq=$i\">Clear Score</a>";
			}
			echo "</div>\n";
			
		}
		echo "<p></p><div class=review>Total: $total/$totalpossible</div>\n";
		if ($isteacher && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			echo "<p>Feedback to student:<br/><textarea cols=60 rows=4 name=\"feedback\">{$line['feedback']}</textarea></p>";
			if ($line['agroupid']>0) {
				echo "<p>Update grade for all group members? <input type=checkbox name=\"updategroup\" checked=\"checked\" /></p>";
			}
			echo "<p><input type=submit value=\"Record Changed Grades\"></p>\n";
			if ($line['agroupid']>0) {
				$q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				$q2 .= "i_u.id=i_a_s.userid AND i_a_s.agroupid='{$line['agroupid']}'";
				$result = mysql_query($q2) or die("Query failed : " . mysql_error());
				echo "Group members: <ul>";
				while ($row = mysql_fetch_row($result)) {
					echo "<li>{$row[0]}, {$row[1]}</li>";
				}
				echo "</ul>";
			}
				
		} else if (trim($line['feedback'])!='') {
			echo "<p>Instructor Feedback:<div class=\"intro\">{$line['feedback']}</div></p>";
		}
		echo "</form>";
		
		echo "<p><a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Return to GradeBook</a></p>\n";
		
		$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			include("../assessment/catscores.php");
			catscores($questions,$scores,$line['defpoints']);
		}
		
	} else if ($_GET['asid']!="average" && $isteacher) { //asid (assessment-session id) is set: show grade detail question/category breakdown
		require("../header.php");
		
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> ";
		if ($stu>0) {echo "&gt; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Student Detail</a> ";}
		echo "&gt; Detail</div>";
		echo "<h2>Grade Book Detail</h2>\n";
		$query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$_GET['uid']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";
		
		$query = "SELECT imas_assessments.name,imas_assessments.defpoints,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line=mysql_fetch_array($result, MYSQL_ASSOC);
		
		echo "<h4>{$line['name']}</h4>\n";
		echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
		if ($line['endtime']==0) { 
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "</p>\n";
		}
		
		
		$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			include("../assessment/catscores.php");
			catscores(explode(',',$line['questions']),explode(',',$line['bestscores']),$line['defpoints']);
		}
		
		$scores = array();
		$qs = explode(',',$line['questions']);
		foreach(explode(',',$line['bestscores']) as $k=>$score) {
			$scores[$qs[$k]] = getpts($score);
		}
		
		echo "<h4>Question Breakdown</h4>\n";
		echo "<table cellpadding=5 class=gb><thead><tr><th>Question</th><th>Points / Possible</th></tr></thead><tbody>\n";
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questions.withdrawn FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ({$line['questions']})";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=1;
		$totpt = 0;
		$totposs = 0;
		while ($row = mysql_fetch_row($result)) {
			if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
			echo '<td>';
			if ($row[3]==1) {
				echo '<span class="red">Withdrawn</span> ';
			}
			echo $row[0];
			echo "</td><td>{$scores[$row[1]]} / ";
			if ($row[2]==9999) {
				$poss= $line['defpoints'];
			} else {
				$poss = $row[2];
			}
			echo $poss;
			
			echo "</td></tr>\n";
			$i++;
			$totpt += $scores[$row[1]];
			$totposs += $poss;
		}
		echo "</table>\n";
		
		$pc = round(100*$totpt/$totposs,1);
		echo "<p>Total:  $totpt / $totposs  ($pc %)</p>\n";
		
		echo "<p><a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Return to GradeBook</a></p>\n";		
				
		
	} else if ($isteacher) {  //do assessment item analysis
		$pagetitle = "Gradebook";
		$placeinhead = '<script type="text/javascript">';
		$placeinhead .= 'function previewq(qn) {';
		$placeinhead .= "var addr = '$imasroot/course/testquestion.php?cid=$cid&qsetid='+qn;";
		$placeinhead .= "window.open(addr,'Testing','width=400,height=300,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
		$placeinhead .= "}\n</script>";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Gradebook</a> &gt; Item Analysis</div>";
		echo "<h2>Item Analysis: \n";
		$aid = $_GET['aid'];
		$qtotal = array();
		$qcnt = array();
		$qincomplete = array();
		$timetaken = array();
		$attempts = array();
		$regens = array();
		
		$query = "SELECT defpoints,name FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$defpoints = mysql_result($result,0,0);
		echo mysql_result($result,0,1).'</h2>';
		
		$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime FROM imas_assessment_sessions AS ias,imas_students ";
		$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			$questions = explode(',',$line['questions']);
			$scores = explode(',',$line['bestscores']);
			$attp = explode(',',$line['bestattempts']);
			$bla = explode('~',$line['bestlastanswers']);
			foreach ($questions as $k=>$ques) {

				if (!isset($qincomplete[$ques])) { $qincomplete[$ques]=0;}
				if (!isset($qtotal[$ques])) { $qtotal[$ques]=0;}
				if (!isset($qcnt[$ques])) { $qcnt[$ques]=0;}
				if (!isset($attempts[$ques])) { $attempts[$ques]=0;}
				if (!isset($regens[$ques])) { $regens[$ques]=0;}
				if (strpos($scores[$k],'-1')!==false) {
					$qincomplete[$ques] += 1;
				}
				$qtotal[$ques] += getpts($scores[$k]);
				$attempts[$ques] += $attp[$k];
				$regens[$ques] += substr_count($bla[$k],'ReGen');
				$qcnt[$ques] += 1;
			}
			if ($line['endtime'] >0 && $line['starttime'] > 0) {
				$timetaken[] = $line['endtime']-$line['starttime'];
			}
		}
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "<table class=gb id=myTable><thead>"; //<tr><td>Name</td>\n";
		echo "<tr><th scope=\"col\">Question</th><th>Grade</th><th scope=\"col\">Average Score<br/>All</th>";
		echo "<th scope=\"col\">Average Score<br/>Attempted</th><th scope=\"col\">Average Attempts<br/>(Regens)</th><th scope=\"col\">% Incomplete</th><th scope=\"col\">Preview</th></tr></thead>\n";
		echo "<tbody>";
		if (count($qtotal)>0) {
			$i = 1;
			$qs = array_keys($qtotal);
			$qslist = implode(',',$qs);
			$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questionset.id,imas_questions.withdrawn ";
			$query .= "FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
			$query .= " AND imas_questions.id IN ($qslist)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$avgscore = array();
			$qs = array();
			
			while ($row = mysql_fetch_row($result)) {
				if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
				$avg = round($qtotal[$row[1]]/$qcnt[$row[1]],2);
				if ($qcnt[$row[1]] - $qincomplete[$row[1]]>0) {
					$avg2 = round($qtotal[$row[1]]/($qcnt[$row[1]] - $qincomplete[$row[1]]),2); //avg adjusted for not attempted
				} else {
					$avg2 = 0;
				}
				$avgscore[$i-1] = $avg;
				$qs[$i-1] = $row[1];
				$pts = $row[2];
				if ($pts==9999) {
					$pts = $defpoints;
				}
				if ($pts>0) {
					$pc = round(100*$avg/$pts);
					$pc2 = round(100*$avg2/$pts);
				} else {
					$pc = 'N/A';
					$pc2 = 'N/A';
				}
				$pi = round(100*$qincomplete[$row[1]]/$qcnt[$row[1]],1);
				
				if ($qcnt[$row[1]] - $qincomplete[$row[1]]>0) {
					$avgatt = round($attempts[$row[1]]/($qcnt[$row[1]] - $qincomplete[$row[1]]),2);
					$avgreg = round($regens[$row[1]]/($qcnt[$row[1]] - $qincomplete[$row[1]]),2);
				} else {
					$avgatt = 0;
					$avgreg = 0;
				}
				echo "<td>";
				if ($row[4]==1) {
					echo '<span class="red">Withdrawn</span> ';
				}
				echo "{$row[0]}</td>";
				echo "<td><a href=\"gradeallq.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid=average&aid=$aid&qid={$row[1]}\">Grade</a></td>";
				echo "<td>$avg/$pts ($pc%)</td><td>$avg2/$pts ($pc2%)</td><td>$avgatt ($avgreg)</td><td>$pi</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq({$row[3]})\"/></td>\n";
				
				echo "</tr>\n";
				$i++;
			}
		
			echo "</tbody></table>\n";
			echo "<script type=\"text/javascript\">\n";		
			echo "initSortTable('myTable',Array('S','N','N'),true);\n";
			echo "</script>\n";
			echo "<p>Average time taken on this assessment: ";
			echo round(array_sum($timetaken)/count($timetaken)/60,1);
			echo " minutes</p>\n";
		} else {
			echo '</tbody></table>';
		}
		echo "<p><a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid\">Return to GradeBook</a></p>\n";
		
		echo "<p>Note: Average Attempts and Regens only counts those who attempted the problem</p>";
		
		$query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='$aid' AND category<>'0'";
		$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			include("../assessment/catscores.php");
			catscores($qs,$avgscore,$defpoints);
		}
	}
	echo "<p><a href=\"course.php?cid=$cid\">Return to Course Page</a></p>\n";
	
	require("../footer.php");
	
	function getpts($sc) {
		if (strpos($sc,'~')===false) {
			if ($sc>0) { 
				return $sc;
			} else {
				return 0;
			}
		} else {
			$sc = explode('~',$sc);
			$tot = 0;
			foreach ($sc as $s) {
				if ($s>0) { 
					$tot+=$s;
				}
			}
			return round($tot,1);
		}
	}
	function printscore($sc) {
		if (strpos($sc,'~')===false) {

			return array($sc,'');
		} else {
			$pts = getpts($sc);
			$sc = str_replace('-1','N/A',$sc);
			$sc = str_replace('~',', ',$sc);
			return array($pts,$sc);
		}		
	}
	
	function gbtable($isdisp) {
		global $cid,$isteacher,$istutor,$tutorid,$userid,$gbmode,$nopracticet,$curonly,$hidenc,$catfilter,$stu;
		if ($isteacher && func_num_args()>1) {
			$limuser = func_get_arg(1);
		} else if (!$isteacher && !$istutor) {
			$limuser = $userid;
		} else {
			$limuser = 0;
		}
		if ($isteacher && func_num_args()>2) {
			$addcheckbox = func_get_arg(2);
		} else {
			$addcheckbox = false;
		}
		$isdiag = false;
		$category = array();
		if ($isteacher || $istutor) {
			$query = "SELECT sel1name,sel2name FROM imas_diags WHERE cid='$cid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$isdiag = true;
				$sel1name = mysql_result($result,0,0);
				$sel2name = mysql_result($result,0,1);
			}
		}
		$gb = array();
		$cathdr = array();
		$feedbacks = array();
		$atots = array();
		$ln = 0;
		if ($isdiag) {
			$shift = 5;
		} else {
			$shift = 1;
		}
		//Build user ID headers - length: $shift
		$gb[0][0] = "Name";
		if ($isdiag) {
			$gb[0][1] = "ID";
			$gb[0][2] = "Term";
			$gb[0][3] = ucfirst($sel1name);
			$gb[0][4] = ucfirst($sel2name);
		}
		if (!$isdisp) {
			$gb[0][] = "Username";
			$shift++;
		}
		$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			$hassection = true;
		} else {
			$hassection = false;
		}
		$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.code IS NOT NULL";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_result($result,0,0)>0) {
			$hascode = true;
		} else {
			$hascode = false;
		}
		if ($hassection) {
			$gb[0][] = "Section";
			$shift++;
		}
		if ($hascode) {
			$gb[0][] = "Code";
			$shift++;
		}
		//Pull Assessment Info
		$now = time();
		$query = "SELECT id,name,defpoints,deffeedback,timelimit,minscore,enddate,itemorder,gbcategory,cntingb FROM imas_assessments WHERE courseid='$cid' ";
		if (!$isteacher) {
			$query .= "AND cntingb>0 ";
		}
		if ($hidenc) {
			$query .= "AND (cntingb=1 OR cntingb=2)";
		}
		if (!$isteacher || $curonly) {
			$query .= "AND startdate<$now ";
		}
		if ($catfilter>-1) {
			$query .= "AND gbcategory='$catfilter' ";
		}
		if ($nopracticet) {
			$query .= "AND deffeedback NOT LIKE 'Practice%'";
		}
	
		$query .= "ORDER BY enddate";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$overallpts = 0;
		$now = time();
		$kcnt = 0;
		$assessments = array();
		$grades = array();
		$timelimits = array();
		$minscores = array();
		$assessmenttype = array();
		$enddate = array();
		$sa = array();
		$category = array();
		$name = array();
		$possible = array();
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			$assessments[$kcnt] = $line['id'];
			$timelimits[$kcnt] = $line['timelimit'];
			$minscores[$kcnt] = $line['minscore'];
			$deffeedback = explode('-',$line['deffeedback']);
			$assessmenttype[$kcnt] = $deffeedback[0];
			$sa[$kcnt] = $deffeedback[1];
			$enddate[$kcnt] = $line['enddate'];
			$category[$kcnt] = $line['gbcategory'];
			$name[$kcnt] = $line['name'];
			$cntingb[$kcnt] = $line['cntingb']; //0: ignore, 1: count, 2: extra credit, 3: no count but show
			
			$aitems = explode(',',$line['itemorder']);
			$aitemcnt = array();
			foreach ($aitems as $k=>$v) {
				if (strpos($v,'~')!==FALSE) {
					$sub = explode('~',$v);
					if (strpos($sub[0],'|')===false) { //backwards compat
						$aitems[$k] = $sub[0];
						$aitemcnt[$k] = 1;
					} else {
						$grpparts = explode('|',$sub[0]);
						$aitems[$k] = $sub[1];
						$aitemcnt[$k] = $grpparts[0];
					}
				} else {
					$aitemcnt[$k] = 1;
				}
			}
			
			$query = "SELECT points,id FROM imas_questions WHERE assessmentid='{$line['id']}'";
			$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$totalpossible = 0;
			while ($r = mysql_fetch_row($result2)) {
				if (in_array($r[1],$aitems)) {
					$k = array_search($r[1],$aitems);
					if ($r[0]==9999) {
						$totalpossible += $aitemcnt[$k]*$line['defpoints']; //use defpoints
					} else {
						$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
					}
				}
			}
			//if ($deffeedback[0]!="Practice") {$overallpts += $totalpossible;}
			$possible[$kcnt] = $totalpossible;
			//$gb[0][$pos] = "{$line['name']}<BR>$totalpossible&nbsp;pts";

			//if ($deffeedback[0]=="Practice") {$gb[0][$pos] .= " (PT)";}
			//$pos++;
			$kcnt++;
		}
		
		//Pull Offline Grade item info
		$query = "SELECT * from imas_gbitems WHERE courseid='$cid' ";
		if (!$isteacher || $curonly) {
			$query .= "AND showdate<$now ";
		}
		if (!$isteacher) {
			$query .= "AND cntingb>0 ";
		}
		if ($hidenc) {
			$query .= "AND (cntingb=1 OR cntingb=2)";
		}
		if ($catfilter>-1) {
			$query .= "AND gbcategory='$catfilter' ";
		}
		$query .= "ORDER BY showdate";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			$grades[$kcnt] = $line['id'];
			$assessmenttype[$kcnt] = "Offline";
			$category[$kcnt] = $line['gbcategory'];
			$enddate[$kcnt] = $line['showdate'];
			$possible[$kcnt] = $line['points'];
			$name[$kcnt] = $line['name'];
			$cntingb[$kcnt] = $line['cntingb'];
			$kcnt++;
		}
		
		//Pull Discussion Grade info
		$query = "SELECT id,name,gbcategory,enddate,points FROM imas_forums WHERE courseid='$cid' AND points>0 ";
		if (!$isteacher || $curonly) {
			$query .= "AND startdate<$now ";
		}
		if ($catfilter>-1) {
			$query .= "AND gbcategory='$catfilter' ";
		}
		$query .= "ORDER BY enddate";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			$discuss[$kcnt] = $line['id'];
			$assessmenttype[$kcnt] = "Discussion";
			$category[$kcnt] = $line['gbcategory'];
			$enddate[$kcnt] = $line['enddate'];
			$possible[$kcnt] = $line['points'];
			$name[$kcnt] = $line['name'];
			$cntingb[$kcnt] = 1;
			$kcnt++;
		}
		
		//Pull Gradebook Scheme info
		$query = "SELECT useweights,orderby,defaultcat FROM imas_gbscheme WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($useweights,$orderby,$defaultcat) = mysql_fetch_row($result);
		
		$cats = array();
		$catcolcnt = 0;
		//Pull Categories:  Name, scale, scaletype, chop, drop, weight
		if (in_array(0,$category)) {  //define default category, if used
			$cats[0] = explode(',',$defaultcat); 
			array_unshift($cats[0],"Default");
			array_push($cats[0],$catcolcnt);
			$catcolcnt++;
			
		}
		if ($catfilter!==0) {
			$query = "SELECT id,name,scale,scaletype,chop,dropn,weight FROM imas_gbcats WHERE courseid='$cid' ";
			if ($catfilter>-1) {
				$query .= "AND id='$catfilter' ";
			}
			$query .= "ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if (in_array($row[0],$category)) { //define category if used
					$cats[$row[0]] = array_slice($row,1);
					array_push($cats[$row[0]],$catcolcnt);
					$catcolcnt++;
				}
			}
		}
		//create item headers
		$pos = $shift;
		$catposs = array();
		$catpossec = array();
		$itemorder = array();
		if ($orderby==1) { //order $category by enddate
			asort($enddate,SORT_NUMERIC);
			$newcategory = array();
			foreach ($enddate as $k=>$v) {
				$newcategory[$k] = $category[$k];
			}
			$category = $newcategory;
		} else if ($orderby==3) { //order $category alpha
			asort($name);
			$newcategory = array();
			foreach ($name as $k=>$v) {
				$newcategory[$k] = $category[$k];
			}
			$category = $newcategory;
		}
		foreach(array_keys($cats) as $cat) {//foreach category
			$catkeys = array_keys($category,$cat); //pull items in that category
			if (($orderby&1)==1) { //order by category
				array_splice($itemorder,count($itemorder),0,$catkeys);
			}
			foreach ($catkeys as $k) {
				if ($assessmenttype[$k]!="Practice" && $cntingb[$k]==1) {
					$catposs[$cat][] = $possible[$k]; //create category totals
				} else if ($cntingb[$k]==2) {
					$catpossec[$cat][] = 0;
				}
				if (($orderby&1)==1) {  //display item header if displaying by category
					$cathdr[$pos] = $cats[$cat][6];
		
					if ($cntingb[$k]==0 || $cntingb[$k]==3) {
						$gb[0][$pos] = $name[$k].'<br/>'.$possible[$k].'(Not Counted)';
					} else {
						$gb[0][$pos] = $name[$k].'<br/>'.$possible[$k].'&nbsp;pts';
						if ($cntingb[$k]==2) {
							$gb[0][$pos] .= ' (EC)';
						}
					}
					if ($assessmenttype[$k]=="Practice") {$gb[0][$pos] .= " (PT)";}
					if ($isteacher && $isdisp) {
						if (isset($assessments[$k])) {
							$gb[0][$pos] .= "<br/><a class=small href=\"addassessment.php?id={$assessments[$k]}&cid=$cid&from=gb\">[Settings]</a>";
						} else if (isset($grades[$k])) {
							$gb[0][$pos] .= "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&gbmode=$gbmode&grades=all&gbitem={$grades[$k]}\">[Settings]</a>";
						} else if (isset($discuss[$k])) {
							$gb[0][$pos] .= "<br/><a class=small href=\"addforum.php?id={$discuss[$k]}&cid=$cid&from=gb\">[Settings]</a>";
						}
					}
					$pos++;
				}
			}
		}
		if (($orderby&1)==0) {//if not grouped by category
			if ($orderby==0) {
				asort($enddate,SORT_NUMERIC);
				$itemorder = array_keys($enddate);
			} else if ($orderby==2) {
				asort($name);
				$itemorder = array_keys($name);
			}
			foreach ($itemorder as $k) {
				$cathdr[$pos] = $cats[$category[$k]][6];
				
				if ($cntingb[$k]==0 || $cntingb[$k]==3) {
					$gb[0][$pos] = $name[$k].'<br/>'.$possible[$k].'(Not Counted)';
				} else {
					$gb[0][$pos] = $name[$k].'<br/>'.$possible[$k].'&nbsp;pts';
					if ($cntingb[$k]==2) {
						$gb[0][$pos] .= ' (EC)';
					}
				}
				if ($assessmenttype[$k]=="Practice") {$gb[0][$pos] .= " (PT)";}
				if ($isteacher && $isdisp) {
					if (isset($assessments[$k])) {
						$gb[0][$pos] .= "<br/><a class=small href=\"addassessment.php?id={$assessments[$k]}&cid=$cid&from=gb\">[Settings]</a>";
					} else if (isset($grades[$k])) {
						$gb[0][$pos] .= "<br/><a class=small href=\"addgrades.php?stu=$stu&cid=$cid&gbmode=$gbmode&grades=all&gbitem={$grades[$k]}\">[Settings]</a>";
					}
				}
				$pos++;
			}
		} 
		$totalspos = $pos;
		//create category headers
		if (count($cats)>1 || !isset($cats[0])) {//something other than default category
			$showcats = true;
		} else {
			$showcats = false;
		}
		$catorder = array_keys($cats);
		$overallpts = 0;
		foreach($catorder as $cat) {//foreach category
			if (!isset($catposs[$cat])) {
				continue;
			}
			//cats: name,scale,scaletype,chop,drop,weight
			$catitemcnt[$cat] = count($catposs[$cat]) + count($catpossec[$cat]);
			if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($catposs[$cat])) { //if drop is set and have enough items
				asort($catposs,SORT_NUMERIC);
				$catposs[$cat] = array_slice($catposs[$cat],$cats[$cat][4]);
			}
			$catposs[$cat] = array_sum($catposs[$cat]);
			if ($showcats) {
				if ($isdisp) {
					$gb[0][$pos] = '<span class="cattothdr">';
				} else {
					$gb[0][$pos] = '';
				}
				if ($useweights==0 && $cats[$cat][5]>-1) { //if scaling cat total to point value
					$gb[0][$pos] .= $cats[$cat][0].'<br/>'.$cats[$cat][5].'&nbsp;pts'; 
				} else {
					$gb[0][$pos] .= $cats[$cat][0].'<br/>'.$catposs[$cat].'&nbsp;pts';
				}
				if ($isdisp) {
					$gb[0][$pos] .= '</span>';
				}
				$atots[$pos] = array();
				$pos++;
			}
			if ($cats[$cat][5]>-1) {
				$overallpts += $cats[$cat][5];
			} else {
				$overallpts += $catposs[$cat];
			}
		}
		
		
		//find total possible points
		if ($catfilter<0) {
			if ($useweights==0) { //use points grading method
				/*$overallpts=0;
				foreach ($catorder as $cat) {
					if ($cats[$cat][5]>-1) {
						$overallpts += $cats[$cat][5];
					} else {
						$overallpts += $catposs[$cat];
					}
				}*/
				if ($isdisp) {
					$gb[0][$pos] = "<span class=\"cattothdr\">Total<br/>$overallpts pts</span>";
					$gb[0][$pos+1] = "<span class=\"cattothdr\">%</span>";
				} else {
					$gb[0][$pos] = "Total<br/>$overallpts pts";
					$gb[0][$pos+1] = "%";
				}
				
				$cathdr[$pos] = $catcolcnt;
				$cathdr[$pos+1] = $catcolcnt;
			} else if ($useweights==1) { //use weights (%) grading method
				if ($isdisp) {
					$gb[0][$pos] = "<span class=\"cattothdr\">Weighted Total %</span>";
				} else {
					$gb[0][$pos] = "Weighted Total %";
				}
				$cathdr[$pos] = $catcolcnt;
			}
		}
		
		//Pull student data
		$ln = 1;
		$query = "SELECT imas_users.id,imas_users.SID,imas_users.FirstName,imas_users.LastName,imas_users.SID,imas_users.email,imas_students.section,imas_students.code ";
		$query .= "FROM imas_users,imas_students WHERE imas_users.id=imas_students.userid AND imas_students.courseid='$cid' ";
		//$query .= "FROM imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND imas_teachers.courseid='$cid' ";
		//if (!$isteacher && !isset($tutorid)) {$query .= "AND imas_users.id='$userid' ";}
		if ($limuser>0) { $query .= "AND imas_users.id='$limuser' ";}
		if ($isdiag) {
			$query .= "ORDER BY imas_users.email,imas_users.LastName,imas_users.FirstName";
		} else if ($hassection) {
			$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$alt = 0;
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) { //foreach student
			unset($asid); unset($pts); unset($IP); unset($timeused);
			//Student ID info
			$gb[$ln][0] = '';
			if ($isteacher && $isdisp && $addcheckbox) {
				$gb[$ln][0] .= "<input type=\"checkbox\" name='checked[]' value='{$line['id']}' />&nbsp;";
			}
			if ($isteacher && $isdisp) {
				$gb[$ln][0] .= "<a href=\"gradebook.php?cid=$cid&gbmode=$gbmode&stu={$line['id']}\">";
			}
			
			$gb[$ln][0] .= "{$line['LastName']},&nbsp;{$line['FirstName']}";
			if ($isteacher && $isdisp) {
				$gb[$ln][0] .= '</a>';
			}
			if ($isdiag) {
				$selparts = explode('~',$line['SID']);
				$gb[$ln][1] = $selparts[0];
				$gb[$ln][2] = $selparts[1];
				$selparts =  explode('@',$line['email']);
				$gb[$ln][3] = $selparts[0];
				$gb[$ln][4] = $selparts[1];
			}
			if ($hassection) {
				$gb[$ln][] = $line['section'];
			}
			if ($hascode) {
				$gb[$ln][] = $line['code'];
			}
			if (!$isdisp) {
				$gb[$ln][] = $line['SID'];
			}
			//Get assessment scores
			$query = "SELECT id,assessmentid,bestscores,starttime,endtime,feedback FROM imas_assessment_sessions WHERE userid='{$line['id']}'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($l = mysql_fetch_array($result2, MYSQL_ASSOC)) {
				$asid[$l['assessmentid']] = $l['id'];
				$scores = explode(",",$l['bestscores']);
				$total = 0;
				for ($i=0;$i<count($scores);$i++) {
					$total += getpts($scores[$i]);
					//if ($scores[$i]>0) {$total += $scores[$i];}
				}
				$timeused[$l['assessmentid']] = $l['endtime']-$l['starttime'];
				$afeedback[$l['assessmentid']] = $l['feedback'];
				if (in_array(-1,$scores)) { $IP[$l['assessmentid']]=1;}
				$pts[$l['assessmentid']] = $total;
			}
			//Get other grades
			unset($gradeid); unset($opts);
			$query = "SELECT imas_gbitems.id,imas_grades.id,imas_grades.score,imas_grades.feedback FROM imas_grades,imas_gbitems WHERE ";
			$query .= "imas_grades.gbitemid=imas_gbitems.id AND imas_grades.userid='{$line['id']}'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($r = mysql_fetch_row($result2)) {
				$gradeid[$r[0]] = $r[1];
				$opts[$r[0]] = $r[2];
				$gfeedback[$r[0]] = $r[3];
			}
			//Get discussion grades
			unset($discusspts);
			$query = "SELECT forumid,SUM(points) FROM imas_forum_posts WHERE userid='{$line['id']}' GROUP BY forumid ";
			$result2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($r = mysql_fetch_row($result2)) {
				$discusspts[$r[0]] = $r[1];
			}
			
			//Create student GB row
			unset($cattot);
			$pos = $shift;
			foreach ($itemorder as $i) { 
				$gb[$ln][$pos] = '';
				if ($assessmenttype[$i]=='Offline') { //is other grade
					if (isset($gradeid[$grades[$i]])) {
						if ($isteacher && $isdisp) {
							$gb[$ln][$pos] .= "<a href=\"addgrades.php?stu=$stu&cid=$cid&gbmode=$gbmode&grades={$line['id']}&gbitem={$grades[$i]}\">";
						} else if ($isdisp) {
							$gb[$ln][$pos] .= "<a href=\"viewgrade.php?cid=$cid&gid={$gradeid[$grades[$i]]}\">";
						}
						$gb[$ln][$pos] .= 1*$opts[$grades[$i]];
						$atots[$pos][] = $opts[$grades[$i]];
						if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
							$cattot[$category[$i]][] = $opts[$grades[$i]];
						}
						if ($isdisp) {
							$gb[$ln][$pos] .= '</a>';
						}
						if ($isteacher && $isdisp && $gfeedback[$grades[$i]]!='') {
							$gb[$ln][$pos] .= '<sup>*</sup>';
						}
						if ($limuser>0) {
							$feedbacks[$pos] = $gfeedback[$grades[$i]];
						}
					} else {
						if ($isteacher && $isdisp) {
							$gb[$ln][$pos] .= "<a href=\"addgrades.php?stu=$stu&cid=$cid&gbmode=$gbmode&grades={$line['id']}&gbitem={$grades[$i]}\">-</a>";
						} else {
							$gb[$ln][$pos] = '-';
						}
					}
				} else if ($assessmenttype[$i]=='Discussion') { //is discussion grade
					if (isset($discusspts[$discuss[$i]])) {
						$gb[$ln][$pos] = $discusspts[$discuss[$i]];
						$atots[$pos][] = $discusspts[$discuss[$i]];
						$cattot[$category[$i]][] = $discusspts[$discuss[$i]];
					} else {
						$gb[$ln][$pos] = '-';
					}
				} else if (isset($asid[$assessments[$i]])) {
					if (!$isteacher) {
						$query = "SELECT enddate FROM imas_exceptions WHERE userid='{$line['id']}' AND assessmentid='{$assessments[$i]}'";
						$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($r2)>0) {
							$exped = mysql_result($r2,0,0);
							if ($exped>$enddate[$i]) {
								$enddate[$i] = $exped;
							}
						}
					}
					if ($isdisp && ($isteacher || $assessmenttype[$i]=="Practice" || $sa[$i]=="I" || ($sa[$i]!="N" && $now>$enddate[$i]))) {
						$gb[$ln][$pos] =  "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$asid[$assessments[$i]]}&uid={$line['id']}\">";
					} else {
						$gb[$ln][$pos] = '';
					}
					if ($assessmenttype[$i]=="NoScores" && $sa[$i]!="I" && $now<$enddate[$i] && !$isteacher) {
						$gb[$ln][$pos] .= 'N/A';
					} else if ($pts[$assessments[$i]]<$minscores[$i]) {
						if ($isteacher) {
							$gb[$ln][$pos] .= "{$pts[$assessments[$i]]}&nbsp;(NC)";
						} else {
							$gb[$ln][$pos] .= 'NC';
						}
					} else 	if ($IP[$assessments[$i]]==1 && $enddate[$i]>$now) {
						$gb[$ln][$pos] .= "{$pts[$assessments[$i]]}&nbsp;(IP)";
					} else	if (($timelimits[$i]>0) &&($timeused[$assessments[$i]] > $timelimits[$i])) {
						$gb[$ln][$pos] .= "{$pts[$assessments[$i]]}&nbsp;(OT)";
					} else if ($assessmenttype[$i]=="Practice") {
						$gb[$ln][$pos] .= "{$pts[$assessments[$i]]}&nbsp;(PT)";
					} else {
						$gb[$ln][$pos] .= "{$pts[$assessments[$i]]}";
						if ($cntingb[$i] == 1 || $cntingb[$i]==2) {
							$cattot[$category[$i]][] = $pts[$assessments[$i]];
						}
						$atots[$pos][] = $pts[$assessments[$i]];
					}
					if ($isteacher && $isdisp && $afeedback[$assessments[$i]]!='') {
						$gb[$ln][$pos] .= '<sup>*</sup>';
					}
					if ($isdisp && ($isteacher || $assessmenttype[$i]=="Practice" || $sa[$i]=="I" || ($sa[$i]!="N" && $now>$enddate[$i]))) {
						$gb[$ln][$pos] .= "</a>";
					}
					if ($limuser>0) {
						$feedbacks[$pos] = $afeedback[$assessments[$i]];
					}
				} else {
					if ($isdisp && $isteacher) {
						$gb[$ln][$pos] = "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid=new&uid={$line['id']}&aid={$assessments[$i]}\">";
					} else {
						$gb[$ln][$pos] = '';
					}
					$gb[$ln][$pos] .= '-';
					if ($isdisp && $isteacher) {
						$gb[$ln][$pos] .= "</a>";
					}
				}
				$pos++;
			
			}
			$tot = 0;
			//create category totals
			
			foreach($catorder as $cat) {//foreach category
				if (!isset($catposs[$cat])) {
					continue;
				}
				if (isset($cattot[$cat])) {
					//cats: name,scale,scaletype,chop,drop,weight
					if ($cats[$cat][4]!=0 && abs($cats[$cat][4])<count($cattot[$cat])) { //if drop is set and have enough items
						asort($cattot[$cat],SORT_NUMERIC);
						while (count($cattot[$cat])<$catitemcnt[$cat]) {
							array_unshift($cattot[$cat],0);
						}
						$cattot[$cat] = array_slice($cattot[$cat],$cats[$cat][4]);
					}
					$cattot[$cat] = array_sum($cattot[$cat]); //**adjust for drop, scale, etc
					if ($cats[$cat][1]!=0) { //scale is set
						if ($cats[$cat][2]==0) { //pts scale
							$cattot[$cat] = round($catposs[$cat]*($cattot[$cat]/$cats[$cat][1]),1);
						} else if ($cats[$cat][2]==1) { //percent scale
							$cattot[$cat] = round($cattot[$cat]*(100/($cats[$cat][1])),1);
						}
					}
					if ($useweights==0 && $cats[$cat][5]>-1) {//use fixed pt value for cat
						$cattot[$cat] = round($cats[$cat][5]*($cattot[$cat]/$catposs[$cat]),1);
					}
					if ($cats[$cat][3]==1) {
						if ($useweights==0  && $cats[$cat][5]>-1) { //set cat pts
							$cattot[$cat] = min($cats[$cat][5],$cattot[$cat]);
						} else {
							$cattot[$cat] = min($catposs[$cat],$cattot[$cat]);
						}
					}
					if ($showcats) {
						$gb[$ln][$pos] = $cattot[$cat];
						$cathdr[$pos] = $cats[$cat][6];
					}
					$atots[$pos][] = $cattot[$cat];
					if ($useweights==1) {
						if ($catposs[$cat]>0) {
							$tot += ($cattot[$cat]*$cats[$cat][5])/(100*$catposs[$cat]); //weight total
						}
					}
				} else { //no items in category yet?
					if ($showcats) {
						$gb[$ln][$pos] = '-';
						$cathdr[$pos] = $cats[$cat][6];
					}
				}
				if ($showcats) {
					$pos++;
				}
			}
			if ($catfilter<0) {
				if ($useweights==0) { //use points grading method
					if (!isset($cattot)) {
						$tot = 0;
					} else {
						$tot = array_sum($cattot);
					}
					$gb[$ln][$pos] = $tot;
					if ($overallpts>0) {
						$gb[$ln][$pos+1] = round(100*$tot/$overallpts,1).'%';
					} else {
						$gb[$ln][$pos+1] = '0%';
					}
					$atots[$pos][] = $tot;
					$atots[$pos+1][] = $gb[$ln][$pos+1];
				} else if ($useweights==1) { //use weights (%) grading method
					//already calculated $tot
					if ($overallpts>0) {
						$tot = 100*($tot/$overallpts);
					} else {
						$tot = 0;
					}
					$gb[$ln][$pos] = round(100*$tot,1);
					$atots[$pos][] = $gb[$ln][$pos];
				}
			}
			$ln++;
		}	
		if ($isdisp && $isteacher) { //calculate averages
			$gb[$ln][0] = "<a href=\"gradebook.php?cid=$cid&gbmode=$gbmode&stu=-1\">Average</a>";
			
			if ($shift>1) {
				for ($i=1;$i<$shift;$i++) {
					$gb[$ln][$i] = '';
				}
			}	
			$pos = $shift;
			foreach ($itemorder as $i) { 
				if (isset($atots[$pos])) {
					if ($assessmenttype[$i]=='Offline') {
						$gb[$ln][$pos] = "<a href=\"addgrades.php?stu=$stu&gbmode=$gbmode&cid=$cid&grades=all&gbitem={$grades[$i]}\">".round(array_sum($atots[$pos])/count($atots[$pos]),1) . "</a>";
					} else {
						$curaid = $assessments[$i];
						$gb[$ln][$pos] = "<a href=\"gradebook.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid=average&aid=$curaid\">".round(array_sum($atots[$pos])/count($atots[$pos]),1) . "</a>";
					}
				} else {
					if ($assessmenttype[$i]=='Offline') {
						$gb[$ln][$pos] = "<a href=\"addgrades.php?stu=$stu&gbmode=$gbmode&cid=$cid&grades=all&gbitem={$grades[$i]}\">-</a>";
					} else {
						$gb[$ln][$pos] = '-';
					}
				}
				$pos++;
			}
			while (isset($atots[$pos])) {
				if (count($atots[$pos])==0) {
					$gb[$ln][$pos] = 0;
				} else {
					$gb[$ln][$pos] = round(array_sum($atots[$pos])/count($atots[$pos]),1);
				}
				$pos++;
			}
			/*
			if (isset($atots[$pos])) { //if totals have been calculated
				$gb[$ln][$pos] = round(array_sum($atots[$pos])/count($atots[$pos]),1);
				$pos++;
			} 
			if ($useweights==0 && isset($atots[$pos])) {
				if ($overallpts>0) {
					$gb[$ln][$pos] = round(100*$gb[$ln][$pos-1]/$overallpts,1).'%';
				} else {
					$gb[$ln][$pos] = '0%';
				}
			}*/
			$ln++;
			
		}
		foreach ($gb[0] as $k=>$v) {
			if (!isset($cathdr[$k])) {
				$cathdr[$k] = -1;
			}
		}
		ksort($cathdr);
		if ($catfilter==-2) {  //showing only category totals
			for ($i=0;$i<$ln;$i++) {
				$gb[$i] = array_merge(array_slice($gb[$i],0,$shift),array_slice($gb[$i],$totalspos));
			}
			$cathdr = array_merge(array_slice($cathdr,0,$shift),array_slice($cathdr,$totalspos));
		}
		if (($gbmode&8)==8) {  //if totals on left
			if ($limuser>0) {
				for ($i=0;$i<count($gb[0]);$i++) {
					if (!isset($feedbacks[$i])) {
						$feedbacks[$i] = '';
					}
				}
				ksort($feedbacks);
				$tots = array_splice($feedbacks,$totalspos);
				if ($catfilter<0) { //move total totals to far left
					if ($useweights==0) { //two totals cols
						$tottots = array_splice($tots,-2);
						array_splice($tots,0,0,$tottots);
					} else if ($useweights==1) {
						$tottots = array_splice($tots,-1);
						array_splice($tots,0,0,$tottots);
					}
				}
						
				array_splice($feedbacks,$shift,0,$tots);
			}
			for ($i=0;$i<$ln;$i++) {
				$tots = array_splice($gb[$i],$totalspos);
				if ($catfilter<0) { //move total totals to far left
					if ($useweights==0) { //two totals cols
						$tottots = array_splice($tots,-2);
						array_splice($tots,0,0,$tottots);
					} else if ($useweights==1) {
						$tottots = array_splice($tots,-1);
						array_splice($tots,0,0,$tottots);
					}
				}
				array_splice($gb[$i],$shift,0,$tots);
			}
			$tots = array_splice($cathdr,$totalspos);
			if ($catfilter<0) { //move total totals to far left
				if ($useweights==0) { //two totals cols
					$tottots = array_splice($tots,-2);
					array_splice($tots,0,0,$tottots);
				} else if ($useweights==1) {
					$tottots = array_splice($tots,-1);
					array_splice($tots,0,0,$tottots);
				}
			}
			array_splice($cathdr,$shift,0,$tots);
			
		}
		if ($limuser>0) {
			return array($gb,$cathdr,$feedbacks);
		} else {
			return array($gb,$cathdr);
		}
	}
	
	function getasidquery($asid) {
		$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$asid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$agroupid = mysql_result($result,0,0);
		if ($agroupid>0) {
			return (" WHERE agroupid='$agroupid'");
		} else {
			return (" WHERE id='$asid' LIMIT 1");
		}
	}
	function isasidgroup($asid) {
		$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$asid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		return (mysql_result($result,0,0)==$asid);
	}
?>
