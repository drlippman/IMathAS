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

var tipobj = 0; var curtipel = null;
function tipshow(el,tip) {
	if (typeof tipobj!= 'object') {
		tipobj = document.createElement("div");
		tipobj.className = "tips";
		tipobj.setAttribute("role","tooltip");
		tipobj.id = "hovertipsholder";
		document.getElementsByTagName("body")[0].appendChild(tipobj);
	}
	curtipel = el;
	if (el.hasAttribute("data-tip")) {
		tipobj.innerHTML = el.getAttribute("data-tip");
	} else {
		tipobj.innerHTML = tip;
	}
	tipobj.style.left = "5px";
	tipobj.style.display = "block";
	tipobj.setAttribute("aria-hidden","false");
	el.setAttribute("aria-describedby", "hovertipsholder");

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
        if ((p[0] + tipobj.offsetWidth)>x-10) {
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
	tipobj.setAttribute("aria-hidden","true");
	if (curtipel) {
		curtipel.removeAttribute("aria-describedby");
	}
	curtipel = null;
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
		return false;
	} else if (target.className == 'submitted2') {
		e.preventDefault ? e.preventDefault() : e.returnValue = false;
		return false;
	} else {
		target.className = 'submitted';
		return true;
	}
}
function setupFormLimiters() {
	var el = document.getElementsByTagName("form");
	for (var i=0;i<el.length;i++) {
		if (typeof el[i].onsubmit != 'function' && el[i].className!="nolimit" && el[i].className!="limitaftervalidate") {
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
		gb_window.setAttribute("aria-role","dialog");
		gb_window.setAttribute("aria-labelledby","GB_caption");
		gb_window.setAttribute("tabindex",-1);
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder" ></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
	}
	document.getElementById("GB_frameholder").innerHTML = '<iframe onload="GB_doneload()" id="GB_frame" src="'+url+'"></iframe>';
	jQuery("#GB_frameholder").isolatedScroll();
	if (url.match(/libtree/)) {
		var btnhtml = '<span class="floatright"><input type="button" value="Use Libraries" onClick="document.getElementById(\'GB_frame\').contentWindow.setlib()" /> ';
		btnhtml += '<a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a>&nbsp;</span>Select Libraries<div class="clear"></div>';
		document.getElementById("GB_caption").innerHTML = btnhtml;
		var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	} else {
		document.getElementById("GB_caption").innerHTML = '<span class="floatright"><a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a></span>'+caption;
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

	//var de = document.documentElement;
	//var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var w = $(document).width();
	if (width > w-20) {
		width = w-20;
	}
	document.getElementById("GB_window").style.width = width + "px";
	document.getElementById("GB_window").style.height = (h-30) + "px";
	//document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	document.getElementById("GB_frame").style.height = (h - 30 -36)+"px";

	document.getElementById("GB_window").focus();
	$(document).on('keydown.GB', function(evt) {
		if (evt.keyCode == 27) {
			GB_hide();
		}
	});
}
function GB_doneload() {
	document.getElementById("GB_loading").style.display = "none";
}
function GB_hide() {
	document.getElementById("GB_window").style.display = "none";
	document.getElementById("GB_overlay").style.display = "none";
	$(document).off('keydown.GB');
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

var tinyMCEPreInit = {base: imasroot+"/tinymce4"};
function initeditor(edmode,edids,css,inline,setupfunction){
	var cssmode = css || 0;
	var inlinemode = inline || 0;
	var selectorstr = '';
	if (edmode=="exact") { //list of IDs
		selectorstr = '#'+edids.split(/,/).join(",#");
	} else if (edmode=="textareas") { //class-based selection
		selectorstr = "textarea."+edids;
	} else if (edmode=="divs") { //class-based selection
		selectorstr = "div."+edids;
	} else if (edmode=="selector") { //flexible selector
		selectorstr = edids;
	}
	var edsetup = {
		selector: selectorstr,
		inline: inlinemode,
		plugins: [
			"lists advlist autolink attach image charmap anchor",
			"searchreplace code link textcolor snippet",
			"media table paste asciimath asciisvg rollups colorpicker"
		],
		menubar: false,//"edit insert format table tools ",
		toolbar1: "myEdit myInsert styleselect | bold italic underline subscript superscript | forecolor backcolor | snippet code | saveclose",
		toolbar2: " alignleft aligncenter alignright | bullist numlist outdent indent  | attach link unlink image | table | asciimath asciimathcharmap asciisvg",
		extended_valid_elements : 'iframe[src|width|height|name|align|allowfullscreen|frameborder],param[name|value],@[sscr]',
		content_css : imasroot+(cssmode==1?'/assessment/mathtest.css,':'/imascore.css,')+imasroot+'/themes/'+coursetheme,
		AScgiloc : imasroot+'/filter/graph/svgimg.php',
		convert_urls: false,
		file_picker_callback: filePickerCallBackFunc,
		file_picker_types: 'file image',
		//imagetools_cors_hosts: ['s3.amazonaws.com'],
		images_upload_url: imasroot+'/tinymce4/upload_handler.php',
		//images_upload_credentials: true,
		paste_data_images: true,
		default_link_target: "_blank",
		browser_spellcheck: true,
		branding: false,
		resize: "both",
		width: '100%',
		content_style: "body {background-color: #ffffff !important;}",
		table_class_list: [{title: "None", value:''},
			{title:"Gridded", value:"gridded"},
			{title:"Gridded Centered", value:"gridded centered"}],
		style_formats_merge: true,
		snippets: (tinymceUseSnippets==1)?imasroot+'/tinymce4/getsnippets.php':false,
		style_formats: [{
			title: "Font Family",
			items: [
			    {title: 'Arial', inline: 'span', styles: { 'font-family':'arial'}},
			    {title: 'Book Antiqua', inline: 'span', styles: { 'font-family':'book antiqua'}},
			    {title: 'Comic Sans MS', inline: 'span', styles: { 'font-family':'comic sans ms,sans-serif'}},
			    {title: 'Courier New', inline: 'span', styles: { 'font-family':'courier new,courier'}},
			    {title: 'Georgia', inline: 'span', styles: { 'font-family':'georgia,palatino'}},
			    {title: 'Helvetica', inline: 'span', styles: { 'font-family':'helvetica'}},
			    {title: 'Impact', inline: 'span', styles: { 'font-family':'impact,chicago'}},
			    {title: 'Open Sans', inline: 'span', styles: { 'font-family':'Open Sans'}},
			    {title: 'Symbol', inline: 'span', styles: { 'font-family':'symbol'}},
			    {title: 'Tahoma', inline: 'span', styles: { 'font-family':'tahoma'}},
			    {title: 'Terminal', inline: 'span', styles: { 'font-family':'terminal,monaco'}},
			    {title: 'Times New Roman', inline: 'span', styles: { 'font-family':'times new roman,times'}},
			    {title: 'Verdana', inline: 'span', styles: { 'font-family':'Verdana'}}
			]
			},
			{title: "Font Size", items: [
                                {title: 'x-small', inline:'span', styles: { fontSize: 'x-small', 'font-size': 'x-small' } },
                                {title: 'small', inline:'span', styles: { fontSize: 'small', 'font-size': 'small' } },
                                {title: 'medium', inline:'span', styles: { fontSize: 'medium', 'font-size': 'medium' } },
                                {title: 'large', inline:'span', styles: { fontSize: 'large', 'font-size': 'large' } },
                                {title: 'x-large', inline:'span', styles: { fontSize: 'x-large', 'font-size': 'x-large' } },
                                {title: 'xx-large', inline:'span', styles: { fontSize: 'xx-large', 'font-size': 'xx-large' } }
                        ]
                }]
        }
	if (document.documentElement.clientWidth<385) {
		edsetup.toolbar1 = "myEdit myInsert styleselect | bold italic underline | saveclose";
		edsetup.toolbar2 = "bullist numlist outdent indent  | link image | asciimath asciisvg";
	} else if (document.documentElement.clientWidth<465) {
		edsetup.toolbar1 = "myEdit myInsert styleselect | bold italic underline forecolor | saveclose";
		edsetup.toolbar2 = "bullist numlist outdent indent  | link unlink image | asciimath asciisvg";
	} else if (document.documentElement.clientWidth<575) {
		edsetup.toolbar1 = "myEdit myInsert styleselect | bold italic underline subscript superscript | forecolor | saveclose";
		edsetup.toolbar2 = " alignleft aligncenter | bullist numlist outdent indent  | link unlink image | asciimath asciimathcharmap asciisvg";
	}
	if (setupfunction) {
		edsetup.setup = setupfunction;
	}
	//for (var i in tinymce.editors) {
	//	tinymce.editors[i].remove();
	//}
	tinymce.remove();
	tinymce.init(edsetup);

};

function filePickerCallBack(callback, value, meta) {
	var connector = imasroot+"/tinymce4/file_manager.php";

	switch (meta.filetype) {
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
		height : 450,
		resizable : "yes",
		inline : "yes",
		close_previous : "no"
	    }, {
		oninsert: function(url, objVal) {
			callback(url);
		}
	    });
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
			} else if (href.match(/\/embed\//)) {
				var vidid = href.split("/embed/")[1].split(/[#&\?]/)[0];
				var vidsrc = 'www.youtube.com/embed/';
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
		var loc_protocol = location.protocol == 'https:' ? 'https:' : 'http:';
		jQuery('<iframe/>', {
			id: 'videoiframe'+id,
			width: 640,
			height: 400,
			src: loc_protocol+'//'+vidsrc+vidid+timeref,
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

function addNoopener(i,el) {
	if (!el.rel && el.target && el.host !== window.location.host) {
		el.setAttribute("rel", "noopener noreferrer");
	}
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

function hidefromcourselist(el,cid,type) {
	if (confirm("Are you SURE you want to hide this course from your course list?")) {
		jQuery.ajax({
				type: "GET",
				url: imasroot+'/admin/hidefromcourselist.php?tohide='+cid+'&type='+type
		}).done(function(msg) {
			if (msg=='OK') {
				jQuery(el).closest("ul.courselist > li").slideUp();
				jQuery('#unhidelink'+type).show();
			}
		});
	}
	return false;
}
function removeSelfAsCoteacher(el,cid,selector,uid) {
	selector = selector || "ul.courselist > li";
	uid = uid || null;
	if (confirm("Are you SURE you want to remove yourself as a co-teacher on this course?")) {
		jQuery.ajax({
			type: "POST",
			url: imasroot+'/admin/actions.php',
			data: {action: "removeself", id: cid, uid: uid}
		}).done(function(msg) {
			if (msg=='OK') {
				jQuery(el).closest(selector).slideUp();
			}
		});
	}
	return false;
}

function rotateimg(el) {
	if ($(el).data('rotation')) {
		var r = ($(el).data('rotation') + 90)%360;
	} else {
		var r = 90;
	}
	$(el).data('rotation', r).css({transform: 'rotate('+r+'deg)'});
	if (r%180==90) {
		var d = ($(el).width() - $(el).height())/2;
		if (d>0) {
			$(el).parent().css({'padding-top':d, 'padding-bottom':d});
		}
	} else {
		$(el).parent().css({'padding-top':0, 'padding-bottom':0});
	}
}

jQuery(document).ready(function($) {
	$(window).on("message", function(e) {
		if (typeof e.originalEvent.data=='string' && e.originalEvent.data.match(/lti\.frameResize/)) {
			var edata = JSON.parse(e.originalEvent.data);
			if ("frame_id" in edata) {
				$("#"+edata["frame_id"]).height(edata.height);
			} else if ("iframe_resize_id" in edata) {
				$("#"+edata["iframe_resize_id"]).height(edata.height);
			} else {
				var frames = document.getElementsByTagName('iframe');
				for (var i = 0; i < frames.length; i++) {
				    if (frames[i].contentWindow === e.originalEvent.source) {
					$(frames[i]).height(edata.height); //the height sent from iframe
					break;
				    }
				}
			}
		} else if (typeof e.originalEvent.data=='string' && e.originalEvent.data.match(/\[iFrameSizer\]/)) {
			var edata = e.originalEvent.data.substr("[iFrameSizer]".length).split(":");
			$("#"+edata[0]).height(edata[1]);
		} else if (typeof e.originalEvent.data=='string' && e.originalEvent.data.match(/imathas\.update/)) {
			var edata = JSON.parse(e.originalEvent.data);
			if ("qn" in edata) {
				var qn = edata['qn'].replace(/[^\d]/, "");
				if (qn != "") {
					$("#qn"+qn).val(edata['value']);
				}
			} else {
				var id = edata['id'].replace(/[^\w\-]/, "");
				if ($("#"+id).hasClass("allowupdate")) {
					$("#"+id).val(edata['value']);
				}
			}
		}
	});
});

jQuery(document).ready(function($) {
	$('a').each(setuptracklinks).each(addNoopener);
	$('a[href*="youtu"]').each(setupvideoembeds);
	$('a[href*="vimeo"]').each(setupvideoembeds);
	$('body').fitVids();
});
jQuery(document).ready(function() {
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
});

jQuery(document).ready(function($) {
	var fixedonscrollel = $('.fixedonscroll');
	var initialtop = [];
	for (var i=0;i<fixedonscrollel.length;i++) {
		initialtop[i] = $(fixedonscrollel[i]).offset().top;
		if ($(fixedonscrollel[i]).height()>$(window).height()) { //skip if element is taller than window
			initialtop[i] = -1;
		}
	}
	if (fixedonscrollel.length>0 && $(fixedonscrollel[0]).css('float')=="left") {
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
        if ($this.closest(".textsegment").length>0) {return true;}
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
	    if (e.which === 8 && !$(e.target).is("input[type='text']:not([readonly]),input:not([type]):not([readonly]),input[type='password']:not([readonly]), textarea, [contenteditable='true']")) {
		e.preventDefault();
	    }
	});
	$("div.breadcrumb").attr("role","navigation").attr("aria-label",_("Navigation breadcrumbs"));
	$("div.cpmid,div.cp").attr("role","group").attr("aria-label",_("Control link group"));
	if ($("#centercontent").length) {
		$("#centercontent").attr("role","main");
		$(".midwrapper").removeAttr("role");
	}
});

//setup mobile nav menu, if exists
jQuery(document).ready(function($) {
	function toggleHeaderMobileMenuList(e) {
		var list = $("#headermobilemenulist");
		if (list.attr("aria-hidden")=="true") { //expand it
			$("#headermobilemenulist").slideDown(50, function() {
				$("#headermobilemenulist").addClass("menuexpanded").removeAttr("style");
				list.attr("aria-hidden",false);
				$("#topnavmenu").attr("aria-expanded",true);
			});
			$("#navlist").slideDown(100, function() {
				$("#navlist").addClass("menuexpanded").removeAttr("style");
			});
		} else { //collapse it
			$("#navlist").slideUp(100, function() {
				$("#navlist").removeClass("menuexpanded").removeAttr("style");
			});
			$("#headermobilemenulist").slideUp(50, function() {
				$("#headermobilemenulist").removeClass("menuexpanded").removeAttr("style");
				list.attr("aria-hidden",true);
				$("#topnavmenu").attr("aria-expanded",false);
			});
		}
		e.preventDefault();
	}
	$("#topnavmenu").on("click", toggleHeaderMobileMenuList)
	   .on("keydown", function(e) { if (e.which===13 || e.which==32) { toggleHeaderMobileMenuList(e);}});
});
var sagecellcounter = 0;
function initSageCell(base) {
	jQuery(base).find(".converttosagecell").each(function() {
		var ta, code;
		var $this = jQuery(this);
		if ($this.is("pre")) {
			ta = this;
			code = jQuery(ta).html().replace(/<br\s*\/?>/g,"\n").replace(/<\/?[a-zA-Z][^>]*>/g,'');
		} else {
			ta = $this.find("textarea");
			if (ta.length==0 || jQuery(ta[0]).val()=="") {
				if ($this.find("pre").length>0) {
					code = $this.find("pre").html().replace(/<br\s*\/?>/g,"\n").replace(/<\/?[a-zA-Z][^>]*>/g,'').replace(/\n\n/g,"\n");
					if (ta.length==0) {
						ta = $this.find("pre")[0];
					} else {
						ta = ta[0];
					}
				} else {
					return false;
				}
			} else {
				code = jQuery(ta[0]).val();
				ta = ta[0];
			}
		}
		if (m = code.match(/^\s+/)) {
			var chop = m[0].length;
			var re = new RegExp('\\n\\s{'+chop+'}',"g");
			code = code.substr(chop).replace(re, "\n").replace(/\s+$/,'');
		}
		var frame_id = "sagecell-"+sagecellcounter;
		sagecellcounter++;
		var url = imasroot+'/assessment/libs/sagecellframe.html?frame_id='+frame_id;
		url += '&code='+encodeURIComponent(code);
		var returnid = null;
		if (typeof jQuery(ta).attr("id") != "undefined") {
				url += '&update_id='+jQuery(ta).attr("id");
		}
		jQuery(ta).addClass("allowupdate").hide()
		.after(jQuery("<iframe/>", {
				id: frame_id,
				class: "sagecellframe",
				style: "border:0",
				width: "100%",
				height: 100,
				src: url
		}));
	});
}
jQuery(function() {
	initSageCell("body");
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
