function ipedssearch() {
  var ipedsname = document.getElementById("ipedsname").value;
  if (ipedsname.trim() !== '') {
    $.ajax({
      type: "POST",
      url: imasroot+"/admin/ipedssearch.php",
      dataType: "json",
      data: {
        "search": ipedsname
      }
    }).done(function(msg) {
      var existing = [];
      $('input[name^=ipeddel]').each(function() {
        existing.push(this.getAttribute('value'));
      });
      $("#newipeds").empty();
      $("#newipeds").append($("<option>", {
        value: 0,
        text: _('None of these')
      }));
      for (var i=0; i<msg.length; i++) {
        if (existing.indexOf(msg[i].id) != -1) {
          continue;
        }
        $("#newipeds").append($("<option>", {
          value: msg[i].id,
          text: msg[i].name
        }));
      }
      $('#newipedswrap').show();
    });
  }
}
