$(document).ready(function() {
    $('.user-table').DataTable({
        "order": [[ 0, "desc" ]]
    });

    $('.login-log-table').DataTable();
});