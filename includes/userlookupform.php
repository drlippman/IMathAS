<?php 

function generateUserLookupForm($userlookupPrefix, $fieldname, $defaultresults = '') {
    global $basesiteurl;

echo '<p><button type=button onclick="userlookupGetgroup()">' . _('List my group members') . '</button> ';
echo _('or lookup a teacher:') . ' <input size=30 id=userlookupName /> <button type=button onclick="userlookupByname()">' . _('Search') . '</button>';
echo '<span id=userlookupstatus class=notice style="display:none">'. _('Looking up teachers...') . ' <img alt="" src="'. $staticroot. '/img/updating.gif"></span>';
echo '</p>';
echo '<p>'. ($userlookupPrefix ?? '') . ' <span id=userlookupResults>'.$defaultresults.'</span></p>';
?>

<script type="text/javascript">
    function userlookupGetgroup() {
        $("#userlookupstatus").show();
        $.ajax({
            dataType: "html",
            type: "POST",
            url: "<?php echo $basesiteurl;?>/util/userlookup.php",
            data: {loadgroup: 1, format: 'select', name: '<?php echo $fieldname; ?>'},
        }).done(function(msg) {
            $('#userlookupResults').html(msg);
        }).always(function() {
            $("#userlookupstatus").hide();
        });
    }
    function userlookupByname() {
        $("#userlookupstatus").show();
        $.ajax({
            dataType: "html",
            type: "POST",
            url: "<?php echo $basesiteurl;?>/util/userlookup.php",
            data: {search: $("#userlookupName").val(), format: 'select', name: '<?php echo $fieldname; ?>'},
        }).done(function(msg) {
            $('#userlookupResults').html(msg);
        }).always(function() {
            $("#userlookupstatus").hide();
        });
    }
</script>

<?php
}
