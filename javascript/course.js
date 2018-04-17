function ahah(url, target) {
  document.getElementById(target).innerHTML = ' Fetching data... ';
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
      document.getElementById(target).innerHTML = req.responseText;
      if (usingASCIIMath) {
	      rendermathnode(document.getElementById(target));
      }
      if (usingASCIISvg) {
	      setTimeout("drawPics()",100);
      }
      $('#'+target+' a').each(setuptracklinks);
      $('#'+target+' a[href*="youtu"]').each(setupvideoembeds);
      $('#'+target+' a[href*="vimeo"]').each(setupvideoembeds);

      var x = document.getElementById(target).getElementsByTagName("script");
      for(var i=0;i<x.length;i++) {
	      if (x[i].src) {
		      var script = document.createElement("script");
		      script.src = x[i].src;
		      var pn = x[i].parentNode;
		      pn.replaceChild(script,x[i]);
	      } else {
		      eval(x[i].text);
	      }
      }


    } else {
      document.getElementById(target).innerHTML=" AHAH Error:\n"+ req.status + "\n" +req.statusText;
    }
  }
}



var loadedblocks = new Array();

function toggleblock(event,bnum,folder) {
      var node = document.getElementById('block'+bnum);
      //var butn = document.getElementById('but'+bnum);
      var img = document.getElementById('img'+bnum);
      oblist = oblist.split(',');
      plblist = plblist.split(',');
      var loc = arraysearch(bnum,oblist);
      if (node.className == 'blockitems') {
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	loadedblocks.push(bnum);
	  }
          node.className = 'hidden';
          node.setAttribute('aria-hidden', true);
          node.setAttribute('aria-expanded', false);
          $("#blockhead"+bnum+" *[aria-expanded=true]").attr("aria-expanded",false);
          //butn.value = 'Expand';
	  if (img != null) {
		  img.src = imasroot+'/img/expand.gif';
	  }
          if (loc>-1) {oblist.splice(loc,1);}
      } else {
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	ahah(getbiaddr+folder,'block'+bnum);
		if (arraysearch(folder,plblist)==-1) {
			plblist.push(folder);
		}
	  }
          node.className = 'blockitems';
          node.setAttribute('aria-hidden', false);
          node.setAttribute('aria-expanded', true);
          $("#blockhead"+bnum+" *[aria-expanded=false]").attr("aria-expanded",true);
          //butn.value = 'Collapse';
	  if (img != null) {
		  img.src = imasroot+'/img/collapse.gif';
	  }
          if (loc==-1) {oblist.push(bnum);}
      }
      oblist = oblist.join(',');
      plblist = plblist.join(',');
      document.cookie = 'openblocks-' +cid+'='+ oblist;
      document.cookie = 'prevloadedblocks-'+cid+'='+plblist;
      if (event.shiftKey && node.className == 'hidden') {
          $("a[id^=blockh]").each(function(i,el) {
          	var id=$(el).attr("id").substr(6);
          	if ($("#block"+id).hasClass("blockitems")) { toggleblock({},id,null);}
          });
      }
   }

function showcalcontents(el) {
	var html = '';
	if (typeof el == 'number') {
		for (moday in caleventsarr) {
			if (caleventsarr[moday].data!=null) {
				html += '<div class="caldatebar">'+caleventsarr[moday].date + '</div>';
				html += showcalcontentsid(moday);
			}
		}

	} else if (caleventsarr[el.id]!=null) {
		html += '<div class="caldatebar">'+caleventsarr[el.id].date + '</div>';
		html += showcalcontentsid(el.id);
		var mlink = document.getElementById("mcelink");
		if (mlink!=null) {
			var href = mlink.href;
			href = href.replace(/^(.*?cid=\d+).*$/,"$1");
			mlink.href = href+"&addto="+(Date.parse(caleventsarr[el.id].date)/1000);
		}
	}

	document.getElementById('caleventslist').innerHTML = html;
	jQuery("table.cal td").removeClass("today");
	if (typeof el != 'number') {
		jQuery(el).addClass("today");
		jQuery("#calshowall").show();
	} else {
		jQuery("#calshowall").hide();
	}
}

function hidevisualcal() {
	showcalcontents(1);
	jQuery("table.cal").toggle();
}

