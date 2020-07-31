<?php
require('init.php');
require('header.php');

?>
<script>
    function triggersubmit() {
        document.getElementById("test1").contentWindow.postMessage("submit", "*");
    }
    function loadnewq(id) {
        document.getElementById("test2").contentWindow.postMessage(
            JSON.stringify({subject:"imathas.show", id: id}), "*");
    }
    $(function() {
        $(window).on("message", function(e) {
            if (typeof e.originalEvent.data=='string' && e.originalEvent.data.match(/lti\.frameResize/)) {
                var edata = JSON.parse(e.originalEvent.data);
                console.log(edata);
                if ("frame_id" in edata) {
                    $("#"+edata["frame_id"]).height(edata.height);
                    $("#"+edata["frame_id"]+"wrap").height(edata.wrapheight);
                }
            }
        });
    });
</script>
<p>
    An embed with external submit button <br>
    <button onclick="triggersubmit()">Submit</button>
</p>
<div id="test1wrap" style="overflow:visible;position:relative">
    <iframe id="test1" src="embedq2.php?id=87878&jssubmit=0&frame_id=test1" frameborder=0
        style="position:absolute;z-index:1"></iframe>
</div>

<p>An embed with options to switch questions <br>
  <button onclick="loadnewq(1)">Question 1</button>
  <button onclick="loadnewq(2)">Question 2</button>
  <button onclick="loadnewq(3)">Question 3</button>
</p>
<div id="test2wrap" style="overflow:visible;">
    <iframe id="test2" src="embedq2.php?id=1&frame_id=test2" frameborder=0 style="position:absolute;z-index:1"></iframe>
</div>


<?php
require('footer.php');
