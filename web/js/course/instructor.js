// Item Ordering on assessment page
var courseId = $('.home-path').val();
function moveitem(from,blk) {
    var to = document.getElementById(blk+'-'+from).value;

    if (to != from) {
        var toopen = courseId+'&block=' + blk + '&from=' + from + '&to=' + to;
        window.location = toopen;
    }
}
// Add new items
function additem(blk,tb) {
    var type = document.getElementById('addtype'+blk+'-'+tb).value;
    if (tb=='BB' || tb=='LB') { tb = 'b';}
    if (type!='') {
        var toopen = courseId+'&block='+blk+'&tb='+tb;
        window.location = toopen;
    }
}

