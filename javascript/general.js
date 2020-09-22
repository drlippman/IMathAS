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
function tipshow(el,tip, e) {
	if (typeof e != 'undefined' && e.type=='touchstart') {
		if (curtipel == el) {
			tipout();
			return false;
		}
		jQuery(document).on('touchstart.tipshow', function() {
			tipout(el);
		});
	}
	if (curtipel==el) {
		return;
	}
	if (typeof tipobj!= 'object') {
        tipobj = document.createElement("div");
        if (window.imathasAssess) {
            tipobj.className = "dropdown-pane tooltip-pane";
        } else {
            tipobj.className = "tips";
        }
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
	//tipobj.style.display = "block";
	$(tipobj).stop(true,true).fadeIn(100);
	tipobj.setAttribute("aria-hidden","false");
	el.setAttribute("aria-describedby", "hovertipsholder");

	if (typeof usingASCIIMath!='undefined' && typeof noMathRender != 'undefined') {
		if (usingASCIIMath && !noMathRender && tipobj.innerHTML.indexOf('`')!=-1) {
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
        	$(tipobj).addClass("tipright");
        } else {
        	$(tipobj).removeClass("tipright");
        }

	tipobj.style.left = (p[0]+15) + "px";
	if (p[1] < 30) {
		tipobj.style.top = (p[1]+20) + "px";
	} else {
		tipobj.style.top = (p[1]-tipobj.offsetHeight) + "px";
	}
}

function tipout(e) {
	jQuery(document).off('touchstart.tipshow');
	//tipobj.style.display = "none";
	$(tipobj).fadeOut(100);
	tipobj.setAttribute("aria-hidden","true");
	if (curtipel) {
		curtipel.removeAttribute("aria-describedby");
	}
	curtipel = null;
}
jQuery(function() {
	jQuery(document).on('keyup', function(e) {
		if (e.which == 27 && curtipel !== null) {
			tipout();
		}
	})
});

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
		if (typeof el[i].onsubmit != 'function' && !el[i].className.match(/(nolimit|limitaftervalidate)/)) {
			$(el[i]).on('submit',submitlimiter);
		}
	}
}
addLoadEvent(setupFormLimiters);