function showcalcontentsid(elid) {
	var html = '';
	if (caleventsarr[elid].data!=null) {
		html += '<ul class=qview style="margin-top: 2px;">';
		for (var i=0; i<caleventsarr[elid].data.length; i++) {
			if (!caleventsarr[elid].data[i].hasOwnProperty("time")) {
				//minimal listing for editing purposes
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				if (caleventsarr[elid].data[i].type.charAt(1)=='S') {
					html += ". " + _("Available After Date");
				} else if (caleventsarr[elid].data[i].type.charAt(1)=='E') {
					html += ". " + _("Available Until Date");
				}
			} else if (caleventsarr[elid].data[i].type=='AE') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null && !caleventsarr[elid].data[i].hasOwnProperty('inactive')) {
					html += '<a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'"';
					if (caleventsarr[elid].data[i].timelimit!=null) {
						html += 'onclick="return confirm(\'This assessment has a time limit. Click OK to start or continue working on the assessment.\')" ';
						//html += 'onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');return confirm(\'This assessment has a time limit. Click OK to start or continue working on the assessment.\')" ';
					} else {
						//html += 'onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';
					}
					html += '>';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].allowlate==1) {
					html += ' <a href="redeemlatepass.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].undolate==1) {
					html += ' <a href="redeemlatepass.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'&undo=true">Un-use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addassessment.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Settings</a>';
					html += ' <a href="addquestions.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Questions</a>';
					html += ' <a href="gb-itemanalysis.php?asid=average&cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Grades</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='AR') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) {
					//html += '<a onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';
					html += '<a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Review until '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addassessment.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Settings</a>';
					html += ' <a href="isolateassessgrade.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Grades</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type.charAt(0)=='I') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].folder != '@@@') {
					html += '<a href="../course/course.php?cid='+cid+'&folder='+caleventsarr[elid].data[i].folder+'#inline'+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addinlinetext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type.charAt(0)=='L') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) {

					if (caleventsarr[elid].data[i].link=='') {
						html += '<a onclick="recclick(\'linkedviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';
						if (caleventsarr[elid].data[i].target==1) { html += 'target="_blank" ';}
						html += 'href="../course/showlinkedtext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
					} else {
						html += '<a onclick="recclick(\'linkedviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].link+'\');" ';
						if (caleventsarr[elid].data[i].target==1) { html += 'target="_blank" ';}
						html += 'href="'+caleventsarr[elid].data[i].link+'">';
					}
					html += caleventsarr[elid].data[i].name;
					html += '</a>';

				} else {
					html += caleventsarr[elid].data[i].name;
				}

				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addlinkedtext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FP') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) {
					html += '<a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' New Threads Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].allowlate==1) {
					html += ' <a href="redeemlatepassforum.php?cid='+cid+'&fid='+caleventsarr[elid].data[i].id+'">Use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].undolate==1) {
					html += ' <a href="redeemlatepassforum.php?cid='+cid+'&fid='+caleventsarr[elid].data[i].id+'&undo=true">Un-use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addforum.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FR') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) {
					html += '<a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Replies Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].allowlate==1) {
					html += ' <a href="redeemlatepassforum.php?cid='+cid+'&fid='+caleventsarr[elid].data[i].id+'">Use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].undolate==1) {
					html += ' <a href="redeemlatepassforum.php?cid='+cid+'&fid='+caleventsarr[elid].data[i].id+'&undo=true">Un-use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addforum.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='DE') {
				html += '<li><span class="calitem" '+(caleventsarr[elid].data[i].color==""?"":('style="background-color:'+caleventsarr[elid].data[i].color+'"'))+'>'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id != null) {
					html += '<a href="../course/drillassess.php?cid='+cid+'&daid='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="adddrillassess.php?cid='+cid+'&daid='+caleventsarr[elid].data[i].id+'">Modify</a>';
					html += ' <a href="gb-viewdrill.php?cid='+cid+'&daid='+caleventsarr[elid].data[i].id+'">Scores</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='CD') {
				html += '<li><span class="calitem">'+ caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				html += '</li>';
			}
		}
		html += '</ul>';
	}
	return html;
}
jQuery(document).ready(function($) {
	$(".caldl").attr("title",_("Bring this day to top"));
});

function changecallength(el) {
	window.location = calcallback + '&callength=' + el.value;
}

