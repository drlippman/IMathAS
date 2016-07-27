//IMathAS: Utility JS for reordering addquestions existing questions
//(c) 2007 IMathAS/WAMAP Project
//Must be predefined:
//beentaken, defpoints
//itemarray: array
//	item: array ( questionid, questionsetid, description, type, points, canedit ,withdrawn )
//	group: array (pick n, without (0) or with (1) replacement, array of items)

//output submitted via AHAH is new assessment itemorder in form:
// item,item,n|w/wo~item~item,item

function refreshTable() {
	document.getElementById("curqtbl").innerHTML = generateTable();
	 if (usingASCIIMath) {
	      rendermathnode(document.getElementById("curqtbl"));
         }
         updateqgrpcookie();
	initeditor("selector",".textsegment",null,true /*inline*/,editorSetup);
	activateLastEditorIfBlank();
}

//Show the editor toolbar on a newly created text segment
function activateLastEditorIfBlank() {
	last_editor = tinymce.editors[tinymce.editors.length-1];
	if (last_editor.getContent()=="") {
		tinyMCE.setActive(last_editor);
		last_editor.fire("focus");
		last_editor.selection.setCursorLocation();
	}
}

//this is called by tinycme during initialization
function editorSetup(editor) {
	editor.on("blur", function() {
		var i=this.id.match(/[0-9]+$/)[0];
		savetextseg(i);
	});
	editor.on("focus", function() {
		var i=this.id.match(/[0-9]+$/)[0];
		edittextseg(i);
	});
}

function generateMoveSelect2(num) {
	var thisistxt = (itemarray[num][0]=="text");
	num++; //adjust indexing
	var sel = "<select id="+num+" onChange=\"moveitem2("+num+")\">";
	var qcnt = 1; var tcnt = 1; var curistxt = false;
	for (var i=1; i<=itemarray.length; i++) {
		//TODO something goes wrong if there are two text segments at the end of an assignment
if (typeof itemarray[i-1]=="undefined") {
	alert("itemarray["+(i-1)+"] undefined "+itemarray.length);
	continue;
}
		curistxt = (itemarray[i-1][0]=="text");
		sel += "<option value=\""+i+"\" ";
		if (i==num) {
			sel += "selected";
		}
		if (curistxt) {
			sel += ">Text"+tcnt+"</option>";
		} else if (itemarray[i-1].length<5 && itemarray[i-1][0]>1) {
			sel += ">Q"+qcnt+"-"+(qcnt+itemarray[i-1][0]-1)+"</option>";
		} else {
			sel += ">Q"+qcnt+"</option>";
		}
		
		if (!curistxt) {
			if (itemarray[i-1].length<5) { //is group
				qcnt += itemarray[i-1][2].length;
			} else {
				qcnt++;
			}
		} else {
			tcnt++;
		}
		/*
		curistxt = (itemarray[i-1][0]=="text");
		if (thisistxt) { //moveselect for text item
			sel += "<option value=\""+i+"\" ";
			if (i==num) {
				sel += "selected";
			}
			if (curistxt) {
				sel += ">Text"+tcnt+"</option>";
			} else {
				if (i==itemarray.length) {
					sel += ">End</option>";
				} else {
					sel += ">Q"+qcnt+"</option>";
				}
			}
		} else if (!curistxt) { //if moveselect for question, skip text items
			sel += "<option value=\""+i+"\" ";
			if (i==num) {
				sel += "selected";
			}
			if (itemarray[i-1].length<5) {
				sel += ">Q"+qcnt+"-"+(qcnt+itemarray[i-1][2].length-1)+"</option>";
			} else {
				sel += ">Q"+qcnt+"</option>";
			}
		}
		if (!curistxt) {
			if (itemarray[i-1].length<5) { //is group
				qcnt += itemarray[i-1][2].length;
			} else {
				qcnt++;
			}
		} else {
			tcnt++;
		}
		*/
	}
	sel += "</select>";
	return sel;
}

function generateMoveSelect(num,itemarray) {
	num++; //adjust indexing
	var sel = "<select id="+num+" onChange=\"moveitem2("+num+")\">";
	for (var i=1; i<=cnt; i++) {
		sel += "<option value=\""+i+"\" ";
		if (i==num) {
			sel += "selected";
		}
		sel += ">"+i+"</option>";
	}
	sel += "</select>";
	return sel;
}