function GB_move(e) {
	e.preventDefault();
	if (e.type == 'touchmove') {
		var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
		var X = touch.pageX;
		var Y = touch.pageY;
	} else {
		X = e.pageX;
		Y = e.pageY;
	}
	var gbwin = jQuery("#GB_window");

	gbwin.css("left", Math.max(0,gbwin.data("original_x") + (X - gbwin.data("original_mouse_x"))))
	 .css("top", Math.max(0,gbwin.data("original_y") + (Y - gbwin.data("original_mouse_y"))));
}
function GB_drop(e) {
	jQuery(window).off("touchmove.GBmove touchend.GBmove mousemove.GBmove mouseup.GBmove");
	jQuery("#GB_frameoverlay").remove();
	jQuery("body").css("user-select","");
}
function GB_resize(e) {
	e.preventDefault();
	if (e.type == 'touchmove') {
		var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
		var X = touch.pageX;
		var Y = touch.pageY;
	} else {
		X = e.pageX;
		Y = e.pageY;
	}
	X = Math.max(0, Math.min(X, document.documentElement.clientWidth-5));
	Y = Math.max(0, Math.min(Y, document.documentElement.clientHeight-5));
	var gbwin = jQuery("#GB_window");
	var dx = (X - gbwin.data("original_mouse_x"));
	var dy = (Y - gbwin.data("original_mouse_y"));
	if (gbwin[0].hasAttribute("data-lockratio")) {
		var ratio = gbwin.data("original_h")/gbwin.data("original_w");
		if ((gbwin.data("original_h") + dy)/(gbwin.data("original_w") + dx) > ratio) { //too tall
			dy = ratio*(gbwin.data("original_w") + dx) - gbwin.data("original_h");
		} else {
			dx = (gbwin.data("original_h") + dy)/ratio - gbwin.data("original_w");
		}
	}

	gbwin.css("width", Math.max(0,gbwin.data("original_w") + dx))
	 .css("height", Math.max(0,gbwin.data("original_h") + dy));
	$("#GB_frameholder").css("height", Math.max(0,gbwin.data("original_h") + dy) - 36);
}
function GB_endresize(e) {
	jQuery(window).off("touchmove.GBresize touchend.GBresize mousemove.GBresize mouseup.GBresize");
	jQuery("#GB_frameoverlay").remove();
	jQuery("body").css("user-select","");
}
var GB_loaded = false;
//based on greybox redux, http://jquery.com/demo/grey/
function GB_show(caption,url,width,height,overlay,posstyle,showbelow) {
    posstyle = posstyle || '';
	if (GB_loaded == false) {
		var gb_overlay = document.createElement("div");
		gb_overlay.id = "GB_overlay";
		gb_overlay.onclick = GB_hide;
		document.getElementsByTagName("body")[0].appendChild(gb_overlay);
		var gb_window = document.createElement("div");
		gb_window.setAttribute("role","dialog");
		gb_window.setAttribute("aria-labelledby","GB_title");
		gb_window.setAttribute("tabindex",-1);
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder" ></div><div id="GB_resizehandle"></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
		jQuery("#GB_caption").on('mousedown touchstart', function(e) {
			if (e.target.nodeName.toLowerCase()=='input' || e.target.nodeName.toLowerCase()=='button'
				|| e.target.nodeName.toLowerCase()=='a') {
				return;
			}
			var gbwin = document.getElementById("GB_window");

			if (e.type == 'touchstart') {
				var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
			}
			jQuery("#GB_window").data("original_x", gbwin.getBoundingClientRect().left)
			  .data("original_y", gbwin.getBoundingClientRect().top)
			  .data("original_mouse_x", (e.type=='touchstart')?touch.pageX:e.pageX)
			  .data("original_mouse_y", (e.type=='touchstart')?touch.pageY:e.pageY)
			  .css("left", gbwin.getBoundingClientRect().left)
			  .css("top", gbwin.getBoundingClientRect().top)
			  .css("margin", 0).css("right","").css("width",$(gbwin).width());
			jQuery("#GB_window").append($("<div/>", {id: "GB_frameoverlay"}));
			jQuery("body").css("user-select","none");

			if (e.type == 'touchstart') {
				jQuery(window).on('touchmove.GBmove', GB_move)
				 .on('touchend.GBmove', GB_drop);
			} else {
				jQuery(window).on('mousemove.GBmove', GB_move)
				 .on('mouseup.GBmove', GB_drop);
			}
		});
		jQuery("#GB_resizehandle").on('mousedown touchstart', function(e) {
			if (e.type == 'touchstart') {
				var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
			}
			var gbwin = document.getElementById("GB_window");

			jQuery("#GB_window").css("left", gbwin.getBoundingClientRect().left)
			  .css("top", gbwin.getBoundingClientRect().top)
			  .css("margin", 0).css("right","")
			  .data("original_w", $(gbwin).width())
			  .data("original_h", $(gbwin).height())
			  .data("original_mouse_x", (e.type=='touchstart')?touch.pageX:e.pageX)
			  .data("original_mouse_y", (e.type=='touchstart')?touch.pageY:e.pageY);

			jQuery("#GB_window").append($("<div/>", {id: "GB_frameoverlay"}));
			jQuery("body").css("user-select","none");

			if (e.type == 'touchstart') {
				jQuery(window).on('touchmove.GBresize', GB_resize)
				 .on('touchend.GBresize', GB_endresize);
			} else {
				jQuery(window).on('mousemove.GBresize', GB_resize)
				 .on('mouseup.GBresize', GB_endresize);
			}
		});
    }
    document.getElementById("GB_loading").style.display = "block";
	if (url.charAt(0)=='<') {
		document.getElementById("GB_frameholder").innerHTML = '<div>'+url+'</div>';
		if (url.match(/data-enlarged/)) {
			jQuery("#GB_window").attr("data-lockratio", 1);
		}
		setTimeout(GB_doneload, 50);
    } else if (!document.getElementById("GB_frame") ||
        document.getElementById("GB_frame").src.replace(/\/$/,'') !=
            url.replace(/\/$/,'')
    ) {
		document.getElementById("GB_frameholder").innerHTML = '<iframe onload="GB_doneload()" id="GB_frame" src="'+url+'" title="'+caption+'"></iframe>';
	} else {
        document.getElementById("GB_loading").style.display = 'none';
    }
	jQuery("#GB_frameholder").isolatedScroll();
	if (url.match(/libtree/)) {
		var btnhtml = '<span class="floatright"><input type="button" value="Use Libraries" onClick="document.getElementById(\'GB_frame\').contentWindow.setlib()" /> ';
		btnhtml += '<a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a>&nbsp;</span><span id="GB_title">Select Libraries</span><div class="clear"></div>';
		document.getElementById("GB_caption").innerHTML = btnhtml;
		var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	} else if (url.match(/assessselect/)) {
		var btnhtml = '<span class="floatright"><input type="button" value="Use Assessments" onClick="document.getElementById(\'GB_frame\').contentWindow.setassess()" /> ';
		btnhtml += '<a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a>&nbsp;</span><span id="GB_title">Select Assessments</span><div class="clear"></div>';
		document.getElementById("GB_caption").innerHTML = btnhtml;
		var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	} else {
		document.getElementById("GB_caption").innerHTML = '<span class="floatright"><a href="#" class="pointer" onclick="GB_hide();return false;" aria-label="Close">[X]</a></span><span id="GB_title">'+caption+'</span>';
		document.getElementById("GB_caption").onclick = GB_hide;
		if (height=='auto') {
            var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
		} else {
			var h = height;
		}
	}
    document.getElementById("GB_window").style.display = "block";
    if (overlay !== false) {
        document.getElementById("GB_overlay").style.display = "block";
    } else {
        document.getElementById("GB_overlay").style.display = "none";
    }

	//var de = document.documentElement;
	//var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var w = $(document).width();
	if (width > w-20) {
		width = w-20;
    }
    if (!posstyle.match(/noreset/) || 
        !jQuery("#GB_window").data("original_mouse_x") || 
        document.getElementById("GB_window").style.left==''
    ) {
        var inittop = '';
        if (typeof showbelow == 'object') {
            var belowel;
            for (var i in showbelow) {
                if (belowel = document.getElementById(showbelow[i])) {
                    inittop = belowel.getBoundingClientRect().bottom + 10;
                    if (height=='auto') {
						h = (window.self !== window.top) ? Math.min(600,self.innerHeight) : self.innerHeight;
						h = Math.max(200, h - inittop - 20);
                    }
                    break;
                }
            }
        }
        $("#GB_window").css("margin","").css("left","").css("top",inittop);
        if (posstyle.match(/left/) && document.getElementById("GB_window").style.left=='') {
            if ($("body").hasClass("fw1000") && w > 1000) {
                width += (w - 1000)/2;
            }
            if ($("body").hasClass("fw1920") && w > 1920) {
                width += (w - 1920)/2;
            }
            $("#GB_window").css("left", width).css("width","auto").css("right",20).css("margin","0");
            width = w - width - 20;
        } else {
            document.getElementById("GB_window").style.width = width + "px";
        }
        
        document.getElementById("GB_window").style.height = (h-30) + "px";
        //document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
        if (url.charAt(0)!='<') {
            document.getElementById("GB_frameholder").style.height = (h - 30 -36)+"px";
        } else {
            document.getElementById("GB_frameholder").style.height = "auto";
        }
    }
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
	if (document.getElementById("GB_overlay")) {
		document.getElementById("GB_overlay").style.display = "none";
	}
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

var tinyMCEPreInit = {base: staticroot+"/tinymce4"};
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
			"media table paste rollups colorpicker"
        ],
        external_plugins: {
            "asciimath": imasroot+'/tinymce4/plugins/asciimath/plugin.min.js',
            "asciisvg": imasroot+'/tinymce4/plugins/asciisvg/plugin.min.js'
        },
		menubar: false,//"edit insert format table tools ",
		toolbar1: "myEdit myInsert styleselect | bold italic underline subscript superscript | forecolor backcolor | snippet code | saveclose",
		toolbar2: " alignleft aligncenter alignright | bullist numlist outdent indent  | attach link unlink image | table | asciimath asciimathcharmap asciisvg",
		extended_valid_elements : 'iframe[src|width|height|name|align|allowfullscreen|frameborder|style|class],param[name|value],@[sscr]',
		content_css : staticroot+(cssmode==1?'/assessment/mathtest.css,':'/imascore.css,')+staticroot+'/themes/'+coursetheme,
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
		content_style: "body {background-color: " + (coursetheme.match(/_dark/) ? "#000" : "#fff") + " !important;}",
		table_class_list: [{title: "None", value:''},
			{title:"Gridded", value:"gridded"},
			{title:"Gridded Centered", value:"gridded centered"}],
		style_formats_merge: true,
        snippets: (tinymceUseSnippets==1)?imasroot+'/tinymce4/getsnippets.php':false,
        autolink_pattern: /^(https?:\/\/|www\.)(.+)$/i,
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
	tinymce.remove(selectorstr);
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
		jQuery(el).off('click.recclick').on('click.recclick', function(e) {
			var inf = jQuery(this).attr('data-base').split('-');
			recclick(inf[0], inf[1], jQuery(this).attr("href"),
				jQuery(this).clone().find(".sr-only").remove().end().text());
			if (typeof(jQuery(el).attr("target"))=="undefined") {
				e.preventDefault();
				setTimeout('window.location.href = "'+jQuery(this).attr('href')+'"',100);
				return false;
			}
		}).off('mousedown.recclick').on('mousedown.recclick', function(e) {
			if (e.which==3) { //right click
				var inf = jQuery(this).attr('data-base').split('-');
				recclick(inf[0], inf[1], jQuery(this).attr("href"),
					jQuery(this).clone().find(".sr-only").remove().end().text());
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
			jQuery(this).text(' [-]')
				.attr('title',_("Hide video"))
				.attr('aria-label',_("Hide embedded video"));
		} else {
			els.hide();
			els.get(0).contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}','*');
			els.parent('.fluid-width-video-wrapper').hide();
			jQuery(this).text(' [+]');
			jQuery(this).attr('title',_("Watch video here"));
			jQuery(this).attr('aria-label',_("Embed video") + ' ' + jQuery(this).prev().text());
		}
	} else {
		var href = jQuery(this).prev().attr('href');
		var qsconn = '?';
		href = href.replace(/%3F/g,'?').replace(/%3D/g,'=');
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
        var viframe = jQuery('<iframe/>', {
			id: 'videoiframe'+id,
			width: 640,
			height: 400,
			src: loc_protocol+'//'+vidsrc+vidid+timeref,
			frameborder: 0,
			allowfullscreen: 1
        });
        var $this = jQuery(this);
        if ($this.closest('.itemhdr').length == 0) {
            viframe.insertAfter($this);
            $this.parent().fitVids();
            jQuery('<br/>').insertAfter($this);
        } else {
            var par = $this.closest('.itemhdr').next();
            par.prepend(viframe);
            par.fitVids();
        }
		
		$this.text(' [-]')
			.attr('title',_("Hide video"))
			.attr('aria-label',_("Hide embedded video"));
		if ($this.prev().attr("data-base")) {
			var inf = $this.prev().attr('data-base').split('-');
			recclick(inf[0], inf[1], href, $this.prev().text());
		}
	}
}
function setupvideoembeds(i,el) {

	jQuery('<span/>', {
		text: " [+]",
		role: "button",
		title: _("Watch video here"),
		"aria-label": _("Embed video") + ' ' + this.textContent,
		id: 'videoembedbtn'+videoembedcounter,
		click: togglevideoembed,
		keydown: function (e) {if (e.which == 13) { $(this).click();}},
		tabindex: 0,
		"class": "videoembedbtn"
	}).insertAfter(el);
	jQuery(el).addClass("prepped");
	videoembedcounter++;
}

