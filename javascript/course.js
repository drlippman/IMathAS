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
      plblist = plblist.split(',');
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
		if (arraysearch(folder,plblist)==-1) {
			plblist.push(folder);	
		}
	  }
          node.className = 'blockitems';
          //butn.value = 'Collapse';
	  if (img != null) {
		  img.src = imasroot+'/img/collapse.gif';
	  }
          if (loc==-1) {oblist.push(bnum);} 
      }
      oblist = oblist.join(',');
      plblist = plblist.join(',');
      document.cookie = 'openblocks-' +cid+'='+ oblist;
      document.cookie = 'prevloadedblocks-'+cid+'='+plblist;
   }
   
function showcalcontents(el) {
	var html = '';
	if (typeof el == 'number') {
		var calwalk = new Date();
		calwalk.setTime(el);
		for (var j=0; j<28; j++) {
			moday = (calwalk.getMonth()+1) + '-' + (calwalk.getDate());
			if (caleventsarr[moday].data!=null) {
				html += '<div style="background-color:#ddf;">'+caleventsarr[moday].date + '</div>';
				html += showcalcontentsid(moday);
			}
			calwalk.setDate(calwalk.getDate()+1);
		}
		
	} else if (caleventsarr[el.id]!=null) {
		html += '<div style="background-color:#ddf;">'+caleventsarr[el.id].date + '</div>';
		html += showcalcontentsid(el.id);
	} 
	
	document.getElementById('caleventslist').innerHTML = html;	
	var alltd = document.getElementsByTagName("td");
	for (var i=0;i<alltd.length;i++) {
		alltd[i].style.backgroundColor = '#fff';
	}
	if (typeof el != 'number') {
		el.style.backgroundColor = '#fdd';
	}
}

function showcalcontentsid(elid) {
	var html = '';
	if (caleventsarr[elid].data!=null) {
		html += '<ul class=qview style="margin-top: 2px;">';
		for (var i=0; i<caleventsarr[elid].data.length; i++) {
			if (caleventsarr[elid].data[i].type=='A') {
				html += '<li><span style="background-color:'+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+caleventsarr[elid].data[i].tag+'</span> <a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
				html += caleventsarr[elid].data[i].name + '</a>';
				html += ' Due '+caleventsarr[elid].data[i].time;
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='AR') {
				html += '<li><span style="background-color: #99f; padding: 0px 5px 0px 5px;">R</span> <a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
				html += caleventsarr[elid].data[i].name + '</a>';
				html += ' Review until '+caleventsarr[elid].data[i].time;
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='I') {
				html += '<li><span style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='L') {
				html += '<li><span style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].link=='') {
					html += '<a href="../course/showlinkedtext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
				} else {
					html += '<a href="'+caleventsarr[elid].data[i].link+'">';
				}
				html += caleventsarr[elid].data[i].name + '</a>';
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FP') {
				html += '<li><span style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">F</span> <a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
				html += caleventsarr[elid].data[i].name + '</a>';
				html += ' New Threads Due '+caleventsarr[elid].data[i].time;
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FR') {
				html += '<li><span style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">F</span> <a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
				html += caleventsarr[elid].data[i].name + '</a>';
				html += ' Replies Due '+caleventsarr[elid].data[i].time;
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='C') {
				html += '<li><span style="background-color: #0ff; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				html += '</li>';
			} 
		}
		html += '</ul>';
	}
	return html;
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
