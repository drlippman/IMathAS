function ipedssearch(options) {
    options = options || {};
    var searchtype = options.type || 'name';
    var ipedtypefield = options.ipedtypefield || '';
    var searchfield = options.searchfield || 'ipedsname';
    var resultfield = options.resultfield || 'newipeds';
    var wrapper = options.wrapper || 'newipedswrap';
    var incsel = options.includeselect || false;
    var skipnone = options.skipnone || false;
    var statefield = options.state || false;
    var searchval = document.getElementById(searchfield).value;
    if (searchval.trim() !== '') {
        var data = {};
        if (searchtype == 'name') {
            data.search = searchval;
        } else if (searchtype == 'country') {
            data.country = searchval;
        }
        if (statefield) {
            data.state = document.getElementById(statefield).value;
        }
        if (ipedtypefield != '') {
            data.type = document.getElementById(ipedtypefield).value;
        }
        $.ajax({
            type: "POST",
            url: imasroot+"/admin/ipedssearch.php",
            dataType: "json",
            data: data
        }).done(function(msg) {
            var existing = [];
            $('input[name^=ipeddel]').each(function() {
                existing.push(this.getAttribute('value'));
            });
            $('#'+resultfield).empty();
            if (incsel) {
                $('#'+resultfield).append($("<option>", {
                    value: "",
                    text: _('Select...')
                }));
            }
            if (!skipnone) {
                $('#'+resultfield).append($("<option>", {
                    value: "0",
                    text: _('None of these')
                }));
            }
            for (var i=0; i<msg.length; i++) {
                if (existing.indexOf(msg[i].id) != -1) {
                    continue;
                }
                $('#'+resultfield).append($("<option>", {
                    value: msg[i].id,
                    text: msg[i].name
                }));
            }
            $('#'+wrapper).show();
        });
    }
}
