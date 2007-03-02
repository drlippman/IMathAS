  function leadingZero(nr) {
	  if (nr < 10) nr = "0" + nr;
	  return nr;
  }
  function makenicetime(d) {
	var Hours = d.getHours();
	var ampm = "am";
	if (Hours > 11)
		ampm = "pm";
	if (Hours == 0) Hours = 12;
	if (Hours > 12)
		Hours -= 12;
	return (Hours + ':' + leadingZero(d.getMinutes()) + ' ' + ampm);
	  
  }

  function senddown(st) {
	  var d = new Date();
	  var newstartdate = document.getElementById("sdate"+st).value;
	  if (newstartdate!=0 && newstartdate!=2000000000) {
		timeel = document.getElementById("stime"+st);
		if (timeel==null) {
			curdate = Date.parse(newstartdate);
		} else {
			newstarttime = timeel.value;
			var curdate = Date.parse(newstartdate + ' ' + newstarttime.replace(/^\s*(\d+)\s*(am|pm)/,"$1:00 $2"))/1000;
		}
		var timediff = curdate - basesdates[st];
		basesdates[st] = curdate;
		for (var i=st+1;i<basesdates.length;i++) {
			curdate = document.getElementById("sdate"+i).value;
			if (curdate!=0 && curdate!=2000000000) {
				curdate = basesdates[i] + timediff;
				basesdates[i] = curdate;
				d.setTime(curdate*1000);
				nicedate = leadingZero(d.getMonth()+1)+'/'+leadingZero(d.getDate())+'/'+d.getFullYear();
				document.getElementById("sdate"+i).value = nicedate;
				nicetime = makenicetime(d);
				document.getElementById("stime"+i).value = nicetime;
			}	
		}
	  }
	  var newstartdate = document.getElementById("edate"+st).value;
	  if (newstartdate!=0 && newstartdate!=2000000000) {
		timeel = document.getElementById("etime"+st);
		if (timeel==null) {
			curdate = Date.parse(newstartdate);
		} else {
			newstarttime = timeel.value;
			var curdate = Date.parse(newstartdate + ' ' + newstarttime.replace(/^\s*(\d+)\s*(am|pm)/,"$1:00 $2"))/1000;
		}
		
		var timediff = curdate - baseedates[st];
		baseedates[st] = curdate;
		for (var i=st+1;i<baseedates.length;i++) {
			curdate = document.getElementById("edate"+i).value;
			if (curdate!=0 && curdate!=2000000000) {
				curdate = baseedates[i] + timediff;
				baseedates[i] = curdate;
				d.setTime(curdate*1000);
				nicedate = leadingZero(d.getMonth()+1)+'/'+leadingZero(d.getDate())+'/'+d.getFullYear();
				document.getElementById("edate"+i).value = nicedate;
				nicetime = makenicetime(d);
				document.getElementById("etime"+i).value = nicetime;
			}	
		}
	  }
	 var newstartdate = document.getElementById("rdate"+st).value;
	  if (newstartdate!=0 && newstartdate!=2000000000) {
		timeel = document.getElementById("rtime"+st);
		if (timeel==null) {
			curdate = Date.parse(newstartdate);
		} else {
			newstarttime = timeel.value;
			var curdate = Date.parse(newstartdate + ' ' + newstarttime.replace(/^\s*(\d+)\s*(am|pm)/,"$1:00 $2"))/1000;
		}
		var timediff = curdate - baserdates[st];
		baserdates[st] = curdate;
		for (var i=st+1;i<baserdates.length;i++) {
			curdate = document.getElementById("rdate"+i).value;
			if (curdate!=0 && curdate!=2000000000) {
				curdate = baserdates[i] + timediff;
				baserdates[i] = curdate;
				d.setTime(curdate*1000);
				nicedate = leadingZero(d.getMonth()+1)+'/'+leadingZero(d.getDate())+'/'+d.getFullYear();
				document.getElementById("rdate"+i).value = nicedate;
				nicetime = makenicetime(d);
				document.getElementById("rtime"+i).value = nicetime;
			}	
		}
	  }
  }
