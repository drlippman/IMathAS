$(function() {
	//if we're moving a block, don't list it in destination blocks
	moveItemAddBlockOptions(blockinfo, "0", item.match(/B/)?item.substr(1):null, "");
	$("#blockselect").val(block);
	$("#blockselect").on('change', function() {
		moveItemAddBlockItems($(this).val());	
	});
	moveItemAddBlockItems(block);
});

//skip is block ID or null
function moveItemAddBlockOptions(arr,parent,skip,pre) {
	var blockref;
	if (parent=="0") {
		$("#blockselect").append($('<option>', {
			value: "0",
			text: _("Main Course Page")
		}));
	}
	for (var i=0;i<arr.length;i++) {
		if (typeof arr[i] === 'object') {
			//is a block
			blockref = parent+'-'+(i+1);
			if (arr[i]['id']==skip) {continue;}
			$("#blockselect").append($('<option>', {
				value: blockref,
				text: pre+arr[i]['name']
			}));
			moveItemAddBlockOptions(arr[i]['items'], blockref, skip, "- "+pre);
		}
	}
}

function moveItemAddBlockItems(block) {
	var i,blockref;
	var parts = block.split(/-/);
	var thisitems = blockinfo.slice();
	if (parts.length>1) {
		for (i=1;i<parts.length;i++) {
			thisitems = thisitems[parts[i]-1]['items'].slice();
		}
	}
	$("#itemselect").find("option").remove();
	$("#itemselect").append($('<option>', {
		value: 'top',
		text: (parts.length>1)?_("Top of the Block"):_("Top of the Page")
	}));
	var index = 0; var selectedindex = 0;
	for (i=0;i<thisitems.length;i++) {
		if (typeof thisitems[i] === 'object') {
			//is a block
			if (item != 'B'+thisitems[i]['id']) { //skip if this is the item we're moving
				$("#itemselect").append($('<option>', {
					value: 'B'+thisitems[i]['id'],
					text: thisitems[i]['name']
				}));
				index++;
			} else {
				selectedindex = index;	
			}	
		} else {
			if (item != thisitems[i]) { //skip if this is the item we're moving
				$("#itemselect").append($('<option>', {
					value: thisitems[i],
					text: iteminfo[thisitems[i]][1]
				}));
				index++;
			} else {
				selectedindex = index;	
			}
		}
	}
	document.getElementById("itemselect").selectedIndex = selectedindex;
}

function moveitem() {
	var data = {
		item: item, 
		block: block,
		newblock: $("#blockselect").val(),
		moveafter: $("#itemselect").val()
	};
	$.ajax({
		type: "POST",
		url: imasroot+"/course/moveitem.php?cid="+cid,
		data: data,
		dataType: "html"
	}).done(function(data) {
		if (data=="OK") {
			window.parent.location.reload();
		} else {
			$("#error").append(data);
		}
	});
}

function cancelmove() {
	window.parent.GB_hide();
}
