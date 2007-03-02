// ASCIIMathML plugin for HTMLArea
// Modified for math formulas by Peter Jipsen (c) 2005
//  with modifications for live edit by David Lippman (c) 2005
// Originally CharacterMap by Holger Hees based on HTMLArea XTD 1.5 (http://mosforge.net/projects/htmlarea3xtd/)
// Original Author - Bernhard Pfeifer novocaine@gmx.net 
// (c) systemconcept.de 2004
// Distributed under the same terms as HTMLArea itself.
// This notice MUST stay intact for use (see license.txt).

function AsciiMath(editor) {
  this.editor = editor;
	var cfg = editor.config;
	var self = this;
	cfg.registerButton({
                id       : "insertmath",
                tooltip  : "Insert Math Symbols",
                image    : editor.imgURL("ed_mathformula.gif", "AsciiMath"),
                textMode : false,
                action   : function(editor) {
                                self.buttonPress(editor,true);
                           }
            })
	   cfg.registerButton({
                id       : "insertnewmath",
                tooltip  : "Insert New Math",
                image    : editor.imgURL("ed_mathformula2.gif", "AsciiMath"),
                textMode : false,
                action   : function(editor) {
                                self.buttonPress(editor,false);
                           }
            })
	    cfg.registerButton({
                id       : "swapmathmode",
                tooltip  : "Swap Math Mode",
                image    : editor.imgURL("ed_mathformula3.gif", "AsciiMath"),
                textMode : false,
                action   : function(editor) {
                                self.swapForExec();
                           }
            })
	//cfg.addToolbarElement("insertsymbol", "inserthorizontalrule", 1);
//	var line = cfg.toolbar[1] ? 1 : 0;
//	cfg.toolbar[line].push("separator","insertnewmath","insertmath","swapmathmode");
	//cfg.toolbar.push([ "insertmath" ]);

};

AsciiMath._pluginInfo = {
	name          : "AsciiMath",
	version       : "1.0",
	developer     : "Peter Jipsen / David Lippman",
	developer_url : "http://www.chapman.edu/~jipsen",
	c_owner       : "Peter Jipsen / David Lippman",
	sponsor       : "Chapman University / ",
	sponsor_url   : "http://www.chapman.edu/",
	license       : "htmlArea"
};

//used in Xinha, not in htmlarea
AsciiMath.prototype._lc = function(string) {
    return HTMLArea._lc(string, 'AsciiMath');
}

//added by David Lippman - requires mod to htmlarea file
//swaps mode before doing execCommand for things like bold, etc.
//this fixes an IE issue, where bold didn't affect rendered MathML
//also allows switch to non-auto-render mode
var mmode = "M";
AsciiMath.prototype.swapForExec = function() {
	if (mmode == "M") {
	
		AMtags = this.editor._doc.getElementsByTagName("span");
		for (var i=0; i<AMtags.length;i++) {
			if (AMtags[i].className) {
				if(AMtags[i].className == 'AM') {
					math2ascii(AMtags[i]);
					AMtags[i].className = 'AMedit';
				}
			}
		}
		mmode = "T";
	} else {
		AMtags = this.editor._doc.getElementsByTagName("span");
		for (var i=0; i<AMtags.length;i++) {
			if (AMtags[i].className) {
				if(AMtags[i].className == 'AMedit') {
					nodeToAM(AMtags[i]);
					AMtags[i].className = 'AM';
				}
			}
		}
		mmode = "M";
	}
	
}

//when first loaded, render AM spans
AsciiMath.prototype.onGenerate = function() {

// added by Peter Jipsen
// This is an attempt to get backtick formulas surrounded by <spanAMs>.
// Needed when pasting ASCIIMath text from elsewhere.
// Currently not working right, messing up svg (?)
/*
      var html = this.editor.getHTML();
      html = html.replace(/\\\$/g, "%escdol%");
      html = html.replace(/\\\`/g, "%esclq%");
      html = html.replace(/<span class=\"AM\">([^<]*)<\/span>/g, "$1");
      html = html.replace(/\$([^\$]+)\$/g, '<span class="AM">%dol%$1%dol%</span>')
      html = html.replace(/%dol%/g, "\$");
      html = html.replace(/\`([^\`]+)\`/g, '<span class="AM">`$1`</span>')
      html = html.replace(/%escdol%/g, "\\\$");
      html = html.replace(/%esclq%/g, "\\\`");
      this.editor.setHTML(html);
*/

      AMtags = this.editor._doc.getElementsByTagName("span");
      for (var i=0; i<AMtags.length;i++) {
	      if (AMtags[i].className) {
		      if(AMtags[i].className == 'AM') {
			      nodeToAM(AMtags[i]);
		      }
	      }
      }
}

AsciiMath.prototype.headerHTML = function() {
	if (HTMLArea.is_ie) {
		return "<object id=\"mathplayer\" classid=\"clsid:32F66A20-7614-11D4-BD11-00104BD3F987\"></object><?import namespace=\"m\" implementation=\"#mathplayer\"?>";	
	} else {
		return '';
	}
}

