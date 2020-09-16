$(function() {
    $("#search").on('keydown', function(e) {
        if (e.key == 'Enter') {
            doQuestionSearch();
        }
    }).on('input', function (e) {
        $("#searchwrap").toggleClass("hastext", e.currentTarget.value.trim() !== '');
    });
});

$(function() {
    $("#searchbtngrp").on('show.bs.dropdown', function () {
        parseAdvSearch();
    }).on('shown.bs.dropdown', function () {
        var advform = document.getElementById("advsearchform");
        var rect = advform.getBoundingClientRect();
        if (rect.bottom > window.innerHeight) {
            advform.scrollIntoView(false);
        }
    });
});
function parseAdvSearch() {
    var search = document.getElementById("search").value;
    var matches;
    if (matches = search.match(/(author|type|id|regex|used|avgtime|mine|unused|private):("[^"]+?"|\w+)/g)) {
        var pts;
        for (var i=0;i<matches.length;i++) {
            pts = matches[i].split(/:/);
            pts[1] = pts[1].replace(/"/g,'');
            if (pts[0] == 'author') {
                $("#search-author").val(pts[1]);
            } else if (pts[0] == 'type') {
                $("#search-type").val(pts[1]);
            } else if (pts[0] == 'id') {
                $("#search-id").val(pts[1]);
            } else if (pts[0] == 'avgtime') {
                var avgt = pts[1].split(/,/);
                $("#search-avgtime-min").val(avgt[0]);
                $("#search-avgtime-max").val(avgt[1]);
            } else if (pts[0] == 'mine') {
                $("#search-mine").prop('checked', pts[1] == 1)
            } else if (pts[0] == 'unused') {
                $("#search-unused").prop('checked', pts[1] == 1)
            }
        }
    }
    search = search.replace(/(author|type|id|regex|used|avgtime|mine|unused|private):("[^"]+?"|\w+)/g, '');
    var words = search.split(/\s+/);
    var haswords = [];
    var excwords = [];
    for (var i=0;i<words.length;i++) {
        if (words[i].charAt(0)=='!') {
            excwords.push(words[i].substring(1));
        } else {
            haswords.push(words[i]);
        }
    }
    $("#search-words").val(haswords.join(' '));
    $("#search-exclude").val(excwords.join(' '));
}


function doAdvSearch() {
    var outstr = '';
    outstr += $("#search-words").val() + ' ';
    var exclude = $("#search-exclude").val().trim();
    if (exclude != '') {
        outstr += '!' + exclude.split(/\s+/).join(' !');
    }
    var author = $("#search-author").val().trim()
    if (author != '') {
        outstr += 'author:"' + author + '" ';
    }
    var qid = $("#search-id").val().trim()
    if (qid != '') {
        if (qid.match(/^[\d\,\s]+$/)) {
            outstr += 'id:"' + qid + '" ';
        } else if (qid.match(/^[\d]+$/)) {
            outstr += 'id:' + qid + ' ';
        }
    }
    var type = $("#search-type").val();
    if (type != '') {
        outstr += 'type:' + type + ' ';
    }
    var avgtmin = $("#search-avgtime-min").val();
    var avgtmax = $("#search-avgtime-max").val();
    if (avgtmin != '' || avgtmax != '') {
        outstr += 'avgtime:"' + avgtmin + ',' + avgtmax + '" ';
    }
    if ($("#search-mine").is(':checked')) {
        outstr += 'mine:1 ';
    }
    if ($("#search-unused").is(':checked')) {
        outstr += 'unused:1 ';
    }
    $("#search").val(outstr.trim());
    $("#advsearchbtn").dropdown('toggle');
    doQuestionSearch();
}

function doQuestionSearch(offset) {
    offset = offset || 0;
    $("#searcherror").hide();
    var search = document.getElementById("search").value;
    $.ajax({
        url: qsearchaddr,
        method: 'POST',
        data: {
            libs: curlibs,
            search: search,
            searchtype: cursearchtype,
            offset: offset
        },
        dataType: 'json'
    }).done(function(msg) {
        displayQuestionList(msg);
        document.getElementById("myTable").focus();
        document.getElementById("fullqsearchwrap").scrollIntoView();
    }).fail(function() {
        $("#searcherror").show();
    });
}

function clearSearch() {
    $("#search").val("");
    $("#searchwrap").removeClass("hastext");
    if (cursearchtype !== 'all') {
        doQuestionSearch();
    }
}

function getExistingQuestions(qlist,flattened) {
    for (var i in qlist) {
        if (qlist[i][0]=="text") { continue; }
        if (typeof qlist[i][2] == 'object') {
            getExistingQuestions(qlist[i][2], flattened);
        } else {
            flattened.push(qlist[i][1]);
        }
    }
}
function displayQuestionList(results) {
    var searchtype = 'libs';
    var colcnt = 8;
    var thead = '<thead><tr>'
        + '<th><span class="sr-only">'+_('Select')+'</span></th>'
        + '<th>'+_('Description')+'</th>'
        + '<th>'+_('Info')+'</th>'
        + '<th>'+_('ID')+'</th>'
        + '<th>'+_('Preview')+'</th>'
        + '<th>'+_('Type')+'</th>'
        + '<th>'+_('Avg Time')+'</th>'
        + '<th>'+_('Actions')+'</th>'
        + '</tr></thead>';
    var tbody = '<tbody>';
    var i,q,row,features,descrclass,descricon;
    var lastlib = -1;
    var existingq = [];
    if (itemarray) {
        getExistingQuestions(itemarray, existingq);
    }
    for (var i in results['qs']) {
        // show lib/assess titles
        q = results['qs'][i];
        if (results.type=='libs' && q['libid'] != lastlib) {
            tbody += '<tr><td colspan="'+colcnt+'"><b>' + results.names[q['libid']] + '</b></td></tr>';
            lastlib = q['libid'];
        } else if (results.type=='assess' && q['grp'] != lastlib) {
            tbody += '<tr><td colspan="'+colcnt+'"><b>' + results.names[q['grp']] + '</b></td></tr>';
            lastlib = q['grp'];
        }
        // build feature icons
        features = '';
        if ((q['extrefval']&1)==1) {
            if ((q['extrefval']&3)==3) {
                features += '<div class="ccvid inlinediv"';
                var altbase = _("Captioned video");
            } else {
                features += '<div class="inlinediv"';
                var altbase = _("Video");
            } 
            features += 'title="'+altbase+'">';
            features +=
                '<img src="' +
                imasroot +
                '/img/video_tiny.png" alt="' +
                altbase +
                '"/>' +
                '</div>';
        }
        if ((q['extrefval'] & 4) == 4) {
            features +=
                '<img src="' +
                imasroot +
                '/img/html_tiny.png" alt="'+_('Help Resource')+'" ' +
                'title="'+_('Help Resource')+'" />';
        }
        if ((q['extrefval'] & 8) == 8) {
            features +=
                '<img src="' +
                imasroot +
                '/img/assess_tiny.png" alt="'+_('Written example')+'" ' +  
                'title="'+_('Written example')+'" />';
        }
        if (q['mine'] == 1) {
            features += '<span title="' + _('My Question') + '" aria-label="' + _('My Question') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>' +
                '</span>';
        }
        if (q['userights'] == 0) {
            features += '<span title="' + _('Private') + '" aria-label="' + _('Private') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>' +
                '</span>';
        }
        descrclass = '';
        descricon = '';
        if (q['broken'] == 1) {
            descrclass = ' class="qbroken"';
            descricon = '<span title="' + _('Marked as broken') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#f66" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M19.7 1.3 19.6 9 16.2 6.3 13.8 11.3 10.5 8.3 7 11.7 3.6 9.2l0-7.9z" class="a"></path><path d="m19.7 22.9 0-7.8-2-1.4-3.1 4-3.3-3-3.8 3.8-4-3.9v8.4z" class="a"></path></svg>' + 
                '</span> ';
        } else if (q['junkflag'] == 1) {
            descrclass = ' class="qwronglib"';
            descricon = '<span title="' + _('Marked as in wrong library') + '">' + 
                '<svg viewBox="0 0 24 24" width="16" height="16" stroke="#f66" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M18.1 12.1C19.7 9.1 19 5.3 16.4 3.2 13.8 1 10 1 7.5 3.2 4.9 5.4 4.2 9.1 5.7 12.1l6.2 10.6z M9.5 11.5 14.5 6.5 M9.5 6.5 14.5 11.5"></path></svg>' +
                '</span> ';
        } else if (existingq.indexOf(parseInt(q['id'])) !== -1) {
            descrclass = ' class="qinassess"';
        }
        // build action dropdown
        var actions = '<div class="dropdown">' +
            '<button role="button" class="dropdown-toggle plain" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' + 
            '⋮</button><ul role="menu" class="dropdown-menu dropdown-menu-right">' + 
            '<li><a href="#">' + _('Add') + '</a></li>' +
            '<li><a href="#">' + (q['mine']==1 ? _('Edit') : _('View Code')) + '</a></li>' + 
            '<li><a href="#">' + _('Template') + '</a></li>' +
            '</ul></div>';

        // build row
        tbody += '<tr>'
            + '<td><input type=checkbox name="nchecked[]" id="qo'+i+'" value="'+q['id']+'"></td>'
            + '<td' + descrclass + '>' + descricon + q['description'] + '</td>'
            + '<td class="nowrap">' + features + '</td>'
            + '<td>' + q['id'] + '</td>'
            + '<td><button class="plain" type=button onclick="previewq(\'selq\',\'qo'+i+'\','+q['id']+',true,false)">'
            + '<svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            + '</td>'
            + '<td>' + q['qtype'] + '</td>'
            + '<td class="c">' + (q['meantime'] > 0 ? q['meantime'] : '') + '</td>'
            + '<td class="c">' + actions + '</td>'
            + '</tr>';
    }
    tbody += '</tbody>';
    document.getElementById("myTable").innerHTML = thead + tbody;
    // TODO init sorting
    initSortTable('myTable',[false,'S',false,'N',false,'S','N',false]);
    if (window.top == window.self && document.getElementById("addbar")) {
         $("#selq input[type=checkbox]").on("change", function () {
             $("#addbar").toggle($("#selq input[type=checkbox]:checked").length > 0);
         });
    }
    if (results.hasOwnProperty('next') || results.hasOwnProperty('prev')) {
        $("#searchnums").show();
        $("#searchnumvals").text((results.offset+1) + '-' + (results.offset + results.qs.length));
    } else {
        $("#searchnums").hide();
    }
    if (results.hasOwnProperty('next')) {
        var resnext = results.next;
        $("#searchnext").show().off('click').on('click', function (e) {
            e.preventDefault();
            doQuestionSearch(resnext);
        });
    } else {
        $("#searchnext").hide();
    }
    if (results.hasOwnProperty('prev')) {
        var resprev = results.prev;
        $("#searchprev").show().off('click').on('click', function (e) {
            e.preventDefault();
            doQuestionSearch(resprev);
        });
    } else {
        $("#searchprev").hide();
    }
}

function updateInAssessMarkers() {
    var existingq = [];
    if (itemarray) {
        getExistingQuestions(itemarray, existingq);

    }
    $("#selq tbody tr").each(function(i,el) {
        if (el.childNodes.length == 1) { return; }
        $(el.childNodes[1]).toggleClass('qinassess', 
            existingq.indexOf(parseInt(el.childNodes[3].textContent)) !== -1);
    });
}
