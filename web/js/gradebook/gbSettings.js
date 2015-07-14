$(document).ready(function () {
    $("#viewfield").hide();
});
var addrowcnt = 0;
function toggleAdv(el) {
    if ($("#viewfield").is(":hidden")) {
        $(el).html("Hide view settings");
        $("#viewfield").slideDown();
    } else {
        $(el).html("Edit view settings");
        $("#viewfield").slideUp();
    }
}
function prepForSubmit() {
    if ($("#viewfield").is(":hidden")) {
        $("#viewfield").css("visibility","hidden").css("position","absolute").show();
    }
    $("select:disabled").prop("disabled",false);
}
function removeExistCat(id) {
    var html = '<div><p>Are you sure? This will remove current category.</p></div>';
    var cancelUrl = $(this).attr('href');
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                $("#theform").append('<input type="hidden" name="deleteCatOnSubmit[]" value="'+id+'"/>');
                var torem = document.getElementById("catrow"+id);
                document.getElementById("cattbody").removeChild(torem);
                $(this).dialog("close");
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}
function calcTypeChange(id,val) {
    $("#calctype"+id).val(val);
    $("#calctype"+id).prop("disabled", val>0);
}
function removeCat(n) {
    var torem = document.getElementById("newrow"+n);
    document.getElementById("cattbody").removeChild(torem);
}
function swapWeightHdr(t) {
    if (t==0) {
        document.getElementById("weighthdr").innerHTML = "Fixed Category Point Total (optional)<br/>Blank to use point sum";
    } else {
        document.getElementById("weighthdr").innerHTML = "Category Weight (%)";
    }
}
function addCat() {
    addrowcnt++;
    var tr = document.createElement("tr");
    tr.id = 'newrow'+addrowcnt;
    tr.className = "grid";
    var td = document.createElement("td");
    td.innerHTML = '<input name="name[new'+addrowcnt+']" value="" type="text">';
    tr.appendChild(td);

    var td = document.createElement("td");
    td.innerHTML = '<select name="hide[new'+addrowcnt+']">' +
			'<option value="1">Hidden</option>' +
			'<option value="0" selected="selected">Expanded</option>' +
			'<option value="2">Collapsed</option>' +
			'</select>';
    //td.innerHTML = \'<input name="hide[new\'+addrowcnt+\']" value="1" type="checkbox">\';
    tr.appendChild(td);

    var td = document.createElement("td");
    td.innerHTML = 'Scale <input size="3" name="scale[new'+addrowcnt+']" value="" type="text"> ' +
		   '(<input name="st[new'+addrowcnt+']" value="0" checked="1" type="radio">points ' +
		   '<input name="st[new'+addrowcnt+']" value="1" type="radio">percent)<br/>' +
		   'to perfect score<br/><input name="chop[new'+addrowcnt+']" value="1" checked="1" type="checkbox"> ' +
		   'no total over <input size="3" name="chopto[new'+addrowcnt+']" value="100" type="text">%';
    tr.appendChild(td);

    var td = document.createElement("td");
    td.innerHTML = 'Calc total: <select name="calctype[new'+addrowcnt+']" id="calctypenew'+addrowcnt+'">' +
			'<option value="0" selected="selected">point total</option>' +
			'<option value="1">averaged percents</option></select><br/>' +
			'<input name="droptype[new'+addrowcnt+']" value="0" checked="1" type="radio" onclick="calcTypeChange(\'new'+addrowcnt+'\',0)">Keep All<br/>' +
			'<input name="droptype[new'+addrowcnt+']" value="1" type="radio" onclick="calcTypeChange(\'new'+addrowcnt+'\',1)">Drop lowest ' +
			'<input size="2" name="dropl[new'+addrowcnt+']" value="0" type="text"> scores<br/> ' +
			'<input name="droptype[new'+addrowcnt+']" value="2" type="radio" onclick="calcTypeChange(\'new'+addrowcnt+'\',1)">Keep highest ' +
			'<input size="2" name="droph[new'+addrowcnt+']" value="0" type="text"> scores';
    tr.appendChild(td);

    var td = document.createElement("td");
    td.innerHTML = '<input size="3" name="weight[new'+addrowcnt+']" value="" type="text">';
    tr.appendChild(td);

    var td = document.createElement("td");
    td.innerHTML = '<a href="#" onclick="removeCat('+addrowcnt+'); return false;">Remove</a>';
    tr.appendChild(td);

    document.getElementById("cattbody").appendChild(tr);
}