function generateShowforSelect(num) {
	var n = 0, i=num;
	if (i>0 && itemarray[i-1][0]=="text") { //no select unless first in list
		return '';
	}
	while (i<itemarray.length && itemarray[i][0]=="text") {
		i++;
	}
	while (i<itemarray.length && itemarray[i][0]!="text") {
		i++;
		n++;
	}
	if (n==0) {
		return '';
	} else {
		out = 'Show for <select id="showforn'+num+'" onchange="updateTextShowN('+num+')">';
		for (j=1;j<=n;j++) {
			out += '<option value="'+j+'"';
			if (itemarray[num][2]==j) {
				out += " selected";
			}
			out += '>'+j+"</option>";	
		}
		out += '</select>';
		return out;
	}
}

function moveitem2(from) {
	var todo = 0;//document.getElementById("group").value;
	var to = document.getElementById(from).value;
	var tomove = itemarray.splice(from-1,1);
	if (todo==0) { //rearrange
		itemarray.splice(to-1,0,tomove[0]);
	} else if (todo==1) { //group
		if (from<to) {
			to--;
		}
		if (itemarray[to-1].length<5) { //to is already group
			if (tomove[0].length<5) { //if grouping a group
				for (var j=0; j<tomove[0][2].length; j++) {
					itemarray[to-1][2].push(tomove[0][2][j]);
				}
			} else {
				itemarray[to-1][2].push(tomove[0]);
			}
		} else { //to is not group
			var existing = itemarray[to-1];
			if (tomove[0].length<5) { //if grouping a group
				tomove[0][2].push(existing);
				itemarray[to-1] = tomove[0];
			} else {
				itemarray[to-1] = [1,0,[existing,tomove[0]],1];
			}
		}
	}
	submitChanges();
	return false;
}

function ungroupitem(from) {
	locparts = from.split("-");
	var tomove = itemarray[locparts[0]][2].splice(locparts[1],1);
	if (itemarray[locparts[0]][2].length==1) {
		itemarray[locparts[0]] = itemarray[locparts[0]][2][0];
	}
	itemarray.splice(++locparts[0],0,tomove[0]);
	submitChanges();
	return false;
}
function removeitem(loc) {
	if (confirm("Are you sure you want to remove this question?")) {
		doremoveitem(loc);
		submitChanges();
	}
	return false;
}

function removegrp(loc) {
	if (confirm("Are you sure you want to remove ALL questions in this group?")) {
		doremoveitem(loc);
		submitChanges();
	}
	return false;
}

function doremoveitem(loc) {
	if (loc.indexOf("-")>-1) {
		locparts = loc.split("-");
		if (itemarray[locparts[0]].length<5) { //usual
			itemarray[locparts[0]][2].splice(locparts[1],1);
			if (itemarray[locparts[0]][2].length==1) {
				itemarray[locparts[0]] = itemarray[locparts[0]][2][0];
			}
		} else { //group already removed
			itemarray.splice(locparts[0],1);
		}
	} else {
		itemarray.splice(loc,1);
	}
}

function removeSelected() {
	if (confirm("Are you sure you want to remove these questions?")) {
		var form = document.getElementById("curqform");
		var chgcnt = 0;
		for (var e = form.elements.length-1; e >-1 ; e--) {
			var el = form.elements[e];
			if (el.type == 'checkbox' && el.checked && el.value!='ignore') {
				val = el.value.split(":");
				doremoveitem(val[0]); 
				chgcnt++;
			}
		}
		if (chgcnt>0) {
			submitChanges();
		}
	}
}