AsciiMath.prototype.buttonPress = function(editor,dopopup) {
  if (dopopup) {  //insert character popup
    editor._popupDialog( "plugin://AsciiMath/select_character", function( entity )
    {
        if ( !entity )
        { 
            //user must have pressed Cancel
            return false;
        }
        if (HTMLArea.is_ie) editor.focusEditor();
        if (lastAMnode==null) {//if we're not current in an AM node, create one
           entity = '<span class=AMedit>`'+entity+'<span id=removeme></span>`</span> ';
           editor.insertHTML( entity );
           nodetokill = editor._doc.getElementById("removeme");
           editor.selectNodeContents(nodetokill);
           p = nodetokill.parentNode;
           p.removeChild(nodetokill);
        } else {
           editor.insertHTML( entity );
        }
             
    }, null);   //ln130
  } else {  //insert new
   if (lastAMnode==null) { //only do this if we're not currently in an AM node.  Otherwise, do nothing
    //gets existing text
    existing = editor.getSelectedHTML();
    if (existing.indexOf('class=AM')==-1) { //existing does not contain an AM node, so turn it into one
       //strip out all existing html tags.
       existing = existing.replace(/<([^>]*)>/g,"");
       entity = '<span class=AMedit>`'+existing+'<span id=removeme></span>`</span> ';
    
       if (HTMLArea.is_ie) editor.focusEditor();
   
       editor.insertHTML( entity ); 

       nodetokill = editor._doc.getElementById("removeme");
       editor.selectNodeContents(nodetokill);

       p = nodetokill.parentNode;
       p.removeChild(nodetokill);
       editor.updateToolbar();
    } else { //if it does contain an AM node
     //an appropriate action here would be to remove the AM node. 
     //I can't think of a great way to do this, so for now, this does nothing
    }
   }
  }
 
}

//added by Peter Jipsen
//backtick behaves the same as pressing the add new math button

AsciiMath.prototype.onKeyPress = function(ev,editor) {
  var key = String.fromCharCode(
    HTMLArea.is_ie ? ev.keyCode : ev.charCode);//.toLowerCase();
  if (key=='`') {
   if (lastAMnode==null) { //only do this if we're not currently in an AM node.  Otherwise, do nothing
    //gets existing text
    existing = editor.getSelectedHTML();
    if (existing.indexOf('class=AM')==-1) { //existing does not contain an AM node, so turn it into one
       //strip out all existing html tags.
       existing = existing.replace(/<([^>]*)>/g,"");
       entity = '<span class=AMedit>`'+existing+'<span id=removeme></span>`</span> ';
    
       if (HTMLArea.is_ie) editor.focusEditor();
   
       editor.insertHTML( entity ); 

       nodetokill = editor._doc.getElementById("removeme");
       editor.selectNodeContents(nodetokill);

       p = nodetokill.parentNode;
       p.removeChild(nodetokill);
//       editor.updateToolbar();
       HTMLArea._stopEvent(ev);
    }
   }
  }
}

//added by David Lippman
//switches mode of AM nodes: render/unrender 
var lastAMnode = null;
AsciiMath.prototype.onUpdateToolbar = function() {
    var doprocessnode = true;
    if (mmode == "M") {
    ancestors = this.editor.getAllAncestors();
    
     for (var i = ancestors.length; --i >= 0;) {
	     var el = ancestors[i];
	     if (el.className) {
		     if ((el.className == 'AM') || (el.className == 'AMedit')) {  
			     if (lastAMnode == el) {  //still in same AM node
				     doprocessnode = false;
			     } else { //in AM node for first time.  switch to edit
				     math2ascii(el); 
				     el.className = 'AMedit';
				     if (lastAMnode != null) { nodeToAM(lastAMnode); lastAMnode.className = 'AM'}
				     lastAMnode = el;
				     doprocessnode = false;
			     }
		     }
	     }
     }
     
     if (doprocessnode && (lastAMnode != null)) { //if not in AM node, process last
	     if (lastAMnode.innerHTML.match(/`(&nbsp;|\s)*`/)) {
		     p = lastAMnode.parentNode;
		     p.removeChild(lastAMnode);
	     } else {
		     nodeToAM(lastAMnode);  
		     lastAMnode.className = 'AM'; 
	     }
	     lastAMnode = null;
     }
    }
};


//added by David Lippman - requires htmlarea mod
//unrenders AM before switch to textmode or on submit
AsciiMath.prototype.onGetHTML = function(mode) {
    if (mode == "wysiwyg") {  //if we're leaving wysiwyg and going to textmode, unrender MathML
	AMtags = this.editor._doc.getElementsByTagName("span");
	for (var i=0; i<AMtags.length;i++) {
	      if (AMtags[i].className) {
		      if(AMtags[i].className == 'AM') {
			      math2ascii(AMtags[i]);
		      }
	      }
      	}
    } 
}

//shortened version from AsciiMathMLeditor.js
//Version 1.4 July 14, 2004, (c) Peter Jipsen http://www.chapman.edu/~jipsen
//distributed under the GPL (at http://www.gnu.org/copyleft/gpl.html)
function math2ascii(el) {
  var myAM = el.innerHTML;
  if (myAM.indexOf("`") == -1) {
	myAM = myAM.replace(/.+title=\"(.*?)\".+/g,"`$1`");
	myAM = myAM.replace(/.+title=([^>]*?)\s.*>.*/g,"`$1`");
	myAM = myAM.replace(/.+title=(.*?)>.*/g,"`$1`");
    el.innerHTML = myAM;
  } 
}

//added by David Lippman
//renders an AM span node from `` to MathML
function nodeToAM(outnode) {  
  if (HTMLArea.is_ie) {
	  var str = outnode.innerHTML.replace(/\`/g,"");
	  var newAM = AMparseMath(str);
	  outnode.innerHTML = newAM.innerHTML;  
  } else {
	  //doesn't work on IE, probably because this script is in the parent
	  //windows, and the node is in the iframe.  Should it work in Moz?
         var myAM = outnode.innerHTML; //next 2 lines needed to make caret
         outnode.innerHTML = myAM;     //move between `` on Firefox insert math
	 AMprocessNode(outnode);
  }
  
}