var fileembedcounter = 0;
function setuppreviewembeds(i,el) {
	var filetypes = 'doc|docx|pdf|xls|xlsx|ppt|pptx|jpg|gif|png|jpeg';
	if (window.fetch) { filetypes += '|heic'; }
	var regex = new RegExp('\.(' + filetypes + ')($|\\?)', 'i');
	if (el.href.match(regex)) {
		jQuery('<span/>', {
			text: " [+]",
			role: "button",
			title: _("Preview file"),
			"aria-label": _("Preview file"),
			id: 'fileembedbtn'+fileembedcounter,
			click: togglefileembed,
			keydown: function (e) {if (e.which == 13) { $(this).click();}},
			tabindex: 0,
			"class": "videoembedbtn"
		}).insertAfter(el);
		jQuery(el).addClass("prepped");
		fileembedcounter++;
	}
}

function supportsPdf() {
	// based on PDFOject
	var ua = window.navigator.userAgent;
	if (ua.indexOf("irefox") !== -1 &&
		parseInt(ua.split("rv:")[1].split(".")[0], 10) > 18) { return true; }
 	if (typeof navigator.mimeTypes['application/pdf'] !== "undefined") {
		return true;
	}
	return false;
}

function togglefileembed() {
	var id = this.id.substr(12);
	var els = jQuery('#fileiframe'+id);
	if (els.length>0) {
		if (els.css('display')=='none') {
			els.show();
			jQuery(this).text(' [-]');
			jQuery(this).attr('title',_("Hide preview"));
			jQuery(this).attr('aria-label',_("Hide file preview"));
		} else {
			els.hide();
			jQuery(this).text(' [+]');
			jQuery(this).attr('title',_("Preview file"));
			jQuery(this).attr('aria-label',_("Preview file"));
		}
	} else {
		var href = jQuery(this).prev().attr('href');
		if (href.match(/\.(doc|docx|pdf|xls|xlsx|ppt|pptx)($|\?)/i)) {
			var src;
			if (href.match(/\.pdf/) && supportsPdf()) {
				src = href;
			} else if (href.match(/\.(doc|docx|xls|xlsx|ppt|pptx)($|\?)/i)) {
				src = 'https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(href);
			} else {
				src = 'https://docs.google.com/gview?embedded=true&url=' + encodeURIComponent(href);
			}
			jQuery('<iframe/>', {
				id: 'fileiframe'+id,
				width: "90%",
				height: 600,
				src: src,
				frameborder: 0,
				allowfullscreen: 1
			}).insertAfter(jQuery(this));
		} else if (href.match(/\.(heic)($|\?)/i)) {
			jQuery('<div>', {
				id: 'fileiframe' + id,
				text: 'Converting HEIC file (this may take a while)...'
			}).insertAfter(jQuery(this));
			if (!window.heic2any) {
				jQuery.getScript(staticroot+'/javascript/heic2any.min.js')
				 .done(function() { convertheic(href, 'fileiframe' + id); });
			} else {
				convertheic(href, 'fileiframe' + id);
			}
		} else {
			jQuery('<div>').append(jQuery('<img/>', {
					id: 'fileiframe'+id,
					src: href
				}).css('display','block').on('click', rotateimg)
		  ).insertAfter(jQuery(this));
		}
		jQuery('<br/>').insertAfter(jQuery(this));
		jQuery(this).text(' [-]');
		jQuery(this).attr('title',_("Hide preview"));
		if (jQuery(this).prev().attr("data-base")) {
			var inf = jQuery(this).prev().attr('data-base').split('-');
			recclick(inf[0], inf[1], href, jQuery(this).prev().text());
		}
	}
}