function groupSelected() {
	var grplist = new Array;
	var form = document.getElementById("curqform");
	for (var e = form.elements.length-1; e >-1 ; e--) {
		var el = form.elements[e];
		if (el.type == 'checkbox' && el.checked && el.value!='ignore' && !el.value.match(":text")) {
			val = el.value.split(":")[0];
			if (val.indexOf("-")>-1) { //is group
				val = val.split("-")[0];
			} else {
				
			}
			isnew = true;
			for (i=0;i<grplist.length;i++) {
				if (grplist[i]==val) {
					isnew = false;
				}
			}
			if (isnew) {
				grplist.push(val);
			}
		}
	}
	if (grplist.length<2) {
		$("#curqtbl input[type=checkbox]").prop("checked",false);
		return;
	}
	var to = grplist[grplist.length-1];
	if (itemarray[to].length<5) {  //moving to existing group
		
	} else {
		var existing = itemarray[to];
		itemarray[to] = [1,0,[existing],1];
	}
	for (i=0; i<grplist.length-1; i++) { //going from last in current to first in current
		tomove = itemarray.splice(grplist[i],1);
		if (tomove[0].length<5) { //if grouping a group
			for (var j=0; j<tomove[0][2].length; j++) {
				itemarray[to][2].push(tomove[0][2][j]);
			}
		} else {
			itemarray[to][2].push(tomove[0]);
		}	
	}
	submitChanges();
}

function updateGrpN(num) {
	var nval = Math.floor(document.getElementById("grpn"+num).value*1);
	if (nval<1 || isNaN(nval)) { nval = 1;} 
	document.getElementById("grpn"+num).value = nval;
	if (nval != itemarray[num][0]) {
		itemarray[num][0] = nval;	
		submitChanges();
	}
}

function updateGrpT(num) {
	
	if (document.getElementById("grptype"+num).value != itemarray[num][1]) {
		itemarray[num][1] = document.getElementById("grptype"+num).value;	
		submitChanges();
	}
	
}

function edittextseg(i) {
	tinyMCE.get("textsegdesdiv"+i).setContent(itemarray[i][1]);

	if (itemarray[i][3]==1) {
		tinyMCE.get("textsegdesheader"+i).setContent(itemarray[i][4]);
	}
}

function savetextseg(i) {
	if (tinyMCE.get("textsegdesdiv"+i).isDirty() ||
			(tinyMCE.get("textsegdesheader"+i) && tinyMCE.get("textsegdesheader"+i).isDirty()) ) {
		itemarray[i][1] = tinyMCE.get("textsegdesdiv"+i).getContent();
		if (itemarray[i][3]==1) {
			itemarray[i][4] = tinyMCE.get("textsegdesheader"+i).getContent();
		}
		submitChanges();
	}
}
function updateTextShowN(i) {
	itemarray[i][2] = $("#showforn"+i).val();
	submitChanges();
}

function chgpagetitle(i) {
	if ($("#ispagetitle"+i).is(":checked")) {
		itemarray[i][3] = 1;
		if (itemarray[i][4]=="") {
			var words = strip_tags(itemarray[i][1]).split(" ");
			if (words.length > 2) {
				itemarray[i][4] = words.slice(0,3).join(" ");
			} else {
				itemarray[i][4] = "Page title (click to edit)";
			}
		}
	} else {
		itemarray[i][3] = 0;
	}
	submitChanges();
}
function strip_tags(txt) {
	return $("<div/>").html(txt).text();
}
/*
function updateTextseg(i) {
	itemarray[i][1] = $("#textseg"+i).val();
}
*/

function generateOutput() {
	var out = '';
	var text_segments = [];
	var qcnt = 0;
	for (var i=0; i<itemarray.length; i++) {
		if (itemarray[i][0]=='text') { //is text item
			//itemarray[i] is ['text',text,displayforN] 
			text_segments.push({"displayBefore":qcnt,"displayUntil":qcnt+itemarray[i][2]-1,"text":itemarray[i][1],"ispage":itemarray[i][3],"pagetitle":itemarray[i][4]});
		} else if (itemarray[i].length<5) {  //is group
			if (out.length>0) {
				out += ',';
			}
			out += itemarray[i][0]+'|'+itemarray[i][1];
			for (var j=0; j<itemarray[i][2].length; j++) {
				out += '~'+itemarray[i][2][j][0];
				qcnt++;
			}
		} else {
			if (out.length>0) {
				out += ',';
			}
			out += itemarray[i][0];
			qcnt++;
		}
	}
	return [out,text_segments];
}

function collapseqgrp(i) {
	itemarray[i][3] = 0;
	updateqgrpcookie();
	refreshTable();
} 
function expandqgrp(i) {
	itemarray[i][3] = 1;
	updateqgrpcookie();
	refreshTable();
}
function updateqgrpcookie() {
	var closegrp = [];
	for (var i=0; i<itemarray.length; i++) {
		if (itemarray[i].length<5) {  //is group
			if (itemarray[i][3]==0) {
				closegrp.push(i);
			}
		}
	}
	document.cookie = 'closeqgrp-' +curaid+'='+ closegrp.join(',');	
}