var playlist = [];
var curvid = [];
var players = [];
function playlistnextvid() {
	var id = $(this).parents('.playlistbar').get(0).id.substr(11);
	if (curvid[id] != null) {
		var curvidk = curvid[id];
		if (curvidk < playlist[id].length-1) {
			playliststart(id,curvidk+1);
		}
	}
}
function playlistprevvid() {
	var id = $(this).parents('.playlistbar').get(0).id.substr(11);
	if (curvid[id] != null) {
		var curvidk = curvid[id];
		if (curvidk >0) {
			playliststart(id,curvidk-1);
		}
	}
}
function playlisttogglelist() {
	var id = $(this).parents('.playlistbar').get(0).id.substr(11);
	var wrap = $('#playlistwrap'+id);
	var bar = $('#playlistbar'+id);
	if (wrap.find('.playlisttext').css('display')=='none') {
		//show list
		wrap.find('.playlisttext').show();
		wrap.find('.playlistvid').hide();
		bar.find('.vidtracks').removeClass("vidtracks").addClass("vidtracksA");
	} else {
		//show vid
		wrap.find('.playlisttext').hide();
		wrap.find('.playlistvid').show();
		bar.find('.vidtracksA').removeClass("vidtracksA").addClass("vidtracks");
	}

}
function playliststart(id,vidk,el) {
	if (el!==null) {
		$(el).hide();
	}
	var wrap = $('#playlistwrap'+id);
	var bar = $('#playlistbar'+id);
	var iframe = wrap.find('iframe');
	if (playlist[id][vidk].isGdrive) {
		var url = "https://drive.google.com/file/d/"+playlist[id][vidk].vidid+"/preview";
	} else {
		var loc_protocol = location.protocol == 'https:' ? 'https:' : 'http:';
		var url = loc_protocol+'//www.youtube.com/embed/'+playlist[id][vidk].vidid;
	}
	if (playlist[id][vidk].start>0) {
		url += '?start='+playlist[id][vidk].start+'&';
		if (playlist[id][vidk].end>0) {
			url += 'end='+playlist[id][vidk].end+'&';
		}
	} else if (playlist[id][vidk].end>0) {
		url += '?end='+playlist[id][vidk].end+'&';
	} else {
		url += '?';
	}
	url += 'rel=0&autoplay=1';
	curvid[id] = vidk;
	if (wrap.find('.playlisttext').css('display')!='none') {
		wrap.find('.playlisttext').hide();
		wrap.find('.playlistvid').show();
	}
	if (iframe.length == 0) { //not init.  Init it
		wrap.find('.playlistvid').append(
			$('<iframe/>', {
				id: 'videoiframe'+id,
				width: 640,
				height: 400,
				src: url,
				frameborder: 0,
				allowfullscreen: 1
			})
		).fitVids();
		/*if (YouTubeApiLoaded) {
			players[id] = new YT.Player('videoiframe'+id, {
			  events: {
			    'onReady': VidlistonPlayerReady,
			    'onStateChange': VidlistonPlayerStateChange
			}});
		}*/
		bar.find('.vidplay').hide();
		bar.find('.vidff').show().bind('click',playlistnextvid).css('cursor','pointer');
		bar.find('.vidrewI').show().bind('click',playlistprevvid).css('cursor','pointer');
		bar.find('.vidtracksA').removeClass('vidtracksA').addClass('vidtracks').css('cursor','pointer')
			.bind('click',playlisttogglelist).next().css('cursor','pointer').bind('click', playlisttogglelist);
	} else {
		wrap.find('iframe').attr('src',url);
	}
	bar.find('.playlisttitle').html(playlist[id][vidk].name);
	/*if (playlist[id][vidk].isGdrive) {
		bar.find('.playlisttitle').html('<a target="_blank" href="https://drive.google.com/file/d/'+playlist[id][vidk].vidid+'/view">'+playlist[id][vidk].name+'</a>');
	} else {
		bar.find('.playlisttitle').html('<a target="_blank" href="http://www.youtube.com/watch?v='+playlist[id][vidk].vidid+'">'+playlist[id][vidk].name+'</a>');
	}
	*/
	if (vidk==0) {
		bar.find('.vidrew,.vidrewI').removeClass("vidrew").addClass("vidrewI");
	} else {
		bar.find('.vidrew,.vidrewI').addClass("vidrew").removeClass("vidrewI");
	}
	if (vidk==playlist[id].length-1) {
		bar.find('.vidff,.vidffI').removeClass("vidff").addClass("vidffI");
	} else {
		bar.find('.vidff,.vidffI').removeClass("vidffI").addClass("vidff");
	}
}

/*
function VidlistonPlayerReady(event) {

}
function VidlistonPlayerStateChange(event) {
	if (event.data==0) {//end of video
		console.log(event.target);
		var vidurl = event.target.getVideoUrl();
		var iframe = $('iframe[src*="'+vidurl+'"]');
		if (iframe.length>0) {
			var id = iframe.attr('id').substr(11);
			console.log('id:'+id);
			var curvidk = curvid[id];
			if (curvidk < playlist[id].length-1) {
				playliststart(id,curvidk+1);
			}
		}
	}
}
$(function() {
	if ($('.playlistbar').length>0) {
		var tag = document.createElement('script');
		tag.src = "https://www.youtube.com/iframe_api";
		$('body').append(tag);
	}
});
var YouTubeApiLoaded = false;
function onYouTubeIframeAPIReady() {
	console.log("API loaded");
	YouTubeApiLoaded = true;
}
*/
(function($) {
$(function() {$("#leftcontenttoggle").on("click", function(e) {
	var el = $("#leftcontenttoggle");
	$("#leftcontent").toggleClass("hiddenmobile").css("top",el.position().top+el.outerHeight(true)-parseInt($("#leftcontent").css("margin-top")));
	el.toggleClass("leftcontentactive");
	if (!$("#leftcontent").hasClass("hiddenmobile")) {
		$(document).on("click.lefttoggle", function(e) {
			var container = $("#leftcontent");
			var togglebtn = $("#leftcontenttoggle");
			if (!container.is(e.target) && container.has(e.target).length===0
				 && !togglebtn.is(e.target) && togglebtn.has(e.target).length===0) {
				$("#leftcontenttoggle").trigger("click");
			}
		});
	} else {
		$(document).off("click.lefttoggle");
	}
	e.preventDefault();});
})
})(jQuery);
