//dropdown menu
var closetimer	= 0;
var ddmenuitem	= 0;
var homemenuloaded = 0;
// open hidden layer
function mopen(id,cid) {	
	if (id=='homemenu' && homemenuloaded==0) {
		basicahah(imasroot+'/gethomemenu.php?cid='+cid,'homemenu');
		homemenuloaded = 1;
	}
	mcancelclosetime();
	if(ddmenuitem) { 
		ddmenuitem.style.visibility = 'hidden';
		ddmenuitem = null;
	}else {
		ddmenuitem = document.getElementById(id);
		ddmenuitem.style.visibility = 'visible';
	}
}
// close showed layer
function mclose() {
	if(ddmenuitem) {
		ddmenuitem.style.visibility = 'hidden';
		ddmenuitem = null;
	}
}
// go close timer
function mclosetime() {
	closetimer = window.setTimeout(mclose, 250);
}
// cancel close timer
function mcancelclosetime() {
	if(closetimer)
	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}

function basicahah(url, target, def) {
  if (def==null) { def =  ' Fetching data... ';}
  document.getElementById(target).innerHTML = def;
  var hasreq = false;
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest();
    hasreq = true;
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP");
    hasreq = true;
  } 
  if (hasreq) { 
    req.onreadystatechange = function() {basicahahDone(url, target);}; 
    req.open("GET", url, true); 
    req.send(""); 
  } 
}  

function basicahahDone(url, target) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
      document.getElementById(target).innerHTML = req.responseText; 
    } else { 
      document.getElementById(target).innerHTML=" AHAH Error:\n"+ req.status + "\n" +req.statusText; 
    } 
  } 
}

function arraysearch(needle,hay) {
      for (var i=0; i<hay.length;i++) {
            if (hay[i]==needle) {
                  return i;
            }
      }
      return -1;
   }
   
var tipobj = 0;
function tipshow(el,tip) {
	if (typeof tipobj!= 'object') {
		tipobj = document.createElement("div");
		tipobj.className = "tips";
		document.getElementsByTagName("body")[0].appendChild(tipobj);
	} 
	tipobj.innerHTML = tip;
	tipobj.style.left = "5px";
	tipobj.style.display = "block";
	
	if (typeof usingASCIIMath!='undefined' && typeof noMathRender != 'undefined') {
		if (usingASCIIMath && !noMathRender) {
			rendermathnode(tipobj);
		}
	}
	var p = findPos(el);

	if (self.innerHeight) {
                x = self.innerWidth;
        } else if (document.documentElement && document.documentElement.clientHeight) {
                x = document.documentElement.clientWidth;
        } else if (document.body) {
                x = document.body.clientWidth;
        }
        var scrOfX = 0;
        if( typeof( window.pageYOffset ) == 'number' ) {
	    scrOfX = window.pageXOffset;
	  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
	    scrOfX = document.body.scrollLeft;
	  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
	    scrOfX = document.documentElement.scrollLeft;
	  }

        x += scrOfX;
        if ((p[0] + tipobj.offsetWidth)>x) {
        	p[0] = x - tipobj.offsetWidth - 30;
        }
        
	tipobj.style.left = (p[0]+20) + "px";
	if (p[1] < 30) {
		tipobj.style.top = (p[1]+20) + "px";
	} else {
		tipobj.style.top = (p[1]-tipobj.offsetHeight) + "px";
	}
}
var popupwins = [];
function popupwindow(id,content,width,height,scroll) {
	if (height=='fit') {
		height = window.height - 80;
	}
	var attr = "width="+width+",height="+height+",status=0,resizable=1,directories=0,menubar=0";
	if (scroll!=null && scroll==true) {
		attr += ",scrollbars=1";
	}
	if (typeof(popupwins[id])!="undefined" && !popupwins[id].closed) {
		popupwins[id].focus();
	}
	if (content.match(/^http/)) {
		popupwins[id] = window.open(content,id,attr);
	} else {
		var win1 = window.open('',id,attr);
		win1.document.write('<html><head><title>Popup</title></head><body>');
		win1.document.write(content);
		win1.document.write('</body></html>');
		win1.document.close();
		popupwins[id] = win1;
	}
}
function tipout(el) {
	tipobj.style.display = "none";	
}

