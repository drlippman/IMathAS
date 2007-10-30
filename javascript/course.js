function ahah(url, target) { 
  document.getElementById(target).innerHTML = ' Fetching data... ';
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (req != undefined) { 
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
	      AMprocessNode(document.getElementById(target));
      }
      if (usingASCIISvg) {
	      setTimeout("drawPics()",100);
      }
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
   
var loadedblocks = new Array();

function toggleblock(bnum,folder) {
      var node = document.getElementById('block'+bnum);
      //var butn = document.getElementById('but'+bnum);
      var img = document.getElementById('img'+bnum);
      oblist = oblist.split(',');
      var loc = arraysearch(bnum,oblist);
      if (node.className == 'blockitems') {
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	loadedblocks.push(bnum);
	  }
          node.className = 'hidden';
          //butn.value = 'Expand';
	  if (img != null) {
		  img.src = imasroot+'/img/expand.gif';
	  }
          if (loc>-1) {oblist.splice(loc,1);}
      } else { 
          if (arraysearch(bnum,loadedblocks)==-1) {
	  	ahah(getbiaddr+folder,'block'+bnum);
	  }
          node.className = 'blockitems';
          //butn.value = 'Collapse';
	  if (img != null) {
		  img.src = imasroot+'/img/collapse.gif';
	  }
          if (loc==-1) {oblist.push(bnum);} 
      }
      oblist = oblist.join(',');
      document.cookie = 'openblocks-' +cid+'='+ oblist;
   }
   
