<?php 

function generateUserLookupForm($userlookupPrefix, $fieldname, $defaultresults = '') {
    global $basesiteurl,$staticroot;

echo '<p><button type=button onclick="userlookupGetgroup()">' . _('List my group members') . '</button> ';
echo '<label>'._('or lookup a teacher:') . ' <input size=30 id=userlookupName /></label> <button type=button onclick="userlookupByname()">' . _('Search') . '</button>';
echo '<span id=userlookupstatus class=notice style="display:none">'. _('Looking up teachers...') . ' <img alt="" src="'. $staticroot. '/img/updating.gif"></span>';
echo '</p>';
echo '<p><label for="'.Sanitize::encodeStringForDisplay($fieldname).'">'. ($userlookupPrefix ?? '') . '</label> ';
echo '<span id=userlookupResults>'.$defaultresults.'</span></p>';
echo '<span id=statusmsg class="sr-only" aria-live="polite" aria-atomic="true"></span>';
?>

<script type="text/javascript">
    function userlookupGetgroup() {
        $("#userlookupstatus").show();
        $("#statusmsg").text(_('Looking up teachers...'));
        $.ajax({
            dataType: "html",
            type: "POST",
            url: "<?php echo $basesiteurl;?>/util/userlookup.php",
            data: {loadgroup: 1, format: 'select', name: '<?php echo $fieldname; ?>'},
        }).done(function(msg) {
            $('#userlookupResults').html(msg);
            $("#statusmsg").text(_('Done'));
            if (typeof userlookupcallback == 'function') {
                userlookupcallback();
            }
        }).always(function() {
            $("#userlookupstatus").hide();
        });
    }
    function userlookupByname() {
        $("#userlookupstatus").show();
        $("#statusmsg").text(_('Looking up teachers...'));
        $.ajax({
            dataType: "html",
            type: "POST",
            url: "<?php echo $basesiteurl;?>/util/userlookup.php",
            data: {search: $("#userlookupName").val(), format: 'select', name: '<?php echo $fieldname; ?>'},
        }).done(function(msg) {
            $('#userlookupResults').html(msg);
            $("#statusmsg").text(_('Done'));
            if (typeof userlookupcallback == 'function') {
                userlookupcallback();
            }
        }).always(function() {
            $("#userlookupstatus").hide();
        });
    }
</script>

<?php
}
