/**
 * Created by supravat on 29/5/15.
 */
$( document ).ready(function() {

    var endDate = $("#datepicker-id1 input").val();
    todaysdate = endDate;
});

$("#go-button").click(function ()
{
    toggleDate();
});


function toggleDate( )
{
    var startDate = $( "#datepicker-id input" ).val();
    var endDate = $( "#datepicker-id1 input" ).val();
    console.log(startDate);
    if(endDate < todaysdate)
    {
        $('#following-link').show();
    }
    $("#first-date-label").text(startDate);
    $('#last-date-label').text(endDate);
    $('#flash-message').hide();
    if (endDate=="" || startDate== ""){
        $('#flash-message').show();
        $('#flash-message').html("<div class='alert alert-danger'>Date field can not be blank.</div>");
    }
    else
    if( (new Date(startDate).getTime() > new Date(endDate).getTime()) || startDate== "" )
    {
        $('#flash-message').show();
        $('#flash-message').html("<div class='alert alert-danger'>First date can not be greater then last date.</div>");
    }
    else
    {
        $('#flash-message').hide();
        $('#form-id').submit();
    }
}

//This method is used to display previous week date in date picker as well as student table.
function previousWeekHandler(){
    var daysInAWeek = 6;
    $("#previous-link").click(function () {
        toggleDate();
    });
}
//This method is used to display next week date in date picker as well as student table.
function nextWeekHandler(){
    var daysInAWeek = 6;
    $("#following-link").click(function () {
        toggleDate();

    });
}
