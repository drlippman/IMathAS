function updatetocopy(el) {
	if (el.value=="all") {
		$("#selectitemstocopy").hide();$("#allitemsnote").show();
		$("#copyoptions").show();
		$("#copyoptions .selectonly").hide();
		$("#copyoptions .allon input[type=checkbox]").prop("checked",true);
	} else {
		$("#selectitemstocopy").show();$("#allitemsnote").hide();
		$("#copyoptions").show();
		$("#copyoptions .selectonly").show();
		$("#copyoptions .allon input[type=checkbox]").prop("checked",false);
	}
}
function copyitemsonsubmit() {
	if (!document.getElementById("whattocopy1").checked && !document.getElementById("whattocopy2").checked) {
		alert(_("Select an option for what to copy"));
		return false;
	} else {
		return true;
	}
}
$(function() {
	$("input:radio").change(function() {
		if ($(this).attr("id")!="coursebrowserctc") {
			$("#coursebrowserout").hide();
		}
		if ($(this).hasClass("copyr")) {
			$("#ekeybox").show();
		} else {
			$("#ekeybox").hide();
		}
		if ($(this).hasClass("termsurl")) {
			$("#termsbox").show();
			$("#termsurl").attr("href",$(this).data("termsurl"));
		} else {
			$("#termsbox").hide();
		}
		$("#continuebutton").show().prop("disabled",false);
	});
	$("#cidlookup").on('keydown', function(e) {
		if (e.which == 13) {
			e.preventDefault();
			lookupcid();
		}
	});
});
function showCourseBrowser() {
	$("#copyoptions").slideUp();
	GB_show("Course Browser",imasroot+"/admin/coursebrowser.php?embedded=true",800,"auto");
}
function setCourse(course) {
	$("#coursebrowserctc").val(course.id).prop("checked",true);
	$("#templatename").text(course.name);
	$("#coursebrowserout").show();
	if (course.termsurl && course.termsurl != "") {
		$("#termsbox").show(); $("#termsurl").attr("href",course.termsurl);
		$("#continuebutton").show().prop("disabled",false);
	} else {
		$("#termsbox").hide();
		$("form").submit();
	}
	GB_hide();
}
function lookupcid() {
	$("#cidlookuperr").text("");
	var cidtolookup = $("#cidlookup").val();
	$.ajax({
		type: "POST",
		url: imasroot+"/includes/coursecopylist.php?cid="+cid,
		data: { cidlookup: cidtolookup},
		dataType: "json"
	}).done(function(res) {
		if ($.isEmptyObject(res)) {
			$("#cidlookuperr").text("Course ID not found");
			$("#cidlookupout").hide();
		} else {
			$("#cidlookupctc").val(res.id);
			if (res.needkey) {
				res.name += " &copy;";
			} else {
				res.name +=  " <a href=\""+imasroot+"/course/course.php?cid="+res.id+"\" target=\"_blank\" class=\"small\">Preview</a>";
			}
			$("#cidlookupname").html(res.name);
			if (res.termsurl != "") {
				$("#cidlookupctc").addClass("termsurl");
				$("#cidlookupctc").attr("data-termsurl",res.termsurl);
			} else {
				$("#cidlookupctc").removeClass("termsurl");
				$("#cidlookupctc").removeAttr("data-termsurl");
			}
			if (res.needkey) {
				$("#cidlookupctc").addClass("copyr");
			} else {
				$("#cidlookupctc").removeClass("copyr");
			}
			$("#cidlookupctc").prop("checked",true).trigger("change");
			$("#cidlookupout").show();
		}
	}).fail(function() {
		$("#cidlookuperr").text("Lookup error");
		$("#cidlookupout").hide();
	});
}
var othersloaded = false;
var othergroupsloaded = [];
function loadothers() {
	if (!othersloaded) {
		//basicahah(ahahurl, "other");
		$.ajax({
			url: imasroot+"/includes/coursecopylist.php?cid="+cid+"&loadothers=true", 
			dataType: "html"}
		).done(function(resp) {
			$('#other').html(resp);
		});
		othersloaded = true;
	}
}
function loadothergroup(n) {
	toggle("g"+n);
	if (othergroupsloaded.indexOf(n) === -1) {
		$.ajax({
			url:imasroot+"/includes/coursecopylist.php?cid="+cid+"&loadothergroup="+n, 
			dataType:"html"}
		).done(function(resp) {
			$('#g'+n).html(resp);
			$("#g"+n+" input:radio").change(function() {
				if ($(this).hasClass("copyr")) {
					$("#ekeybox").show();
				} else {
					$("#ekeybox").hide();
				}
				if ($(this).hasClass("termsurl")) {
					$("#termsbox").show();
					$("#termsurl").attr("href",$(this).data("termsurl"));
				} else {
					$("#termsbox").hide();
				}
				$("#continuebutton").show().prop("disabled",false);
			});
		});
		othergroupsloaded.push(n);
	}
}
function showCopyOpts() {
	$("#copyoptions").slideToggle();	
}