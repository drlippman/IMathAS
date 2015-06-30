// Item Ordering on assessment page
var homePath = $('.home-path').val();
function moveitem(from,blk) {
    var to = document.getElementById(blk+'-'+from).value;

    if (to != from) {
        var toopen = homePath+'&block=' + blk + '&from=' + from + '&to=' + to;
        window.location = toopen;
    }
}
// Add new items
function additem(blk,tb) {
    var courseId = $('#courseIdentity').val();
    var type = document.getElementById('addtype'+blk+'-'+tb).value;
    if (tb=='BB' || tb=='LB') { tb = 'b';}
    if (type!='') {
        var toOpen = homePath+'&block='+blk+'&tb='+tb+'&type='+type;
        }
        window.location = toOpen;
}

