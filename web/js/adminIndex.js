$(document).ready(function () {
    $('.course-table').DataTable();

    $('.course-section').DataTable(
        {
            "aoColumnDefs": [ { "bSortable": false, "aTargets": [2,3] } ],
            "bPaginate": false
        }
    );
    $('.diagnostics-section').DataTable(
        {
            "aoColumnDefs": [ { "bSortable": false, "aTargets": [1,2,3] } ],
            "bPaginate": false
        }
    );

    $('.user-section').DataTable(
        {
            "aoColumnDefs": [ { "bSortable": false, "aTargets": [3,4,5] } ],
            "bPaginate": false
        }
    );

});


function bindEvent() {
    //Show pop dialog for delete the course.
    $('.delete-link').click(function (e) {
        e.preventDefault();
        var html = "<div>Are you sure to delete your course?</div>";
        var cancelUrl = $(this).attr('href');
        $('<div  id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Delete Course', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Confirm": function () {
                    window.location = cancelUrl;
                    $(this).dialog("close");
                    return true;
                },
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });
}

function showcourses() {
    var uid=document.getElementById("seluid").value;
    if (uid > 0) {
        window.location = 'index?showcourses=' + uid;
    }
}

function deleteDiagnostics(diagnoId) {
    jQuerySubmit('delete-diagnostics-ajax', {diagnoId: diagnoId}, 'removeResponseSuccess');
}

function removeResponseSuccess(response) {
    response = JSON.parse(response);
    var id = response.data.id;

    if (response.status == 0) {
        var message = '';
        message += 'Are you sure you want to delete this diagnostic?' + '<br>';
        message += 'This does not delete the connected course and does not remove students or their scores.';
        var html = '<div><p>' + message + '</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Remove Diagnostics', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Nevermind": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Yes,Delete": function () {
                    window.location = "actions?action=removediag&id=" + id;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function () {
                jQuery('.ui-widget-overlay').bind('click', function () {
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }
}

function deleteCourse(courseID) {
    jQuerySubmit('delete-course-ajax', {id: courseID}, 'removeSuccess')
}

function removeSuccess(response) {
    console.log(response);
    response = JSON.parse(response);
    var id = response.data.id;
    var name = response.data.name;

    if (response.status == 0) {
        var message = '';
        message += 'Are you sure you want to delete the course <b>' + name + '</b>' + '<br>';
        var html = '<div><p>' + message + '</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Remove Course', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    window.location = "actions?action=delete&id=" + id;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function () {
                jQuery('.ui-widget-overlay').bind('click', function () {
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }

}
function showgroupusers() {
    var grpid = document.getElementById("selgrpid").value;

    window.location = 'index?showusers=' + grpid;
}

function deleteAdmin(userId)
{
    jQuerySubmit('delete-user-ajax', {id: userId}, 'removeUserSuccess')
}

function removeUserSuccess(response) {
    console.log(response);
    response = JSON.parse(response);
    var id = response.data.id;

    if (response.status == 0) {
        var message = '';
        message += 'Are you sure you want to delete this user?';
        var html = '<div><p>' + message + '</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Remove User', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Nevermind": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Yes,Delete": function () {
                    window.location = "actions?action=deladmin&id=" + id;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            },
            open: function () {
                jQuery('.ui-widget-overlay').bind('click', function () {
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    }
}