jQuery(function() {
	var m;
	if (m = window.location.href.match(/course\.php.*cid=(\d+).*folder=([\d\-]+)/)) {
		window.sessionStorage.setItem('btf'+m[1], m[2]);
	}
	jQuery('a[href*="course.php"]').each(function(i,el) {
		if (!el.href.match(/folder=/) && (m=el.href.match(/cid=(\d+)/))) {
			var btf = window.sessionStorage.getItem('btf'+m[1]) || '';
			if (btf !== '') {
				el.href += '&folder='+btf;
			}
		}
	});
	jQuery('form').each(function(i,el) {
		if (el.hasAttribute('action') && (m=el.getAttribute('action').match(/cid=(\d+)/))) {
			var btf = window.sessionStorage.getItem('btf'+m[1]) || '';
			if (btf !== '') {
				el.setAttribute('action', el.getAttribute('action') + '&btf='+btf);
			}
		}
	});
});

function convertheic(href, divid) {
	fetch(href)
  .then(function(res) { return res.blob();})
  .then(function(blob) {
		return heic2any({blob:blob});
	})
  .then(function(conversionResult) {
    var url = URL.createObjectURL(conversionResult);
    document.getElementById(divid).innerHTML = '<img src="' + url + '" onclick="rotateimg(this)">';
  })
  .catch(function(e) {
    console.log(e);
  });
}

