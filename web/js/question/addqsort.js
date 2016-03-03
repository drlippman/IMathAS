//IMathAS: Utility JS for reordering addquestions existing questions
//(c) 2007 IMathAS/WAMAP Project
//Must be predefined:
//beentaken, defpoints
//itemarray: array
//	item: array ( questionid, questionsetid, description, type, points, canedit ,withdrawn )
//	group: array (pick n, without (0) or with (1) replacement, array of items)
$(document).ready(function () {
    generate();
});
var imasroot = $('.home-path').val();
//output submitted via AHAH is new assessment itemorder in form:
// item,item,n|w/wo~item~item,item
function generate(){
    if(itemarray != 0) {
    document.getElementById("curqtbl").innerHTML = generateTable();
    }
}

function refreshTable() {
	document.getElementById("curqtbl").innerHTML = generateTable();
	 if (usingASCIIMath) {
	      rendermathnode(document.getElementById("curqtbl"));
         }
         updateqgrpcookie();
}
function generateMoveSelect(num,cnt) {
	num++; //adjust indexing
	var sel = "<select style='padding-left: 10px;' id="+num+" class='order-btn background-color-blue' onChange=\"moveitem2("+num+")\">";
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
    var message = '';
    message += "Are you sure you want to remove this question";
    var html = '<div><p>' + message + '</p></div>';
    j('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Question Delete', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",draggable:false,
        buttons: {
            "Cancel": function () {
                j(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                doremoveitem(loc);
                submitChanges();
                j(this).remove();

            }
        },
        close: function (event, ui) {
            j(this).remove();
        },
        open: function () {
            j('.ui-widget-overlay').bind('click', function () {
                j('#dialog').dialog('close');
            })
        }
    });
}


//function removeitem(loc) {
//	if (confirm("Are you sure you want to remove this question?")) {
//		doremoveitem(loc);
//		submitChanges();
//	}
//	return false;
//}


function removegrp(loc) {
    var message = '';
    message += "Are you sure you want to remove ALL questions in this group";
    var html = '<div><p>' + message + '</p></div>';
    j('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Group Question Delete', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",draggable:false,
        buttons: {
            "Cancel": function () {
                j(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                doremoveitem(loc);
                submitChanges();
                j(this).remove();

            }
        },
        close: function (event, ui) {
            j(this).remove();
        },
        open: function () {
            j('.ui-widget-overlay').bind('click', function () {
                j('#dialog').dialog('close');
            })
        }
    });
}


//function removegrp(loc) {
//	if (confirm("Are you sure you want to remove ALL questions in this group?")) {
//		doremoveitem(loc);
//		submitChanges();
//	}
//	return false;
//}

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
    var message = '';
    message += "Are you sure you want to remove these questions";
    var html = '<div><p>' + message + '</p></div>';
    var form = document.getElementById("curqform");
    var chgcnt = 0;
    j('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Question Delete', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",draggable:false,
        buttons: {
            "Cancel": function () {
                j(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                for (var e = form.elements.length-1; e >-1 ; e--) {
                    var el = form.elements[e];
                    if (el.type == 'checkbox' && el.checked && el.value != 'ignore') {
                        val = el.value.split(":");
                        doremoveitem(val[0]);
                        chgcnt++;
                    }
                }
                if (chgcnt>0) {
                    submitChanges();
                }
                j(this).remove();

            }
        },
        close: function (event, ui) {
            j(this).remove();
        },
        open: function () {
            j('.ui-widget-overlay').bind('click', function () {
                j('#dialog').dialog('close');
            })
        }
    });
}


