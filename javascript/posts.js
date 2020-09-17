var haschanges = false;
$(function() {
	$(".scorebox").on('keypress', function() {haschanges = true;});
});
function checkchgstatus(type,id) {
	//type: 0 reply, 1 modify
	if (haschanges) {
		if (type==0) {
			action = 'reply';
		} else if (type==1) {
			action = 'modify';
		}
		if (confirm("You have unsaved changes. Click OK to save changes before continuing, or Cancel to discard changes")) {
			$("form").append('<input type="hidden" name="actionrequest" value="'+action+':'+id+'"/>').submit();
			return false;
		} else {
			return true;
		}
	}
}
function toggleshow(butn) {
	var forumgrp = $(butn).closest(".block").nextAll(".forumgrp").first();
	if (forumgrp.hasClass("hidden")) {
		forumgrp.removeClass("hidden");
		butn.src = staticroot+'/img/collapse.gif';
	} else {
		forumgrp.addClass("hidden");
		butn.src = staticroot+'/img/expand.gif';
	}
}
function toggleitem(butn) {
	var blockitems = $(butn).closest(".block").nextAll(".blockitems").first();
	if (blockitems.hasClass("hidden")) {
		blockitems.removeClass("hidden");
		butn.value = _('Hide');
	} else {
		blockitems.addClass("hidden");
		butn.value = _('Show');
	}
}
function expandall() {
	$(".expcol").each(function(i) {
		var forumgrp = $(this).closest(".block").nextAll(".forumgrp").first().removeClass("hidden");
		this.src = staticroot+'/img/collapse.gif';
	});
}
function collapseall() {
	$(".expcol").each(function(i) {
		var forumgrp = $(this).closest(".block").nextAll(".forumgrp").first().addClass("hidden");
		this.src = staticroot+'/img/expand.gif';
	});
}
function showall() {
	$(".shbtn").each(function(i) {
		var blockitems = $(this).closest(".block").nextAll(".blockitems").first().removeClass("hidden");
		this.value = _('Hide');
	});
}
function hideall() {
	$(".shbtn").each(function(i) {
		var blockitems = $(this).closest(".block").nextAll(".blockitems").first().addClass("hidden");
		this.value = _('Show');
	});
}

function savelike(el) {
	var like = (el.src.match(/gray/))?1:0;
	var postid = el.id.substring(8);
	$(el).parent().append('<img style="vertical-align: middle" src="'+staticroot+'/img/updating.gif" id="updating" alt="Updating"/>');
	$.ajax({
		url: "recordlikes.php",
		data: {cid: cid, postid: postid, like: like},
		dataType: "json"
	}).done(function(msg) {
		if (msg.aff==1) {
			el.title = msg.msg;
			$('#likecnt'+postid).text(msg.cnt>0?msg.cnt:'');
			el.className = "likeicon"+msg.classn;
			if (like==0) {
				el.src = el.src.replace("liked","likedgray");
			} else {
				el.src = el.src.replace("likedgray","liked");
			}
		}
		$('#updating').remove();
	});
}
function toggletagged(id) {
	var cursrc = $("#tag"+id).attr("src");
	var settag = cursrc.match(/filled/)?0:1;
	$.ajax({url: "savetagged.php?cid="+cid+"&threadid="+id+"&tagged="+settag})
	   .done(function(msg) { if (msg=="OK") {
	   	if (settag==0) { $("#tag"+id).attr("src",cursrc.replace(/filled/,"empty"));}
	   	else {$("#tag"+id).attr("src",cursrc.replace(/empty/,"filled"));}
	   }});
}