function generateTable() {
	olditemarray = itemarray;
	itemcount = itemarray.length;
	//string that's unique to each generateTable() call for unique ids
	var alt = 0;
	var ln = 0;
	var pttotal = 0;
	var html = '';
	html += "<table cellpadding=5 class=gb><thead><tr>";
	if (!beentaken) {
		html += "<th></th>";
	}
	html += "<th>Order</th>";
	html += "<th>Description</th><th>&nbsp;</th><th>ID</th><th>Preview</th><th>Type</th><th>Points</th><th>Settings</th><th>Source</th>";
	if (beentaken) {
		html += "<th>Clear Attempts</th><th>Withdraw</th>";
	} else {
		html += "<th>Template</th><th>Remove</th>";
	}
	html += "</thead><tbody>";
	for (var i=0; i<itemcount; i++) {
		curistext = 0;
		curisgroup = 0;
		if (itemarray[i][0]=="text") {
			var curitems = new Array();
			curitems[0] = itemarray[i];
			curistext = 1;
		} else if (itemarray[i].length<5) { //is group
			curitems = itemarray[i][2];
			curisgroup = 1;
		} else {  //not group
			var curitems = new Array();
			curitems[0] = itemarray[i];
		}
		
		//var ms = generateMoveSelect(i,itemcount);
		var ms = generateMoveSelect2(i);
		for (var j=0; j<curitems.length; j++) {
			if (alt == 0) {
				curclass = 'even';		
			} else {
				curclass = 'odd';
			}
			html += "<tr class='"+curclass+"'>";
			if (beentaken) {
				if (curisgroup) {
					if (j==0) {
						html += "<td>"+(i+1)+"</td><td><b>Group</b>, choosing "+itemarray[i][0];
						if (itemarray[i][1]==0) { 
							html += " without";
						} else if (itemarray[i][1]==1) { 
							html += " with";
						}
						html += " replacement</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><tr class="+curclass+">";
					}
					html += "<td>&nbsp;"+(i+1)+'-'+(j+1);
				} else {
					html += "<td>"+(i+1);
				}
				html += "<input type=hidden id=\"qc"+ln+"\" name=\"checked[]\" value=\""+(curisgroup?i+'-'+j:i)+":"+curitems[j][0]+"\"/>";
				html += "</td>";
			} else {
				html += "<td>";
				if (j==0) {
					if (!curisgroup) {
						html += "<input type=checkbox id=\"qc"+ln+"\" name=\"checked[]\" value=\""+(curisgroup?i+'-'+j:i)+":"+curitems[j][0]+"\"/></td><td>";
					} else {
						if (itemarray[i][3]==1) {
							html += "<img src=\""+imasroot+"/img/collapse.gif\" onclick=\"collapseqgrp("+i+")\"/>";	
						} else {
							html += "<img src=\""+imasroot+"/img/expand.gif\" onclick=\"expandqgrp("+i+")\"/>";
						}
						html += '</td><td>';
					}
					html += ms;
					if (curisgroup) {
						html += "</td><td colspan='"+(beentaken?8:9)+"'><b>Group</b> ";
						html += "Select <input type='text' size='3' id='grpn"+i+"' value='"+itemarray[i][0]+"' onblur='updateGrpN("+i+")'/> from group of "+curitems.length;
						html += " <select id='grptype"+i+"' onchange='updateGrpT("+i+")'><option value=0 ";
						if (itemarray[i][1]==0) { 
							html += "selected=1";
						}
						html += ">Without</option><option value=1 ";
						if (itemarray[i][1]==1) { 
							html += "selected=1";
						}
						html += ">With</option></select> replacement";
						html += "</td><td class=c><a href=\"#\" onclick=\"return removegrp('"+i+"');\">Remove</a></td></tr>";
						if (itemarray[i][3]==0) { //collapsed group
							if (curitems[0][4]==9999) { //points
								curpt = defpoints;
							} else {
								curpt = curitems[0][4];
							}
							break;
						}
						html += "<tr class="+curclass+"><td>";
						
					}
				}
				if (curisgroup) {
					html += "<input type=checkbox id=\"qc"+ln+"\" name=\"checked[]\" value=\""+(curisgroup?i+'-'+j:i)+":"+curitems[j][0]+"\"/></td><td>";
					html += "<a href=\"#\" onclick=\"return ungroupitem('"+i+"-"+j+"');\">Ungroup</a>"; //FIX
				}
				html += "</td>";
			}
			if (curistext==1) {
				//html += "<td colspan=7><input type=\"text\" id=\"textseg"+i+"\" onkeyup=\"updateTextseg("+i+")\" value=\""+curitems[j][1]+"\" size=40 /></td>"; //description
				//html += '<td>Show for <input type="text" id="showforn'+i+'" size="1" value="'+curitems[j][2]+'"/></td>';
				if (displaymethod=="Embed") {
					html += "<td colspan=6 id=\"textsegdescr"+i+"\">";
					if (curitems[j][3]==1) {
						html += "<h4 id=\"textsegdesheader"+i+"\" class=\"textsegment\">"+strip_tags(curitems[j][4]).substr(0,50)+"</h2>";
					} 
					html += "<div id=\"textsegdesdiv"+i+"\" class=\"intro textsegment\">"+strip_tags(curitems[j][1]).substr(0,55)+"</div></td>"; //description
					html += '<td><input type="hidden" id="showforn'+i+'" value="1"/>';
					html += '<label><input type="checkbox" id="ispagetitle'+i+'" onchange="chgpagetitle('+i+')" ';
					if (curitems[j][3]==1) { html += "checked";}
					html += '>New page<label></td>';
				} else {
					html += "<td colspan=6 id=\"textsegdescr"+i+"\" ><div id=\"textsegdesdiv"+i+"\" class=\"intro textsegment\">"+strip_tags(curitems[j][1]).substr(0,55)+"</div></td>"; //description
					html += "<td>"+generateShowforSelect(i)+"</td>";
				}
				html += '<td class=c><a href="#" onclick="edittextseg('+i+');return false;">Edit</a></td>';
				html += '<td></td>';
				if (beentaken) {
					html += "<td></td>";
				} else {
					html += "<td class=c><a href=\"#\" onclick=\"return removeitem('"+i+"');\">Remove</a></td>";
				}
			} else {
				html += "<td><input type=hidden name=\"curq[]\" id=\"oqc"+ln+"\" value=\""+curitems[j][1]+"\"/>"+curitems[j][2]+"</td>"; //description
				html += "<td class=\"nowrap\"><div";
				if ((curitems[j][7]&16) == 16) {
					html += " class=\"ccvid\"";
				}
				html += ">";
				if ((curitems[j][7]&1) == 1) {
					var showicons = "";
				} else {
					var showicons = "_no";
				}
				if ((curitems[j][7]&4) == 4) {
					html += '<img src="'+imasroot+'/img/video_tiny'+showicons+'.png"/>';
				}
				if ((curitems[j][7]&2) == 2) {
					html += '<img src="'+imasroot+'/img/html_tiny'+showicons+'.png"/>';
				}
				if ((curitems[j][7]&8) == 8) {
					html += '<img src="'+imasroot+'/img/assess_tiny'+showicons+'.png"/>';
				}  
				html += "</div></td>";
				html += "<td>"+curitems[j][1]+"</td>";
				if (beentaken) {
					html += "<td><input type=button value='Preview' onClick=\"previewq('curqform','qc"+ln+"',"+curitems[j][1]+",false,false)\"/></td>"; //Preview
				} else {
					html += "<td><input type=button value='Preview' onClick=\"previewq('curqform','qc"+ln+"',"+curitems[j][1]+",true,false)\"/></td>"; //Preview
				}
				html += "<td>"+curitems[j][3]+"</td>"; //question type
				if (curitems[j][4]==9999) { //points
					html += "<td>"+defpoints+"</td>";
					curpt = defpoints;
				} else {
					html += "<td>"+curitems[j][4]+"</td>";
					curpt = curitems[j][4];
				}
				html += "<td class=c><a href=\"modquestion.php?id="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\">Change</a></td>"; //settings
				if (curitems[j][5]) {
					html += "<td class=c><a href=\"moddataset.php?id="+curitems[j][1]+"&qid="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\">Edit</a></td>"; //edit
				} else {
					html += "<td class=c><a href=\"moddataset.php?id="+curitems[j][1]+"&template=true&makelocal="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\">Edit</a></td>"; //edit makelocal
				}
				if (beentaken) {
					html += "<td><a href=\"addquestions.php?aid="+curaid+"&cid="+curcid+"&clearqattempts="+curitems[j][0]+"\">Clear Attempts</a></td>"; //add link
					if (curitems[j][6]==1) {
						html += "<td><span class='red'>Withdrawn</span></td>";
					} else {
						html += "<td><a href=\"addquestions.php?aid="+curaid+"&cid="+curcid+"&withdraw="+(curisgroup?i+'-'+j:i)+"\">Withdraw</a></td>";
					}
				} else {
					html += "<td class=c><a href=\"moddataset.php?id="+curitems[j][1]+"&template=true&aid="+curaid+"&cid="+curcid+"\">Template</a></td>"; //add link
					html += "<td class=c><a href=\"#\" onclick=\"return removeitem("+(curisgroup?"'"+i+'-'+j+"'":"'"+i+"'")+");\">Remove</a></td>"; //add link and checkbox
				}
			}
			html += "</tr>";
			ln++;
		}
		if (curistext==0) {
			pttotal += curpt*(curisgroup?itemarray[i][0]:1);
		}
		alt = 1-alt;
	}
	html += "</tbody></table>";
	document.getElementById("pttotal").innerHTML = pttotal;
	return html;
}

