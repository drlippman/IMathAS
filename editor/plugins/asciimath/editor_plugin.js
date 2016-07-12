/**
 * ASCIIMath Plugin for TinyMCE editor
 *   port of ASCIIMath plugin for HTMLArea written by 
 *   David Lippman & Peter Jipsen
 *
 * @author David Lippman
 * @copyright Copyright � 2008 David Lippman.
 *
 * Plugin format based on code that is:
 * @copyright Copyright � 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('asciimath');

	tinymce.create('tinymce.plugins.AsciimathPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			var t = this;
			
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceAsciimath');
			ed.addCommand('mceAsciimath', function(val) {
				
				if (t.lastAMnode==null) {
					existing = ed.selection.getContent();
					if (existing.indexOf('class=AM')==-1) { //existing does not contain an AM node, so turn it into one
					       //strip out all existing html tags.
					       existing = existing.replace(/<([^>]*)>/g,"");
					       existing = existing.replace(/&(m|n)dash;/g,"-");
					       existing = existing.replace(/&?nbsp;?/g," ");
					       existing = existing.replace(/&(.*?);/g,"$1");
					       if (val) {
						       existing = val;
					       }
					       entity = '<span class=AMedit>`'+existing+'<span id="removeme"></span>`</span>&nbsp;';
					    
					       if (tinymce.isIE) ed.focus();
					   
					       ed.selection.setContent(entity);
					       
					       ed.selection.setCursorLocation(ed.dom.get('removeme'),0);
					       ed.dom.remove('removeme');
					       t.justinserted = true;
					       ed.nodeChanged();
					       t.justinserted = null;
					 }
					
				} else if (val) {
					ed.selection.setContent(val);
				}
				
			});
			
			ed.addCommand('mceAsciimathDlg', function() {
				if (typeof AMTcgiloc == 'undefined') {
					AMTcgiloc = "";	
				}
				ed.windowManager.open({
					file : url + '/amcharmap.htm',
					width : 630 + parseInt(ed.getLang('asciimathdlg.delta_width', 0)),
					height : 390 + parseInt(ed.getLang('asciimathdlg.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					AMTcgiloc : AMTcgiloc
				});
				
			});
			
			ed.onKeyDown.add(function(ed, ev) {
				if (ev.keyCode == 13 || ev.keyCode == 35 || ev.keyCode == 40) {
				    var rng, AMcontainer, dom = ed.dom;
		
				    AMcontainer = dom.getParent(ed.selection.getNode(), 'span.AMedit');
		
				    if (AMcontainer) {
					/*rng = dom.createRng();
		
					if (e.keyCode == 37 || e.keyCode == 38) {
					    rng.setStartBefore(AMcontainer);
					    rng.setEndBefore(AMcontainer);
					} else {
					    rng.setStartAfter(AMcontainer);
					    rng.setEndAfter(AMcontainer);
					}
					ed.selection.setRng(rng);
					*/
					ed.selection.select(AMcontainer);
					ed.selection.collapse(false);
				    }
				} else if (ev.keyCode == 46 || ev.keyCode == 8) {
					/* handle backspaces - not working :( */
				    node = ed.selection.getNode();
				    var AMcontainer = ed.dom.getParent(node, 'span.AM');
				    if (AMcontainer) {
					AMcontainer.parentNode.removeChild(AMcontainer);
				    }
				}
			});
			
			ed.onKeyPress.add(function(ed, ev) {
				var key = String.fromCharCode(ev.charCode || ev.keyCode);
				if (key=='`') {
					if (t.lastAMnode == null) {
						existing = ed.selection.getContent();
						if (existing.indexOf('class=AM')==-1) { //existing does not contain an AM node, so turn it into one
						       //strip out all existing html tags.
						       existing = existing.replace(/<([^>]*)>/g,"");
						       existing = existing.replace(/&(m|n)dash;/g,"-");
						       existing = existing.replace(/&?nbsp;?/g," ");
						       existing = existing.replace(/&(.*?);/g,"$1");
						       entity = '<span class="AMedit">`'+existing+'<span id="removeme"></span>`</span>&nbsp;';
						       
						       if (tinymce.isIE) ed.focus();
						   
						       ed.selection.setContent(entity);
						   
						       //ed.selection.select(ed.dom.get('removeme'));
						       ed.selection.setCursorLocation(ed.dom.get('removeme'),0);
						       ed.dom.remove('removeme');
						       t.justinserted = true;
						       ed.nodeChanged();
						       t.justinserted = null;
						 }
					}
					if (ev.stopPropagation) {
						ev.stopPropagation();
						ev.preventDefault();
				       } else {
						ev.cancelBubble = true;
						ev.returnValue = false;
				       }
				} 
			});
			
			// Register asciimath button
			ed.addButton('asciimath', {
				title : 'asciimath.desc',
				cmd : 'mceAsciimath',
				image : url + '/img/ed_mathformula2.gif'
			});
			
			ed.addButton('asciimathcharmap', {
				title : 'asciimathcharmap.desc',
				cmd : 'mceAsciimathDlg',
				image : url + '/img/ed_mathformula.gif'
			});
			/*
			ed.onInit.add(function(ed) {
					AMtags = ed.dom.select('span.AM');
					for (var i=0; i<AMtags.length; i++) {
						t.nodeToAM(AMtags[i]);
					}
			});
			*/
			ed.onPreInit.add(function(ed) {
				//took out - was triggering on toolbar clicks
				//tinymce.dom.Event.add(ed.getWin(), 'blur', function(e) {	
				//});
				if (tinymce.isIE) {
					addhtml = "<object id=\"mathplayer\" classid=\"clsid:32F66A20-7614-11D4-BD11-00104BD3F987\"></object>";
					addhtml +="<?import namespace=\"m\" implementation=\"#mathplayer\"?>";
			
					ed.dom.doc.getElementsByTagName("head")[0].insertAdjacentHTML("beforeEnd",addhtml);
				}
				
			});
			
			ed.onPreProcess.add(function(ed,o) {
				//if (o.get) {    //commented out to trigger preprocess on paste
					AMtags = ed.dom.select('span.AM', o.node);
					for (var i=0; i<AMtags.length; i++) {
						t.math2ascii(AMtags[i]); 
					}
					MJtags = ed.dom.select('span[data-asciimath]', o.node);
					for (var i=0; i<MJtags.length; i++) {
						t.mathjax2ascii(MJtags[i]); 
					}
					AMtags = ed.dom.select('span.AMedit', o.node);
					for (var i=0; i<AMtags.length; i++) {
						var myAM = AMtags[i].innerHTML;
						myAM = "`"+myAM.replace(/\`/g,"")+"`";
						AMtags[i].innerHTML = myAM;
						AMtags[i].className = "AM";
					}
				//} 
				
			});

			
			ed.onLoadContent.add(function(ed,o) {
					AMtags = ed.dom.select('span.AM');
					for (var i=0; i<AMtags.length; i++) {
						t.nodeToAM(AMtags[i]);
					}
					t.loaded = true;
			});
			ed.onSetContent.add(function(ed,o) {
				if (t.loaded) {
					AMtags = ed.dom.select('span.AM');
					for (var i=0; i<AMtags.length; i++) {
						t.nodeToAM(AMtags[i]);
					}
				}
			});
			
			
			ed.onBeforeSetContent.add(function(ed,o) {
				o.content = o.content.replace(/(<span[^>]+AM.*?<\/span>)</, "$1 <");
				
			});
			ed.onBeforeExecCommand.add(function(ed,cmd) {
				if (cmd != 'mceAsciimath' && cmd != 'mceAsciimathDlg') {
					AMtags = ed.dom.select('span.AM');
					for (var i=0; i<AMtags.length; i++) {
						t.math2ascii(AMtags[i]);
						AMtags[i].className = "AMedit";
					}
				}
			});
			
			ed.onExecCommand.add(function(ed,cmd) {
				if (cmd != 'mceAsciimath' && cmd != 'mceAsciimathDlg') {
					AMtags = ed.dom.select('span.AMedit');
					for (var i=0; i<AMtags.length; i++) {
						t.nodeToAM(AMtags[i]);
						AMtags[i].className = "AM";
					}
					AMtags = ed.dom.select('span.AM');
					for (var i=0; i<AMtags.length; i++) {
						if (!AMtags[i].innerHTML.match(/(math|img)/)) {
							AMtags[i].innerHTML = AMtags[i].title;
							t.nodeToAM(AMtags[i]);
						}
					}
					AMimgs = ed.dom.select('img.AMimg');
					for (var i=0; i<AMimgs.length; i++) {
						t.imgwrap(ed, AMimgs[i]);
					}
				}
			});
			
			ed.onNodeChange.add(function(ed, cm, e) {
				var doprocessnode = true;
				if (t.testAMclass(e)) {
					p = e;
				} else {
					p = ed.dom.getParent(e,t.testAMclass);
				}
				cm.setDisabled('charmap', p!=null);
				cm.setDisabled('sub', p!=null);
				cm.setDisabled('sup', p!=null);
				
				if (p != null) {
					if (t.lastAMnode == p) {
						doprocessnode = false;
					} else {
						t.math2ascii(p); 
						p.className = 'AMedit';
						if (t.lastAMnode != null) { 
							t.nodeToAM(t.lastAMnode); 
							t.lastAMnode.className = 'AM';
						}
						if (p.parentNode.lastChild==p) {
							//not working 
							//p.parentNode.appendChild(document.createTextNode(" "));
						}
						if (t.justinserted==null && p.parentNode.innerHTML.match(/<span[^>]*class="AMedit"[^<]*?<\/span>(\s*<br>)?\s*$/)) {
							while (p.parentNode.lastChild.nodeName.toLowerCase()!='span') {
								p.parentNode.removeChild(p.parentNode.lastChild);
							}
							p.parentNode.appendChild(ed.dom.doc.createTextNode("\u00A0"));
						}
						if (t.justinserted==null && p.parentNode.innerHTML.match(/^\s*<span[^>]*class="AMedit"/)) {
							while (p.parentNode.firstChild.nodeName.toLowerCase()!='span') {
								p.parentNode.removeChild(p.parentNode.firstChild);
							}
							p.parentNode.insertBefore(ed.dom.doc.createTextNode("\u00A0"),p.parentNode.firstChild);
						}
						if (t.justinserted==null) {
							ed.selection.setCursorLocation(p,0);
						}
						t.lastAMnode = p;
						doprocessnode = false;
					}
				}
				if (doprocessnode && (t.lastAMnode != null)) { //if not in AM node, process last
				     if (t.lastAMnode.innerHTML.match(/`(&nbsp;|\s|\u00a0|&#160;)*`/) || t.lastAMnode.innerHTML.match(/^(&nbsp;|\s|\u00a0|&#160;)*$/)) {
					     p = t.lastAMnode.parentNode;
					     p.removeChild(t.lastAMnode);
				     } else {
					     t.nodeToAM(t.lastAMnode);  
					     t.lastAMnode.className = 'AM'; 
				     }
				     t.lastAMnode = null;
			       }
					
			});
			ed.onDeactivate.add(function(ed) {
				if (t.lastAMnode != null) {
				     if (t.lastAMnode.innerHTML.match(/`(&nbsp;|\s)*`/)|| t.lastAMnode.innerHTML.match(/^(&nbsp;|\s|\u00a0|&#160;)*$/)) {
					     p = t.lastAMnode.parentNode;
					     p.removeChild(t.lastAMnode);
				     } else {
					     t.nodeToAM(t.lastAMnode);  
					     t.lastAMnode.className = 'AM'; 
				     }
				     t.lastAMnode = null;
				}
			});
			
			

		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'Asciimath plugin',
				author : 'David Lippman',
				authorurl : 'http://www.pierce.ctc.edu/dlippman',
				infourl : '',
				version : "1.0"
			};
		},
		
		math2ascii : function(el) {
			var myAM = el.innerHTML;

			if (myAM.indexOf("`") == -1) {
				if (el.title!='' && el.title.indexOf('math')==-1 && el.title.indexOf('img')==-1) { //myAM.indexOf('math')==-1 && myAM.indexOf('img')==-1 &&
					myAM = el.title;  //if cut-and-paste, grab eqn from title if there
				} else if (myAM.indexOf('title')==-1) {
					myAM = myAM.replace(/.+alt=\"(.*?)\".+/g,"$1");
					myAM = myAM.replace(/.+alt=\'(.*?)\'.+/g,"$1");
					myAM = myAM.replace(/.+alt=([^>]*?)\s.*>.*/g,"$1");
					myAM = myAM.replace(/.+alt=(.*?)>.*/g,"$1");
				} else {
					myAM = myAM.replace(/.+title=\"(.*?)\".+/g,"$1");
					myAM = myAM.replace(/.+title=\'(.*?)\'.+/g,"$1");
					myAM = myAM.replace(/.+title=([^>]*?)\s.*>.*/g,"$1");
					myAM = myAM.replace(/.+title=(.*?)>.*/g,"$1");
				}
				//myAM = myAM.replace(/&gt;/g,">");
				//myAM = myAM.replace(/&lt;/g,"<");
				myAM = myAM.replace(/>/g,"&gt;");
				myAM = myAM.replace(/</g,"&lt;");
				myAM = "`"+myAM.replace(/\`/g,"")+"`";
				el.innerHTML = myAM;
			}
		},
		
		mathjax2ascii : function(el) {
			var myAM = el.getAttribute("data-asciimath");
			var attr = el.attributes;
			var i = attr.length;
			while (i--) {
				el.removeAttribute(attr[i].name);
				console.log(el);
			}
			el.className = "AM";
			el.title = myAM;
			el.innerHTML = "`"+myAM+"`";
		},
		
		nodeToAM : function(outnode) {
			if (tinymce.isIE) {
				  var str = outnode.innerHTML.replace(/\`/g,"");
				  str.replace(/\"/,"&quot;");
				  var newAM = document.createElement("span");
				  newAM.className = "AM";
				  newAM.appendChild(AMTparseMath(str));
				  
				  outnode.innerHTML = newAM.innerHTML;    
				  outnode.title=str;  //add title to <span class="AM"> with equation for cut-and-paste
			  } else {
				  //doesn't work on IE, probably because this script is in the parent
				  //windows, and the node is in the iframe.  Should it work in Moz?
				 var myAM = "`"+outnode.innerHTML.replace(/\`/g,"")+"`"; //next 2 lines needed to make caret
				 outnode.innerHTML = myAM;     //move between `` on Firefox insert math
				 AMprocessNode(outnode);
				 outnode.title=myAM.replace(/\`/g,""); //add title to <span class="AM"> with equation for cut-and-paste
			  }
			
		}, 
		
		imgwrap : function(ed, imgnode) {
			p = ed.dom.getParent(imgnode,this.testAMclass);
			if (p==null) {
				var newAM = document.createElement("span");
				newAM.className = "AM";
				var rimgnode = imgnode.parentNode.replaceChild(newAM, imgnode);
				newAM.appendChild(rimgnode);
				p = newAM;
			}
		},
		
		lastAMnode : null,
		loaded : false,
		preventAMrender : false,
		
		testAMclass : function(el) {
			if ((el.className == 'AM') || (el.className == 'AMedit')) {
				return true;
			} else {
				return false;
			}
		}
	});

	// Register plugin
	tinymce.PluginManager.add('asciimath', tinymce.plugins.AsciimathPlugin);
})();