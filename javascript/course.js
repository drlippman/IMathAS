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
      var x = document.getElementById(target).getElementsByTagName("script"); 
      for(var i=0;i<x.length;i++) {
	      eval(x[i].text);
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
   
function showcalcontents(el) {
	html = '';
	if (caleventsarr[el.id]!=null) {
		html += '<div style="background-color:#ddf;">'+caleventsarr[el.id].date + '</div>';
		if (caleventsarr[el.id].data!=null) {
			html += '<ul class=qview style="margin-top: 2px;">';
			for (var i=0; i<caleventsarr[el.id].data.length; i++) {
				if (caleventsarr[el.id].data[i].type=='A') {
					html += '<li><span style="background-color: #f66; padding: 0px 5px 0px 5px;">?</span> <a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[el.id].data[i].id+'">';
					html += caleventsarr[el.id].data[i].name + '</a>';
					html += ' Due '+caleventsarr[el.id].data[i].time;
					html += '</li>';
				} else if (caleventsarr[el.id].data[i].type=='I') {
					html += '<li><span style="background-color: #f66; padding: 0px 5px 0px 5px;">!</span> ';
					html += caleventsarr[el.id].data[i].name;
					html += '</li>';
				} else if (caleventsarr[el.id].data[i].type=='L') {
					html += '<li><span style="background-color: #f66; padding: 0px 5px 0px 5px;">!</span> ';
					if (caleventsarr[el.id].data[i].link=='') {
						html += '<a href="../course/showlinkedtext.php?cid='+cid+'&id='+caleventsarr[el.id].data[i].id+'">';
					} else {
						html += '<a href="'+caleventsarr[el.id].data[i].link+'">';
					}
					html += caleventsarr[el.id].data[i].name + '</a>';
					html += '</li>';
				} else if (caleventsarr[el.id].data[i].type=='FP') {
					html += '<li><span style="background-color: #f66; padding: 0px 5px 0px 5px;">F</span> <a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[el.id].data[i].id+'">';
					html += caleventsarr[el.id].data[i].name + '</a>';
					html += ' New Threads Due '+caleventsarr[el.id].data[i].time;
					html += '</li>';
				} else if (caleventsarr[el.id].data[i].type=='FR') {
					html += '<li><span style="background-color: #f66; padding: 0px 5px 0px 5px;">F</span> <a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[el.id].data[i].id+'">';
					html += caleventsarr[el.id].data[i].name + '</a>';
					html += ' Replies Due '+caleventsarr[el.id].data[i].time;
					html += '</li>';
				}
			}
		}
	}
	html += '</ul>';
	document.getElementById('step').innerHTML = html;	
	var alltd = document.getElementsByTagName("td");
	for (var i=0;i<alltd.length;i++) {
		alltd[i].style.backgroundColor = '#fff';
	}
	el.style.backgroundColor = '#fdd';
}

function editinplace(el) {
	input = document.getElementById(el.id+'input');
	if (input==null) {
		var input = document.createElement("input");
		input.id = el.id+'input';
		input.type = "text";
		input.setAttribute("onBlur","editinplaceun('"+el.id+"')");
		el.parentNode.insertBefore(input,el);	
	} else {
		input.type="text";
	}
	input.value = el.innerHTML;
	el.style.visibility = "hidden";
	input.focus();
}

function editinplaceun(id) {
	el = document.getElementById(id);
	input = document.getElementById(id + 'input');
	el.innerHTML = input.value;
	//input.parentNode.removeChild(input);
	input.type = "hidden";
	el.style.visibility = '';
}
