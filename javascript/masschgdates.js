  //http://lawrence.ecorp.net/inet/samples/js-date-fx.shtml
Date.prototype.DAYNAMES = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
Date.prototype.SHORTDAYS = ["Su","M","Tu","W","Th","F","Sa"];
Date.prototype.MONTHNAMES = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
Date.prototype.msPERMIN = 1000 * 60;
Date.prototype.msPERDAY = 1000 * 60 * 60 * 24;

Date.prototype.addDays = function(d) {
        /* Adds the number of days to the date */
        this.setDate( this.getDate() + d );
    };
Date.prototype.addBizDays = function(d) {
    /* Adds the necessary number of days
     * to the date to include the required
     * weekdays.
     */
    var day = this.getDay();    //weekday number 0 through 6
    var wkEnd = 0;              //number of weekends needed
    var m = d % 5;              //number of weekdays for partial week

    if (d < 0) {
        wkEnd = Math.ceil(d/5);        //Yields a negative number of weekends
        switch (day) {
        case 6:
            //If staring day is Sat. one less weekend needed
            if (m == 0 && wkEnd < 0) wkEnd++;
            break;
        case 0:

            if (m == 0) d++; //decrease - part of weekend
            else d--;    //increase - part of weekend
            break;
        default:
            if (m <= -day) wkEnd--; //add weekend if not enough days to cover
        }
    }
    else if (d > 0) {
        wkEnd = Math.floor(d/5);
        var w = wkEnd;

        switch (day) {
        case 6:
            /* If staring day is Sat. and
             * no partial week one less day needed
             * if partial week one more day needed
             */
            if (m == 0) d--;
            else d++;
            break;
        case 0:
            if (m == 0 && wkEnd > 0) wkEnd--;
            break;
        default:
            if (5 - day < m) wkEnd++;
        }
    }

    d += wkEnd * 2;

    this.addDays(d);
};
Date.prototype.daysBetween = function(d) {
        //Returns number of days between to dates.

        var rtrn = null;

        if (! d instanceof Date) {
            try {
                d = new Date(d);
            }
            catch (e) {
                d = null;
            }
        }

        if (d) {
            /* Save time values */
            var th = this.getHours();
            var tm = this.getMinutes();
            var ts = this.getSeconds();
            var tms = this.getMilliseconds();
            var dth = d.getHours();
            var dtm = d.getMinutes();
            var dts = d.getSeconds();
            var dtms = d.getMilliseconds();

            /* Set times to midnight */
            this.setHours(0, 0, 0, 0);
            d.setHours(0, 0, 0, 0);

            /* get date value */
            var c = this.getTime();
            var n = d.getTime();

            /* restore times */
            this.setHours(th, tm, ts, tms);
            d.setHours(dth, dtm, dts, dtms);

            c = Math.floor(c/this.msPERDAY);
            n = Math.floor(n/this.msPERDAY);
            rtrn = n - c;
        }
        return rtrn;
    };