function addNoopener(i,el) {
	if (!el.rel && el.target && el.host !== window.location.host) {
		el.setAttribute("rel", "noopener noreferrer");
	}
	if (el.target && jQuery(el).find('.openext').length == 0) {
		jQuery(el).append('<span class="sr-only openext">Opens externally</span>');
	}
}
function addBlankTarget(i,el) {
	if (el.host !== window.location.host) {
		el.setAttribute("target", "_blank");
	}
}

function uniqid(prefix) {
    return (prefix || '') + '_' + Math.random().toString(36).substr(2, 9);
}

function setariastatus(status) {
    var el = document.getElementById("ariastatus");
    if (!el) {
        el = $("<div>", {id:"ariastatus", role:"status", class:"sr-only", "aria-live":"polite"});
        $("body").append(el);
        el = el[0];
    }
    el.innerHTML = status;
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
	if (el.hasOwnProperty("target")) {
		el = el.target;
	}
	if ($(el).data('rotation')) {
		var r = ($(el).data('rotation') + 90)%360;
	} else {
		var r = 90;
	}
	var bnd, sc;
	$(el).parent().css("height", "auto");
	var parentwidth = $(el).parent().width();
	$(el).data('rotation', r).css({transform: 'rotate('+r+'deg)'});
	if (r%180==90) {
		$(el).css("transform-origin", "0 0").css("max-width","none");
		bnd = el.getBoundingClientRect();
		if (bnd.width>parentwidth) {
			sc = .95*parentwidth/bnd.width;
		} else {
			sc = 1;
		}
		if (r==90) {
			$(el).data('rotation', r).css({transform: 'rotate('+r+'deg) scale('+sc+') translateY('+(-bnd.width)+'px) '});
		} else {
			$(el).data('rotation', r).css({transform: 'rotate('+r+'deg) scale('+sc+') translateX('+(-bnd.height)+'px) '});
		}
		bnd = el.getBoundingClientRect();
		$(el).parent().css("height", bnd.height);
	} else {
		$(el).css("transform-origin", "").css("max-width", "80%");
		$(el).parent().css("height", "");
	}
}