function findPos(obj) { //from quirksmode.org
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
			if (obj.offsetParent) {
				if (obj.parentNode && obj.offsetParent!=obj.parentNode) {
					curleft -= obj.parentNode.scrollLeft;
					curtop -= obj.parentNode.scrollTop;
				} else {
					curleft -= obj.offsetParent.scrollLeft;
					curtop -= obj.offsetParent.scrollTop;
				}
			}
		} while (obj = obj.offsetParent);
	}
	return [curleft,curtop];
}
function togglepic(el) {
	if (el.getAttribute("src").match("userimg_sm")) {
		el.setAttribute("src",el.getAttribute("src").replace("_sm","_"));
	} else {
		el.setAttribute("src",el.getAttribute("src").replace("_","_sm"));
	}
}


//from http://www.webreference.com/programming/javascript/onloads/
function addLoadEvent(func) { 
	  var oldonload = window.onload; 
	  if (typeof window.onload != 'function') { 
	    window.onload = func; 
	  } else { 
	    window.onload = function() { 
	      if (oldonload) { 
	        oldonload(); 
	      } 
	      func(); 
	    } 
	  } 
} 

function submitlimiter(e) {
	var target = e.target;
	if (target.className == 'submitted') {
		alert("You have already submitted this page.  Please be patient while your submission is processed.");
		target.className = "submitted2";
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
	} else if (target.className == 'submitted2') {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
	} else {
		target.className = 'submitted';
	}
}
function setupFormLimiters() {
	var el = document.getElementsByTagName("form");
	for (var i=0;i<el.length;i++) {
		if (typeof el[i].onsubmit != 'function' && el[i].className!="nolimit") {
			$(el[i]).on('submit',submitlimiter);
		}
	}
}
addLoadEvent(setupFormLimiters);


var GB_loaded = false;
//based on greybox redux, http://jquery.com/demo/grey/
function GB_show(caption,url,width,height) {
	if (GB_loaded == false) {
		var gb_overlay = document.createElement("div");
		gb_overlay.id = "GB_overlay";
		gb_overlay.onclick = GB_hide;
		document.getElementsByTagName("body")[0].appendChild(gb_overlay);
		var gb_window = document.createElement("div");
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder" ></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
	}
	document.getElementById("GB_frameholder").innerHTML = '<iframe onload="GB_doneload()" id="GB_frame" src="'+url+'"></iframe>';
	jQuery("#GB_frameholder").isolatedScroll();
	if (url.match(/libtree/)) {
		var btnhtml = '<span class="floatright"><input type="button" value="Use Libraries" onClick="document.getElementById(\'GB_frame\').contentWindow.setlib()" /> ';
		btnhtml += '<span class="pointer" onclick="GB_hide()">[X]</span>&nbsp;</span>Select Libraries<div class="clear"></div>';
		document.getElementById("GB_caption").innerHTML = btnhtml;
		var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	} else {
		document.getElementById("GB_caption").innerHTML = '<span class="floatright"><span class="pointer" onclick="GB_hide()">[X]</span></span>'+caption;
		document.getElementById("GB_caption").onclick = GB_hide;
		if (height=='auto') {
			var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
		} else {
			var h = height;
		}
	}
	document.getElementById("GB_window").style.display = "block";
	document.getElementById("GB_overlay").style.display = "block";
	document.getElementById("GB_loading").style.display = "block";
	
	var de = document.documentElement;
	var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	
	document.getElementById("GB_window").style.width = width + "px";
	document.getElementById("GB_window").style.height = (h-30) + "px";
	document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	document.getElementById("GB_frame").style.height = (h - 30 -34)+"px";
}
function GB_doneload() {
	document.getElementById("GB_loading").style.display = "none";
}
function GB_hide() {
	document.getElementById("GB_window").style.display = "none";
	document.getElementById("GB_overlay").style.display = "none";
}

function chkAllNone(frmid, arr, mark, skip) {
  var frm = document.getElementById(frmid);
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if ((arr=='all' && frm.elements[i].type=='checkbox') || frm.elements[i].name == arr) {
       if (skip && frm.elements[i].className==skip) {
       	 frm.elements[i].checked = !mark;
       } else {
       	 frm.elements[i].checked = mark;      
       }
      
     }
   } catch(er) {}
  }
  return false;
}

