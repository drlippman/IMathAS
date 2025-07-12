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
function setupGBpercents() {
  var colpts = [];
  $("thead th").each(function(i,el) {
	let col = el.cellIndex;
  	if (el.className.match(/nocolorize/)) {
  		colpts[col] = -1;
  	} else if (el.hasAttribute('data-pts')) {
  		colpts[col] = 1*el.getAttribute('data-pts');
  	} else {
  		colpts[col] = 100;
  	}
  });
  $("tbody td").each(function(i,el) {
	let col = el.cellIndex;
    if (el.innerHTML.match(/tipshow/) || colpts[col]==-1) {return;}
  	var a = $(el).find("a");
  	if (a.length>0) {
  	  el = a[0];
  	} else {
  		a = $(el).find("div");
  		if (a.length>0) {
  			el = a[0];
  		}
  	}
    if (p = el.textContent.match(/^\s*(\d+\.?\d*)\s*\/\s*(\d+\.?\d*)\s*$/)) {
      var pct = p[2]>0?Math.round( 1000*p[1]/p[2] )/10:0;
			$(el).attr("data-ptv", p[0]).attr("data-pct", pct)
				.attr("title", gbmod.pts==1?p[0]+"pts":pct+"%");
			if (gbmod.pts!=0) {
				$(el).text(pct+"%");
			}
    } else if (p = el.textContent.match(/^\s*(\d+(\.\d*)?)\s*$/)) {
      var pct = colpts[col]>0?Math.round( 1000*p[1]/colpts[col] )/10:0;
			$(el).attr("data-ptv", p[0]).attr("data-pct", pct)
				.attr("title", gbmod.pts==1?p[0]+"pts":pct+"%");
			if (gbmod.pts!=0) {
				$(el).text(pct+"%");
			}
    }
 });
};
$(function() {
	$("input[name=links]").on("change",function(e) {
		var gbmode = gbmodebase - 100*gbmod.links;
		let val = $(this).val();
		gbmode += 100*val;
		$.ajax({
			url: basesite+"?cid="+cid+"&setgbmodeonly=true&gbmode="+gbmode,
			type: "GET"
		}).done(function( data ) {
			gbmod.links = val;
			gbmodebase = gbmode;
		});
	});
	$("input[name=pts]").on("change",function(e) {
		var val = 1*$(this).val();
		if (val != gbmod.pts) {
			var gbmode = gbmodebase - 400000*gbmod.pts;
			gbmode += 400000*val;
			if (val == 0) { //show points
				$("*[data-ptv]").each(function() {
					$(this).text($(this).attr("data-ptv"))
					 .attr("title", $(this).attr("data-pct")+"%");
				});
			} else { //show percents
				$("*[data-pct]").each(function() {
					$(this).text($(this).attr("data-pct")+"%")
					 .attr("title", $(this).attr("data-ptv")+"pts");
				});
			}
			$.ajax({
				url: basesite+"?cid="+cid+"&setgbmodeonly=true&gbmode="+gbmode,
				type: "GET"
			}).done(function( data ) {
				gbmod.pts = val;
				gbmodebase = gbmode;
			});
		}
	});
	$("input[name=pics]").on("change",function(e) {
		var gbmode = gbmodebase - 10000*gbmod.showpics;
		let val = $(this).val();
		gbmode += 10000*val;
		
		if (val == 0) {
			$(".pii-image").hide();
		} else {
			$(".pii-image").each(function() {
				let cursrc = $(this).attr('src');
				if (val == 1) {
					$(this).attr('src', cursrc.replace(/userimg_(?!sm)/,'userimg_sm'));
				} else if (val == 2) {
					$(this).attr('src', cursrc.replace(/userimg_sm/,'userimg_'));				
				}
			});
			$(".pii-image").show();
		}
		
		$.ajax({
			url: basesite+"?cid="+cid+"&setgbmodeonly=true&gbmode="+gbmode,
			type: "GET"
		}).done(function( data ) {
			gbmod.showpics = val;
			gbmodebase = gbmode;
		});
		//TODO: actually change pics display
	});
	$("input[name=newflag]").on("change",function(e) {
		chgnewflag();
	});
	$("input[name=hdrs]").on("change",function(e) {
		var gbmode = gbmodebase - 40000*gbmod.headerlockdef;
		let val = $(this).val();
		gbmode += 40000*(1-val); // gbmod is inverted
		if (val==0) {
			if ($("body").attr("class").match(/fw\d+/)) {
				$("body").attr("data-fw", $("body").attr("class").replace(/.*(fw\d+).*/,'$1'));
				$("body").removeClass("fw1000 fw1920").addClass("notfw");
			}
			$("#tblcontmyTable").removeClass("sticky-table");
			document.cookie = "skiplhdrwarn_"+cid+"=0";
			$("#pgwgroup").hide();
		} else {
			$("#tblcontmyTable").addClass("sticky-table");
			$("#pgwgroup").show();
		}
		$.ajax({
			url: basesite+"?cid="+cid+"&setgbmodeonly=true&gbmode="+gbmode,
			type: "GET"
		}).done(function( data ) {
			gbmod.headerlockdef = 1-val;
			gbmodebase = gbmode;
		});
	});
	$("input[name=pgw]").on("change",function(e) {
		var val=$(this).val();
		document.cookie = "gbfullw-"+cid+"="+val;

		if (val == 0) {
			if (!$("body").attr("class").match(/fw\d+/)) {
				$("body").removeClass("notfw").addClass($("body").attr("data-fw"));
			}
		} else {
			if ($("body").attr("class").match(/fw\d+/)) {
				$("body").attr("data-fw", $("body").attr("class").replace(/.*(fw\d+).*/,'$1'));
				$("body").removeClass("fw1000 fw1920").addClass("notfw");
			}
		}

		gbmod.fullwidth = val;
	});
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
			let idx = ths[i].cellIndex;
			if (k = ths[i].innerHTML.match(/(\d+)(&nbsp;|\u00a0)pts/)) {
				poss[idx] = parseFloat(k[1]);
				if (poss[idx]==0) {poss[idx]=.0000001;}
			} else {
				poss[i] = 100;
				if(ths[i].className.match(/nocolorize/)) {
					startat++;
				}
			}
		}

		var trs = tbl.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
		var v, perc;
		for (var j=0;j<trs.length;j++) {
			var tds = trs[j].getElementsByTagName("td");
			for (var i=0;i<tds.length;i++) {
				let idx = tds[i].cellIndex;
				if (idx < startat) { continue; }
				if (low==-1) {
					if (tds[i].className.match("isact")) {
						tds[i].style.backgroundColor = "#99ff99";
					} else {
						tds[i].style.backgroundColor = "#ffffff";
					}
				} else {
					if (tds[i].innerText) {
						v = tds[i].innerText;
					} else {
						v = tds[i].textContent;
					}

                    if (low==-2) {
                        if (v.match(/\d/) && !v.match(/NC/)) {
                            tds[i].style.backgroundColor = "#99ff99";
                        } else if (v.match(/\d/)) {
                            tds[i].style.backgroundColor = "#ff9999";
                        } else {
                            tds[i].style.backgroundColor = "#ffffff";
                        }
                        continue;
                    }
					if (tds[i].querySelector("[data-pct]")) {
						perc = parseFloat(tds[i].querySelector("[data-pct]").getAttribute("data-pct"));
					} else if (k = v.match(/([\d\.]+)%/)) {
						perc = parseFloat(k[1]);
					} else if (v.match(/\d+\/\d+\/\d+/)) {
						continue;
					} else if (k = v.match(/([\d\.]+)\/(\d+)/)) {
						if (k[2]==0) { perc = 0;} else { perc= Math.round(1000*parseFloat(k[1])/parseFloat(k[2]))/10;}
					} else if (v.replace(/[^\d\.\-]/g,"")=="-") {
						perc = 0;
					} else {
						v = v.replace(/[^\d\.]/g,"");
						if (v=="") {
							continue;
						} else {
							perc = Math.round(1000*parseFloat(v)/poss[idx])/10;
						}
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
	$("#tblcontmyTable").toggleClass("sticky-table");
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
