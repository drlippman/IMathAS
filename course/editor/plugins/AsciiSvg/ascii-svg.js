// ASCIISvg plugin for HTMLArea
// Modified for inserting edittable Svg equation graphs by David Lippman (c) 2005
// Uses a modification of ASCIIsvg.js, originally by Peter Jipsen (c) 2005,
//  modified for this use by David Lippman (c) 2005
// Based on ASCIIMath plugin by Peter Jipsen (c) 2005
// Originally CharacterMap by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/)
// Original Author - Bernhard Pfeifer novocaine@gmx.net 
//
// (c) systemconcept.de 2004
// Distributed under the same terms as HTMLArea itself.
// This notice MUST stay intact for use (see license.txt).
var hideembeds = true;

function AsciiSvg(editor) {
  this.editor = editor;
	var cfg = editor.config;
	var self = this;
	hideembeds = !HTMLArea.is_ie;
	if (svgimgbackup==false && !HTMLArea.is_ie) {
		svgimgbackup=true;
	}
  cfg.registerButton({
                id       : "insertsvg",
                tooltip  : "Insert Svg Graph",
                image    : editor.imgURL("ed_asciisvg.gif", "AsciiSvg"),
                textMode : false,
                action   : function(editor) {
                                self.buttonPress(editor);
                           }
            });
	    
	//cfg.addToolbarElement("insertsvg", "inserthorizontalrule", 1);
	//cfg.toolbar.push([ "insertsvg" ]);
//	var line = cfg.toolbar[1] ? 1 : 0;
//	cfg.toolbar[line].push("separator","insertsvg");
};

var numgraphs = 0;

AsciiSvg._pluginInfo = {
	name          : "AsciiSvg",
	version       : "1.0",
	developer     : "David Lippman",
	developer_url : "http://www.pierce.ctc.edu/dlippman",
	c_owner       : "David Lippman",
	sponsor       : " ",
	sponsor_url   : " ",
	license       : "htmlArea"
};

//used in Xinha, not in htmlarea
AsciiSvg.prototype._lc = function(string) {
    return HTMLArea._lc(string, 'AsciiSvg');
}

AsciiSvg.prototype.buttonPress = function(editor) {
    var param = new Object();
	param.editor = editor;
	param.editor_url = _editor_url;
	
	//if(param.editor_url == "../") {
	//	param.editor_url = document.URL;
	//	param.editor_url = param.editor_url.replace(/^(.*\/).*\/.*$/g, "$1");
	//}
    editor.focusEditor();
    if (editor._getSelection().type != "Control" || (svgimgbackup && HTMLArea.is_ie && editor._getSelection().type == "Control")) {
	    if (svgimgbackup) {
		    
		    image = editor.getParentElement();
		    		    
		    if (image && !/^img$/i.test(image.tagName))
			image = null;
		    
		    if (image != null) {
			    cureditor = editor;
			   popup(image.id);//open control panel
		    } else {
			    var toadd = '<img id="mygraph' + numgraphs +'" style="width: 300px; height: 200px; vertical-align: middle; float: none;" src="' + AScgiloc + '?sscr='+encodeURIComponent("-7.5,7.5,-5,5,1,1,1,1,1,300,200")+'" sscr="-7.5,7.5,-5,5,1,1,1,1,1,300,200" script=" " />';
			    editor.insertHTML(toadd);
		    }
	    } else if (hideembeds) {	 
		    image = editor.getParentElement();
		    if (image && !/^img$/i.test(image.tagName))
			image = null;
		    
		    if (image != null) {
			    cureditor = editor;
			   popup(image.id);//open control panel
		    } else {
			    var toadd = '<img id="mygraph' + numgraphs +'" style="width: 300px; height: 200px; vertical-align: middle; float: none;" src="' + param.editor_url + 'plugins/AsciiSvg/svgplaceholder.gif" sscr="-7.5,7.5,-5,5,1,1,1,1,1,300,200" script=" " />';
			    editor.insertHTML(toadd);
		    }
	    } else {
		    var toadd = '<embed id="mygraph' + numgraphs +'" type="image/svg+xml" style="width: 300px; height: 200px; vertical-align: middle; float: none;" src="' + param.editor_url + 'plugins/AsciiSvg/d3.svg" sscr="-7.5,7.5,-5,5,1,1,1,1,1,300,200" script=" " alt="SVG Graph"/>';
		    editor.insertHTML(toadd);
	    }
	    
	    numgraphs++;
    } 
     
}

