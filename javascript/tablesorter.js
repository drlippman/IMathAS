/************************************************************************************************************
(C) www.dhtmlgoodies.com, November 2005

This is a script from www.dhtmlgoodies.com. You will find this and a lot of other scripts at our website.

Terms of use:
You are free to use this script as long as the copyright message is kept intact. However, you may not
redistribute, sell or repost it without our permission.

Thank you!

www.dhtmlgoodies.com
Alf Magne Kalleland

************************************************************************************************************/

(function() {
	var tableWidget_okToSort = true;
	var tableWidget_arraySort = [];
	var tableWidget_tableCounter = 1;
	var activeColumn = [];
	var evenodd = [];
	var dosortlast = [];
	var skiplast = [];
	var skipfirst = [];

	function sortNumeric(a,b){
		var p;
		try {
			//reRowText = /(\< *[^\>]*\>|\&nbsp\;|\,|[^\d\.\/])/g;
			//a = a.replace(reRowText,"");
			//b = b.replace(reRowText,"");

			//a = a.replace(/,/,'.');
			//b = b.replace(/,/,'.');
			//a = a.replace(/[^\d\.\/]/g,'');
			//b = b.replace(/[^\d\.\/]/g,'');
			//if(a.indexOf('/')>=0) a = eval(a);
			//if(b.indexOf('/')>=0) b = eval(b);
			a = a.replace(/\<\s*[^\>]*\>/g,'');
			b = b.replace(/\<\s*[^\>]*\>/g,'');
			if (p = a.match(/([\d\.]+)\s*(%|min)/)) {
				a = parseFloat(p[1]);
			} else {
				a = parseFloat(a);
			}
			if (p = b.match(/([\d\.]+)\s*(%|min)/)) {
				b = parseFloat(p[1]);
			} else {
				b = parseFloat(b);
			}

			if (isNaN(a)) { a=-1; }
			if (isNaN(b)) { b=-1; }

			return a/1 - b/1;
		} catch(e) {
			return 0;
		}
	}
	function sortPercent(a,b){
		try {
			a = parseFloat(a.match(/[\d\.]+\s*%/));
			b = parseFloat(b.match(/[\d\.]+\s*%/));

			if (isNaN(a)) { a=-1; }
			if (isNaN(b)) { b=-1; }

			return a/1 - b/1;
		} catch(e) {
			return 0;
		}
	}
	function sortDate(a,b) {
		var months = {"jan":1,"feb":2,"mar":3,"apr":4,"may":5,"jun":6,"jul":7,"aug":8,"sep":9,"oct":10,"nov":11,"dec":12};
		var ar;
		if (ar = a.match(/(\d+)\/(\d+)\/(\d+),?\s+(\d+):(\d+)\s*(am|pm)/)) {
			a = ar[3]*10000 + ar[1]*100 + 1*ar[2] + .01*(ar[4]/1-(ar[4]/1==12?12:0)+(ar[6]=='pm'?12:0))+.0001*ar[5]/1;
		} else if (ar = a.match(/(\d+)\/(\d+)\/(\d+)/)) {
			a = ar[3]*10000 + ar[1]*100 + 1*ar[2];
		} else if (ar = a.match(/([a-zA-Z]+)\s+(\d+),?\s*(\d+),?\s*(\d+):(\d+)\s*(am|pm)/)) {
			a = ar[3]*10000 + months[ar[1].toLowerCase().substr(0,3)]*100 + 1*ar[2] + .01*(ar[4]/1-(ar[4]/1==12?12:0)+(ar[6]=='pm'?12:0))+.0001*ar[5]/1;
		} else {
			return -1;
		}
		if (ar = b.match(/(\d+)\/(\d+)\/(\d+),?\s+(\d+):(\d+)\s*(am|pm)/)) {
			b = ar[3]*10000 + ar[1]*100 + 1*ar[2] + .01*(ar[4]/1-(ar[4]/1==12?12:0)+(ar[6]=='pm'?12:0))+.0001*ar[5]/1;
		} else if (ar = b.match(/(\d+)\/(\d+)\/(\d+)/)) {
			b = ar[3]*10000 + ar[1]*100 + 1*ar[2];
		} else if (ar = b.match(/([a-zA-Z]+)\s+(\d+),?\s*(\d+),?\s*(\d+):(\d+)\s*(am|pm)/)) {
			b = ar[3]*10000 + months[ar[1].toLowerCase().substr(0,3)]*100 + 1*ar[2] + .01*(ar[4]/1-(ar[4]/1==12?12:0)+(ar[6]=='pm'?12:0))+.0001*ar[5]/1;
		} else {
			return 1;
		}
		return a/1 - b/1;
	}

	function sortString(a, b) {
		try {
			a+='';
			b+='';

			var reRowText = /(\< *[^\>]*\>|\&nbsp\;)/g;
			a = a.replace(reRowText,"");
			b = b.replace(reRowText,"");

			if (!isNaN(a/1) && !isNaN(b/1)) {
				return a/1 - b/1;
			}

			if ( a.toUpperCase() < b.toUpperCase() ) return -1;
			if ( a.toUpperCase() > b.toUpperCase() ) return 1;
			return 0;
		} catch(e) {
			return 0;
		}
	}
	function sortSortby(a, b) {
		try {
			a+='';
			b+='';

			var reRowText = /.*sortby([\d\.]+).*/;
			a = a.replace(reRowText,"$1");
			b = b.replace(reRowText,"$1");
			if (!isNaN(a/1) && !isNaN(b/1)) {
				return a/1 - b/1;
			}

			if ( a.toUpperCase() < b.toUpperCase() ) return -1;
			if ( a.toUpperCase() > b.toUpperCase() ) return 1;
			return 0;
		} catch(e) {
			return 0;
		}
	}

	function sortTable(e)
	{
		var el = e.currentTarget;
		if(!tableWidget_okToSort)return;
		tableWidget_okToSort = false;
		/* Getting index of current column */
		var obj = el;
		var indexThis = 0;
		while (obj.previousSibling) {
			obj = obj.previousSibling;
			if (obj.tagName=='TH') indexThis++;
		}

		var direction;
		if(el.getAttribute('aria-sort') || el.direction) {
			direction = el.getAttribute('aria-sort') ?? el.direction;
			if (direction=='ascending') {
				direction = 'descending';
				el.setAttribute('aria-sort','descending');
				el.direction = 'descending';
			}else{
				direction = 'ascending';
				el.setAttribute('aria-sort','ascending');
				el.direction = 'ascending';
			}
		} else {
			direction = 'ascending';
			el.setAttribute('aria-sort','ascending');
			el.direction = 'ascending';
		}

		var tableObj = el.parentNode.parentNode.parentNode;
		var tBody = tableObj.getElementsByTagName('TBODY')[0];
		var widgetIndex = tableObj.getAttribute('tableIndex');
		if(!widgetIndex) widgetIndex = tableObj.tableIndex;

		var sortMethod = tableWidget_arraySort[widgetIndex][indexThis]; // N = numeric, S = String
		var tableSkipFirst = skipfirst[widgetIndex] !== undefined ? skipfirst[widgetIndex] : 0;
		var tableSkipLast = skiplast[widgetIndex] !== undefined ? skiplast[widgetIndex] : 0;
		var tableEvenOdd = evenodd[widgetIndex] === true;
		if(activeColumn[widgetIndex] && activeColumn[widgetIndex]!=el){
			if(activeColumn[widgetIndex])activeColumn[widgetIndex].removeAttribute('aria-sort');
		}

		activeColumn[widgetIndex] = el;

		var cellArray = new Array();
		var cellObjArray = new Array();
		var cellStartObjArray = new Array();
		var cellEndObjArray = new Array();
		for (var no=1; no<1+tableSkipFirst; no++) {
			cellStartObjArray.push(tableObj.rows[no].cells[indexThis]);
		}
		for(var no=1+tableSkipFirst;no<tableObj.rows.length-tableSkipLast;no++){
			if (indexThis>=tableObj.rows[no].cells.length) {continue;}
			var content= tableObj.rows[no].cells[indexThis].innerHTML+'';
			cellArray.push(content);
			cellObjArray.push(tableObj.rows[no].cells[indexThis]);
		}
		for (var no=tableObj.rows.length-tableSkipLast; no<tableObj.rows.length; no++) {
			cellEndObjArray.push(tableObj.rows[no].cells[indexThis]);
		}

		if(sortMethod=='N'){
			cellArray = cellArray.sort(sortNumeric);
		} else if (sortMethod=='D') {
			cellArray = cellArray.sort(sortDate);
		} else if (sortMethod=='B') {
			cellArray = cellArray.sort(sortSortby);
		} else if (sortMethod=='P') {
			cellArray = cellArray.sort(sortPercent);
		} else{
			cellArray = cellArray.sort(sortString);
		}

		if (tableSkipFirst>0) {
			for (var no=0; no<tableSkipFirst; no++) {
				tBody.appendChild(cellStartObjArray[no].parentNode);
			}
		}
		if(direction=='descending'){
			for(var no=cellArray.length;no>=0;no--){
				for(var no2=0;no2<cellObjArray.length;no2++){
					if(cellObjArray[no2].innerHTML == cellArray[no] && !cellObjArray[no2].getAttribute('allreadySorted')){
						cellObjArray[no2].setAttribute('allreadySorted','1');
						tBody.appendChild(cellObjArray[no2].parentNode);
					}
				}
			}
		}else{
			for(var no=0;no<cellArray.length;no++){
				for(var no2=0;no2<cellObjArray.length;no2++){
					if(cellObjArray[no2].innerHTML == cellArray[no] && !cellObjArray[no2].getAttribute('allreadySorted')){
						cellObjArray[no2].setAttribute('allreadySorted','1');
						tBody.appendChild(cellObjArray[no2].parentNode);
					}
				}
			}
		}
		if (tableSkipLast>0) {
			for (var no=0; no<tableSkipLast; no++) {
				tBody.appendChild(cellEndObjArray[no].parentNode);
			}
		}



		if (tableEvenOdd) {
			for(var no=1;no<tableObj.rows.length;no++){
				if (no%2==0) {
					tableObj.rows[no].className = 'odd';
				} else {
					tableObj.rows[no].className = 'even';
				}
			}
		}

		for(var no2=0;no2<cellObjArray.length;no2++){
			cellObjArray[no2].removeAttribute('allreadySorted');
		}

		tableWidget_okToSort = true;


	}
	//sortlast:  true to sort last, false to not sort last, -n to not
	//sort last n rows.  undef sorts all
	function initSortTable(objId,sortArray,switchit,sortlast,sortfirst)
	{
		var obj = document.getElementById(objId);
		var widgetIndex = tableWidget_tableCounter;
		obj.setAttribute('tableIndex', widgetIndex);
		obj.tableIndex = widgetIndex;
		tableWidget_arraySort[widgetIndex] = sortArray;
		var tHead = obj.getElementsByTagName('THEAD')[0];
		var cells = tHead.getElementsByTagName('TH');
		for(var no=0;no<cells.length;no++){
			if(sortArray[no]){
				cells[no].addEventListener("click", sortTable);
				cells[no].setAttribute('tabindex', 0);
				cells[no].addEventListener("keydown", function(e) {
					if (e.key == 'Enter') {
						sortTable(e);
						e.preventDefault();
						return false;
					}
				});
			}
		}
		let caption = obj.querySelector("caption");
		if (!caption) {
			caption = document.createElement("caption");
			caption.className = "sr-only";
			obj.insertBefore(caption, obj.firstChild);
		}
		let infspan = document.createElement("span");
		infspan.className = "sr-only";
		infspan.textContent = _('Any focusable table header can be clicked to sort');
		caption.append(infspan);

		//for(var no2=0;no2<sortArray.length;no2++){	/* Right align numeric cells */
		//	if(sortArray[no2] && sortArray[no2]=='N')obj.rows[0].cells[no2].style.textAlign='right';
		//}

		if (switchit==true) {
			evenodd[widgetIndex] = true;
		} else {
			evenodd[widgetIndex] = false;
		}
		if (sortlast==false) {
			dosortlast[widgetIndex] = false;
			skiplast[widgetIndex] = 1;
		} else if (sortlast==true) {
			dosortlast[widgetIndex] = true;
			skiplast[widgetIndex] = 0;
		} else if (sortlast<0) {
			dosortlast[widgetIndex] = true;
			skiplast[widgetIndex] = -1*sortlast;
		} else {
			dosortlast[widgetIndex] = true;
			skiplast[widgetIndex] = 0;
		}
		if (sortfirst==false) {
			skipfirst[widgetIndex] = 1;
		} else if (sortfirst==true) {
			skipfirst[widgetIndex] = 0;
		} else if (sortfirst<0) {
			skipfirst[widgetIndex] = -1*sortfirst;
		} else {
			skipfirst[widgetIndex] = 0;
		}

		tableWidget_tableCounter++;
	}
	window.initSortTable = initSortTable;
})();