function addtextsegment() {
	itemarray.push(["text","",1,0,"",1]);
	refreshTable();
}

function check_textseg_itemarray() {
	var lastwastext = false, numq, j, firstpageloc=-1;
	for (var i=0;i<itemarray.length;i++) {
		if (itemarray[i][0]=="text") {//this is text item
			if (lastwastext) { //make sure showN matches
				itemarray[i][2] = itemarray[i-1][2];
			}
			if (itemarray[i][3]==1 && firstpageloc==-1) {
				firstpageloc = i;
			}
			numq = 0;
			j = i+1;
			while (j<itemarray.length && itemarray[j][0]!="text") {
				numq++;
				j++;
			}
			//make sure isn't bigger than number of q, but is at least 1
			itemarray[i][2] = Math.max(1, Math.min(itemarray[i][2], numq));
			
			lastwastext = true;
		} else {
			lastwastext = false;
		}
	}
	if (firstpageloc>0) {
		alert("If you are using page titles, you need to have a page title at the beginning.");
		if (itemarray[0][0]=="text") {
			itemarray[0][3] = 1;
			itemarray[0][4] = "First Page Title";
		} else {
			itemarray.unshift(["text","",1,1,"First Page Title",1]);
		}
	}
}

function submitChanges() {
	var target = "submitnotice";
	check_textseg_itemarray();
	document.getElementById(target).innerHTML = ' Saving Changes... ';
	data=generateOutput();
	$.ajax({
		type: "POST",
		//url: "$imasroot/course/addquestions.php?cid=$cid&aid=$aid", 
		url: AHAHsaveurl,
		data: {order: data[0], text_order: JSON.stringify(data[1])}
	})
	.done(function() {
		document.getElementById(target).innerHTML='';
		refreshTable();
	})
	.fail(function(xhr, status, errorThrown) {
	    document.getElementById(target).innerHTML=" Couldn't save changes:\n"+ 
			status + "\n" +req.statusText+
			"\nError: "+errorThrown 
		itemarray = olditemarray;
		generateTable();
	}) 
}

/*
function submitChanges() { 
  url = AHAHsaveurl + '&order='+generateOutput();
  var target = "submitnotice";
  document.getElementById(target).innerHTML = ' Saving Changes... ';
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (typeof req != 'undefined') { 
    req.onreadystatechange = function() {ahahDone(url, target);}; 
    req.open("GET", url, true); 
    req.send(""); 
  } 
}  

function ahahDone(url, target) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
	    if (req.responseText=='OK') {
		    document.getElementById(target).innerHTML='';
		    refreshTable();
	    } else {
		    document.getElementById(target).innerHTML=req.responseText;
		    itemarray = olditemarray;
	    }
    } else { 
	    document.getElementById(target).innerHTML=" Couldn't save changes:\n"+ req.status + "\n" +req.statusText; 
	    itemarray = olditemarray;
    } 
  } 
}
*/

