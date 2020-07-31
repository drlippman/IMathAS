<?php
require('init.php');
require("includes/JWT.php");

// A global LTI key/secret is used as an auth key/secret.
$authkey = 'test';

$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=?");
$stm->execute(array($authkey));
$authsecret = $stm->fetchColumn(0);

// form initial JWT object;
$params1 = [
    'id'=>87878,
    'auth'=>$authkey
];

if (isset($_POST['save'])) {
    // decode the JWT.  Also verifies the signature
    // extra json encode/decode is hack to get the result as assoc array
    $savedata = json_decode(json_encode(JWT::decode($_POST['save'], $authsecret)),true);

    // here you'd save the results to your db, the do whatever 
    // the appropriate action is.  For demo, we're going 
    // to generate both a redisplay and showscored jwt.
    $redisplay = $params1;
    $redisplay['redisplay'] = $savedata['state'];

    $showscored = $params1;
    $showscored['showscored'] = $savedata['state'];
    //let's also force display of answers
    $showscored['showans'] = 1;
    
    // generate the jwt's.  Either one of these could be put in a new
    // iframe jwt= query string, but in this demo we're 
    // going to pass it to the front end, then trigger the embed 
    // to use the new data.
    $out = [
        'redisplay' => JWT::encode($redisplay, $authsecret),
        'showscored' => JWT::encode($showscored, $authsecret)
    ];
    echo json_encode($out);
    exit;
}

// build json for regular first display
$jwt1 = JWT::encode($params1, $authsecret);


require('header.php');

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
    var redispdata = null;
    $(window).on("message", function(e) {
        if (e.originalEvent.data.match(/lti\.ext\.imathas\.result/)) {
            var data = JSON.parse(e.originalEvent.data);
            if (data.jwt) {
                var contents = parseJwt(data.jwt);
                console.log(contents);
                if (contents.allans) { // all parts have been answered
                    // report the results
                    $("#result").html("Score: "+contents.score);

                    // usually this is where an embedding system would save
                    // the results to the backend
                    $.post({
                        url: 'embedq2test.php',
                        data: {save: data.jwt},
                        dataType: 'json'
                    }).done(function(res) {
                        redispdata = res;
                        // and reveal buttons for reshowing
                        $("#reshow").show();
                    })
                    
                }
            }
        }
    });
    function triggersubmit() {
        document.getElementById("test1").contentWindow.postMessage("submit", "*");
    }
    function redisplay() {
        document.getElementById("test1").contentWindow.postMessage(
            JSON.stringify({subject:"imathas.show", jwt: redispdata.redisplay}), "*");
    }
    function showscored() {
        document.getElementById("test1").contentWindow.postMessage(
            JSON.stringify({subject:"imathas.show", jwt: redispdata.showscored}), "*");
    }
    
</script>
<p>
    <button onclick="triggersubmit()">Submit</button>
    <span id="reshow" style="display:none;">
        <button onclick="redisplay()">Redisplay</button>
        <button onclick="showscored()">Show Scored</button>
    </span>
</p>
<div id="result"></div>
<div id="test1wrap" style="position:relative;">
<iframe id="test1" src="embedq2.php?jwt=<?php
    echo Sanitize::encodeStringForDisplay($jwt1);
?>&frame_id=test1" frameborder=0 style="position:absolute;z-index:1"></iframe>
</div>


<?php
require('footer.php');
