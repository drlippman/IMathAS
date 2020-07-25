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
</script>
<p>
    An embed with external submit button <br>
    <button onclick="triggersubmit()">Submit</button>
</p>
<iframe id="test1" src="embedq2.php?id=1&jssubmit=1&frame_id=test1" frameborder=0></iframe>

<p>An embed with options to switch questions <br>
  <button onclick="loadnewq(1)">Question 1</button>
  <button onclick="loadnewq(2)">Question 2</button>
  <button onclick="loadnewq(3)">Question 3</button>
</p>
<iframe id="test2" src="embedq2.php?id=1&frame_id=test2" frameborder=0></iframe>


<?php
require('footer.php');
