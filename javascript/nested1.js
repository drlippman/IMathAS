// Version: 1.20
// Date: 2007-01-25
// Author: CrazyDave
// Website: http://www.clanccc.co.uk/moo/nested.html
var noblockcookie = false;
var Nested = new Class({
	getOptions: function() {
		return {
			childTag: 'LI',
			ghost: false,
			childStep: 20, // attempts to become a child if the mouse is moved this number of pixels right
			handleClass: 'icon', 
			onStart: Class.empty,
			onComplete: Class.empty,
			onFirstChange: Class.empty,
			collapse: false, // true/false
			collapseClass: 'nCollapse', // Class added to collapsed items
			expandKey: 'shift', // control | shift
			lock: 'class', // parent || depth || class
			lockClass: 'locked'
		};
	},

	initialize: function(list, options) {
		
		this.setOptions(this.getOptions(), options);
		if (!this.options.expandKey.match(/^(control|shift)$/)) {
			this.options.expandKey = 'shift';
		}
		this.list = $(list);
		this.options.parentTag = this.list.nodeName;
		this.bound = {};
		this.bound.start = this.start.bindWithEvent(this);
		this.haschanged = false;
		this.list.addEvent('mousedown', this.bound.start);
		if (this.options.collapse) {
			this.bound.collapse = this.collapse.bindWithEvent(this);
			this.list.addEvent('click', this.bound.collapse);
		}
		if (this.options.initialize) this.options.initialize.call(this);
	},

	start: function(event) {
		var el = $(event.target);
		if (this.options.handleClass) {
			while (el.nodeName != this.options.childTag && !el.hasClass(this.options.handleClass) && el != this.list) {
				el = el.getParent();
			}
			if (!el.hasClass(this.options.handleClass)) return true;
		} 
		while (el.nodeName != this.options.childTag && el != this.list) {
			el = el.parentNode;
		}
		if (el.nodeName != this.options.childTag) return true;
		el = $(el);
		if (this.options.lock == 'class' && el.hasClass(this.options.lockClass)) return;
		if (this.options.ghost) { // Create the ghost
			this.ghost = el.clone().setStyles({
				'list-style-type': 'none',
				'opacity': 0.5,
				'position': 'absolute',
				'visibility': 'hidden',
				'top': event.page.y+'px',
				'left': (event.page.x)+'px'
			}).injectInside(document.body);
		}
		el.depth = this.getDepth(el);
		el.moved = false;
		this.bound.movement = this.movement.bindWithEvent(this, el);
		this.bound.end = this.end.bind(this, el);
		this.list.removeEvent('mousedown', this.bound.start);
		this.list.addEvent('mousedown', this.bound.end);
		this.list.addEvent('mousemove', this.bound.movement);
		document.addEvent('mouseup', this.bound.end);
		if (window.ie) { // IE fix to stop selection of text when dragging
			this.bound.stop = this.stop.bindWithEvent(this);
			$(document.body).addEvent('drag', this.bound.stop).addEvent('selectstart', this.bound.stop);
		}
		this.fireEvent('onStart', el);
		event.stop();
	},

	collapse: function(event) {
		var el = $(event.target);
		if (this.options.handleClass) {
			while (el.nodeName != this.options.childTag && !el.hasClass(this.options.handleClass) && el != this.list) {
				el = el.getParent();
			}
			if (!el.hasClass(this.options.handleClass)) return true;
		} 
		while (el.nodeName != this.options.childTag && el != this.list) {
			el = el.parentNode;
		}
		if (el == this.list) return;
		el = $(el);
		if (!el.moved) {
			var sub = $E(this.options.parentTag, el);
			if (sub) {
				if (noblockcookie) {
					if (sub.getStyle('display') == 'none') {
						sub.setStyle('display', 'block');
						el.removeClass(this.options.collapseClass);
					} else {
						sub.setStyle('display', 'none');
						el.addClass(this.options.collapseClass);
					}
				} else {
					oblist = oblist.split(',');
					var obn = el.getAttribute("obn");
					var loc = arraysearch(obn,oblist);
					if (sub.getStyle('display') == 'none') {
						sub.setStyle('display', 'block');
						el.removeClass(this.options.collapseClass);
						if (loc==-1) {oblist.push(obn);}
					} else {
						sub.setStyle('display', 'none');
						el.addClass(this.options.collapseClass);
						if (loc>-1) {oblist.splice(loc,1);}
					}
					oblist = oblist.join(',');
					document.cookie = 'openblocks-' +cid+'='+ oblist;
				}
			}
		}
		event.stop();
	},
	
	stop: function(event) {
		event.stop();
		return false;
	},
	
	getDepth: function(el, add) {
		var counter = (add) ? 1 : 0;
		while (el != this.list) {
			if (el.nodeName == this.options.parentTag) counter += 1;
			el = el.parentNode;
		}
		return counter;
	},
	
	movement: function(event, el) {
		var dir, over, check, items;
		var dest, move, prev, prevParent;
		var abort = false;
		if (this.options.ghost && el.moved) { // Position the ghost
			this.ghost.setStyles({
				'position': 'absolute',
				'visibility': 'visible',
				'top': event.page.y+'px',
				'left': (event.page.x-20)+'px'
			});
		}
		over = event.target;
		while (over.nodeName != this.options.childTag && over != this.list) {
			over = over.parentNode;
		}
		if (over == this.list) return;
		if (event[this.options.expandKey] && over != el && over.hasClass(this.options.collapseClass)) {
			check = $E(this.options.parentTag, over);
			over.removeClass(this.options.collapseClass);
			check.setStyle('display', 'block');
		}
		// Check if it's actually inline with a child element of the event firer
		orig = over;
		if (el != over) {
			items = $ES(this.options.childTag, over);
			items.each(function(item) {
				if (event.page.y > item.getTop() && item.offsetHeight > 0) over = item;
			});
		}
		// Make sure we end up with a childTag element
		if (over.nodeName != this.options.childTag) return;
			
		// store the previous parent 'ol' to remove it if a move makes it empty
		prevParent = el.getParent();
		dir = (event.page.y < el.getTop()) ? 'up' : 'down';
		move = 'before';
		dest = el;

		if (el != over) {
			check = over;
			while (check != null && check != el) {
				check = check.parentNode;
			} // Make sure we're not trying to move something below itself
			if (check == el) return;
			if (dir == 'up') {
				move = 'before'; dest = over;
			} else {
				sub = $E(this.options.childTag, over);
				if (sub && sub.offsetHeight > 0) {
					move = 'before'; dest = sub;
				} else {
					move = 'after'; dest = over;
				}
			}
		}

		// Check if we're trying to go deeper -->>
		prev = (move == 'before') ? dest.getPrevious() : dest;
		if (prev) {
			move = 'after';
			dest = prev;
			check = $E(this.options.parentTag, dest);
			while (check && event.page.x > check.getLeft() && check.offsetHeight > 0) {
				dest = check.getLast();
				check = $E(this.options.parentTag, dest);
			}
			if (!check && event.page.x > dest.getLeft()+this.options.childStep && dest.tagName == 'LI' && dest.className=="blockli") {
				//document.getElementById("submitnotice").innerHTML = dest.parentNode.tagName + ',' + dest.parentNode.parentNode.tagName;
				move = 'inside';
			}
			
		}

		last = dest.getParent().getLast();
		while (((move == 'after' && last == dest) || last == el) && dest.getParent() != this.list && event.page.x < dest.getLeft()) {
			move = 'after';
			dest = $(dest.parentNode.parentNode);
			last = dest.getParent().getLast();
		}
		
		abort = false;
		if (move != '') {
			abort += (dest == el);
			abort += (move == 'after' && dest.getNext() == el);
			abort += (move == 'before' && dest.getPrevious() == el);
			abort += (this.options.lock == 'depth' && el.depth != this.getDepth(dest, (move == 'inside')));
			abort += (this.options.lock == 'parent' && (move == 'inside' || dest.parentNode != el.parentNode));
			//abort += (move=='inside' && dest.parentNode.className != "blockli");
			abort += (dest.parentNode.hasClass('nochildren'));
			abort += (dest.offsetHeight == 0);
			sub = $E(this.options.parentTag, over);
			sub = (sub) ? sub.getTop() : 0;
			sub = (sub > 0) ? sub-over.getTop() : over.offsetHeight;
			abort += (event.page.y < (sub-el.offsetHeight)+over.getTop());
			if (!abort) {
				if (move == 'inside') {
					dest = new Element(this.options.parentTag).injectInside(dest);
					dest.className = "qview";
				}
				$(el).inject(dest, move);
				el.moved = true;
				if (!prevParent.getFirst()) prevParent.remove();
				if (!this.haschanged) {
					this.haschanged = true;
					this.fireEvent('onFirstChange', el);
				}
			}
		}
		event.stop();
	},

	detach: function() {
		this.list.removeEvent('mousedown', this.start.bindWithEvent(this));
		if (this.options.collapse) this.list.removeEvent('click', this.bound.collapse);
	},

	serialize: function(listEl) {
		var serial = [];
		var kids;
		if (!listEl) listEl = this.list;
		$$(listEl.childNodes).each(function(node, i) {
			kids = $E(this.options.parentTag, node);
			serial[i] = {
				id: node.id,
				children: (kids) ? this.serialize(kids) : []
			};
		}.bind(this));
		return serial;
	},

	end: function(el) {
		if (this.options.ghost) this.ghost.remove();
		this.list.removeEvent('mousemove', this.bound.movement);
		document.removeEvent('mouseup', this.bound.end);
		this.list.removeEvent('mousedown', this.bound.end);
		this.list.addEvent('mousedown', this.bound.start);
		this.fireEvent('onComplete', el);
		if (window.ie) $(document.body).removeEvent('drag', this.bound.stop).removeEvent('selectstart', this.bound.stop);
	}
});

