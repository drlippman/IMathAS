var controlEditor;
var qEditor = [];

function toggleeditor(el) {
	var qtextbox = document.getElementById(el);
	if ((el == "qtext" && editoron == 0) || (el == "solution" && seditoron == 0)) {
		if (typeof qEditor[el] != "undefined") {
			qEditor[el].toTextArea();
		}
		qtextbox.rows += 3;
		qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\/span >/g,"$1");
		qtextbox.value = qtextbox.value.replace(/`(.*?)`/g, function (match, p1, offset, string) {
			let before = string.substring(0, offset);
			let lastOpenTag = before.lastIndexOf("<");
			let lastCloseTag = before.lastIndexOf(">");
			if (lastOpenTag > lastCloseTag) {
				return match; // in tag; do not replace
			} else {
				return "<span class=\"AM\" title=\"" + p1 + "\">`" + p1 + "`</span>";
			}
		});
		qtextbox.value = qtextbox.value.replace(/\n\n/g, "<br/><br/>");

		initeditor("exact", el);
		if (el == "qtext") {
			editoron = 1;
		} else if (el == "solution") {
			seditoron = 1;
		}
	} else if ((el == "qtext" && editoron == 1) || (el == "solution" && seditoron == 1)) {
		tinymce.remove("#" + el);
		qtextbox.rows -= 3;
		qtextbox.value = qtextbox.value.replace(/<span\s+class="AM"[^>]*>(.*?)<\/span > /g,"$1").replace(/ &#96;/g,"`");
		setupQtextEditor(el);
		if (el == "qtext") {
			editoron = 0;
		} else if (el == "solution") {
			seditoron = 0;
		}
	}
}
function initsolneditor() {
	/*
	if (document.cookie.match(/seditoron=1/)) {
		  var val = document.getElementById("solution").value;
		  if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("solution");}
		  else {setupQtextEditor("solution");}
	}else {setupQtextEditor("solution");}
	*/
}

addLoadEvent(function () { setupQtextEditor("qtext"); setupQtextEditor("solution"); });
$(function () {
	$("#mainform").on("submit", function (e) {
		if (!saveEditors()) {
			e.preventDefault();
		}
		return true;
	});
});
/*
if (document.cookie.match(/qeditoron=1/)) {
	   var val = document.getElementById("qtext").value;
	   if (val.length<3 || val.match(/<.*?>/)) {toggleeditor("qtext");}
	   else {setupQtextEditor("qtext");}
}else {setupQtextEditor("qtext");}});
*/

function setupQtextEditor(id) {
	var qtextbox = document.getElementById(id);
	if (!qtextbox) { return; }
	qtextbox.value = qtextbox.value.replace(/\s*((<br\s*\/>\s*){1,}<br\s*\/>)\s*/g, "\n$1\n");
	qEditor[id] = CodeMirror.fromTextArea(qtextbox, {
		matchTags: true,
		mode: "imathasqtext",
		smartIndent: true,
		lineWrapping: true,
		indentUnit: 2,
		tabSize: 2,
		viewportMargin: 500,
		readOnly: !canedit,
		styleSelectedText: true
	});
	for (var i = 0; i < qEditor[id].lineCount(); i++) { qEditor[id].indentLine(i); }
}

$(function () {
	controlEditor = CodeMirror.fromTextArea(document.getElementById("control"), {
		lineNumbers: true,
		matchBrackets: true,
		autoCloseBrackets: true,
		mode: "text/x-imathas",
		smartIndent: true,
		lineWrapping: true,
		indentUnit: 2,
		tabSize: 2,
		viewportMargin: 500,
		readOnly: !canedit,
		styleSelectedText: true
	});
	//controlEditor.setSize("100%",6+14*document.getElementById("control").rows);
});


