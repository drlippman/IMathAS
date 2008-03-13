//dropdown menu
var closetimer	= 0;
var ddmenuitem	= 0;

// open hidden layer
function mopen(id) {	
	mcancelclosetime();
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
	ddmenuitem = document.getElementById(id);
	ddmenuitem.style.visibility = 'visible';
}
// close showed layer
function mclose() {
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
}
// go close timer
function mclosetime() {
	closetimer = window.setTimeout(mclose, 250);
}
// cancel close timer
function mcancelclosetime() {
	if(closetimer)
	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
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

function arraysearch(needle,hay) {
      for (var i=0; i<hay.length;i++) {
            if (hay[i]==needle) {
                  return i;
            }
      }
      return -1;
   }
