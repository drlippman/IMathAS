<?php
//IMathAS:  Display the calendar by itself
//(c) 2008 David Lippman
	require("../init.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}

	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_GET['editing'])) {
		$editingon = $_GET['editing']=='on';
		$sessiondata[$cid.'caledit'] = $editingon;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'caledit'])) {
		$editingon = $sessiondata[$cid.'caledit'];
	} else {
		$editington = false;
	}

	require_once("../includes/exceptionfuncs.php");

	if (isset($studentid) && !isset($sessiondata['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	require("../includes/calendardisp.php");
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/course.js?v=072917\"></script>";
	if ($editingon) {
		$placeinhead .= '<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
				<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>';
		$loadiconfont = true;
	}

	require("../header.php");
	if ($editingon) {
	?>
	<style type="text/css">
	span.calitem {
		display: inline-block;
		padding: 2px 5px;
		cursor: move;
	}
	span.calitemhighlight {
		background-color: #6cf;
	}
	span.calitem span {
		display: table-cell;
		vertical-align:middle;
	}
	span.calitemtitle {
		font-size: 80%;
	}
	span.calitem[id^=AS],span.calitem[id^=IS],span.calitem[id^=LS],span.calitem[id^=DS],span.calitem[id^=FS] {
		border-radius: 10px 0 0 10px;
	}
	span.calitem[id^=AE],span.calitem[id^=IE],span.calitem[id^=LE],span.calitem[id^=DE],span.calitem[id^=FE] {
		border-radius: 0 10px 10px 0;
	}
	</style>
  <script type="text/javascript">
	$(function() {
			$("span.calitem[id^=CD]").attr("title",_("Calendar Event Date"));
			$("span.calitem[id^=AS]").attr("title",_("Assessment Available After"));
			$("span.calitem[id^=AE]").attr("title",_("Assessment Due Date"));
			$("span.calitem[id^=AR]").attr("title",_("Assessment Review Date"));
			$("span.calitem[id^=IS]").attr("title",_("Inline Text Available After"));
			$("span.calitem[id^=IE]").attr("title",_("Inline Text Available Until"));
			$("span.calitem[id^=IO]").attr("title",_("Inline Text On Calendar Date"));
			$("span.calitem[id^=LS]").attr("title",_("Link Available After"));
			$("span.calitem[id^=LE]").attr("title",_("Link Available Until"));
			$("span.calitem[id^=LO]").attr("title",_("Link On Calendar Date"));
			$("span.calitem[id^=DS]").attr("title",_("Drill Available After"));
			$("span.calitem[id^=DE]").attr("title",_("Drill Available Until"));
			$("span.calitem[id^=FS]").attr("title",_("Forum Available After"));
			$("span.calitem[id^=FE]").attr("title",_("Forum Available Until"));
			$("span.calitem[id^=FP]").attr("title",_("Forum Post By"));
			$("span.calitem[id^=FR]").attr("title",_("Forum Reply By"));
			$("span.calitem").draggable({
					containment: "table.cal",
					revert: "invalid",
					start: function() {
						$(this).data("originalParent", $(this).closest("td").attr("id"));
					}
				}).on('mouseenter', function(ev) {
					var id = this.id.substr(2);
					$("span.calitem[id$="+id+"]").addClass("calitemhighlight");
				}).on('mouseleave', function(ev) {
					var id = this.id.substr(2);
					$("span.calitem[id$="+id+"]").removeClass("calitemhighlight");
				});
			$("table.cal td").droppable({
				drop: function(event,ui) {
					var dropped = ui.draggable;
					var droppedOn = $(this);
					$(dropped).detach().css({top: 0,left: 0}).appendTo(droppedOn.find("div.center"));
					if (droppedOn.attr("id") != $(dropped).data("originalParent")) {
						$(".calupdatenotice").html('<img src="../img/updating.gif" alt="Saving"/> '+_("Saving..."));
						$.ajax({
							"url": "savecalendardrag.php",
							data: {
								cid: <?php echo $cid;?>,
								item: ui.draggable[0].id,
								dest: this.id
							}
						}).done(function(msg) {
							if (msg.res=="error") {
								console.log("ERROR: "+msg.error);
								$(".calupdatenotice").html(_("Error saving change"));
								$(dropped).detach().css({top: 0,left: 0}).appendTo($("#"+$(dropped).data("originalParent")).find("div.center"));
							} else {
								console.log(msg.success);
								$(".calupdatenotice").html("");
								var daycaldata = caleventsarr[$(dropped).data("originalParent")].data;
								for (var i=0; i<daycaldata.length;i++) {
									if (daycaldata[i].type + daycaldata[i].typeref == dropped[0].id) {
										var thisrec = daycaldata.splice(i,1);
										if (caleventsarr[droppedOn.attr("id")].hasOwnProperty("data")) {
											caleventsarr[droppedOn.attr("id")].data.push(thisrec[0]);
										} else {
											caleventsarr[droppedOn.attr("id")].data = thisrec;
										}
										if ($("table.cal td.today").length>0) {
											showcalcontents($("table.cal td.today")[0]);
										}
										break;
									}
								}

							}
						}).fail(function() {
								$(dropped).detach().css({top: 0,left: 0}).appendTo($("#"+$(dropped).data("originalParent")).find("div.center"));
						});
						console.log(ui.draggable[0].id + " dropped on " + this.id);
					}

				}
			});
	});
	</script>
	<?php
	} //end $editingon block
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Calendar</div>";
	echo '<div id="headercalendar" class="pagetitle"><h2>Calendar</h2></div>';

	 if (isset($teacherid)) {
		echo "<div class=\"cpmid\"><a id=\"mcelink\" href=\"managecalitems.php?from=cal&cid=$cid\">Manage Events</a> | ";
		if ($editingon) {
			echo '<a href="showcalendar.php?cid='.$cid.'&editing=off">'._('Disable Drag-and-drop Editing').'</a> | ';
		} else {
			echo '<a href="showcalendar.php?cid='.$cid.'&editing=on">'._('Enable Drag-and-drop Editing').'</a> | ';
		}
		echo '<a href="exportcalfeed.php?cid='.$cid.'">'._('Export Calendar Feed').'</a>';
		echo "</div>";
	 }
	 if ($editingon) {
		 echo '<p>'._('Drag-and-drop events to change dates. Note that time of day is not changed - use Mass Change Dates for that.').'</p>';
		 echo '<p>'._('Item Legend:').' <span class="icon-startdate"></span>'. _('Available After date');
		 echo ', <span class="icon-enddate"></span> '. _('Available Until (Due) date');
		 echo ', <span class="icon-eye2"></span>'. _('Assessment Review date');
		 echo ', <span class="icon-forumpost"></span>'. _('Forum post-by date');
		 echo ', <span class="icon-forumreply"></span>'. _('Forum reply-by date');
		 echo '</p>';
	 }
	 if (!isset($teacherid) && !isset($tutorid) && !$inInstrStuView && isset($studentinfo)) {
	   //$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
	   //$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	   //$latepasses = mysql_result($result,0,0);
	   $latepasses = $studentinfo['latepasses'];
	} else {
		$latepasses = 0;
	}

	 $stm = $DBH->prepare("SELECT name,itemorder,hideicons,picicons,allowunenroll,msgset,toolset,latepasshrs FROM imas_courses WHERE id=:id");
	 $stm->execute(array(':id'=>$cid));
	 $line = $stm->fetch(PDO::FETCH_ASSOC);
	 $latepasshrs = $line['latepasshrs'];
	 $msgset = $line['msgset']%5;

	 showcalendar("showcalendar");

	 require("../footer.php");



	 function makecolor2($stime,$etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   if ($etime==2000000000 && $now >= $stime) {
		   return '#0f0';
	   } else if ($stime==0) {
		   return makecolor($etime,$now);
	   }
	   if ($etime==$stime) {
		   return '#ccc';
	   }
	   $r = ($etime-$now)/($etime-$stime);  //0 = etime, 1=stime; 0:#f00, 1:#0f0, .5:#ff0
	   if ($etime<$now || $stime>$now) {
		   $color = '#ccc';
	   } else if ($r<.5) {
		   $color = '#f'.dechex(floor(32*$r)).'0';
	   } else if ($r<1) {
		   $color = '#'.dechex(floor(32*(1-$r))).'f0';
	   } else {
		   $color = '#0f0';
	   }
	    return $color;
	 }

	 function makecolor($etime,$now) {
	   if (!$GLOBALS['colorshift']) {
		   return "#ff0";
	   }
	   //$now = time();
	   if ($etime<$now) {
		   $color = "#ccc";
	   } else if ($etime-$now < 605800) {  //due within a week
		   $color = "#f".dechex(floor(16*($etime-$now)/605801))."0";
	   } else if ($etime-$now < 1211600) { //due within two weeks
		   $color = "#". dechex(floor(16*(1-($etime-$now-605800)/605801))) . "f0";
	   } else {
		   $color = "#0f0";
	   }
	   return $color;
   }
	 function formatdate($date) {
	return tzdate("D n/j/y, g:i a",$date);
		//return tzdate("M j, Y, g:i a",$date);
	   }
	function getpts($scs) {
		$tot = 0;
		foreach(explode(',',$scs) as $sc) {
			if (strpos($sc,'~')===false) {
				if ($sc>0) {
					$tot += $sc;
				}
			} else {
				$sc = explode('~',$sc);
				foreach ($sc as $s) {
					if ($s>0) {
						$tot+=$s;
					}
				}
			}
		}
		return $tot;
	   }

?>
