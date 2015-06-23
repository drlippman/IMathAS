// Item Ordering on assessment page
var courseId = $('.home-path').val();
function moveitem(from,blk) {
alert('1');
    var to = document.getElementById(blk+'-'+from).value;

    if (to != from) {
        var toopen = courseId+'&block=' + blk + '&from=' + from + '&to=' + to;alert(toopen);
        window.location = toopen;
    }
}
// Add new items
function additem(blk,tb) {alert(blk);alert(tb);
    var type = document.getElementById('addtype'+blk+'-'+tb).value;alert(type);
    if (tb=='BB' || tb=='LB') { tb = 'b';}
    if (type!='') {
        var toopen = courseId+'&block='+blk+'&tb='+tb;alert(toopen);
        window.location = toopen;
    }
}