Nested.implement(new Events);
Nested.implement(new Options);

var sortIt;
window.onDomReady(function() {
	sortIt = new Nested('qviewtree', {
		collapse: true,
		onStart: function(el) {
			el.addClass('drag');
		},
		onComplete: function(el) {
			el.removeClass('drag');
		},
		onFirstChange: function(el) {
			document.getElementById('recchg').disabled = false;
			setlinksdisp("none");
			window.onbeforeunload = function() {return unsavedmsg;}
			document.getElementById("submitnotice").innerHTML = "";
		}
	});
});

function toSimpleJSON(a) {
	var out = '[';
	for (var i=0;i<a.length;i++) {
		if (i>0) { out += ',';}
		out += a[i].id
		if (a[i].children.length>0) {
			out += ':'+toSimpleJSON(a[i].children);
		}
	}
	out += ']';
	return out;
}

function submitChanges() { 
  var params = 'checkhash='+itemorderhash+'&order='+toSimpleJSON(sortIt.serialize());
  var url = AHAHsaveurl;
  var els = document.getElementsByTagName("input");
  for (var i=0; i<els.length; i++) {
	  if (els[i].type=="hidden" && els[i].value!="") {
	  	  params += '&'+els[i].id.substring(5) + '=' + encodeURIComponent(els[i].value);
	  } else if (els[i].type=="text" && els[i].className=="outcome") {
		  params += '&'+els[i].id + '=' + encodeURIComponent(els[i].value);
	  }
  }

  var target = "submitnotice";
  //document.getElementById(target).innerHTML = url;
  //return;

  document.getElementById(target).innerHTML = ' Saving Changes... ';
  jQuery.ajax({
		type: "POST",
		url: url,
		data: params
	})
	.done(function(data) {
		if (data.charAt(0)=='1') {
			var p = data.indexOf(':');
			itemorderhash = data.substring(2,p);
			document.getElementById(target).innerHTML='';
			document.getElementById('recchg').disabled = true;
			window.onbeforeunload = null;
			setlinksdisp("");
			document.getElementById("qviewtree").innerHTML = data.substring(p+1);
			sortIt.haschanged = false;
		} else if (data.charAt(0)=='2') {
			document.getElementById('recchg').disabled = true;
			window.onbeforeunload = null;
			document.getElementById(target).innerHTML=_("Saved");
			sortIt.haschanged = false;
	    	} else {
			document.getElementById(target).innerHTML=data.substring(2);
		}
	})
	.fail(function(xhr, status, errorThrown) {
	  document.getElementById(target).innerHTML=" Couldn't save changes:\n"+
			status + "\n" +req.statusText+
			"\nError: "+errorThrown
	});
}  

