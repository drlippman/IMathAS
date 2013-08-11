var last = 0;
var t;
var f;
var newon = 0;
var polldelay = 2000;
var cnt = 0;
var origtitle = null;
var isnewpost = true;
function flashnew(n) {
	if (isnewpost) {
		isnewpost = false;
		return;
	}
	if (origtitle==null) {
		origtitle = document.title;
	}
	if (n!=null) {
		newon = 9;
	}
	clearTimeout(f);
	if (newon%2==0) {
		document.title = "New Message! - Math Chat";
	} else {
		document.title = origtitle;
	}
	newon--;
	if (newon>0) {
		f = setTimeout("flashnew()",400);
	}
}
	
function updatemsgs() {
	clearTimeout(t);
	ahah();
	t = setTimeout("updatemsgs()", polldelay);
}

function posttxt() {
	clearTimeout(t);
	var v = tinyMCE.get('addtxt').getContent();
	var pstr = 'addtxt=' + encodeURIComponent(v);
	ahah(pstr);
	tinyMCE.get('addtxt').setContent("");
	isnewpost = true;
	t = setTimeout("updatemsgs()", polldelay);
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
	    cnt++;
	    req.open("GET", postback+'&update='+last+'&cnt='+cnt, true); 
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
	      var respobj = eval('('+req.responseText+')');
	      if (respobj.msgs.length>0) {
	      	       var msgbody = document.getElementById("msgbody");
	      	       for (var i=0; i<respobj.msgs.length; i++) {
	      	       	       n = respobj.msgs[i].id;
	      	       	       if (n>last) {
					last = n;
					var newdiv = document.createElement("div");
					newdiv.id = respobj.msgs[i].id;
					newdiv.className = "msg";
					var usrdiv = document.createElement("div");
					usrdiv.className = "user";
					usrdiv.innerHTML = respobj.msgs[i].user;
					var txtdiv = document.createElement("div");
					txtdiv.className = "txt";
					txtdiv.innerHTML = respobj.msgs[i].msg;
					newdiv.appendChild(usrdiv);
					newdiv.appendChild(txtdiv);
					msgbody.appendChild(newdiv);
			       }
		      }
		      newmsgs = true;
	      }
	      ucnt = respobj.users.length;
	      document.getElementById("userscontent").innerHTML = respobj.users.join('<br/>');
	      if (ucnt<2) {
		      polldelay = 10000;
	      } else {
		      polldelay = 2000;
	      }
	   
	      //alert(document.getElementById("msgbody").innerHTML);
	      if (newmsgs) {
		      rendermathnode(newdiv);
		      setTimeout("drawPics()",100);
		      
		      msgbody.scrollTop = msgbody.scrollHeight;
		      flashnew(9);
		      
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

