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
}
function generateMoveSelect(num,cnt) {
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
		if (el.type == 'checkbox' && el.checked && el.value!='ignore') {
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
	console.log(nval);
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

function generateOutput() {
	var out = '';
	for (var i=0; i<itemarray.length; i++) {
		if (i!=0) {
			out += ',';
		}
		if (itemarray[i].length<5) {  //is group
			out += itemarray[i][0]+'|'+itemarray[i][1];
			for (var j=0; j<itemarray[i][2].length; j++) {
				out += '~'+itemarray[i][2][j][0];
			}
		} else {
			out += itemarray[i][0];
		}
	}
	return out;
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
		if (itemarray[i].length<5) { //is group
			curitems = itemarray[i][2];
			curisgroup = 1;
		} else {  //not group
			var curitems = new Array();
			curitems[0] = itemarray[i];
			curisgroup = 0;
		}
		
		var ms = generateMoveSelect(i,itemcount);
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
			html += "</tr>";
			ln++;
		}
		pttotal += curpt*(curisgroup?itemarray[i][0]:1);
		alt = 1-alt;
	}
	html += "</tbody></table>";
	document.getElementById("pttotal").innerHTML = pttotal;
	return html;
}

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
