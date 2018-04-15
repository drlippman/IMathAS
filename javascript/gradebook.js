function chgfilter() {
	var cat = document.getElementById("filtersel").value;
	var toopen = basesite+"?cid="+cid+"&stu="+stu+"&catfilter=" + encodeURIComponent(cat);
	window.location = toopen;
}
function chgsecfilter() {
	var sec = document.getElementById("secfiltersel").value;
	var toopen = basesite+"?cid="+cid+"&stu="+stu+"&secfilter=" + encodeURIComponent(sec);
	window.location = toopen;
}
function chgnewflag() {
	var toopen = basesite+"?cid="+cid+"&stu="+stu+"&togglenewflag=true";
	basicahah(toopen,"newflag",_('Recording...'));
}
function chgstu(el) {
	$('#updatingicon').show();
	var toopen = basesite+"?cid="+cid+"&stu="+el.value;
	window.location = toopen;
}
function chggbfilters() {
	var gbmode = gbmodebase - 10*gbmod.hidenc - 1*gbmod.availshow;
	gbmode += 10*$("#hidenc").val() + 1*$("#availshow").val();
	window.location = basesite+"?cid="+cid+"&stu="+stu+"&gbmode="+gbmode;
}
function chglockedtoggle() {
	var gbmode = gbmodebase - 100*gbmod.hidelocked;
	gbmode += 100*$("#lockedtoggle").val();
	window.location = basesite+"?cid="+cid+"&stu="+stu+"&gbmode="+gbmode;
}
function chglinktoggle() {
	var gbmode = gbmodebase - 100*gbmod.links;
	gbmode += 100*$("#linktoggle").val();
	window.location = basesite+"?cid="+cid+"&stu="+stu+"&gbmode="+gbmode;
}
$(function() {
	$("a[data-links]").on("click",function(e) {
		e.preventDefault();
		var gbmode = gbmodebase - 100*gbmod.links;
		gbmode += 100*$(this).attr("data-links");;
		window.location = basesite+"?cid="+cid+"&stu="+stu+"&gbmode="+gbmode;
	});
	$("a[data-pics]").on("click",function(e) {
		e.preventDefault();
		var gbmode = gbmodebase - 10000*gbmod.showpics;
		gbmode += 10000*$(this).attr("data-pics");
		window.location = basesite+"?cid="+cid+"&stu="+stu+"&gbmode="+gbmode;
	});
	$("a[data-newflag]").on("click",function(e) {
		e.preventDefault();
		chgnewflag();
		$("a[data-newflag]").parent().removeClass("active");
		$(this).parent().addClass("active");
	});
	$("a[data-hdrs]").on("click",function(e) {
		e.preventDefault();
		var val=$(this).attr("data-hdrs");
		if (val==0) {
			ts.unlock();
		} else {
			ts.lock();
		}
		document.cookie = "gblhdr-"+cid+"="+val;
		$("a[data-hdrs]").parent().removeClass("active");
		$(this).parent().addClass("active");
	});
	$(".gbtoggle a").attr("href","#");
});
$(function() {
	$("th a, th select").bind("click", function(e) { e.stopPropagation(); });
});
function makeofflineeditable(el) {
	var anchors = document.getElementsByTagName("a");
	for (var i=0;i<anchors.length;i++) {
		if (bits=anchors[i].href.match(/addgrades.*gbitem=(\d+)/)) {
			if (anchors[i].innerHTML.match("-")) {
			    type = "newscore";
			} else {
			    type = "score";
			}
			anchors[i].style.display = "none";
			var newinp = document.createElement("input");
			newinp.size = 4;
			if (type=="newscore") {
			    newinp.name = "newscore["+bits[1]+"]";
			} else {
			    newinp.name = "score["+bits[1]+"]";
			    newinp.value = anchors[i].innerHTML;
			}
			anchors[i].parentNode.appendChild(newinp);
			var newtxta = document.createElement("textarea");
			newtxta.name = "feedback["+bits[1]+"]";
			newtxta.cols = 50;
			var feedbtd = anchors[i].parentNode.nextSibling.nextSibling.nextSibling;
			newtxta.value = feedbtd.innerHTML;
			feedbtd.innerHTML = "";
			feedbtd.appendChild(newtxta);
		}
	}
	document.getElementById("savechgbtn").style.display = "";
	el.onclick = null;
}
function conditionalColor(table,type,low,high) {
	var tbl = document.getElementById(table);
	if (type==0) {  //instr gb view
		var poss = [];
		var startat = 2;
		var ths = tbl.getElementsByTagName("thead")[0].getElementsByTagName("th");
		for (var i=0;i<ths.length;i++) {
			if (k = ths[i].innerHTML.match(/(\d+)(&nbsp;|\u00a0)pts/)) {
				poss[i] = parseFloat(k[1]);
				if (poss[i]==0) {poss[i]=.0000001;}
			} else {
				poss[i] = 100;
				if(ths[i].className.match(/nocolorize/)) {
					startat++;
				}
			}
		}
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			for (var i=startat;i<tds.length;i++) {
				if (low==-1) {
					if (tds[i].className.match("isact")) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				} else {
					if (tds[i].innerText) {
						var v = tds[i].innerText;
					} else {
						var v = tds[i].textContent;
					}
					if (k = v.match(/\(([\d\.]+)%\)/)) {
						var perc = parseFloat(k[1]);
					} else if (k = v.match(/([\d\.]+)\/(\d+)/)) {
						if (k[2]==0) { var perc = 0;} else { var perc= Math.round(1000*parseFloat(k[1])/parseFloat(k[2]))/10;}
					} else {
						v = v.replace(/[^\d\.]/g,"");
						var perc = Math.round(1000*parseFloat(v)/poss[i])/10;
					}

					if (perc<low) {
						tds[i].style.backgroundColor = "#ff9999";

					} else if (perc>=high) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				}
			}
		}
	} else {
		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			if (tds[1].innerText) {
				var poss = tds[1].innerText.replace(/[^\d\.]/g,"");
				var v = tds[2].innerText.replace(/[^\d\.]/g,"");
			} else {
				var poss = tds[1].textContent.replace(/[^\d\.]/g,"");
				var v = tds[2].textContent.replace(/[^\d\.]/g,"");
			}
			if (v/poss<low/100) {
				tds[2].style.backgroundColor = "#ff6666";

			} else if (v/poss>high/100) {
				tds[2].style.backgroundColor = "#66ff66";
			} else {
				tds[2].style.backgroundColor = "#ffffff";

			}

		}
	}
}
function updateColors(el) {
	if (el.value==0) {
		var tds=document.getElementById("myTable").getElementsByTagName("td");
		for (var i=0;i<tds.length;i++) {
			tds[i].style.backgroundColor = "";
		}
	} else {
		var s = el.value.split(/:/);
		conditionalColor("myTable",0,s[0],s[1]);
	}
	document.cookie = "colorize-"+cid+"="+el.value;
}
function copyemails() {
	var ids = [];
	$("#myTable input:checkbox:checked").each(function(i) {
		ids.push(this.value);
	});
	GB_show("Emails","viewemails.php?cid="+cid+"&ids="+ids.join("-"),500,500);
}

function lockcol() {
	var tog = ts.toggle();
	document.cookie = 'gblhdr-'+cid+'=1';
}
function cancellockcol() {
	document.cookie = 'gblhdr-'+cid+'=0';
}
function highlightrow(el) {
	$(el).addClass("highlight");
}
function unhighlightrow(el) {
	$(el).removeClass("highlight");
}
function postGBform(val) {
	$("#qform").append($("<input>", {name:"posted", value:val, type:"hidden"})).submit();
}