function quickviewexpandAll() {
	jQuery("#qviewtree li.blockli.nCollapse").removeClass("nCollapse").children("ul").show();
}
function quickviewcollapseAll() {
	jQuery("#qviewtree li.blockli:not(.nCollapse)").addClass("nCollapse").children("ul").hide();
}

function setlinksdisp(disp) {
	var el = document.getElementsByTagName("span");
	for (var i=0; i<el.length; i++) {
		if (el[i].className=='links') {
			el[i].style.display = disp;
		}
	}
}

function editinplace(el) {
	var inputh = document.getElementById('input'+el.id);
	if (inputh==null) {
		var inputh = document.createElement("input");
		inputh.id = 'input'+el.id;
		inputh.type = "hidden";
		el.parentNode.insertBefore(inputh,el);	
		var inputt  = document.createElement("input");
		inputt.id = 'inputt'+el.id;
		inputt.type = "text";
		inputt.size = 60;
		inputt.onblur = editinplaceun;
		el.parentNode.insertBefore(inputt,el);	
	} else {
		inputt = document.getElementById('inputt'+el.id);
		inputt.style.display = "inline";
	}
	inputt.value = el.innerHTML;
	el.style.display = "none";
	inputt.focus();
}

function editinplaceun() {
	var el = document.getElementById(this.id.substring(6));
	var input =  document.getElementById('input'+this.id.substring(6));
	if (el.innerHTML != this.value) {
		el.innerHTML = this.value;
		//input.parentNode.removeChild(input);
		input.value = this.value;
		document.getElementById('recchg').disabled = false;
		setlinksdisp("none");
		window.onbeforeunload = function() {return unsavedmsg;}
	}

	el.style.display = 'inline';
	this.style.display = "none";
}
