var path = jQuery('.home-path').val();
function ahah(url, target) {
  document.getElementById(target).innerHTML = ' Fetching data... ';
  if (window.XMLHttpRequest) {
      req = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
      req = new ActiveXObject("Microsoft.XMLHTTP");
  } 
  if (typeof req != 'undefined') {
      req.onreadystatechange = function()
    {
        ahahDone(url, target);
    };
    req.open("GET", url, true); 
    req.send(""); 
  } 
}  

function ahahDone(url, target) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
      document.getElementById(target).innerHTML = req.responseText; 
//      if (usingASCIIMath) {
//	      rendermathnode(document.getElementById(target));
//      }
//      if (usingASCIISvg) {
//	      setTimeout("drawPics()",100);
//      }
        jQuery('#'+target+' a').each(setuptracklinks);
        jQuery('#'+target+' a[href*="youtu"]').each(setupvideoembeds);
        jQuery('#'+target+' a[href*="vimeo"]').each(setupvideoembeds);
      
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

function toggleblock(bnum,folder) {
      var node = document.getElementById('block'+bnum);
      var img = document.getElementById('img'+bnum);
      oblist = oblist.split(',');
      plblist = plblist.split(',');

    var loc = arraysearch(bnum,oblist);
      if (node.className == 'blockitems block-alignment') {
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	loadedblocks.push(bnum);
	  }
          node.className = 'hidden';
          //butn.value = 'Expand';
	  if (img != null) {
		  img.src =  path+'img/expand.gif';
	  }
          if (loc>-1) {oblist.splice(loc,1);}
      } else {
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	ahah(getbiaddr+folder,'block'+bnum);
		if (arraysearch(folder,plblist)==-1) {
			plblist.push(folder);	
		}
	  }
          node.className = 'blockitems block-alignment';
          //butn.value = 'Collapse';
	  if (img != null) {
		  img.src = path+'img/collapse.gif';
	  }
          if (loc==-1) {oblist.push(bnum);} 
      }
      oblist = oblist.join(',');
      plblist = plblist.join(',');
      document.cookie = 'openblocks-' +cid+'='+ oblist;
      document.cookie = 'prevloadedblocks-'+cid+'='+plblist;
   }
   

function changecallength(el) {
	window.location = calcallback + '&callength=' + el.value;
}

var playlist = [];
var curvid = [];
var players = [];
function playlistnextvid() {
	var id = jQuery(this).parents('.playlistbar').get(0).id.substr(11);
	if (curvid[id] != null) {
		var curvidk = curvid[id];
		if (curvidk < playlist[id].length-1) {
			playliststart(id,curvidk+1);
		}
	}
}
function playlistprevvid() {
	var id = jQuery(this).parents('.playlistbar').get(0).id.substr(11);
	if (curvid[id] != null) {
		var curvidk = curvid[id];
		if (curvidk >0) {
			playliststart(id,curvidk-1);
		}
	}
}
function playlisttogglelist() {
	var id = jQuery(this).parents('.playlistbar').get(0).id.substr(11);
	var wrap = jQuery('#playlistwrap'+id);
	var bar = jQuery('#playlistbar'+id);
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
        jQuery(el).hide();
	}
	var wrap = jQuery('#playlistwrap'+id);
	var bar = jQuery('#playlistbar'+id);
	var iframe = wrap.find('iframe');
	var url = location.protocol+'//www.youtube.com/embed/'+playlist[id][vidk].vidid;
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
            jQuery('<iframe/>', {
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
	
	bar.find('.playlisttitle').html(playlist[id][vidk].name+' <a target="_blank" href="http://www.youtube.com/watch?v='+playlist[id][vidk].vidid+'"><img src="'+imasroot+'/img/extlink.png"/></a>');
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


function studLocked()
{
    var html = '<div><p>You have been locked out of this course by your instructor. Please see your instructor for more information.</p></div>';
    var cancelUrl = jQuery(this).attr('href');

    jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false, draggable: false,
        closeText: "hide",
        buttons: {
            "Ok": function () {
                jQuery(this).dialog('destroy').remove();
                return false;
            }
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        },
        close: function (event, ui) {
            jQuery(this).remove();
        }
    });
}

function locked()
{
    var lockId = $('.lockId').val();
    var courseId = $('.courseId').val();
    var assessmentName = $('.assessmentName').val();
    var html = '<div><p>This course is currently locked for an assessment.</p></div>';
    html += '<div><a class=" " style="color: #0000ff;font-size: 16px" href="../assessment/assessment/show-test?id='+lockId+'&cid='+courseId+' ">Go to Assessment</a> | ' +
        '<a class=" " style="color: #0000ff;font-size: 16px" href="../site/dashboard">Go Back</a></div>';
    var cancelUrl = jQuery(this).attr('href');
    jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false, draggable: false,
        closeText: "hide",
        buttons: {
            "Ok": function () {
                jQuery(this).dialog('destroy').remove();
                return false;
            }
        },
        close: function (event, ui) {
            jQuery(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}
