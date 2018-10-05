<?php
//Edit message snippets
//(c) 2017 David Lippman, IMathAS

require("../init.php");

if ($myrights<=10) {
	exit;
}

$snippets = array();

if (isset($_POST['snippets'])) {
	require_once("../includes/htmLawed.php");

	$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));

	$jsondata = json_decode($stm->fetchColumn(0), true);
	if ($jsondata===null) {
		$jsondata = array();
	}
	$newsnippets = json_decode($_POST['snippets'], true);
	$safesnips = array();
	foreach ($newsnippets as $g=>$gv) {
		if (!isset($gv['items'])) {
			continue; //skip group with no items;
		}
		$newitems = array();
		foreach ($gv['items'] as $k=>$sv) {
			if (empty($sv['text']) || empty($sv['content'])) {
				continue;
			}
			$newitem = array();
			$newitem['text'] = Sanitize::stripHtmlTags($sv['text']);
			$newitem['content'] = myhtmlawed($sv['content']);
			$newitems[] = $newitem;
		}
		if (count($newitems)>0) {
			$grptitle = Sanitize::stripHtmlTags($gv['text']);
			if ($grptitle=='') {
				$grptitle = "Group";
			}
			$safesnips[] = array('text'=>$grptitle, 'items'=>$newitems);
		}
	}
	$jsondata['snippets'] = $safesnips;
	$stm = $DBH->prepare("UPDATE imas_users SET jsondata=:jsondata WHERE id=:id");
	$stm->execute(array(':jsondata'=>json_encode($jsondata), ':id'=>$userid));

	echo _("Snippets saved");

} else {
	$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userid));

	$jsondata = json_decode($stm->fetchColumn(0), true);
	if ($jsondata===null) {
		$jsondata = array();
	}
	if (isset($jsondata['snippets'])) {
		$snippets = $jsondata['snippets'];
	} else {
		$snippets = array();
	}

	$useeditor = "noinit";
	$placeinhead = '<script src="../javascript/jquery-sortable.js"></script>';
	$placeinhead .= '<script type="text/javascript">
	var pageIsDirty = false;
	$(function() {
		initeditor("selector","div.snipcont",0,1,editorSetup);
		$(".grouplist").sortable({
			isValidTarget: function($item, container) {
				return $item.parent("ul").attr("class")==$(container.el[0]).attr("class");
			},
			handle: ".icon"
		});
		/*
		$(window).on("beforeunload",function(){
			if (pageIsDirty) {
				return "There are unsaved changes. Press Stay on Page to return to the page without taking any action.";
			}
		});
		*/
	});
	var newsnipcnt = 0;
	function addsnip(event) {
		$("<li>", {class: "snipwrap"})
		.html("<span class=icon style=\"background-color:#0f0\">S</span> '._('Snippet Title').': ")
		.append(
		  $("<input>", {
			type: "text",
			class: "sniptitle",
			oninput: markDirty
			}).attr("size",50),
		  $("<button>", {
		  	type: "button",
		  	text: "'._('Delete').'"
		  }).on("click", deletesnip),
		  $("<div>", {
			class: "snipcont",
			id: "newsnip"+newsnipcnt
		  }).html("<p></p>")
		)
		.appendTo($(event.target).parent().find(".snipgroup"));
		markDirty();
		newsnipcnt++;
		initeditor("selector","div.snipcont",0,1,editorSetup);
	}
	function addgrp() {
		$("<li>", {class:"grpwrap"}).html("<span class=icon style=\"background-color:#66f\">G</span> '._('Group Title').': ")
		.append(
		  $("<input>", {
			type: "text",
			class: "grptitle",
			oninput: markDirty
			}).attr("size",50),
		  $("<button>", {
		  	type: "button"
		  	}).text("'._('Add Snippet').'")
		  	.on("click", addsnip),
		  $("<button>", {
		  	type: "button"
		  	}).text("-")
		  	.on("click", togglegrp),
		  $("<ul>", {
		  	class: "snipgroup"
		  })
		 ).appendTo($(".grouplist"));
		 markDirty();
	}
	function togglegrp(event) {
		event.preventDefault();
		var $t = $(event.target);
		if ($t.text()=="-") {
			$t.text("+");
		} else {
			$t.text("-");
		}
		$t.siblings(".snipgroup").slideToggle();
		return false;
	}
	function deletesnip(event) {
		if (confirm("'._('Are you SURE you want to delete this snippet?').'")) {
			$(event.target).closest(".snipwrap").slideUp("normal", function() { $(this).remove(); });
			markDirty();
		}
	}
	function savesnippets() {
		$(".submitnotice").text(_(" Saving Changes... "));
		var snippetdata = [];
		$(".grpwrap").each(function(i,el) {
			var groupitems = [];
			$(el).find(".snipwrap").each(function(j,subel) {
				var edid = $(subel).find(".snipcont").attr("id");
				groupitems.push({"text": $(subel).find(".sniptitle").val(),
					"content": tinyMCE.get(edid).getContent()})
			});
			snippetdata.push({"text": $(el).find(".grptitle").val(), "items":groupitems});
		});
		$.ajax({
			type: "POST",
			url: "editsnippets.php",
			data: {snippets: JSON.stringify(snippetdata)}
		})
		.done(function(msg) {
			$(".submitnotice").text(msg);
			pageIsDirty = false;
		})
		.fail(function(xhr, status, errorThrown) {
		    	$(".submitnotice").text("Error saving changes: "+
				status + "; Error: "+errorThrown);
		});
	}
	function editorSetup(editor) {
		editor.on("dirty", markDirty);
	}
	function markDirty() {
		if (pageIsDirty==false) {
			$(".submitnotice").text("");
			pageIsDirty = true;
		}
	}
	</script>
	<style type="text/css">

		.snipcont {
			margin-left: 20px;
			border: 1px solid #ccc;
			padding: 5px;
			max-width: 700px;
		}
		body.dragging, body.dragging * {
			cursor: move !important;
		}
		.dragged {
		  position: absolute;
		  opacity: 0.5;
		  z-index: 2000;
		}

		li.placeholder {
		  position: relative;
		  background-color: #cfc;
		  height: 2em;
		}
		li {
		  list-style-type: none;
		  padding: 5px;
		}
		.icon {cursor: pointer;}
		body.dragging .icon {
			cursor: move;
		}
		ul.grouplist {
			padding-left: 0px;
		}
	</style>';
	require("../header.php");


	echo '<div class=breadcrumb>'.$breadcrumbbase.' '._("Prewritten Snippets").'</div>';

	echo "<div id=\"headercourse\" class=\"pagetitle\"><h1>"._("Prewritten Snippets")."</h1></div>\n";

	echo '<p><button type="button" class="savebtn" onclick="savesnippets()">'._('Save Changes').'</button> ';
	echo '<span class="submitnotice noticetext"></span></p>';
	echo '<ul class="grouplist">';
	$snipcnt = 0;
	foreach ($snippets as $snipgroup) {
		echo '<li class="grpwrap"><span class=icon style="background-color:#66f">G</span> ';
		echo _('Group Title'),': ';
		echo '<input type="text" class="grptitle" oninput="markDirty()" ';
		echo 'value="'.Sanitize::encodeStringForDisplay($snipgroup['text']).'" size=50 /> ';
		echo '<button type="button" onclick="addsnip(event)">'._('Add Snippet').'</button>';
		echo '<button type="button" onclick="togglegrp(event)">-</button>';
		echo '<ul class="snipgroup">';
		foreach ($snipgroup['items'] as $snip) {
			echo '<li class="snipwrap"><span class=icon style="background-color:#0f0">S</span> ';
			echo _('Snippet Title').': ';
			echo '<input type="text" class="sniptitle" oninput="markDirty()" ';
			echo 'value="'.Sanitize::encodeStringForDisplay($snip['text']).'" size=50 /> ';
			echo '<button type="button" onclick="deletesnip(event)">'._('Delete').'</button>';
			echo '<div id="snip'.$snipcnt.'" class="snipcont skipmathrender">';
			//presanitized HTML
			echo $snip['content'].'</div></li>';
			$snipcnt++;
		}

		echo '</ul></li>';
	}
	echo '</ul>';
	echo '<p><button type="button" onclick="addgrp()">'._('Add Group').'</button> ';
	echo '<button type="button" class="savebtn" onclick="savesnippets()">'._('Save Changes').'</button> <span class="submitnotice noticetext"></span></p>';

	require("../footer.php");
}
