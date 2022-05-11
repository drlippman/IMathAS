/*
tree form:
[id,rights,name,disabled,checked,federated]

var tree = {
0:[
   [1,4,"Root1",0,0,0],
   [2,8,"Root2",0,0,0],
   [3,4,"Root3",0,0,0] ],
1:[
   [4,4,"Child1",1,0,0],
   [5,4,"Child2",0,0,0] ],
3:[
   [6,4,"Child3",0,0,0],
   [7,4,"Child4",1,0,0] ]
};

var treebox = "checkbox";
var select = "child";
*/

var showlibtreechecks = true;

function buildbranch(parentid) {
        var outnode = document.createElement("ul");
	if (parentid==0) {
		outnode.className = "base";
	} else {
		outnode.className = 'show';
	}
	outnode.id = parentid;
        for (var i=0; i<tree[parentid].length; i++) {
                node = document.createElement("li");
                node.id = 'li'+tree[parentid][i][0];
		dashspan = document.createElement("span");
		if ((tree[tree[parentid][i][0]]==null || tree[parentid][i][0]==0) && (select=="parent" || treebox=="radio"))  {
			dashspan.appendChild(document.createTextNode('---'));
		} else {
			dashspan.appendChild(document.createTextNode('-'));
		}

		dashspan.className = 'dd';
		node.appendChild(dashspan);

                if (treebox == "checkbox") {
			chbx = document.createElement("input");
			chbx.type = "checkbox"
			chbx.name = "libs[]";
		} else {// if (treebox == "radio") {
			try {
				chbx = document.createElement('<input type="radio" name="libs" />');
			} catch(err){
				chbx = document.createElement("input");
				chbx.type = "radio"
				chbx.name = "libs";
			}
		}
		chbx.value = tree[parentid][i][0];
		if (tree[parentid][i][3]==1) {
			chbx.disabled = true;
		}
		if (tree[parentid][i][4]==1 && showlibtreechecks) {
			chbx.defaultChecked = true;
		}
                span = document.createElement("span");
                span.id = 'n'+tree[parentid][i][0];
                span.className = 'r'+tree[parentid][i][1];
                //span.appendChild(document.createTextNode(' '+tree[parentid][i][2]));
                span.innerHTML = ' '+tree[parentid][i][2]+(tree[parentid][i][5]?' <span class=fedico title="Federated">&lrarr;</span>':'');
                if (tree[tree[parentid][i][0]]!=null && tree[parentid][i][0]!=0) {  //has children
                        node.className = 'lihdr';
                        hdr = document.createElement("span");
                        hdr.className = 'hdr';
                        //hdr.onclick = 'toggle('+tree[parentid][i][0]+')';

                        btn = document.createElement("span");
                        btn.className = 'btn';
                        btn.id = 'b'+tree[parentid][i][0];
			btn.appendChild(document.createTextNode('+'));
                        hdr.appendChild(btn);
			if (tree[parentid][i][3]!=-1) {
				hdr.appendChild(chbx);
				btn.onclick = new Function("toggle("+tree[parentid][i][0]+")");
				span.onclick = new Function("toggle("+tree[parentid][i][0]+")");
			} else {
				hdr.onclick = new Function("toggle("+tree[parentid][i][0]+")");
			}
                        hdr.appendChild(span);
                        node.appendChild(hdr);
                } else {  //no children
			if (tree[parentid][i][3]!=-1) {
				node.appendChild(chbx);
			}
                        node.appendChild(span);
                }
                outnode.appendChild(node);
        }
        return outnode;
}

function toggle(id) {
        if (document.getElementById('li'+id).lastChild.tagName!="UL") {
                addbranch(id);
		document.getElementById('b'+id).innerHTML = '-';
        } else {
                node = document.getElementById(id);
		button = document.getElementById('b'+id);
		if (node.className == "show") {
			node.className = "hide";
			button.innerHTML = "+";
		} else {
			node.className = "show";
			button.innerHTML = "-";
		}
        }
}

function addbranch(id) {
	try {
		var addtoli = document.getElementById("li"+id);
		document.getElementById('b'+id).innerHTML = '-';
		addtoli.appendChild(buildbranch(id));
	} catch (er) {}
}
function setlib() {
	var frm = document.getElementById("libselectform");
	var cnt = 0;
	var chlibs = new Array();
	var chlibsn = new Array();
	for (i = 0; i <= frm.elements.length; i++) {
		try{
			if(frm.elements[i].name == 'libs[]' || frm.elements[i].name=='libs') {
				if (frm.elements[i].checked == true && frm.elements[i].disabled == false) {
					chlibs[cnt] = frm.elements[i].value;
					chlibsn[cnt] = document.getElementById('n'+chlibs[cnt]).innerHTML.replace(/\s+/g,' ').trim();
					cnt++;
				}
			}
		} catch(er) {}
	}
	if (opener) {
  	opener.setlib(chlibs.join(","));
  	opener.setlibnames(chlibsn.join(", "));
  	self.close();
	} else {
		window.parent.setlib(chlibs.join(","));
		window.parent.setlibnames(chlibsn.join(", "));
		window.parent.GB_hide();
	}

}

function uncheckall(frm) {
	for (i = 0; i <= frm.elements.length; i++) {
		try{
			if(frm.elements[i].name == 'libs[]' || frm.elements[i].name=='libs') {
				if (frm.elements[i].checked == true) {
					frm.elements[i].checked = false;
				}
			}
		} catch(er) {}
	}
}