function initeditor(edmode,edids,css) {
	var cssmode = css || 0;
	var edsetup = {
	    mode : edmode,
	    theme : "advanced",
	    theme_advanced_buttons1 : "fontselect,fontsizeselect,formatselect,bold,italic,underline,strikethrough,separator,sub,sup,separator,cut,copy,paste,pasteword,undo,redo",
	    theme_advanced_buttons2 : "justifyleft,justifycenter,justifyright,justifyfull,separator,numlist,bullist,outdent,indent,separator,forecolor,backcolor,separator,hr,anchor,link,unlink,charmap,image,"+((fileBrowserCallBackFunc != null)?"attach,":"") + "table"+(document.documentElement.clientWidth<900?"":",tablecontrols,separator")+",code,separator,asciimath,asciimathcharmap,asciisvg",
	    theme_advanced_buttons3 : "",
	    theme_advanced_fonts : "Arial=arial,helvetica,sans-serif,Courier New=courier new,courier,monospace,Georgia=georgia,times new roman,times,serif,Tahoma=tahoma,arial,helvetica,sans-serif,Times=times new roman,times,serif,Verdana=verdana,arial,helvetica,sans-serif",
	    theme_advanced_toolbar_location : "top",
	    theme_advanced_toolbar_align : "left",
	    theme_advanced_statusbar_location : "bottom",
	    theme_advanced_source_editor_height: "500",
	    plugins : 'asciimath,asciisvg,dataimage,table,inlinepopups,paste,media,advlist'+((fileBrowserCallBackFunc != null)?",attach":""),
	    gecko_spellcheck : true,
	    extended_valid_elements : 'iframe[src|width|height|name|align],param[name|value],@[sscr]',
	    content_css : imasroot+(cssmode==1?'/assessment/mathtest.css,':'/imascore.css,')+imasroot+'/themes/'+coursetheme,
	    popup_css_add : imasroot+'/themes/'+coursetheme,
	    theme_advanced_resizing : true,
	    table_styles: "Gridded=gridded;Gridded Centered=gridded centered",
	    cleanup_callback : "imascleanup",
	    convert_urls: false,
	    AScgiloc : imasroot+'/filter/graph/svgimg.php',
	    ASdloc : imasroot+'/javascript/d.svg',
	    file_browser_callback : fileBrowserCallBackFunc
	}
	if (edmode=="exact") {
		edsetup.elements = edids
	} else if (edmode=="textareas") {
		edsetup.editor_selector = edids;
	}
	for (var i in tinyMCE.editors) {
		tinyMCE.editors[i].remove();
	}
	tinyMCE.init(edsetup);	
}

function fileBrowserCallBack(field_name, url, type, win) {
	var connector = imasroot+"/editor/file_manager.php";
	my_field = field_name;
	my_win = win;
	switch (type) {
		case "image":
			connector += "?type=img";
			break;
		case "file":
			connector += "?type=files";
			break;
	}
	tinyMCE.activeEditor.windowManager.open({
		file : connector,
		title : 'File Manager',
		width : 350,  
		height : 430,
		resizable : "yes",
		inline : "yes",  
		close_previous : "no"
	    }, {
		window : win,
		input : field_name
	    });

	//window.open(connector, "file_manager", "modal,width=450,height=440,scrollbars=1");
}
function imascleanup(type, value) {
	if (type=="get_from_editor") {
		//value = value.replace(/[\x84\x93\x94]/g,'"');
		//var rl = '\u2122,<sup>TM</sup>,\u2026,...,\u201c|\u201d,",\u2018|\u2019,\',\u2013|\u2014|\u2015|\u2212,-'.split(',');
		//for (var i=0; i<rl.length; i+=2) {
		//	value = value.replace(new RegExp(rl[i], 'gi'), rl[i+1]);
		//}
		value = value.replace(/<!--([\s\S]*?)-->|&lt;!--([\s\S]*?)--&gt;|<style>[\s\S]*?<\/style>/g, "");  // Word comments
		value = value.replace(/class="?Mso\w+"?/g,'');
		value = value.replace(/<p\s*>\s*<\/p>/gi,'');
		value = value.replace(/<script.*?\/script>/gi,'');
		value = value.replace(/<input[^>]*button[^>]*>/gi,'');
	}
	return value;
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) == 0) return unescape(c.substring(nameEQ.length, c.length));
  }
  return null;
}

