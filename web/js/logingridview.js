/**
 * Created by supravat on 29/5/15.
 */
$( document ).ready(function() {
    var startDate = $("#datepicker-id input").val();
    var endDate = $("#datepicker-id1 input").val();
    todaysdate = endDate;

    $('#following-link').hide()
    $("#first-date-label").text(startDate);
    $('#last-date-label').text(endDate);

    var course_id = $("#course-id").val();
    var transferData = {newStartDate: startDate, newEndDate: endDate, cid: course_id};
    jQuerySubmit('login-grid-view-ajax', transferData, 'loginGridViewSuccess');
    $("#go-button").click(function () {
        var startDate = $( "#datepicker-id input" ).val();
        var endDate = $( "#datepicker-id1 input" ).val();
        if(endDate < todaysdate)
        {
            $('#following-link').show();
        }
        $("#first-date-label").text(startDate);
        $('#last-date-label').text(endDate);
        $('#flash-message').hide();
        var course_id =  $( "#course-id" ).val();
        var transferData = {newStartDate: startDate,newEndDate: endDate,cid: course_id};


        if (endDate=="" || startDate== ""){
            $('#flash-message').show();
            $('#flash-message').html("<div class='alert alert-danger'>Date field can not be blank.</div>");
        }
        else if ((startDate > endDate) || startDate== ""){
            $('#flash-message').show();
            $('#flash-message').html("<div class='alert alert-danger'>First date can not be greater then last date.</div>");
        }
        else
        {
            $('#flash-message').hide();
            jQuerySubmit('login-grid-view-ajax', transferData, 'loginGridViewSuccess');

        }
    });

});

function pad(number, length) {

    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
    return str;
}

function toggleDate(selector, dayDiff, adjustment) {
    inputString = $("#"+selector).val();
    var dString = inputString.split('-');
    var dt = new Date(dString[2], dString[0] - 1, dString[1]);

    dayDiff = parseInt(dayDiff);
    if (adjustment == 'add') {
        dt.setDate(dt.getDate()+dayDiff);
    } else{
        dt.setDate(dt.getDate()-dayDiff);
    }

    var finalDate = pad(dt.getMonth()+1,2) + "-" + pad(dt.getDate(),2) + "-" + dt.getFullYear();
    return finalDate;
}
function lastDate(inputString, dayDiff, adjustment) {
    var dString = inputString.split('-');
    var dt = new Date(dString[2], dString[0] - 1, dString[1]);

    dayDiff = parseInt(dayDiff);
    if (adjustment == 'add') {
        dt.setDate(dt.getDate()+dayDiff);
    } else{
        dt.setDate(dt.getDate()+dayDiff);
    }
    var finalDate = pad(dt.getMonth()+1,2) + "-" + pad(dt.getDate(),2) + "-" + dt.getFullYear();
    return finalDate;
}

$( document ).ready(function() {
    previousWeekHandler();
    nextWeekHandler();
});
//This method is used to display previous week date in date picker as well as student table.
function previousWeekHandler(){
    var daysInAWeek = 6;
    $("#previous-link").click(function () {
        finalDate = toggleDate('w0', daysInAWeek, 'deduct');
//"w0" is first date picker id
        $( "#w0").val(finalDate);
        $("#first-date-label").text(finalDate);
        finalDate = lastDate(finalDate, daysInAWeek, 'deduct');
        $('#count').val(finalDate);
//"w1" is second date picker id
        $( "#w1").val(finalDate);
        $('#go-button').trigger('click');
        $('#last-date-label').text(finalDate);
        if(finalDate >= todaysdate)
        {
            $('#following-link').hide();
        }
    });
}
//This method is used to display next week date in date picker as well as student table.
function nextWeekHandler(){
    var daysInAWeek = 6;
    $("#following-link").click(function () {
//"w0" is first date picker id
        finalDate = toggleDate('w0', daysInAWeek, 'add');
        $( "#w0").val(finalDate);
        finalDate = lastDate(finalDate, daysInAWeek, 'add');
//"w1" is second date picker id
        $( "#w1").val(finalDate);
        $('#go-button').trigger('click');
        $("#last-date-label").text(finalDate);
        if(finalDate >= todaysdate)
        {
            $('#following-link').hide();
        }
    });
}
//This method is used to show student table on Login Grid View page.
function loginGridViewSuccess(response) {
    var data = JSON.parse(response);
    data = data.data;
    var tableString = '';
    headerArray = data.header;
    rows = data.rows;
    tableString = "<table class='login-grid-table table table-striped table-hover datatable' bPaginate='false'><thead>";
    for(i=0; i<headerArray.length; i++){
        tableString = tableString + "<th>" + headerArray[i]+"</th>";
    }
    tableString = tableString+ "</thead><tbody>";
    $.each( rows, function(id, studata){
        name = studata.name;
        rows = studata.row;
        tableString = tableString+ "<tr>";
        tableString = tableString + "<td>" + name + "</td>";
        for(i=1; i<headerArray.length; i++) {
            var headerVal = headerArray[i];
            tableString = tableString + "<td>" + rows[headerVal] + "</td>";
        }
        tableString = tableString+ "</tr>";
    });
    tableString = tableString + "</tbody></table>";
    $('#table_placeholder').html(tableString);
}