Date.prototype.getWeekDays = function(d) {
        //Returns number of weekdays between to dates

        var wkEnds = 0, days = 0;
        var s = 0, e = 0;

        days = Math.abs(this.daysBetween(d));

        if (days) {

            wkEnds = Math.floor(days/7);

            s = (d < this) ? d.getDay() : this.getDay() ;
            e = (d < this) ? this.getDay() : d.getDay();

            if (s != 6 && s > e) wkEnds++;
            if (s != e && (s == 6 || e == 6) ) days--;

            days -= (wkEnds * 2);
	    if (d<this) {days *= -1;}
        }
        return days;
    };

 $(function() {
 	$('img[src*="swap.gif"]').css("cursor","pointer");
 	$('span[id^="availname"]').css("cursor","pointer").each(function(i,el) {
 		$(el).on('click',function() {
 			MCDtoggle("a",el.id.substring(9));
 		});
 	});
 });

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
  var basesdates = new Array(); var baseedates = new Array(); var baserdates = new Array();
  var basefpdates = new Array(); var basefrdates = new Array(); var baselpdates = new Array();

  var globd = new Date();
  function ob(el) {
	 globd.setTime(Date.parse(el.value));
	 if (el.id.charAt(0)=='f' || el.id.charAt(0)=='l') {
	 	 var chgid = el.id.substring(0,3)+el.id.substring(6);
	 } else {
	 	 var chgid = el.id.substring(0,2)+el.id.substring(5);
	 }
	 document.getElementById(chgid).innerHTML = globd.SHORTDAYS[globd.getDay()];
  }
  function senddownselect(el) {
  	  if ($("input[id^='cb']:checked").length==0) {
  	  	  if (!confirm(_("No items have been selected. This action will apply to ALL items below this item"))) {
  	  	  	  el.selectedIndex= 0;
  	  	  	  return;
  	  	  }
  	  }
	  var ln = el.id.substr(3)*1;
	  var val = el.value*1;
	  if (val==1) {
		  senddown(ln);
	  } else if (val==2) {
		  copydown(ln,0);
	  } else if (val==3) {
		  copydown(ln,1);
	  } else if (val==4) {
		  copydown(ln,1,'s');
	  } else if (val==5) {
		  copydown(ln,1,'e');
	  } else if (val==6) {
		  copydown(ln,1,'r');
	  } else if (val==7) {
		  copydown(ln,1,'lp');
	  } else if (val==8) {
		  copydown(ln,1,'fp');
	  } else if (val==9) {
		  copydown(ln,1,'fr');
	  }
	  el.selectedIndex= 0;
  }
  function senddownaction(ln,val) {
  	  if ($("input[id^='cb']:checked").length==0) {
  	  	  if (!confirm(_("No items have been selected. This action will apply to ALL items below this item"))) {
  	  	  	  return false;
  	  	  }
  	  }
	  if (val==1) {
		  senddown(ln);
	  } else if (val==2) {
		  copydown(ln,0);
	  } else if (val==3) {
		  copydown(ln,1);
	  } else if (val==4) {
		  copydown(ln,1,'s');
	  } else if (val==5) {
		  copydown(ln,1,'e');
	  } else if (val==6) {
		  copydown(ln,1,'r');
	  } else if (val==7) {
		  copydown(ln,1,'lp');
	  } else if (val==8) {
		  copydown(ln,1,'fp');
	  } else if (val==9) {
		  copydown(ln,1,'fr');
	  }
	  return false;
  }
  function copydownsub(type,basearr,st,usecb,ctype) {  //type: s,e,r,lp,fp,fr
	  if (document.getElementById(type+"datetype"+st).value==1) {
		   var newstartdate = document.getElementById(type+"date"+st).value;
		   globd.setTime(Date.parse(newstartdate));
		   var shortd = globd.SHORTDAYS[globd.getDay()];
		   if (newstartdate!=0 && newstartdate!=2000000000 && basearr[st]!="NA") {
			  var newstarttime = document.getElementById(type+"time"+st).value;
			  for (var i=st+1;i<basearr.length;i++) {
				  if (usecb && !document.getElementById("cb"+i).checked) {
					  continue;
                  }
                  if (!document.getElementById(type+"datetype"+i)) { continue; }
				  if (basearr[i]!="NA" && document.getElementById(type+"datetype"+i).value==1) {
					  if (ctype==1) {
					  	document.getElementById(type+"date"+i).value = newstartdate;
					  	document.getElementById(type+"d"+i).innerHTML = shortd ;
					  }
					  document.getElementById(type+"time"+i).value = newstarttime;
				  }
			  }
		   }
	  }
  }

  function copydown(st,type,limit) {
	  var usecb = false;
	  var cbs = document.getElementsByTagName("input");
	  for (var i=0;i<cbs.length;i++) {
		  if (cbs[i].type=="checkbox" && cbs[i].checked && cbs[i].id.match(/cb/)) {
			  usecb = true;
			  break;
		  }
	  }
	  if (limit == null || limit == 's') {
	  	  copydownsub('s',basesdates,st,usecb,type);
	  }
	  if (limit == null || limit == 'e') {
	  	  copydownsub('e',baseedates,st,usecb,type);
	  }
	  /*if ((limit == null || limit == 'r') && baserdates[st]!="NA") {
		 copydownsub('r',baserdates,st,usecb,type);
	  }*/
	  if ((limit == null || limit == 'lp') && baselpdates[st]!="NA") {
		 copydownsub('lp',baselpdates,st,usecb,type);
	  }
	  if ((limit == null || limit == 'fp') && basefpdates[st]!="NA") {
		 copydownsub('fp',basefpdates,st,usecb,type);
	  }
	  if ((limit == null || limit == 'fr') && basefrdates[st]!="NA") {
		 copydownsub('fr',basefrdates,st,usecb,type);
	  }
  }
  function senddownsub(type,basearr,st,usebusdays,usecb) {  //type: s,e,r,lp,fp,fr
	  var d = new Date();
	  var db = new Date();
	  if (document.getElementById(type+"datetype"+st).value==1) {
		  var newstartdate = document.getElementById(type+"date"+st).value;
		  if (newstartdate!=0 && newstartdate!=2000000000 && basearr[st]!="NA") {
			  var newstarttime = document.getElementById(type+"time"+st).value;
			  newstarttime = newstarttime.replace(/^\s*(\d+:\d+)(am|pm)/,"$1 $2");
			  newstarttime = newstarttime.replace(/^\s*(\d+)\s*(am|pm)/,"$1:00 $2");
			  d.setTime(Date.parse(newstartdate + ' ' + newstarttime));
			  db.setTime(basearr[st]*1000);
			  if (usebusdays) {
				  var daydiff = db.getWeekDays(d); //days
			  } else {
				  var daydiff = db.daysBetween(d); //days
			  }
			  var timediff = (d.getHours()*60+d.getMinutes()) - (db.getHours()*60+db.getMinutes()); //minutes
			  for (var i=st+1;i<basearr.length;i++) {
				  if (usecb && !document.getElementById("cb"+i).checked) {
					  continue;
				  }
				  if (basearr[i]!="NA" && document.getElementById(type+"datetype"+i).value==1) {
					 curdate = document.getElementById(type+"date"+i).value;
					 if (curdate!=0 && curdate!=2000000000) {
						 d.setTime(basearr[i]*1000);
						 d.setTime(d.getTime()+timediff*60000);
						 if (usebusdays) {
							 d.addBizDays(daydiff);
						 } else {
							 d.addDays(daydiff);
						 }
						 nicedate = leadingZero(d.getMonth()+1)+'/'+leadingZero(d.getDate())+'/'+d.getFullYear();
						 document.getElementById(type+"date"+i).value = nicedate;
						 document.getElementById(type+"d"+i).innerHTML = d.SHORTDAYS[d.getDay()];
						 nicetime = makenicetime(d);
						 document.getElementById(type+"time"+i).value = nicetime;
					 }
				  }
			  }
		  }
	  }
  }
  function senddown(st) {
	  var usebusdays = document.getElementById("onlyweekdays").checked;
	  var usecb = false;
	  var cbs = document.getElementsByTagName("input");
	  for (var i=0;i<cbs.length;i++) {
		  if (cbs[i].type=="checkbox" && cbs[i].checked && cbs[i].id.match(/cb/)) {
			  usecb = true;
			  break;
		  }
	  }

	  senddownsub('s',basesdates,st,usebusdays,usecb);
	  senddownsub('e',baseedates,st,usebusdays,usecb);
	 /* if (baserdates[st]!="NA") {
		  senddownsub('r',baserdates,st,usebusdays,usecb);
	  }*/
	  if (baselpdates[st]!="NA") {
		  senddownsub('lp',baselpdates,st,usebusdays,usecb);
	  }
	  if (basefpdates[st]!="NA") {
		  senddownsub('fp',basefpdates,st,usebusdays,usecb);
	  }
	  if (basefrdates[st]!="NA") {
		  senddownsub('fr',basefrdates,st,usebusdays,usecb);
	  }
  }

  function filteritems() {
	  var filtertype = document.getElementById("filter").value;
	  window.location = filteraddr + '&filter=' + filtertype;
  }
  function chgorderby() {
	  var ordertype = document.getElementById("orderby").value;
	  window.location = orderaddr + '&orderby=' + ordertype;
  }
  function calcallback(y,m,d) {
	  globd.setYear(y);
	  globd.setMonth(m-1);
	  globd.setDate(d);
	  var el = window.CP_targetInput;
	  if (el.id.charAt(0)=='f') {
	 	 var chgid = el.id.substring(0,3)+el.id.substring(6);
	  } else {
	 	 var chgid = el.id.substring(0,2)+el.id.substring(5);
	  }
	  document.getElementById(chgid).innerHTML = globd.SHORTDAYS[globd.getDay()];
	  CP_tmpReturnFunction(y,m,d);
  }
  function datePickerClosed(dateField) {
	  var globd = getFieldDate(dateField.value);
	 if (dateField.id.charAt(0)=='f' || dateField.id.charAt(0)=='l') {
	 	 var chgid = dateField.id.substring(0,3)+dateField.id.substring(6);
	 } else {
	 	 var chgid = dateField.id.substring(0,2)+dateField.id.substring(5);
	 }
	  document.getElementById(chgid).innerHTML = globd.SHORTDAYS[globd.getDay()];
  }
  var availnames = [_("Hidden"),_("By Dates"),_("Always")];
  function MCDtoggle(type,cnt) {
  	if (type=='a') {
		var curval = $('#avail'+cnt).val();
		if (baserdates[cnt]=='NA') {
			curval = (curval+1)%3;
		} else {
			curval = 1-curval;
		}
		$('#avail'+cnt).val(curval);
		if (curval==1) {
			$('#avail'+cnt).closest('tr').find('td.togdis').removeClass('dis');
		} else {
			$('#avail'+cnt).closest('tr').find('td.togdis').addClass('dis');
		}
		if (curval!=0) {
			$('#avail'+cnt).closest('tr').find('td.togdishid').removeClass('dis');
		} else {
			$('#avail'+cnt).closest('tr').find('td.togdishid').addClass('dis');
		}
		$('#availname'+cnt).text(availnames[curval]);
	} else {
		var typeinput = document.getElementById(type+"datetype"+cnt);
		if (typeinput.value==0) { //swap from A/N to date
			document.getElementById(type+"span0"+cnt).className="hide";
			document.getElementById(type+"span1"+cnt).className="show";
			typeinput.value = 1;
		} else { //swap from date to A/N
			document.getElementById(type+"span0"+cnt).className="show";
			document.getElementById(type+"span1"+cnt).className="hide";
			typeinput.value = 0;
		}
	}

  }
  function MCDtoggleselected(form) {
	  var type = document.getElementById("swaptype").value;
	  var to = document.getElementById("swapselected").value;
	  var els = form.getElementsByTagName("input");
	  for (var i=0; i<els.length; i++) {
		  if (els[i].type=='checkbox' && els[i].checked && els[i].id!='ca') {
			var cnt = els[i].id.substr(2);
			try {
				if (type=='a') {
					$('#avail'+cnt).val((baserdates[cnt]!='NA' && to==2)?1:to);
					if (to==0) {
						$(els[i]).closest('tr').find('td.togdishid').addClass('dis');
					} else {
						$(els[i]).closest('tr').find('td.togdishid').removeClass('dis');
					}
					if (to==1 || (baserdates[cnt]!='NA' && to==2)) {
						$('#availname'+cnt).text(availnames[1]);
						$(els[i]).closest('tr').find('td.togdis').removeClass('dis');
					} else {
						$('#availname'+cnt).text(availnames[to]);
						$(els[i]).closest('tr').find('td.togdis').addClass('dis');
					}

				} else {
					if (to=="dates") { //swap from A/N to date
						document.getElementById(type+"span0"+cnt).className="hide";
						document.getElementById(type+"span1"+cnt).className="show";
						document.getElementById(type+"datetype"+cnt).value = 1;
					} else { //swap from date to A/N
						document.getElementById(type+"span0"+cnt).className="show";
						document.getElementById(type+"span1"+cnt).className="hide";
						document.getElementById(type+"datetype"+cnt).value = 0;
						if (/*type=='r' ||*/ type=='fp' || type=='fr') {
							if (to=='always') {
								document.getElementById(type+"dateanA"+cnt).checked=true;
							} else {
								document.getElementById(type+"dateanN"+cnt).checked=true;
							}
						}
					}
				}
			} catch (e) { };
			//els[i].checked = false;
		  }
	  }
  }
  var MCDrepeatcounter = 0;
  function MCDselectblockgrp(el,lvl) {
  	if (MCDrepeatcounter==0) {setTimeout(function() {MCDrepeatcounter=0;},500);}
  	MCDrepeatcounter++;
  	if (MCDrepeatcounter>2) {return;}

  	var val = el.checked;
  	var thisid = parseInt(el.id.substr(2));
  	var els = el.form.getElementsByTagName("input");
	  for (var i=0; i<els.length; i++) {
		  if (els[i].type=='checkbox' && els[i].id.match(/cb/)) {
		  	  curid = parseInt(els[i].id.substr(2));
		  	  if (curid>thisid) {
		  	  	if (els[i].value > lvl) {
		  	  		els[i].checked = val;
		  	  	} else {
		  	  		break;
		  	  	}
		  	  }
		  }
  	}
  }

  function chkAll(frm, mark) {
  	var els = frm.getElementsByTagName("input");
	  for (var i=0; i<els.length; i++) {
		  if (els[i].type=='checkbox' && els[i].id!='ca') {
			  try{
				  if(els[i].type == "checkbox" && els[i].id != 'ca') {
					  els[i].checked = mark;
				  }
  			  } catch(er) {}
		  }
  	}
  }
  function submittheform() {
  	  var form = document.getElementById("realform");
  	  prepforsubmit(form);
  	  form.submit();
  }
  function prepforsubmit(frm) {
  	var cnt = document.getElementById("chgcnt").value;
  	for (var i=0;i<cnt;i++) {
  		var out = [];

  		if (document.getElementById("sdatetype"+i).value == 0) {
  			out.push(0);
  		} else {
  			out.push(document.getElementById("sdate"+i).value + "~" + document.getElementById("stime"+i).value);
  		}
  		if (document.getElementById("edatetype"+i).value == 0) {
  			out.push(2000000000);
  		} else {
  			out.push(document.getElementById("edate"+i).value + "~" + document.getElementById("etime"+i).value);
  		}
  		if (includeassess && document.getElementById("rdatetype"+i)) {
  			if (document.getElementById("rdatetype"+i).value == 0) {
  				out.push("N");
  			} else {
  				out.push("A");
  			}
  		} else {
  			out.push('NA');
  		}
  		if (includeforums && document.getElementById("fpdatetype"+i)) {
  			if (document.getElementById("fpdatetype"+i).value == 0) {
  				if (document.getElementById("fpdateanN"+i).checked) {
  					out.push("N");
  				} else {
  					out.push("A");
  				}
  			} else {
  				out.push(document.getElementById("fpdate"+i).value + "~" + document.getElementById("fptime"+i).value);
  			}
  		} else {
  			out.push('NA');
  		}
  		if (includeforums && document.getElementById("frdatetype"+i)) {
  			if (document.getElementById("frdatetype"+i).value == 0) {
  				if (document.getElementById("frdateanN"+i).checked) {
  					out.push("N");
  				} else {
  					out.push("A");
  				}
  			} else {
  				out.push(document.getElementById("frdate"+i).value + "~" + document.getElementById("frtime"+i).value);
  			}
  		} else {
  			out.push('NA');
  		}
  		if (includeassess && document.getElementById("lpdatetype"+i)) {
  			if (document.getElementById("lpdatetype"+i).value == 0) {
  				out.push("N");
  			} else {
  				out.push(document.getElementById("lpdate"+i).value + "~" + document.getElementById("lptime"+i).value);
  			}
  		} else {
  			out.push('NA');
  		}
  		out.push(document.getElementById("type"+i).value);
  		out.push(document.getElementById("id"+i).value);
  		out.push(document.getElementById("avail"+i).value);
  		var newel = document.createElement("input");
  		newel.name = "data"+i;
  		newel.type = "hidden";
  		newel.value = out.join(",");
  		frm.appendChild(newel);
  	}
  }

  function chgswaptype(el) {
  	  var elout = document.getElementById("swapselected");
  	  elout.options.length = 0;

  	  if (el.value=='a') {
  	  	   elout.options[elout.options.length] = new Option('Hidden','0',false,false);
  	  	   elout.options[elout.options.length] = new Option('By Dates','1',false,false);
  	  	   elout.options[elout.options.length] = new Option('Always/By Dates','2',false,false);
  	  } else if (el.value=='r') {
  	  	   elout.options[elout.options.length] = new Option('Never','never',false,false);
  	  	   elout.options[elout.options.length] = new Option('After Due','dates',false,false);
  	  } else if (el.value=='lp') {
  	  	   elout.options[elout.options.length] = new Option('No Limit','always',false,false);
  	  	   elout.options[elout.options.length] = new Option('By Dates','dates',false,false);
  	  } else {
  	  	   elout.options[elout.options.length] = new Option('Always','always',false,false);
		  if (el.value=='r' || el.value=='fp' || el.value=='fr') {
			  elout.options[elout.options.length] = new Option('Never','never',false,false);
		  }
		  elout.options[elout.options.length] = new Option('Dates','dates',false,false);
  	  }

  }

  function toggleMCDincforum() {
  	  var cookiereg = new RegExp('mcdincforum'+cid+'=[^;]*','i');
  	  if (includeforums) {
  	  	$('#MCDforumtoggle').text(_('Show Forum Dates'));
  	  	$('td.mcf, th.mcf').hide();
  	  } else {
  	  	$('#MCDforumtoggle').text(_('Hide Forum Dates'));
  	  	$('td.mcf, th.mcf').show();
  	  }
  	  includeforums = !includeforums;
  }
  function toggleMCDincassess() {
  	  var cookiereg = new RegExp('mcdincassess'+cid+'=[^;]*','i');
  	  if (includeassess) {
  	  	$('#MCDassesstoggle').text(_('Show Assessment Dates'));
  	  	$('td.mca, th.mca').hide();
  	  } else {
  	  	$('#MCDassesstoggle').text(_('Hide Assessment Dates'));
  	  	$('td.mca, th.mca').show();
  	  }
  	  includeassess = !includeassess;
  }

  	//TODO: separately calculate day difference (using daysBetween and getWeekDays) and time difference separately
	//can use getHours()*60+getMinutes() to get minutes into day, then multiply to get ms for timediff
	//then use date object, set to basesdate, use addDays or addBizDays to add the days, and setTime(getTime()+d) for time diff.
 /* function senddown(st) {
	  var d = new Date();
	  var newstartdate = document.getElementById("sdate"+st).value;
	  if (newstartdate!=0 && newstartdate!=2000000000) {
		timeel = document.getElementById("stime"+st);
		if (timeel==null) {
			curdate = Date.parse(newstartdate)/1000;
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
			curdate = Date.parse(newstartdate)/1000;
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
  */
