var cnt = new Array();
jQuery(document).ready(function(){
    cnt['ipout'] = jQuery('.ip-out-count').val();
    cnt['pwout'] = jQuery('.pwd-out-count').val();
    cnt['pwsout'] = jQuery('.pws-out-count').val();
});

function additemIpAddress(inloc,outloc)
{
    var text = document.getElementById(inloc).value;

    if(ValidateIPaddress(text) == true)
    {
    document.getElementById(inloc).value = '';
    var outn = document.getElementById(outloc);
    var newn = document.createElement("tr");
    var newid = outloc+'-'+cnt[outloc];
    cnt[outloc] += 1;

    newn.id = 'tr'+newid;
    var newtd = document.createElement("td");
    var html = "<input type=hidden name="+newid+" id="+newid+" value='"+text+"'>" + text;
    newtd.innerHTML = html;
    newn.appendChild(newtd);

    html = "  <a href='#' onclick=\"return removeitem('"+newid+"','"+outloc+"')\">Remove</a>";
    html += " <a href='#' onclick=\"return moveitemup('"+newid+"','"+outloc+"')\">Move up</a>";
    html += " <a href='#' onclick=\"return moveitemdown('"+newid+"','"+outloc+"')\">Move down</a>";
    newtd = document.createElement("td");
    newtd.innerHTML = html;
    newn.appendChild(newtd);
    outn.appendChild(newn);
    }
    else {
        document.getElementById(inloc).value="";
    }
}

function additem(inloc,outloc) {
    var text = document.getElementById(inloc).value;
    if(text.length==0){
        message = 'Field cant be blank..!!';
        var html = '<div><p>' + message + '</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: '', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,draggable:false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function () {
                jQuery('.ui-widget-overlay').bind('click', function () {
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }else{
    document.getElementById(inloc).value = '';
    var outn = document.getElementById(outloc);
    var newn = document.createElement("tr");
    var newid = outloc+'-'+cnt[outloc];
    cnt[outloc] += 1;
        newn.id = 'tr'+newid;
        var newtd = document.createElement("td");
        var html = "<input type=hidden name="+newid+" id="+newid+" value='"+text+"'>" + text;
        newtd.innerHTML = html;
        newn.appendChild(newtd);

        html = "  <a href='#' onclick=\"return removeitem('"+newid+"','"+outloc+"')\">Remove</a>";
        html += " <a href='#' onclick=\"return moveitemup('"+newid+"','"+outloc+"')\">Move up</a>";
        html += " <a href='#' onclick=\"return moveitemdown('"+newid+"','"+outloc+"')\">Move down</a>";
        newtd = document.createElement("td");
        newtd.innerHTML = html;
        newn.appendChild(newtd);
        outn.appendChild(newn);
    }
}

function ValidateIPaddress(ipaddress)
{
    if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ipaddress))
    {
        return (true)
    }
    message = 'You have entered an invalid IP address!';
    var html = '<div><p>' + message + '</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: '', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,draggable:false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        },
        open: function () {
            jQuery('.ui-widget-overlay').bind('click', function () {
                jQuery('#dialog').dialog('close');
            })
        }
    });
    return (false)
}


function onenterip(e,inloc,outloc) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	if (key==13) {
        additemIpAddress(inloc,outloc);
		return false;
	} else {
		return true;
	}
}

function onenterpassword(e,inloc,outloc) {
    if (window.event) {
        var key = window.event.keyCode;
    } else if (e.which) {
        var key = e.which;
    }
    if (key==13) {
        additem(inloc,outloc);
        return false;
    } else {
        return true;
    }
}

function removeitem(id,outloc) {
	var outn = document.getElementById(outloc);
	outn.removeChild(document.getElementById('tr'+id));
	return false;
}
function moveitemup(id,outloc) {
	var outn = document.getElementById(outloc);
	var cur = document.getElementById('tr'+id);
	var prev = cur.previousSibling;
	if (prev != null) {
		outn.removeChild(cur);
		outn.insertBefore(cur,prev);
	}
	return false;
}
function moveitemdown(id,outloc) {
	var outn = document.getElementById(outloc);
	var cur = document.getElementById('tr'+id);
	var next = cur.nextSibling;
	if (next != null) {
        outn.removeChild(cur);
		if (next.nextSibling!=null) {
			outn.insertBefore(cur,next.nextSibling);
		} else {
			outn.appendChild(cur);
		}
	}
	return false;
}

function toggleonefor(el) {
	var hide = el.checked;
	var els = document.getElementsByTagName("div");
	var isfirst = 1;
	for (var i=0; i < els.length; i++) {
		if (els[i].className=="sel2") {
			if (isfirst) {
				isfirst = false;
			} else {
				if (hide) {
					els[i].style.display = "none";
				} else {
					els[i].style.display = "block";
				}
			}
		}
	}
}