function sendLTIresizemsg() {
	var default_height = Math.max(
		document.body.scrollHeight, document.body.offsetHeight)+100;
		//document.documentElement.clientHeight, document.documentElement.scrollHeight,
		//document.documentElement.offsetHeight
	if (window.parent != window.self) {
		parent.postMessage(JSON.stringify({subject:'lti.frameResize', height: default_height}), '*');
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
				    if (frames[i].contentWindow === e.originalEvent.source &&
							!frames[i].hasAttribute('data-noresize')
						) {
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
					$("#qn"+qn).val(edata['value']).trigger('change');
				}
			} else {
				var id = edata['id'].replace(/[^\w\-]/, "");
				if ($("#"+id).hasClass("allowupdate")) {
					$("#"+id).val(edata['value']).trigger('change');;
				}
			}
		}
	});
});


function initlinkmarkup(base) {
	if (typeof isImathasAssessment != 'undefined') {
		$(base).find('a:not([target])').not('.textsegment a, .mce-content-body a').each(addBlankTarget);
	}
	$(base).find('a').each(setuptracklinks).each(addNoopener);
	$(base).find('a[href*="youtu"]').not('.textsegment a,.mce-content-body a,.prepped').each(setupvideoembeds);
	$(base).find('a[href*="vimeo"]').not('.textsegment a,.mce-content-body a,.prepped').each(setupvideoembeds);
	$(base).find("a.attach").not('.textsegment a,.mce-content-body a').not(".prepped").each(setuppreviewembeds);
	setupToggler(base);
	setupToggler2(base);
    $(base).fitVids();
    resizeResponsiveIframes(base, true);
}

