<?php
require('init.php');
require('header.php');
require("includes/JWT.php");

// A global LTI key/secret is used as an auth key/secret.
$authkey = 'test';

$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=?");
$stm->execute(array($authkey));
$authsecret = $stm->fetchColumn(0);

// form initial JWT object;
$params1 = [
    'id'=>9,
    'showscoredonsubmit'=>1,
    'auth'=>$authkey
];
$jwt1 = JWT::encode($params1, $authsecret);

$params2 = [
    'id'=>9,
    'showscoredonsubmit'=>1,
    'auth'=>$authkey,
    'showscored' => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzZWVkcyI6eyI1Ijo2MzYxfSwicXNpZCI6eyI1Ijo5fSwic3R1YW5zd2VycyI6eyI2IjpbIjEiLCIyIl19LCJzdHVhbnN3ZXJzdmFsIjp7IjYiOlsiIiwiMiJdfSwic2NvcmVub256ZXJvIjp7IjYiOlt0cnVlLHRydWVdfSwic2NvcmVpc2NvcnJlY3QiOnsiNiI6W3RydWUsdHJ1ZV19LCJwYXJ0YXR0ZW1wdG4iOnsiNSI6WzEsMV19LCJyYXdzY29yZXMiOnsiNSI6WzEsMV19LCJqc3N1Ym1pdCI6dHJ1ZSwic2hvd2FucyI6ZmFsc2UsInNob3doaW50cyI6Mywic2hvd3Njb3JlZG9uc3VibWl0IjoxLCJhbGxvd3JlZ2VuIjpmYWxzZSwiYXV0aCI6InRlc3QifQ.Fir2E6LOKg_vQVFRK5sBlA-AlOcCdTslFMVS_d391ks",
];
$jwt2 = JWT::encode($params2, $authsecret);

?>
<script>
    function parseJwt (token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    };
    $(window).on("message", function(e) {
        var data = JSON.parse(e.originalEvent.data);
        if (data.jwt) {
            var contents = parseJwt(data.jwt);
            console.log(contents);
        }
    });
    function triggersubmit() {
        document.getElementById("test1").contentWindow.postMessage("submit", "*");
    }
    function loadnewq() {
        document.getElementById("test1").contentWindow.postMessage(
            JSON.stringify({subject:"imathas.show", jwt: "<?php
            echo Sanitize::encodeStringForDisplay($jwt2);
            ?>"}), "*");
    }
</script>
<p>
    <button onclick="triggersubmit()">Submit</button>
    <button onclick="loadnewq()">Load Other Question</button>
</p>
<iframe id="test1" src="embedq2.php?jwt=<?php
    echo Sanitize::encodeStringForDisplay($jwt1);
?>&frame_id=test1" frameborder=0></iframe>


<?php
require('footer.php');
