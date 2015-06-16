$(document).ready(function(){
    var isCourseHidden = $('.hidden-course').val();
    if(isCourseHidden)
    {
        $('#unhidelink').show();
    }else{
        $('#unhidelink').hide();
    }
});