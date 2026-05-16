<?php
//IMathAS:  Display the calendar by itself
//(c) 2008 David Lippman
	require_once "../init.php";
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid) && !isset($instrPreviewId)) {
	   require_once "../header.php";
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require_once "../footer.php";
	   exit;
	}

	$cid = Sanitize::courseId($_GET['cid']);
	if (isset($_GET['editing']) && isset($teacherid)) {
		$editingon = $_GET['editing']=='on';
		$_SESSION[$cid.'caledit'] = $editingon;
	} else if (isset($_SESSION[$cid.'caledit']) && isset($teacherid)) {
		$editingon = $_SESSION[$cid.'caledit'];
	} else {
		$editingon = false;
	}
	
	if (isset($teacherid)) {
		$stm = $DBH->prepare("SELECT iu.id,iu.LastName,iu.FirstName,istu.section,istu.latepass FROM imas_users AS iu JOIN imas_students AS istu ON istu.userid=iu.id WHERE istu.courseid=:id ORDER BY iu.LastName,iu.FirstName");
		$stm->execute(array(':id'=>$cid));
		$stunames = [];
		$stulatepasses = [];
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$stunames[$row['id']] = $row['LastName'].', '.$row['FirstName'].($row['section'] != '' ? ' ('.$row['section'].')':'');
			$stulatepasses[$row['id']] = $row['latepass'];
		}
	}

	$viewcalasstu = false;
	if (!$editingon && isset($teacherid)) {
		if (isset($_GET['viewcalas'])) {
			if ($_GET['viewcalas'] == 0) {
				unset($_SESSION[$cid.'viewcalas']);
			} else {
				$userid = intval($_GET['viewcalas']);
				if (!isset($stunames[$userid])) {
					echo 'Error - invalid user';
					exit;
				}
				$viewcalasstu = true;
				$_SESSION[$cid.'viewcalas'] = $userid;
			}
		} else if (isset($_SESSION[$cid.'viewcalas'])) {
			$userid = $_SESSION[$cid.'viewcalas'];
			$viewcalasstu = true;
		}
		if ($viewcalasstu) {
			$studentid = $userid;
			unset($teacherid);
		}
	}

	require_once "../includes/exceptionfuncs.php";

	if ($viewcalasstu) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $stulatepasses[$userid], $latepasshrs);
	} else if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	require_once "../includes/calendardisp.php";

	if (isset($_GET['ajax'])) {
		$stm = $DBH->prepare("SELECT name,itemorder,allowunenroll,msgset,toolset,latepasshrs FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		$latepasshrs = $line['latepasshrs'];
		$msgset = $line['msgset']%5;

		showcalendar("showcalendar");
		exit;
	}
	$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/course.js?v=021326\"></script>";
	if ($editingon) {
		$loadiconfont = true;
	}

    $pagetitle = _('Calendar');
	require_once "../header.php";
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
	span.calitem[id^=AS],span.calitem[id^=IS],span.calitem[id^=LS],span.calitem[id^=DS],span.calitem[id^=FS],span.calitem[id^=BS] {
		border-radius: 10px 0 0 10px;
	}
	span.calitem[id^=AE],span.calitem[id^=IE],span.calitem[id^=LE],span.calitem[id^=DE],span.calitem[id^=FE],span.calitem[id^=BE] {
		border-radius: 0 10px 10px 0;
	}
	.drag-over {
		background-color: #eee;
	}
	</style>
  <script type="text/javascript">
	var dragState = null; // Tracks current drag operation
	var ghostEl = null;   // Visual drag ghost element
	function createGhost(sourceEl, x, y) {
		var ghost = sourceEl.cloneNode(true);
		var rect = sourceEl.getBoundingClientRect();
		ghost.style.cssText = 
			'position:fixed;' +
			'left:' + rect.left + 'px;' +
			'top:' + rect.top + 'px;' +
			'width:' + rect.width + 'px;' +
			'opacity:0.7;' +
			'pointer-events:none;' +
			'z-index:9999;' +
			'margin:0;';
		ghost.classList.add('dragging');
		document.body.appendChild(ghost);
		return ghost;
	}

	function getTdUnderPointer(x, y) {
		// Temporarily hide ghost so elementFromPoint can see through it
		if (ghostEl) ghostEl.style.display = 'none';
		var el = document.elementFromPoint(x, y);
		if (ghostEl) ghostEl.style.display = '';
		return el ? $(el).closest("table.cal td") : $();
	}
	function initcaldragreorder() {
		// Set titles
		$("span.calitem[id^=CD]").attr("title", _("Calendar Event Date"));
		$("span.calitem[id^=AS]").attr("title", _("Assessment Available After"));
		$("span.calitem[id^=AE]").attr("title", _("Assessment Due Date"));
		$("span.calitem[id^=AR]").attr("title", _("Assessment Review Date"));
		$("span.calitem[id^=IS]").attr("title", _("Inline Text Available After"));
		$("span.calitem[id^=IE]").attr("title", _("Inline Text Available Until"));
		$("span.calitem[id^=IO]").attr("title", _("Inline Text On Calendar Date"));
		$("span.calitem[id^=LS]").attr("title", _("Link Available After"));
		$("span.calitem[id^=LE]").attr("title", _("Link Available Until"));
		$("span.calitem[id^=LO]").attr("title", _("Link On Calendar Date"));
		$("span.calitem[id^=DS]").attr("title", _("Drill Available After"));
		$("span.calitem[id^=DE]").attr("title", _("Drill Available Until"));
		$("span.calitem[id^=FS]").attr("title", _("Forum Available After"));
		$("span.calitem[id^=FE]").attr("title", _("Forum Available Until"));
		$("span.calitem[id^=FP]").attr("title", _("Forum Post By"));
		$("span.calitem[id^=FR]").attr("title", _("Forum Reply By"));

		// Make calitems draggable with Pointer Events
		$("span.calitem").each(function() {
			this.style.touchAction = 'none'; // Prevent scroll hijacking
			this.style.userSelect = 'none';
			this.style.cursor = 'grab';

			this.addEventListener('pointerdown', function(e) {
				e.preventDefault();
				this.setPointerCapture(e.pointerId);

				var originalParent = $(this).closest("td").attr("id");
				$(this).data("originalParent", originalParent);

				dragState = {
					el: this,
					id: this.id,
					originalParent: originalParent,
					currentTd: null,
					offsetX: e.clientX - this.getBoundingClientRect().left,
					offsetY: e.clientY - this.getBoundingClientRect().top,
				};

				ghostEl = createGhost(this, e.clientX, e.clientY);
			});

			this.addEventListener('pointermove', function(e) {
				if (!dragState) return;
				e.preventDefault();

				// Move the ghost
				ghostEl.style.left = (e.clientX - dragState.offsetX) + 'px';
				ghostEl.style.top  = (e.clientY - dragState.offsetY) + 'px';

				// Highlight the td under the pointer
				var $td = getTdUnderPointer(e.clientX, e.clientY);
				if (dragState.currentTd && (!$td.length || $td[0] !== dragState.currentTd[0])) {
					dragState.currentTd.removeClass('drag-over');
				}
				if ($td.length) {
					$td.addClass('drag-over');
					dragState.currentTd = $td;
				} else {
					dragState.currentTd = null;
				}
			});

			this.addEventListener('pointerup', function(e) {
				if (!dragState) return;

				// Clean up ghost and highlighting
				if (ghostEl) { ghostEl.remove(); ghostEl = null; }
				if (dragState.currentTd) dragState.currentTd.removeClass('drag-over');

				var $td = getTdUnderPointer(e.clientX, e.clientY);
				if ($td.length) {
					handleDrop($td, dragState.el, dragState.id, dragState.originalParent);
				}

				dragState = null;
			});

			this.addEventListener('pointercancel', function(e) {
				if (!dragState) return;
				if (ghostEl) { ghostEl.remove(); ghostEl = null; }
				if (dragState.currentTd) dragState.currentTd.removeClass('drag-over');
				dragState = null;
			});
		});
		// add item highlight of item and bookend pair on hover
		$("span.calitem").on('pointerenter', function(ev) {
			var id = this.id.substr(2);
			$("span.calitem[id$='"+id+"']").addClass("calitemhighlight");
		}).on('pointerleave', function(ev) {
			var id = this.id.substr(2);
			$("span.calitem[id$='"+id+"']").removeClass("calitemhighlight");
		});
	}
	function handleDrop(droppedOn, droppedEl, draggedId, originalParent) {
		var dropped = $(droppedEl);

		// Move the element
		dropped.detach().appendTo(droppedOn.find("div.center"));

		// Unhighlight it
		var id = draggedId.substr(2);
		$("span.calitem[id$='"+id+"']").removeClass("calitemhighlight");

		// Check if actually moved to a different cell
		if (droppedOn.attr("id") != originalParent) {
			$(".calupdatenotice").html('<img src="<?php echo $staticroot;?>/img/updating.gif" alt="Saving"/> ' + _("Saving..."));

			$.ajax({
				"url": "savecalendardrag.php",
				data: {
					cid: <?php echo $cid;?>,
					item: draggedId,
					dest: droppedOn.attr("id")
				}
			}).done(function(msg) {
				if (msg.res == "error") {
					console.log("ERROR: " + msg.error);
					$(".calupdatenotice").html(_("Error saving change"));
					dropped.detach().appendTo($("#" + originalParent).find("div.center"));
				} else {
					$(".calupdatenotice").html("");
					var daycaldata = caleventsarr[originalParent].data;
					for (var i = 0; i < daycaldata.length; i++) {
						if (daycaldata[i].type + daycaldata[i].typeref == draggedId) {
							var thisrec = daycaldata.splice(i, 1);
							if (caleventsarr[droppedOn.attr("id")].hasOwnProperty("data")) {
								caleventsarr[droppedOn.attr("id")].data.push(thisrec[0]);
							} else {
								caleventsarr[droppedOn.attr("id")].data = thisrec;
							}
							if ($("table.cal td.today").length > 0) {
								showcalcontents($("table.cal td.today")[0]);
							}
							break;
						}
					}
				}
			}).fail(function() {
				dropped.detach().appendTo($("#" + originalParent).find("div.center"));
			});
		}
	}

	$(function() {
		initcaldragreorder();
	});
	</script>
	<?php
	} //end $editingon block
	echo '<script>function changeviewcalas(el) {
		window.location.href = "showcalendar.php?cid='.$cid.'&viewcalas="+el.value;
		}</script>';
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; Calendar</div>";
	echo '<div id="headercalendar" class="pagetitle"><h1>Calendar</h1></div>';

	if (isset($teacherid) || $viewcalasstu) {
		echo "<div class=\"cpmid\">";
	}
	if (isset($teacherid)) {
		echo "<a id=\"mcelink\" href=\"managecalitems.php?from=cal&cid=$cid\">Manage Events</a> | ";
		if ($editingon) {
			echo '<a href="showcalendar.php?cid='.$cid.'&editing=off">'._('Disable Drag-and-drop Editing').'</a> ';
		} else {
			echo '<a href="showcalendar.php?cid='.$cid.'&editing=on">'._('Enable Drag-and-drop Editing').'</a> ';
		}
		//echo '<a href="exportcalfeed.php?cid='.$cid.'">'._('Export Calendar Feed').'</a>';
		echo '<br>';
	 }
	 if ((isset($teacherid) || $viewcalasstu)) {
		if (!$editingon) {
			echo '<label>'._('View calendar as:').' <select onchange="changeviewcalas(this)">';
			echo '<option value=0'.(!$viewcalasstu ? ' selected':'').'>'._('Instructor').'</option>';
			foreach ($stunames as $id=>$name) {
				echo '<option value='.$id.($userid == $id ? ' selected':'').'>'.Sanitize::encodeStringForDisplay($name).'</option>';
			}
			echo '</select>';
		}
		echo "</div>";
	 }
	 if ($editingon) {
		 echo '<p>'._('Drag-and-drop events to change dates. Note that time of day is not changed - use Mass Change Dates for that.').'</p>';
		 echo '<p>'.sprintf(_('Drag-and-drop is not keyboard accessible. Use <%s>Mass Change Dates</a> instead.'),'a href="masschgdates.php?cid='.$cid.'"').'</p>';
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

	 $stm = $DBH->prepare("SELECT name,itemorder,allowunenroll,msgset,toolset,latepasshrs FROM imas_courses WHERE id=:id");
	 $stm->execute(array(':id'=>$cid));
	 $line = $stm->fetch(PDO::FETCH_ASSOC);
	 $latepasshrs = $line['latepasshrs'];
	 $msgset = $line['msgset']%5;

	 showcalendar("showcalendar");

	 if ($viewcalasstu) {
		echo '<p></p><p>'._('Note: The due dates shown here are the due dates <em>for the student you are viewing as</em>, reflecting any LatePasses or exceptions that have been applied.').'</p>';		
	 } else if (isset($studentid)) {
		echo '<p></p><p>'._('Note: The due dates shown here are <em>your</em> due dates, reflecting any LatePasses or exceptions that have been applied.').'</p>';
	 }

	 require_once "../footer.php";



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
