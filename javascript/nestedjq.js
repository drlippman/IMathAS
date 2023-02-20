// Version: 1.20
// Date: 2007-01-25
// Author: CrazyDave
// Website: http://www.clanccc.co.uk/moo/nested.html
var noblockcookie = false;
var Nested = function(listid, newoptions) {
	var options;
	var list;
	var haschanged;
	var ghost;

	initialize(listid, newoptions);

	function getOptions() {
		return {
			childTag: 'LI',
			ghost: false,
			childStep: 20, // attempts to become a child if the mouse is moved this number of pixels right
			handleClass: 'icon',
			onStart: function() {},
			onComplete: function() {},
			onFirstChange: function() {},
			collapse: false, // true/false
			collapseClass: 'nCollapse', // Class added to collapsed items
			expandKey: 'shift', // control | shift
			lock: 'class', // parent || depth || class
			lockClass: 'locked'
		};
	};

	function initialize(listid, newoptions) {
		options = getOptions();
		for (var i in newoptions) {
			options[i] = newoptions[i];
		}
		if (!options.expandKey.match(/^(control|shift)$/)) {
			options.expandKey = 'shift';
		}
		list = $('#'+listid);
		options.parentTag = list[0].nodeName;
		haschanged = false;
		list.on('mousedown.start', start);
		if (options.collapse) {
			list.on('click.collapse', collapse);
		}
		if (options.initialize) options.initialize.call(this);
	};

	function start(event) {
		var el = $(event.target);
		if (options.handleClass) {
			while (el[0].nodeName != options.childTag && !el.hasClass(options.handleClass) && el[0] != list[0]) {
				el = el.parent();
			}
			if (!el.hasClass(options.handleClass)) return true;
		}
		while (el[0].nodeName != options.childTag && el[0] != list[0]) {
			el = el.parent();
		}
		if (el[0].nodeName != options.childTag) return true;
		el = $(el);

		if (options.lock == 'class' && el.hasClass(options.lockClass)) return;
		if (options.ghost) { // Create the ghost
			ghost = el.clone().css({
				'list-style-type': 'none',
				'opacity': 0.5,
				'position': 'absolute',
				'visibility': 'hidden',
				'top': event.pageY+'px',
				'left': (event.pageX)+'px'
			}).appendTo(document.body);
		}
		el.depth = getDepth(el);
		el.moved = false;
		list.off('mousedown.start');
		list.on('mousedown.end', {el:el}, end);
		list.on('mousemove.movement', {el:el}, movement);
		$(document).on('mouseup.end', {el:el}, end);
		if (window.ie) { // IE fix to stop selection of text when dragging
			$(document.body).on('drag.stop', stop).on('selectstart.stop', stop);
		}
		options.onStart(el);
		event.stopPropagation();
		event.preventDefault();
	};

	function collapse(event) {
		var el = $(event.target);
		if (options.handleClass) {
			while (el[0].nodeName != options.childTag && !el.hasClass(options.handleClass) && el[0] != list[0]) {
				el = el.parent();
			}
			if (!el.hasClass(options.handleClass)) return true;
		}
		while (el[0].nodeName != options.childTag && el[0] != list[0]) {
			el = el.parent();
		}
		if (el[0] == list[0]) return;

		if (!el.moved) {
			var sub = el.find(options.parentTag);
			if (sub) {
				if (noblockcookie) {
					if (sub.length == 0 || sub.css('display') == 'none') {
						sub.css('display', 'block');
						el.removeClass(options.collapseClass);
					} else {
						sub.css('display', 'none');
						el.addClass(options.collapseClass);
					}
				} else {
					oblist = oblist.split(',');
					var obn = el.attr("obn");
					var loc = arraysearch(obn,oblist);
					if (sub.length == 0 || sub.css('display') == 'none') {
						sub.css('display', 'block');
						el.removeClass(options.collapseClass);
						if (loc==-1) {oblist.push(obn);}
					} else {
						sub.css('display', 'none');
						el.addClass(options.collapseClass);
						if (loc>-1) {oblist.splice(loc,1);}
					}
					oblist = oblist.join(',');
					document.cookie = 'openblocks-' +cid+'='+ oblist;
				}
			}
		}
		event.stopPropagation();
		event.preventDefault();
	};

	function stop(event) {
		event.stopPropagation();
		event.preventDefault();
		return false;
	};

	function getDepth(el, add) {
		var counter = (add) ? 1 : 0;
		while (el[0] != list[0]) {
			if (el[0].nodeName == options.parentTag) counter += 1;
			el = el.parent();
		}
		return counter;
	};

	function movement(event) {
		var dir, over, check, items;
		var dest, move, prev, prevParent;
		var abort = false;
		var el = event.data.el;
		if (options.ghost && el.moved) { // Position the ghost
			ghost.css({
				'position': 'absolute',
				'visibility': 'visible',
				'top': event.pageY+'px',
				'left': (event.pageX-20)+'px'
			});
		}
		over = event.target;
		while (over.nodeName != options.childTag && over != list[0]) {
			over = over.parentNode;
		}
		if (over == list[0]) return;
		if (event[options.expandKey] && over != el && over.hasClass(options.collapseClass)) {
			check = $(over).find(options.parentTag);
			over.removeClass(options.collapseClass);
			check.css('display', 'block');
		}
		// Check if it's actually inline with a child element of the event firer
		orig = over;
		if (el != over) {
			items = $(over).find(options.childTag);
			items.each(function(index,item) {
				if (event.pageY > $(item).offset().top && item.offsetHeight > 0) over = item;
			});
		}
		// Make sure we end up with a childTag element
		if (over.nodeName != options.childTag) return;

		// store the previous parent 'ol' to remove it if a move makes it empty
		prevParent = el.parent();
		dir = (event.pageY < el.offset().top) ? 'up' : 'down';
		move = 'before';
		dest = el;

		if (el[0] != over) {
			check = over;
			while (check != null && check != el[0]) {
				check = check.parentNode;
			} // Make sure we're not trying to move something below itself
			if (check == el[0]) return;
			if (dir == 'up') {
				move = 'before'; dest = $(over);
			} else {
				sub = $(over).find(options.childTag).first();
				if (sub && sub.height() > 0) {
					move = 'before'; dest = sub;
				} else {
					move = 'after'; dest = $(over);
				}
			}
		}
		// Check if we're trying to go deeper -->>
		prev = (move == 'before') ? dest.prev() : dest;
		if (prev.length > 0) {
			move = 'after';
			dest = prev;
			check = $(dest).find(options.parentTag).filter(':visible');
			while (check.length>0 && event.pageX > check.offset().left && check.height() > 0) {
				dest = check.find(options.childTag).last();
				check = dest.find(options.parentTag);
			}
			if (check.length==0 && dest[0]!=el[0] && event.pageX > dest.offset().left+options.childStep && dest[0].tagName == 'LI' && dest[0].className=="blockli") {
				//document.getElementById("submitnotice").innerHTML = dest.parentNode.tagName + ',' + dest.parentNode.parentNode.tagName;
				move = 'inside';
			}

		}
		last = dest.parent().children().last();

		while (((move == 'after' && last[0] == dest[0]) || last[0] == el[0]) && dest.parent() != list && event.pageX < dest.offset().left) {
			move = 'after';
			dest = dest.parent().parent();
			last = dest.parent().children(options.childTag).last();
		}
		abort = false;
		if (move != '') {
			abort += (dest == el);
			abort += (move == 'after' && dest.next() == el);
			abort += (move == 'before' && dest.prev() == el);
			abort += (options.lock == 'depth' && el.depth != getDepth(dest, (move == 'inside')));
			abort += (options.lock == 'parent' && (move == 'inside' || dest.parent() != el.parent()));
			//abort += (move=='inside' && dest.parentNode.className != "blockli");
			abort += (dest.parent().hasClass('nochildren'));
			abort += (dest.height() == 0);
			sub = $(over).find(options.parentTag);
			sub = (sub.length>0) ? sub.offset().top : 0;
			sub = (sub > 0) ? sub-$(over).offset().top : over.offsetHeight;
			abort += (event.pageY < (sub-el.height())+$(over).offset().top);
			if (!abort) {
				if (move == 'inside') {
					var newsub = $(document.createElement(options.parentTag)).addClass('qview');
					dest.append(newsub);
					dest = newsub;
				}
				if (move =='inside') {
					$(dest).append(el);
				} else if (move == 'after') {
					$(el).insertAfter(dest);
				} else if (move == 'before') {
					$(el).insertBefore(dest);
				}

				el.moved = true;
				if (prevParent.children().length==0) prevParent.remove();
				if (!haschanged) {
					haschanged = true;
					options.onFirstChange(el);
				}
			}
		}
		event.stopPropagation();
		event.preventDefault();
	};

	function detach() {
		list.off('mousedown.start');
		if (options.collapse) list.off('click.collapse');
	};

	function serialize(listEl) {
		var serial = [];
		var kids;
		if (!listEl) listEl = list;
		$(listEl).children().each(function(i,node) {
			kids = $(node).children(options.parentTag);
			serial[i] = {
				id: node.id,
				children: (kids) ? serialize(kids) : []
			};
		}.bind(this));
		return serial;
	};

	function end(event) {
		var el = event.data.el;
		if (options.ghost) ghost.remove();
		list.off('mousemove.movement');
		$(document).off('mouseup.end');
		list.off('mousedown.end');
		list.on('mousedown.start', start);
		options.onComplete(el);
		if (window.ie) $(document.body).off('drag.stop').off('selectstart.stop');
	};

	return {
		get haschanged() {return haschanged;},
		set haschanged(val) {haschanged = val;},
		fireEvent: function(eventName, args) { options[eventName](args);},
		serialize: serialize
	}
};

