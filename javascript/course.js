function ahah(url, target) { 
  document.getElementById(target).innerHTML = ' Fetching data... ';
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (typeof req != 'undefined') { 
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
	      rendermathnode(document.getElementById(target));
      }
      if (usingASCIISvg) {
	      setTimeout("drawPics()",100);
      }
      $('#'+target+' a').each(setuptracklinks);
      $('#'+target+' a[href*="youtu"]').each(setupvideoembeds);
      $('#'+target+' a[href*="vimeo"]').each(setupvideoembeds);
      
      var x = document.getElementById(target).getElementsByTagName("script"); 
      for(var i=0;i<x.length;i++) {
	      if (x[i].src) {
		      var script = document.createElement("script");
		      script.src = x[i].src;
		      var pn = x[i].parentNode;
		      pn.replaceChild(script,x[i]);
	      } else {
		      eval(x[i].text);
	      }
      }

   
    } else { 
      document.getElementById(target).innerHTML=" AHAH Error:\n"+ req.status + "\n" +req.statusText; 
    } 
  } 
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
		var mlink = document.getElementById("mcelink");
		if (mlink!=null) {
			var href = mlink.href;
			href = href.replace(/^(.*?cid=\d+).*$/,"$1");
			mlink.href = href+"&addto="+(Date.parse(caleventsarr[el.id].date)/1000);
		}
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
				html += '<li><span class="calitem" style="background-color:'+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) { 
					html += '<a href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'"';
					if (caleventsarr[elid].data[i].timelimit!=null) {
						html += 'return confirm(\'This assessment has a time limit. Click OK to start or continue working on the assessment.\')" ';
						//html += 'onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');return confirm(\'This assessment has a time limit. Click OK to start or continue working on the assessment.\')" ';
					} else {
						//html += 'onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';	
					}
					html += '>';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].allowlate==1) {
					html += ' <a href="redeemlatepass.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].undolate==1) {
					html += ' <a href="redeemlatepass.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'&undo=true">Un-use LatePass</a>';
				}
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addassessment.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Settings</a>';
					html += ' <a href="addquestions.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Questions</a>';
					html += ' <a href="gb-itemanalysis.php?asid=average&cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Grades</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='AR') {
				html += '<li><span class="calitem" style="background-color: '+caleventsarr[elid].data[i].color+';padding: 0px 5px 0px 5px;">'+caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) { 
					//html += '<a onclick="recclick(\'assessviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';	
					html += 'href="../assessment/showtest.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Review until '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addassessment.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Settings</a>';
					html += ' <a href="isolateassessgrade.php?cid='+cid+'&aid='+caleventsarr[elid].data[i].id+'">Grades</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='I') {
				html += '<li><span class="calitem" style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addinlinetext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='L') {
				html += '<li><span class="calitem" style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) { 
					
					if (caleventsarr[elid].data[i].link=='') {
						html += '<a onclick="recclick(\'linkedviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].id+'\');" ';
						html += 'href="../course/showlinkedtext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">';
					} else {
						html += '<a onclick="recclick(\'linkedviacal\','+caleventsarr[elid].data[i].id+',\''+caleventsarr[elid].data[i].link+'\');" ';
						html += 'href="'+caleventsarr[elid].data[i].link+'">';
					}
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addlinkedtext.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FP') {
				html += '<li><span class="calitem" style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) { 
					html += '<a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' New Threads Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addforum.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='FR') {
				html += '<li><span class="calitem" style="background-color: '+caleventsarr[elid].data[i].color+'; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				if (caleventsarr[elid].data[i].id!=null) { 
					html += '<a href="../forums/thread.php?cid='+cid+'&forum='+caleventsarr[elid].data[i].id+'">';
					html += caleventsarr[elid].data[i].name + '</a>';
				} else {
					html += caleventsarr[elid].data[i].name;
				}
				html += ' Replies Due '+caleventsarr[elid].data[i].time;
				if (caleventsarr[elid].data[i].editlink!=null) {
					html += ' <a href="addforum.php?cid='+cid+'&id='+caleventsarr[elid].data[i].id+'">Modify</a>';
				}
				html += '</li>';
			} else if (caleventsarr[elid].data[i].type=='C') {
				html += '<li><span class="calitem" style="background-color: #0ff; padding: 0px 5px 0px 5px;">'+ caleventsarr[elid].data[i].tag+'</span> ';
				html += caleventsarr[elid].data[i].name;
				html += '</li>';
			} 
		}
		html += '</ul>';
	}
	return html;
}

function changecallength(el) {
	window.location = calcallback + '&callength=' + el.value;
}