function checklicense() {
	var lic = $("#license").val();
	var warn = "";
	if (originallicense > -1) {
		if (originallicense == 0 && lic != 0) {
			warn = _('If the original question contained copyrighted material, you should not change the license unless you have removed all the copyrighted material');
		} else if ((originallicense == 1 || originallicense == 3 || originallicense == 4) && lic != originallicense) {
			warn = _('The original license REQUIRES that all derivative versions be kept under the same license. You should only be changing the license if you are the creator of this questions and all questions it was derived from');
		}
	}
	$("#licensewarn").html("<br/>" + warn);
}
function changea11ytype() {
	let val = document.getElementById("a11yalttype").value;
	if (val > 0) {
		$("#a11yaltwarn").show();
	} else {
		$("#a11yaltwarn").hide();
	}
}
function incctrlboxsize() {
	$("#ccbox").find(".CodeMirror-scroll").css("min-height", 0).css("max-height", "none");
	controlEditor.setSize("100%", $(controlEditor.getWrapperElement()).height() + 28);
}
function decctrlboxsize() {
	$("#ccbox").find(".CodeMirror-scroll").css("min-height", 0).css("max-height", "none");
	controlEditor.setSize("100%", $(controlEditor.getWrapperElement()).height() - 28);
}
function incqtboxsize(id) {
	if (!editoron) {
		$("#" + id).parent().find(".CodeMirror-scroll").css("min-height", 0).css("max-height", "none");
		qEditor[id].setSize("100%", $(qEditor[id].getWrapperElement()).height() + 28);
		document.getElementById(id).rows += 2;
	}
}
function decqtboxsize(id) {
	if (!editoron) {
		$("#" + id).parent().find(".CodeMirror-scroll").css("min-height", 0).css("max-height", "none");
		qEditor[id].setSize("100%", $(qEditor[id].getWrapperElement()).height() - 28);
		document.getElementById(id).rows -= 2;
	}
}
$(function () {
	$("#qtypedd a[data-sn]").on("click", function (e) {
		$("#qtypedd dd-active").removeClass("dd-active");
		selectqtype(this.getAttribute("data-sn"));
		$("#qtype").val(this.getAttribute("data-sn"));
	});
});
function selectqtype(sn) {
	// close all second-levels
	$(".dropdown-submenu").removeClass("open");
	$("#qtypedd a").removeClass("dd-active").attr("aria-expanded", false);
	var selel = $("#qtypedd a[data-sn=" + sn + "]");
	selel.addClass("dd-active");
	selel.closest(".dropdown-submenu").addClass("open").children("a").addClass("dd-active").attr("aria-expanded", true);
	var longname = selel.text();
	if (selel.attr("data-ln")) {
		longname = selel.attr("data-ln");
	}
	$("#qtypedd > button.dropdown-toggle").html(longname + " <span class=\'arrow-down\'></span>");
}
$(function () { selectqtype($("#qtype").val()); });

function libselect() {
	GB_show(_('Library Select'),'libtree3.php?cid='+cid+'&libtree=popup&selectrights=1&libs='+curlibs+'&locklibs='+locklibs,500,500);
}
function setlib(libs) {
	if (libs.charAt(0)=='0' && libs.indexOf(',')>-1) {
		libs = libs.substring(2);
	}
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	if (libn.indexOf('Unassigned')>-1 && libn.indexOf(',')>-1) {
		libn = libn.substring(11);
	}
	document.getElementById("libnames").textContent = libn;
	$("#libonlysubmit").show();
}
function swapentrymode() {
	var butn = document.getElementById("entrymode");
	if (butn.value=="2-box entry") {
		document.getElementById("qcbox").style.display = "none";
		document.getElementById("abox").style.display = "none";
		document.getElementById("control").rows = 20;
		butn.value = "4-box entry";
	} else {
		document.getElementById("qcbox").style.display = "block";
		document.getElementById("abox").style.display = "block";
		document.getElementById("control").rows = 10;
		butn.value = "2-box entry";
	}
}
function incboxsize(box) {
	document.getElementById(box).rows += 2;
}
function decboxsize(box) {
	if (document.getElementById(box).rows > 2)
		document.getElementById(box).rows -= 2;
}

$("input[name=imgfile]").on("change", function(event) {
	var maxsize = $("input[name=MAX_FILE_SIZE]").val();
	if (this.files && this.files[0] && this.files[0].size>maxsize) {
		alert("Your image is too large. Size cannot exceed "+maxsize+" btyes");
		$(this).val("");
	}
});