var sortIt;
$(function() {
	sortIt = Nested('qviewtree', {
		collapse: true,
		onStart: function(el) {
			$(el).addClass('drag');
		},
		onComplete: function(el) {
			$(el).removeClass('drag');
		},
		onFirstChange: function(el) {
			document.getElementById('recchg').disabled = false;
			setlinksdisp("none");
			//TODO: window.onbeforeunload = function() {return unsavedmsg;}
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

function submitChanges(format) {
	if (format === 'json') {
		var params = {
			checkhash: itemorderhash,
			order: JSON.stringify(sortIt.serialize())
		};
		var url = AHAHsaveurl;
		var els = document.getElementsByTagName("input");
	  for (var i=0; i<els.length; i++) {
		  if (els[i].type=="hidden" && els[i].value!="") {
		  	 params[els[i].id.substring(5)] = els[i].value;
		  } else if (els[i].type=="text" && els[i].className=="outcome") {
				params[els[i].id] = els[i].value;
		  }
	  }
	} else {
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

var newblockcnt = 0;
function addnewblock() {
    $("#qviewtree").prepend(
        $("<li>", {id: "newblock" + newblockcnt, class: "blockli"}).append(
            $("<img>", {
                alt: _("block"),
                src: blockiconsrc,
                class: "mida icon"
            })
        ).append(" ").append(
            $("<b>").append(
                $("<span>", {
                    text: _("New Block"),
                    id: "NB" + newblockcnt,
                    onclick: "editinplace(this)"
                })
            )
        )
    );
    newblockcnt++;
    document.getElementById('recchg').disabled = false;
}