function resizeResponsiveIframes(base, init) {
    if (init) {
        jQuery(base).find('iframe.scaleresponsive').wrap(jQuery('<div>', {css:{overflow:"hidden"}}));
    }
    jQuery(base).find('iframe.scaleresponsive').each(function(i,el) {
        var p = el.parentNode; 
        var sc = Math.min(1,p.offsetWidth/parseInt(el.width || el.style.width));
        el.style.transform = "scale("+sc+")";
        p.style.height = (sc*parseInt(el.height || el.style.height)+3)+"px";
    });
}
jQuery(document).ready(function($) {
    initlinkmarkup('body');
    $(window).on('resize', function () {resizeResponsiveIframes('body');});
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
	if (fixedonscrollel.length>0) { // && $(fixedonscrollel[0]).css('float')=="left") {
		$(window).scroll(function() {
			var winscrolltop = $(window).scrollTop();
			for (var i=0;i<fixedonscrollel.length;i++) {
				if (winscrolltop > initialtop[i] && initialtop[i]>0) {
					$(fixedonscrollel[i]).css('position','fixed').css('top','5px').attr("data-fixed",true);
				} else {
					$(fixedonscrollel[i]).css('position','static').attr("data-fixed",false);
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

function randID() {
	return '_' + Math.random().toString(36).substr(2, 9);
}

function setupToggler(base) {
	$(base).find("*[data-toggler]:not(.togglerinit)").each(function() {
		var id = $(this).attr("id") || randID();
		if ($(this).prop('tagName') === 'IFRAME') {
			$(this).css("display", "block");
		}
		var doslide = ($(this).css("display") == 'block');
		var showtext = $(this).attr("data-toggler");
		var hidetext = $(this).attr("data-toggler-hide") || showtext;
		var button = $("<button>", {
			id: "togbtn" + id,
			type: "button",
			text: showtext,
			"aria-controls": id
		});
		$(this).hide().attr("id",id).addClass("togglerinit").before(button);
		button.attr("aria-expanded", false)
		.attr("tabindex", 0)
		.css("cursor", "pointer")
		.on("click keydown", function(e) {
			if (e.type=="click" || e.which==13) {
				var targ = $("#"+$(this).attr("aria-controls"));
				if ($(this).attr("aria-expanded") == "true") {
					$(this).attr("aria-expanded", false).text(showtext);
					if (doslide) {
						targ.slideUp(300);
					} else {
						targ.hide();
					}
				} else {
					$(this).attr("aria-expanded", true).text(hidetext);
					if (doslide) {
						targ.slideDown(300);
					} else {
						targ.show();
					}
				}
			}
		});
	});
}

function setupToggler2(base) {
	$(base).find(".togglecontrol:not(.togglerinit)").each(function() {
		$(this).addClass("togglerinit").attr("aria-expanded", false)
		.on("click keydown", function(e) {
			if (e.type=="click" || e.which==13) {
				var targ = $("#"+$(this).attr("aria-controls"));
				if ($(this).attr("aria-expanded") == "true") {
					$(this).attr("aria-expanded", false);
					targ.hide();
				} else {
					$(this).attr("aria-expanded", true);
					targ.show();
				}
				return false;
			}
		});
	});
}

//generic grouping block toggle
function groupToggleAll(dir) {
	$(".grouptoggle").attr("aria-expanded", dir==1?false:true)
	 .trigger("click");
}
jQuery(document).ready(function($) {
	$(".grouptoggle").each(function() {
		var id = randID();
		var blockitem = $(this).next(".blockitems");
		var initclosed = blockitem.hasClass("hidden");
		if (initclosed) {
			blockitem.hide().removeClass("hidden");
		} else {
			blockitem.show();
		}
		blockitem.attr("id", "bi"+id);

		$(this).attr("id", id).attr("aria-controls", "bi"+id)
			.attr("aria-expanded", !initclosed)
			.attr("tabindex", 0)
			.css("cursor", "pointer")
			.on("click keydown", function(e) {
				if (e.type=="click" || e.which==13) {
					if ($(this).attr("aria-expanded") == "true") {
						$(this).attr("aria-expanded", false);
						$(this).children("img").attr("src", staticroot+"/img/expand.gif");
						$(this).next(".blockitems").slideUp();
					} else {
						$(this).attr("aria-expanded", true);
						$(this).children("img").attr("src", staticroot+"/img/collapse.gif");
						$(this).next(".blockitems").slideDown();
					}
				}
			});
	});
	$(".grouptoggle img").attr("alt", "expand/collapse");
});

// restyled file uploads
function initFileAlt(el) {
	var label = jQuery(el).next().find(".filealt-label");
	var origLabel = label.attr('data-def') || label.html();
	jQuery(el).off("focus.filealt, blur.filealt, click.filealt, change.filealt")
		.on("focus.filealt", function(e) { jQuery(e.target).addClass("has-focus");} )
		.on("blur.filealt", function(e) { jQuery(e.target).removeClass("has-focus");} )
		.on("click.filealt", function(e) { label.html(origLabel); } )
		.on("change.filealt", function(e) {
			var fileName = '';
			fileName = e.target.value.split(/(\\|\/)/g).pop();
			if (fileName) {
				var maxFileSize = 10000*1024; // 10MB
        if (this.files[0].size > maxFileSize) {
          alert(_('This file is too large - maximum size is 10MB'));
          $(this).val('');
        } else {
					label.html(fileName);
				}
			}
		});
}
jQuery('input.filealt').each(function(i,el) { initFileAlt(el);});

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
        if ($this.closest(".fluid-width-video-wrapper").length>0) {return true;}
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
	    if (e.which === 8 && !$(e.target).is("input[type='text']:not([readonly]),input[type='number']:not([readonly]),input:not([type]):not([readonly]),input[type='password']:not([readonly]),input[type='url']:not([readonly]),input[type='email']:not([readonly]), textarea, [contenteditable='true']")) {
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
            code = jQuery(ta).html().replace(/<br\s*\/?>/g,"\n").replace(/<\/?[a-zA-Z][^>]*>/g,'')
                    .replace(/&lt;/g,'<').replace(/&gt;/g,'>');
		} else {
			ta = $this.find("textarea");
			if (ta.length==0 || jQuery(ta[0]).val()=="") {
				if ($this.find("pre").length>0) {
                    code = $this.find("pre").html().replace(/<br\s*\/?>/g,"\n").replace(/<\/?[a-zA-Z][^>]*>/g,'').replace(/\n\n/g,"\n")
                            .replace(/&lt;/g,'<').replace(/&gt;/g,'>');
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
		url += '&evallabel=' + encodeURIComponent(_('Evaluate'));
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

function setActiveTab(el) {
	jQuery(el).closest(".tabwrap").find("li.active").removeClass("active");
	jQuery(el).closest(".tablist").find("a[role=tab]").attr("aria-selected",false);
	jQuery(el).attr("aria-selected",true);
	jQuery(el).parent().addClass("active");
	jQuery(el).closest(".tabwrap").find(".tabpanel").hide().attr("aria-hidden",true);
	var tabpanelid = el.getAttribute('aria-controls');
	jQuery(el).closest(".tabwrap").find("#"+tabpanelid).show().attr("aria-hidden",false);
}

/* ========================================================================
 * Bootstrap: dropdown.js v3.4.1
 * https://getbootstrap.com/docs/3.4/javascript/#dropdowns
 * ========================================================================
 * Copyright 2011-2019 Twitter, Inc.
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
  
    Dropdown.VERSION = '3.4.1'
  
    function getParent($this) {
      var selector = $this.attr('data-target')
  
      if (!selector) {
        selector = $this.attr('href')
        selector = selector && /#[A-Za-z]/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
      }
  
      var $parent = selector !== '#' ? $(document).find(selector) : null
  
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
        $parent.removeClass('open').trigger($.Event('hidden.bs.dropdown', relatedTarget))
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
          .trigger($.Event('shown.bs.dropdown', relatedTarget))
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
      var $items = $this.next('.dropdown-menu' + desc);
      if (!$items.length) {
        $items = $parent.find('.dropdown-menu' + desc);
      }
  
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
