var last = 0;
var t;
var polldelay = 2000;
function updatemsgs() {
	ahah();
	clearTimeout(t);
	t = setTimeout("updatemsgs()", polldelay);
}

function posttxt() {
	clearTimeout(t);
	var v = tinyMCE.get('addtxt').getContent();
	var pstr = 'addtxt=' + encodeURIComponent(v);
	ahah(pstr);
	tinyMCE.get('addtxt').setContent("");
	setTimeout("updatemsgs()", polldelay);
}

function ahah(params) { 
 
 if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (req != undefined) { 
	  document.getElementById("loading").style.display = "";
    req.onreadystatechange = function() {ahahDone();}; 
    if (params==null) {
	    req.open("GET", postback+'&update='+last, true); 
	    req.send("");
    } else {
	    params += '&update='+last;
	    req.open("POST", postback, true);
	    req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    req.setRequestHeader("Content-length", params.length);
	    req.setRequestHeader("Connection", "close");
	    req.send(params); 
    }
  } 
}  

function ahahDone() { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
      if (req.responseText != '') {
	      var newmsgs = false;
	      var mids = req.responseText.match(/class="msg"\s+id="(\d+)/g);
	      if (mids!=null && mids.length>0) {
		      for (var i=0; i<mids.length; i++) {
				n = parseInt(mids[i].replace(/^.*?(\d+)/,"$1"));
				if (n>last) {
					last = n;
				}
		      }
		      newmsgs = true;
	      }
	      
	      newdiv = document.createElement("div");
	      newdiv.innerHTML = req.responseText;// + '<div class="clear">&nbsp;</div>';
	      var msgbody = document.getElementById("msgbody")
	      msgbody.appendChild(newdiv);
	      ulist = document.getElementById("userlist");
	      var umat = ulist.innerHTML.match(/br/ig);
	      ucnt = 0;
	      if (umat!=null) {
		      ucnt = umat.length;
	      }
	      if (ucnt<2) {
		      polldelay = 10000;
	      } else {
		      polldelay = 2000;
	      }
	      document.getElementById("userscontent").innerHTML = ulist.innerHTML;
	      newdiv.removeChild(ulist);
	      
	      //alert(document.getElementById("msgbody").innerHTML);
	      if (newmsgs) {
		      AMprocessNode(newdiv);
		      setTimeout("drawPics()",100);
		      
		      msgbody.scrollTop = msgbody.scrollHeight;
	      }
	     // var x = document.getElementById("msgbody").getElementsByTagName("script"); 
	      //for(var i=0;i<x.length;i++) {
	//	      eval(x[i].text);
	  //    }
	  
      }
    } else { 
      document.getElementById("msgbody").innerHTML +=" AHAH Error:\n"+ req.status + "\n" +req.statusText; 
    } 
    document.getElementById("loading").style.display = "none";
  }
  
}