function groupSelected() {
	var grplist = new Array;
	var form = document.getElementById("curqform");
	for (var e = form.elements.length-1; e >-1 ; e--) {
		var el = form.elements[e];
		if (el.type == 'checkbox' && el.checked && el.value!='ignore')
        {
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
	html += "<table cellpadding=5 class='floatleft question-table' id='add-remove-ques-table'><thead><tr>";
	if (!beentaken) {
		html += "<th><div class='checkbox override-hidden'><label><input type='checkbox' id='header-checked1' name='header-checked1' value='ignore'>" +
        "<span class='cr'><i class='cr-icon fa fa-check'></i></span></label></div></th>";
	}
	html += "<th>Order</th>";
	html += "<th>Description</th><th>&nbsp;</th><th>ID</th><th>Type</th><th>Points</th><th class='setting-btn'>Action</th><th class='preview-btn'></th>";
	html += "</thead><tbody id='question-information-table'>";
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
				html += "<td class='question-check'>";
				if (j==0) {
					if (!curisgroup) {
						html += "<input type=checkbox id=\"qc"+ln+"\" name=\"checked[]\" value=\""+(curisgroup?i+'-'+j:i)+":"+curitems[j][0]+"\"/></td><td>";
					} else {
						if (itemarray[i][3]==1) {
							html += "<img src=\""+imasroot+"img/collapse.gif\" onclick=\"collapseqgrp("+i+")\"/>";
						} else {
							html += "<img src=\""+imasroot+"img/expand.gif\" onclick=\"expandqgrp("+i+")\"/>";
						}
						html += '</td><td>';
					}
					html += ms;
					if (curisgroup) {
						html += "</td><td colspan='"+(beentaken?3:4)+"'><b>Group</b> ";
						html += "Select <input class='width-ten-per form-control display-inline-block' type='text' size='3' id='grpn"+i+"' value='"+itemarray[i][0]+"' onblur='updateGrpN("+i+")'/> from group of "+curitems.length;
						html += " <select class='width-thirty-per display-inline-block form-control' id='grptype"+i+"' onchange='updateGrpT("+i+")'><option value=0 ";
						if (itemarray[i][1]==0) { 
							html += "selected=1";
						}
						html += ">Without</option><option value=1 ";
						if (itemarray[i][1]==1) { 
							html += "selected=1";
						}
						html += ">With</option></select> replacement";
						html += "</td><td class=c><td><div class='btn-group settings setting-btn'> <a style='width: 72% !important;' class='btn btn-primary disable-btn background-color-blue'>" +
                        "<i class='fa fa-cog fa-fw'></i> Settings</a><a class='btn btn-primary dropdown-toggle' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>" +
                        "<ul class='dropdown-menu'>" +
                        "<li class=c><a href=\"#\" onclick=\"return removegrp('"+i+"');\"><i class='fa fa-trash-o fa-fw'></i></i> Remove</a></li>" ;//remove
                        html += "</ul></div></td><td></td></tr>";
						if (itemarray[i][3]==0) { //collapsed group
							if (curitems[0][4]==9999) { //points
								curpt = defpoints;
							} else {
								curpt = curitems[0][4];
							}
							break;
						}
						html += "<tr class="+curclass+"><td class='question-check'>";
						
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
				html += '<img src="'+imasroot+'img/video_tiny'+showicons+'.png"/>';
			}
			if ((curitems[j][7]&2) == 2) {
				html += '<img src="'+imasroot+'img/html_tiny'+showicons+'.png"/>';
			}
			if ((curitems[j][7]&8) == 8) {
				html += '<img src="'+imasroot+'img/assess_tiny'+showicons+'.png"/>';
			}  
			html += "</div></td>";
			html += "<td>"+curitems[j][1]+"</td>";

			html += "<td>"+curitems[j][3]+"</td>"; //question type
			if (curitems[j][4]==9999) { //points
				html += "<td>"+defpoints+"</td>";
				curpt = defpoints;
			} else {
				html += "<td>"+curitems[j][4]+"</td>";
				curpt = curitems[j][4];
			}
            //Action
            html += "<td><div class='btn-group settings'> " +
                "<a href=\"mod-question?id="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\" style='width: 72% !important;' class='btn btn-primary background-color-blue'>" +
            "<i class='fa fa-cog fa-fw'></i> Settings</a>" +
                "<a class='btn btn-primary dropdown-toggle' data-toggle='dropdown' href='#'><span class='fa fa-caret-down'></span></a>" +
            "<ul class='dropdown-menu' style='min-width: 92%;max-width: 92%;'>";
            if (curitems[j][5]) {
                html += "<li class=c><a href=\"mod-data-set?id="+curitems[j][1]+"&qid="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\"><i class='fa fa-fw'></i></i> Edit</a></li>";//edit
            } else {
                html += "<li class=c><a href=\"mod-data-set?id="+curitems[j][1]+"&template=true&makelocal="+curitems[j][0]+"&aid="+curaid+"&cid="+curcid+"\"><i class='fa fa-fw'></i></i> Edit</a></li>";//edit makelocal
            }

            if (beentaken) {
                html += "<li><a href=\"add-questions?aid="+curaid+"&cid="+curcid+"&clearqattempts="+curitems[j][0]+"\"><img class='small-icon' src='../../img/gradebook.png'></i> Clear Attempts</a></li>";//edit
                if (curitems[j][6]==1) {
                    html += "<li><span class='red'>Withdrawn</span></li>";
                } else {
                    html += "<li><a href=\"add-questions?aid="+curaid+"&cid="+curcid+"&withdraw="+(curisgroup?i+'-'+j:i)+"\"><img class='small-icon' src='../../img/gradebook.png'></i> Withdrawn</a></li>";
                }
            } else {

                html += "<li class=c><a href=\"mod-data-set?id="+curitems[j][1]+"&template=true&aid="+curaid+"&cid="+curcid+"\"><i class='fa fa-archive'></i></i>&nbsp;&nbsp;Template</a></li>";//add link
                html += "<li class=c><a href=\"#\" onclick=\"return removeitem("+(curisgroup?"'"+i+'-'+j+"'":"'"+i+"'")+");\"><i class='fa fa-trash-o fa-fw'></i></i> Remove</a></li>";//add link and checkbox
            }

            html += "</ul></div></td>";

            if (beentaken) {
                html += "<td>" +
                    "<div class='btn btn-primary add-question-preview-btn' onClick=\"previewq('curqform','qc"+ln+"',"+curitems[j][1]+",false,false)\">" +
                     "<img class ='margin-right-ten small-preview-icon' src='../../img/prvAssess.png'>&nbsp;Preview" +
                    "</div>" +
                    "</td>"; //Preview
            } else {
                html += "<td>" +
                    "<div class='btn btn-primary add-question-preview-btn' onClick=\"previewq('curqform','qc"+ln+"',"+curitems[j][1]+",true,false)\"><img class ='margin-right-ten small-preview-icon' src='../../img/prvAssess.png'>&nbsp;Preview</div>" +
                    "</td>"; //Preview
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
		    document.getElementById(target).innerHTML='';
		    refreshTable();
}

function changeSetting(){
    document.forms["curqform"].submit()
}