function selectByDivID(el) {
	var v = el.value;
	var c = el.value.split(":")[0];
	var els = document.getElementsByTagName("div");
	for (var i=0;i<els.length;i++) {
		if (els[i].className.match(c)) {
			els[i].style.display = (els[i].id==v)?"block":"none";
		}
	}
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + 365);
	document.cookie = c+"store"+"="+escape(v) + ";expires="+exdate.toGMTString();
}
function setselectbycookie() {
	var els = document.getElementsByTagName("select");	
	for (var i=0;i<els.length;i++) {
		if (els[i].className.match("alts")) {
			var cl = els[i].className.replace(/alts/,'').replace(/\s/g,'');
			if ((co = readCookie(cl+"store"))!=null) { //has cookie
				co = co.replace('store','');
				els[i].value = co;
				selectByDivID(els[i]);
			}
		}
	}
}
addLoadEvent(setselectbycookie);

var recordedunload = false;
function recclick(type,typeid,info,txt) {
	if (cid>0) {
		var extradata = '',m;
		if ((m = window.location.href.match(/showlinkedtext.*?&id=(\d+)/)) !== null && recordedunload==false) {
			extradata = '&unloadinglinked='+m[1];
			recordedunload = true;
		}
		jQuery.ajax({
			type: "POST",
			url: imasroot+'/course/rectrack.php?cid='+cid,
			data: "type="+encodeURIComponent(type)+"&typeid="+encodeURIComponent(typeid)+"&info="+encodeURIComponent(info+'::'+txt)+extradata
		});
	}			
}
function setuptracklinks(i,el) {
	if (jQuery(el).attr("data-base")) {
		jQuery(el).click(function(e) {
			var inf = jQuery(this).attr('data-base').split('-');
			recclick(inf[0], inf[1], jQuery(this).attr("href"), jQuery(this).text());
			if (typeof(jQuery(el).attr("target"))=="undefined") {
				e.preventDefault();
				setTimeout('window.location.href = "'+jQuery(this).attr('href')+'"',100);
				return false;
			}
		}).mousedown(function(e) {
			if (e.which==3) { //right click
				var inf = jQuery(this).attr('data-base').split('-');
				recclick(inf[0], inf[1], jQuery(this).attr("href"), jQuery(this).text());
			}
		});
	}
}
var videoembedcounter = 0;
function togglevideoembed() {
	var id = this.id.substr(13);
	var els = jQuery('#videoiframe'+id);
	if (els.length>0) {
		if (els.css('display')=='none') {
			els.show();
			els.parent('.fluid-width-video-wrapper').show();
			jQuery(this).text(' [-]');
			jQuery(this).attr('title',_("Hide video"));
		} else {
			els.hide();
			els.get(0).contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}','*');
			els.parent('.fluid-width-video-wrapper').hide();
			jQuery(this).text(' [+]');
			jQuery(this).attr('title',_("Watch video here"));
		}
	} else {
		var href = jQuery(this).prev().attr('href');
		var qsconn = '?';
		if (href.match(/youtube\.com/)) {
			if (href.indexOf('playlist?list=')>-1) {
				var vidid = href.split('list=')[1].split(/[#&]/)[0];
				var vidsrc = 'www.youtube.com/embed/videoseries?list=';
				qsconn = '&'
			} else {
				var vidid = href.split('v=')[1].split(/[#&]/)[0];
				var vidsrc = 'www.youtube.com/embed/';
			}
		} else if (href.match(/youtu\.be/)) {
			var vidid = href.split('.be/')[1].split(/[#&]/)[0];
			var vidsrc = 'www.youtube.com/embed/';
		} else if (href.match(/vimeo/)) {
			var vidid = href.split('.com/')[1].split(/[#&]/)[0];
			var vidsrc = 'player.vimeo.com/video/';
		}
		var m = href.match(/.*\Wt=((\d+)m)?((\d+)s)?.*/);
		if (m == null) {
			var timeref = qsconn+'rel=0';
			m = href.match(/.*start=(\d+)/);
			if (m != null) {
				timeref += '&start='+m[1];
			}
		} else {
			var timeref = qsconn+'rel=0&start='+((m[2]?m[2]*60:0) + (m[4]?m[4]*1:0));
		}
		m = href.match(/.*end=(\d+)/);
		if (m != null) {
			timeref += '&end='+m[1];
		}
		timeref += '&enablejsapi=1';
		jQuery('<iframe/>', {
			id: 'videoiframe'+id,
			width: 640,
			height: 400,
			src: location.protocol+'//'+vidsrc+vidid+timeref,
			frameborder: 0,
			allowfullscreen: 1
		}).insertAfter(jQuery(this));
		jQuery(this).parent().fitVids();
		jQuery('<br/>').insertAfter(jQuery(this));
		jQuery(this).text(' [-]');
		jQuery(this).attr('title',_("Hide video"));
		if (jQuery(this).prev().attr("data-base")) {
			var inf = jQuery(this).prev().attr('data-base').split('-');
			recclick(inf[0], inf[1], href, jQuery(this).prev().text());
		}
	}	
}
function setupvideoembeds(i,el) {
	
	jQuery('<span/>', {
		text: " [+]",
		title: _("Watch video here"),
		id: 'videoembedbtn'+videoembedcounter,
		click: togglevideoembed,
		"class": "videoembedbtn"
	}).insertAfter(el);
	videoembedcounter++;
}

function addmultiselect(el,n) {
	var p = jQuery(el).parent();
	var val = jQuery('#'+n).val();
	var txt = jQuery('#'+n+' option[value='+val+']').prop('disabled',true).html();
	if (val != 'null') {
		p.append('<div class="multiselitem"><span class="right"><a href="#" onclick="removemultiselect(this);return false;">Remove</a></span><input type="hidden" name="'+n+'[]" value="'+val+'"/>'+txt+'</div>');
	}
	jQuery('#'+n).val('null');
}
function removemultiselect(el) {
	var p = jQuery(el).parent().parent();
	var val = p.find('input').val();
	p.parent().find('option[value='+val+']').prop('disabled',false);
	p.remove();
}

function hidefromcourselist(el,cid) {
	if (confirm("Are you SURE you want to hide this course from your course list?")) {
		jQuery.ajax({
				type: "GET",
				url: imasroot+'/admin/hidefromcourselist.php?cid='+cid
		}).done(function(msg) {
			if (msg=='OK') {
				jQuery(el).parent().slideUp();
				jQuery('#unhidelink').show();
			}
		});
	}
}

jQuery(document).ready(function($) {
	$(window).on("message", function(e) {
		if (e.originalEvent.data.match(/lti\.frameResize/)) {
			var edata = JSON.parse(e.originalEvent.data);
			var frames = document.getElementsByTagName('iframe');
			for (var i = 0; i < frames.length; i++) {
			    if (frames[i].contentWindow === e.originalEvent.source) {
				$(frames[i]).height(edata.height); //the height sent from iframe
				break;
			    }
			}
		}
	});
});

jQuery(document).ready(function($) {
	$('a').each(setuptracklinks);	
	$('a[href*="youtu"]').each(setupvideoembeds);
	$('a[href*="vimeo"]').each(setupvideoembeds);	
	$('body').fitVids();
	$('a[target="_blank"]').each(function() {
		if (!this.href.match(/youtu/) && !this.href.match(/vimeo/)) {
		   $(this).append(' <img src="'+imasroot+'/img/extlink.png"/>')
		}
	});
});

jQuery.fn.isolatedScroll = function() {
    this.bind('mousewheel DOMMouseScroll', function (e) {
        var delta = e.wheelDelta || (e.originalEvent && e.originalEvent.wheelDelta) || -e.detail,
            bottomOverflow = this.scrollTop + jQuery(this).outerHeight() - this.scrollHeight >= 0,
            topOverflow = this.scrollTop <= 0;

        if ((delta < 0 && bottomOverflow) || (delta > 0 && topOverflow)) {
            e.preventDefault();
        }
    });
    return this;
};

jQuery(document).ready(function($) {
	var fixedonscrollel = $('.fixedonscroll');
	var initialtop = [];
	for (var i=0;i<fixedonscrollel.length;i++) {
		initialtop[i] = $(fixedonscrollel[i]).offset().top;
		if ($(fixedonscrollel[i]).height()>$(window).height()) { //skip if element is taller than window
			initialtop[i] = -1;
		}
	}
	if (fixedonscrollel.length>0) {
		$(window).scroll(function() {
			var winscrolltop = $(window).scrollTop();
			for (var i=0;i<fixedonscrollel.length;i++) {
				if (winscrolltop > initialtop[i] && initialtop[i]>0) {
					$(fixedonscrollel[i]).css('position','fixed').css('top','5px');
				} else {
					$(fixedonscrollel[i]).css('position','static');
				}
			}
		});
	}
});


function _(txt) {
	if (typeof i18njs != "undefined" && i18njs[txt]) {
		var outtxt = i18njs[txt];
	} else {
		var outtxt = txt;
	}
	if (arguments.length>1) {
		for (var i=1;i<arguments.length;i++) {
			outtxt = outtxt.replace('$'+i,arguments[i]);
		}
	}
	return outtxt;
}

//https://github.com/davatron5000/FitVids.js
(function( $ ){

  "use strict";

  $.fn.fitVids = function( ) {
  
    return this.each(function(){
      var selectors = [
        "iframe[src*='player.vimeo.com']",
        "iframe[src*='youtube.com']",
        "iframe[src*='youtube-nocookie.com']"
      ];

      var $allVideos = $(this).find(selectors.join(','));
      
      $allVideos.each(function(){
        var $this = $(this);
        $this.parentsUntil(".intro","table").each(function() {
        	$(this).css('width','100%');
        });
        var height = ($this.attr('height') && !isNaN(parseInt($this.attr('height'), 10))) ? parseInt($this.attr('height'), 10) : $this.height(),
            width = !isNaN(parseInt($this.attr('width'), 10)) ? parseInt($this.attr('width'), 10) : $this.width(),
            aspectRatio = height / width;
       
        if(!$this.attr('id')){
          var videoID = 'fitvid' + Math.floor(Math.random()*999999);
          $this.attr('id', videoID);
        }
        $this.wrap('<div class="fluid-width-video-wrapper"></div>').parent('.fluid-width-video-wrapper').css('padding-top', (aspectRatio * 100)+"%")
        	.wrap('<div class="video-wrapper-wrapper"></div>').parent('.video-wrapper-wrapper').css('max-width',width+'px');
        $this.removeAttr('height').removeAttr('width').css('height','').css('width','');
      });
    });
  };
// Works with either jQuery or Zepto
})( window.jQuery || window.Zepto );

//code for alt selectors
function setAltSelectors(group,val) {
	console.log("looking for "+group);
	$(".alts."+group).parents(".altWrap").find(".altContentOn").removeClass("altContentOn").addClass("altContentOff");
	$(".alts."+group).parents(".altWrap").find("."+val).addClass("altContentOn").removeClass("altContentOff");	
	$("select.alts."+group).val(group+":"+val);
	
	var exdate=new Date();
	exdate.setDate(exdate.getDate() + 365);
	document.cookie = 'alt_store_'+group+"="+escape(val) + ";expires="+exdate.toGMTString()+ ";path=/";
}
jQuery(document).ready(function($) {
	$(".alts").on('change', function() {
		var groupValue = this.value.split(':');
		if (groupValue.length > 1) {
			setAltSelectors(groupValue[0],groupValue[1]);
		}
	}).each(function (i,el) {
		var groupValue = el.value.split(':');
		if ((co = readCookie('alt_store_'+groupValue[0]))!=null) { //has cookie
			setAltSelectors(groupValue[0], co);	
		} else if ($(el).hasClass("setDefault")) {
			setAltSelectors(groupValue[0], groupValue[1]);
		}
	});
	$(document).on("keydown", function (e) {
	    if (e.which === 8 && !$(e.target).is("input[type='text']:not([readonly]),input[type='password']:not([readonly]), textarea")) {
		e.preventDefault();
	    }
	});
	
});

/* ========================================================================
 * Bootstrap: dropdown.js v3.3.5
 * http://getbootstrap.com/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2015 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 * ======================================================================== */


+function ($) {
  'use strict';

  // DROPDOWN CLASS DEFINITION
  // =========================

  var backdrop = '.dropdown-backdrop'
  var toggle   = '[data-toggle="dropdown"]'
  var Dropdown = function (element) {
    $(element).on('click.bs.dropdown', this.toggle)
  }

  Dropdown.VERSION = '3.3.5'

  function getParent($this) {
    var selector = $this.attr('data-target')

    if (!selector) {
      selector = $this.attr('href')
      selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
    }

    var $parent = selector && $(selector)

    return $parent && $parent.length ? $parent : $this.parent()
  }

  function clearMenus(e) {
    if (e && e.which === 3) return
    $(backdrop).remove()
    $(toggle).each(function () {
      var $this         = $(this)
      var $parent       = getParent($this)
      var relatedTarget = { relatedTarget: this }

      if (!$parent.hasClass('open')) return

      if (e && e.type == 'click' && /input|textarea/i.test(e.target.tagName) && $.contains($parent[0], e.target)) return

      $parent.trigger(e = $.Event('hide.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this.attr('aria-expanded', 'false')
      $parent.removeClass('open').trigger('hidden.bs.dropdown', relatedTarget)
    })
  }

  Dropdown.prototype.toggle = function (e) {
    var $this = $(this)

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    clearMenus()

    if (!isActive) {
      if ('ontouchstart' in document.documentElement && !$parent.closest('.navbar-nav').length) {
        // if mobile we use a backdrop because click events don't delegate
        $(document.createElement('div'))
          .addClass('dropdown-backdrop')
          .insertAfter($(this))
          .on('click', clearMenus)
      }

      var relatedTarget = { relatedTarget: this }
      $parent.trigger(e = $.Event('show.bs.dropdown', relatedTarget))

      if (e.isDefaultPrevented()) return

      $this
        .trigger('focus')
        .attr('aria-expanded', 'true')

      $parent
        .toggleClass('open')
        .trigger('shown.bs.dropdown', relatedTarget)
    }

    return false
  }

  Dropdown.prototype.keydown = function (e) {
    if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) return

    var $this = $(this)

    e.preventDefault()
    e.stopPropagation()

    if ($this.is('.disabled, :disabled')) return

    var $parent  = getParent($this)
    var isActive = $parent.hasClass('open')

    if (!isActive && e.which != 27 || isActive && e.which == 27) {
      if (e.which == 27) $parent.find(toggle).trigger('focus')
      return $this.trigger('click')
    }

    var desc = ' li:not(.disabled):visible a'
    var $items = $parent.find('.dropdown-menu' + desc)

    if (!$items.length) return

    var index = $items.index(e.target)

    if (e.which == 38 && index > 0)                 index--         // up
    if (e.which == 40 && index < $items.length - 1) index++         // down
    if (!~index)                                    index = 0

    $items.eq(index).trigger('focus')
  }


  // DROPDOWN PLUGIN DEFINITION
  // ==========================

  function Plugin(option) {
    return this.each(function () {
      var $this = $(this)
      var data  = $this.data('bs.dropdown')

      if (!data) $this.data('bs.dropdown', (data = new Dropdown(this)))
      if (typeof option == 'string') data[option].call($this)
    })
  }

  var old = $.fn.dropdown

  $.fn.dropdown             = Plugin
  $.fn.dropdown.Constructor = Dropdown


  // DROPDOWN NO CONFLICT
  // ====================

  $.fn.dropdown.noConflict = function () {
    $.fn.dropdown = old
    return this
  }


  // APPLY TO STANDARD DROPDOWN ELEMENTS
  // ===================================

  $(document)
    .on('click.bs.dropdown.data-api', clearMenus)
    .on('click.bs.dropdown.data-api', '.dropdown form', function (e) { e.stopPropagation() })
    .on('click.bs.dropdown.data-api', toggle, Dropdown.prototype.toggle)
    .on('keydown.bs.dropdown.data-api', toggle, Dropdown.prototype.keydown)
    .on('keydown.bs.dropdown.data-api', '.dropdown-menu', Dropdown.prototype.keydown)

}(jQuery);