function saveEditors() {
	try {
		if (controlEditor) controlEditor.save();
		if (window.tinymce) {
			window.tinymce.triggerSave();
			if (editoron) {
				tinymce.get("qtext").save();
			} else {
				qEditor["qtext"].save();
			}
			if (seditoron) {
				tinymce.get("solution").save();
			} else {
				qEditor["solution"].save();
			}
		} else {
			for (i in qEditor) { qEditor[i].save(); }
		}
		return true;
	} catch (err){
		quickSaveQuestion.errorFunc();
		return false;
		
	}
}
if (FormData){ // Only allow quicksave if FormData object exists
	$(function () {
		// use quicksave and exit instead of regular form submission
		$("button[type=submit]").attr("type","button").on('click', function() {
			quickSaveQuestion(true);
		});
	});
	var quickSaveQuestion = function(exit){
		// Add text to notice areas
		$(".quickSaveNotice").html("Saving...");

		// Save codemirror and tinymce data
		if (!saveEditors()) {
			return;
		}

		// Get form data
		var data = new FormData($("form")[0]);
		var cleara11yreviews = ($("[name=cleara11yreviews]").prop("checked") === true);

		$.ajax({
			url: quickSaveQuestion.url + "&quick=1",
			type: 'POST',
			data: data,
			contentType: false,
			processData: false,
			success: function(res){
				// Parse out response string
				var res = JSON.parse(res);
				var formAction = res.formAction;
				if (exit && formAction.indexOf("frompot=1")===-1) {
					window.location.href = $(".breadcrumb a").last().attr("href");
					return;
				}
				var images = res.images;
				// Change form action url and testing address
				if (formAction.indexOf("moddataset.php") > -1) {
					quickSaveQuestion.url = formAction;
					quickSaveQuestion.testAddr = basetestaddr + res.id
				} else {
					quickSaveQuestion.errorFunc();
				}
				// Change form action and url in address bar
				$("form")[0].action = quickSaveQuestion.url;
				if (window.history.replaceState) window.history.replaceState({}, "qs", quickSaveQuestion.url);
				// Change outputmsg and errmsg
				$("#outputmsgContainer").html(res.outputmsg).toggleClass("cpmid", res.outputmsg !== '');
				$("#errmsgContainer").html(res.errmsg).toggleClass("cpmid", res.errmsg !== '');
				$("#a11yerrContainer").html(res.a11yerr).toggleClass("cpmid", res.a11yerr !== '');
				if (exit) {
					$("#mainform").hide();
					$("#outputmsgContainer")[0].scrollIntoView();
					return;
				}
				// HANDLE IMAGES
				var imgUploaded = $("input[name='imgfile']")[0].files.length > 0 ? true : false; // Image uploaded
				var imgDeleted = $("input[name^='delimg-']:checked").length > 0 ? true : false; // Image deleted
				if (Object.keys(images.vars).length>0 || imgUploaded || imgDeleted) {
					// Clear image inputs
					var imgFile = $("input[name='imgfile']");
					imgFile.replaceWith( imgFile = imgFile.val('').clone(true));
					$("input[name='newimgvar'], input[name='newimgalt']").val('');

					// Update image list
					$("#imgList").empty();
					var imgCount = 0;
					for (id in images.vars){
						imgCount++;
						$("#imgList").append(
							"<li><label>Variable: <input type='text' name='imgvar-" + id + "' value='$" + images.vars[id] + "' size='10' /></label>" +
							" <a href='" + res.imgUrlBase + images.files[id] + "' target='_blank'>View</a>" +
							" <label>Description: <textarea rows=1 cols=30 name='imgalt-" + id + "'>" + images.alttext[id] + "</textarea></label>" +
							" <label><input type='checkbox' name='delimg-" + id + "'/> Delete?</label>" +
							"</li>"
						);
					}
				} else { // No uploads/deletes: still count number of images
					var imgCount = 0;
					for (i in images.vars) imgCount++;
				}
				// Hide image list if no images in question
				$("#imgListContainer").css("display", imgCount > 0 ? "block" : "none");

				//handle extref help buttons
				if (res.extref.length>0) {
					$("#helpbtnlist").html('');
					for (var i=0;i<res.extref.length;i++) {
						$("#helpbtnlist").append("<li>Type: "+res.extref[i][0] +
                            ", URL: <a href='"+res.extref[i][1]+"'>"+res.extref[i][1]+"</a>. " +
                            ((res.extref[i][2]) ? (_("Description")+": "+res.extref[i][2]+". "):"") +
							"<label><input type=\"checkbox\" name=\"delhelp-"+i+"\"/>" + _("Delete?") + "</label></li>");
					}
					$("#helpbtnwrap").removeClass("hidden");
				} else {
					$("#helpbtnwrap").addClass("hidden");
				}
				$("input[name=helpurl],input[name=helpdescr]").val('');

				// Empty notices
				$(".quickSaveNotice").empty();
				if (cleara11yreviews) {
					$(".a11ynegrev").hide();
				}
				// Load preview page
				let leftpos = screen.left ?? screen.availLeft ?? 0;
    			let toppos = screen.top ?? screen.availTop ?? 0;

				var previewpop = window.open(quickSaveQuestion.testAddr, 'Testing', 'width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top='+(20+toppos)+',left='+(.6*screen.width-20+leftpos));
				previewpop.focus();
			},
			error: function(res){
				quickSaveQuestion.errorFunc();
			}
		});
	}
	quickSaveQuestion.url = initFormAction;
	quickSaveQuestion.testAddr = inittestaddr;
	// Method to handle errors...
	quickSaveQuestion.errorFunc = function(){
		$(".quickSaveNotice").html("Error with Quick Save: try again, or use the \"Save\" option.");
	}
	// Key-binding method
	quickSaveQuestion.keyBind = function(e){
		var key = e.which || e.keyCode;
		if (key == 83 && e.ctrlKey == true){
			e.preventDefault();
			e.stopPropagation();
			quickSaveQuestion();
			return false;
		}
	}
	// Bind key event
	$(document).on("keydown", quickSaveQuestion.keyBind);
	// A little trickier for tinymce due to race conditions
	var mceTry = setInterval(function(){
		try {
			tinymce.get('qtext').on('keydown', quickSaveQuestion.keyBind);
			clearInterval(mceTry);
		} catch (e) {}
	}, 1000);

	// Show Quick Save and Preview buttons
	$(function() {
		$(".quickSaveButton").css("display", "inline");
		$(".saveandtest").remove();
	});
} else { // No FormData object
	$(function() {
		$(".quickSaveButton, .quickSaveNotice").remove();
	});
}