//count number of graphs on load
AsciiSvg.prototype.onGenerate = function() {
	var graphs = HTMLArea.getHTML(this.editor._doc.body, false, this.editor).match(/id=\"mygraph(\d+)/g);
	if (graphs != null) {
		for (var i=0; i < graphs.length; i++) {
				gnum = graphs[i].replace(/.*mygraph(\d+).*/,"$1");
				if (gnum > numgraphs) { numgraphs = gnum;}
		}
	}
	numgraphs++;
	/*
	if (svgimgbackup) {
		AStags = this.editor._doc.getElementsByTagName("embed");
		alert(AStags.length);
		var i=0;
		while((AStags.length > 0) && (i < AStags.length)) {
			if (AStags[i].id.indexOf("mygraph") != -1) {
				node = this.editor._doc.createElement("img");
				//node.setAttribute("src", AScgiloc + '?' +encodeURIComponent(AStags[i].getAttribute("sscr")));
				node.setAttribute("sscr", AStags[i].getAttribute("sscr"));
				node.setAttribute("script", AStags[i].getAttribute("script"));
				node.setAttribute("style", AStags[i].getAttribute("style"));
				node.id = AStags[i].id;
				
				AStags[i].parentNode.replaceChild(node,AStags[i]);
			} else {i++;}
		}
	}
	*/
}


AsciiSvg.prototype.headerHTML = function() {
	if (HTMLArea.is_ie && !svgimgbackup) {
		return '<script type="text/javascript" src="' + _imasroot + 'javascript/ASCIIsvg.js"></script>\n<script type="text/javascript" src="' + _editor_url + 'plugins/AsciiSvg/AsvgHA.js"></script>\n';
	} else {
		return '';
	}
}

AsciiSvg.prototype.onGetHTML = function(mode) {
	if (svgimgbackup) {
		if (mode == "wysiwyg") {  //if we're leaving wysiwyg and going to textmode
			AStags = this.editor._doc.getElementsByTagName("img");
			var i=0
			while((AStags.length > 0) && (i < AStags.length)) {
				if (AStags[i].id.indexOf("mygraph") != -1) {
					
					node = this.editor._doc.createElement("embed");
					node.src =  _editor_url + 'plugins/AsciiSvg/d.svg';
					node.setAttribute("sscr", AStags[i].getAttribute("sscr"));
					node.setAttribute("script", AStags[i].getAttribute("script"));
					if (HTMLArea.is_ie) {
						node.style.cssText = AStags[i].getAttribute("style").cssText;
					} else {
						node.setAttribute("style", AStags[i].getAttribute("style"));
					}
					node.setAttribute("type","image/svg+xml");
					node.id = AStags[i].id;
					AStags[i].parentNode.replaceChild(node,AStags[i]);
				} else {i++;}
			}
		} else if (mode == "textmode") {  //if we're leaving textmode and going to wysiwyg
			//doesn't work!!  stupid security alerts
			var embs = this.editor._textArea.value.match(/<embed[^>]*>/gi);
			if (embs!=null) {
				for (var i=0; i<embs.length; i++) {
					rep = embs[i].replace(/embed/,'img');
					rep = rep.replace(/type=\"?.*?[\"\s]/,'');
					sscr = rep.replace(/.*sscr=\"?(.*?)[\"\s].*/,"$1");
					rep = rep.replace(/src=.*?\s/, "src=\""+AScgiloc + "?sscr="+encodeURIComponent(sscr)+"\" ");
					this.editor._textArea.value = this.editor._textArea.value.replace(embs[i],rep);
				}
			}
		}
	} else if (hideembeds) {
		if (mode == "wysiwyg") {  //if we're leaving wysiwyg and going to textmode
			AStags = this.editor._doc.getElementsByTagName("img");
			var i=0
			while((AStags.length > 0) && (i < AStags.length)) {
				if (AStags[i].id.indexOf("mygraph") != -1) {
					node = this.editor._doc.createElement("embed");
					node.src = AStags[i].src.replace(/svgplaceholder.gif/,"d.svg");
					node.setAttribute("sscr", AStags[i].getAttribute("sscr"));
					node.setAttribute("script", AStags[i].getAttribute("script"));
					node.setAttribute("style", AStags[i].getAttribute("style"));
					node.setAttribute("type","image/svg+xml");
					node.id = AStags[i].id;
					AStags[i].parentNode.replaceChild(node,AStags[i]);
				} else {i++;}
			}
		} else if (mode == "textmode") {  //if we're leaving textmode and going to wysiwyg
			//doesn't work!!  stupid security alerts
			this.editor._textArea.value = this.editor._textArea.value.replace(/<embed([^>]*)d3?.svg([^>]*)/gi,"<img $1svgplaceholder.gif$2");
			
		}
	} else {
		if (mode == "wysiwyg") {  //if we're leaving wysiwyg and going to textmode
			//add code to switch d3.svg to another svg without onloads and onclicks
			AStags = this.editor._doc.getElementsByTagName("embed");
			for (var i=0; i < AStags.length; i++) {
				AStags[i].src = AStags[i].src.replace(/d3.svg/gi,"d.svg");
				AStags[i].removeAttribute("width");
				AStags[i].removeAttribute("height");
			}
		} else if (mode == "textmode") {  //if we're leaving textmode and going to wysiwyg
			//back to d3.svg
			this.editor._textArea.value = this.editor._textArea.value.replace(/(<embed[^>]*)d.svg([^>]*)/gi,"$1d3.svg$2");
			
		}
		
	}
}


//Handles popup calls from outside iframe (when svg is represented with img)
var popupwindow = '';
var lastgraphname = null;

function popup(graphname) {
	//var mywindow;
	if (!popupwindow.closed && popupwindow.location) {
		if (lastgraphname != graphname) {  //if popup open, but different graph
			if (isIE) {
				var alignm = cureditor._doc.getElementById(graphname).style.styleFloat;
			} else {
				var alignm = cureditor._doc.getElementById(graphname).style.cssFloat;
			}
			
			if (alignm == "none") {
				alignm = cureditor._doc.getElementById(graphname).style.verticalAlign;
			}
			var sa = cureditor._doc.getElementById(graphname).getAttribute('sscr').split(",");
			sa[9] = cureditor._doc.getElementById(graphname).style.width.replace(/(\d+)px/,"$1");
			sa[10] = cureditor._doc.getElementById(graphname).style.height.replace(/(\d+)px/,"$1");
			if (svgimgbackup) {
				popupwindow.getsscr(sa.join(","),graphname,alignm,false);
			} else {
				popupwindow.getsscr(sa.join(","),graphname,alignm,true);
			}
			lastgraphname = graphname;
		}
	} else {  //popup is closed
		svgpluginpath = cureditor._doc.getElementById(graphname).getAttribute('src');
		svgpluginpath = svgpluginpath.substring(0,svgpluginpath.lastIndexOf("/"));
		//popupwindow = window.open(svgpluginpath+"/svggraphcpwp.htm","mywindow","width=700,height=515,resizable=1,status=1,scrollbars=1");
		if (svgimgbackup) {
			popupwindow = window.open(_editor_url+"/plugins/AsciiSvg/svggraphcpwp.htm","mywindow","width=700,height=320,resizable=1,status=1,scrollbars=1");
		} else {
			popupwindow = window.open(_editor_url+"/plugins/AsciiSvg/svggraphcpwp.htm","mywindow","width=700,height=515,resizable=1,status=1,scrollbars=1");
		}
		lastgraphname = graphname;
	}
	if (window.focus) {popupwindow.focus()}
	//setTimeout(function() { mywindow.getsscr(cureditor._doc.getElementById(graphname).getAttribute('sscr'),graphname);}, 300);
	
}

function setsscr() {
	if (HTMLArea.is_ie) {
		var alignm = cureditor._doc.getElementById(lastgraphname).style.styleFloat;
	} else {
		var alignm = cureditor._doc.getElementById(lastgraphname).style.cssFloat;
	}
	if (alignm == "none") {
		alignm = cureditor._doc.getElementById(lastgraphname).style.verticalAlign;
	}
	var sa = cureditor._doc.getElementById(lastgraphname).getAttribute('sscr').split(",");
	sa[9] = cureditor._doc.getElementById(lastgraphname).style.width.replace(/(\d+)px/,"$1");
	sa[10] = cureditor._doc.getElementById(lastgraphname).style.height.replace(/(\d+)px/,"$1");
	if (svgimgbackup) {
		popupwindow.getsscr(sa.join(","),lastgraphname,alignm,false);
	} else {
		popupwindow.getsscr(sa.join(","),lastgraphname,alignm,true);
	}
}

function defineGraph(text,graphname,alignment) {
	//initialized = false;
	//switchTo(graphname);
	//parseShortScript(text);
	cureditor._doc.getElementById(graphname).setAttribute('sscr',text);
	if (svgimgbackup) {
		cureditor._doc.getElementById(graphname).setAttribute('src',AScgiloc + "?sscr="+encodeURIComponent(text));
	}
	sa = text.split(",");
	cureditor._doc.getElementById(graphname).style.width = sa[9] + "px";
	cureditor._doc.getElementById(graphname).style.height = sa[10] + "px";
	
	if ((alignment == "left") || (alignment == "right")) {
		if (HTMLArea.is_ie) {
			cureditor._doc.getElementById(graphname).style.styleFloat = alignment;
		} else {
			cureditor._doc.getElementById(graphname).style.cssFloat = alignment;
		}
	} else {
		if (HTMLArea.is_ie) {
			cureditor._doc.getElementById(graphname).style.styleFloat = "none";
		} else {
			cureditor._doc.getElementById(graphname).style.cssFloat = "none";
		}
		cureditor._doc.getElementById(graphname).style.verticalAlign = alignment;
	}
}



