
$(document).ready(function(){
    var cid = $(".course-id").val();
    var courseTeacher = {cid: cid};
jQuerySubmit('get-teachers', courseTeacher, 'displayTeacherSuccess');
});

function displayTeacherSuccess(response)
    {
        var result = JSON.parse(response);
        var count = result.data.countTeach;
        if(result.status == 0)
        {
        var teachers = result.data.teachers;
        var nonTeachers = result.data.nonTeachers;

        $.each(nonTeachers, function(index, nonTeacher){
        displayNonTeacher(nonTeacher);
        });

$.each(teachers, function(index, teacher){
    displayTeacher(teacher,count);
    });
}
}

function displayTeacher(teacher,count)
    {
        var firstName = capitalizeFirstLetter(teacher.FirstName);
        var lastName = capitalizeFirstLetter(teacher.LastName);
        var teacherHtml = "";
        if(count == 1){
            teacherHtml = "<tr><td> </td><td id='convertToUpper' class='word-break-break-all'>"+firstName+' '+lastName+"</td><td><a href='' onclick='removeTeacher("+teacher.id+")' class='addRemoveTeacher removeTeacherLink removeTeacher-"+teacher.id+"'></a></td></tr>";
        }else{
            teacherHtml = "<tr><td><input type='checkbox' name='teacher' value='"+teacher.id+"' class='addRemoveTeacherCheckbox removeCheckbox removeTeacherCheckbox-"+teacher.id+"' > </td> <td id='convertToUpper' class='word-break-break-all'>"+firstName+' '+lastName+"</td><td><a href='' onclick='removeTeacher("+teacher.id+")' class='addRemoveTeacher removeTeacherLink removeTeacher-"+teacher.id+"'>Remove As Teacher</a></td></tr>";
        }


        $('#teach').append(teacherHtml);
        }

function displayNonTeacher(nonTeacher)
    {
        var firstName = capitalizeFirstLetter(nonTeacher.FirstName);
        var lastName = capitalizeFirstLetter(nonTeacher.LastName);
        var nonTeacherHtml = "";
        nonTeacherHtml = "<tr><td><input type='checkbox' name='nonTeacher' value='"+nonTeacher.id+"' class= 'addRemoveTeacherCheckbox addCheckbox addTeacherCheckbox-"+nonTeacher.id+"'> </td> <td id='convertToUpper' class='word-break-break-all'>"+firstName+' '+lastName+"</td><td><a href='' onclick='addTeacher("+nonTeacher.id+")' class='addRemoveTeacher addTeacherLink addTeacher-"+nonTeacher.id+"'>Add as Teacher</a></td></tr>";
        $('#nonTeach').append(nonTeacherHtml);
    }

function addTeacher(userId)
    {
        var cid = $(".course-id").val();
        jQuerySubmit('add-teacher-ajax',{cid:cid, userId:userId },'addTeacherSuccess');
    }

function addTeacherSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
            window.location = "add-remove-course?cid="+cid;
        }
    }

function removeTeacher(userId)
    {
        var cid = $(".course-id").val();
        jQuerySubmit('remove-teacher-ajax',{cid:cid, userId:userId },'removeTeacherSuccess');
}

function removeTeacherSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
        window.location = "add-remove-course?cid="+cid;
        }
}

function addAllAsTeacher()
    {
        var cid = $(".course-id").val();
        var nonTeachers = [];
        $("input:checkbox[name=nonTeacher]:checked").each(function()
        {
        nonTeachers.push($(this).val());
        });
if (nonTeachers.length == 0)
        {
            alert('Select atleast one teacher.');
            }else{
    jQuerySubmit('add-all-as-teacher-ajax',{'usersId':JSON.stringify(nonTeachers), 'cid':cid},'addAllAsTeacherSuccess');
}
}

function addAllAsTeacherSuccess(response)
    {
        var cid = $(".course-id").val();
        var result = JSON.parse(response);
        if(result.status == 0)
        {
        window.location = "add-remove-course?cid="+cid;
        }
}

function removeAllAsTeacher()
    {
        var cid = $(".course-id").val();
        var teachers = [];
        $("input:checkbox[name=teacher]:checked").each(function()
        {
        teachers.push($(this).val());
        });
if (teachers.length == 0)
        {
            alert('Select atleast one teacher.');
            }else{
    jQuerySubmit('remove-all-as-teacher-ajax',{'usersId':JSON.stringify(teachers), 'cid':cid},'removeAllAsTeacherSuccess');
}

}
function removeAllAsTeacherSuccess(response)
    {
        var result = JSON.parse(response);
        if(result.status == 0)
        {
        var cid = $(".course-id").val();
        window.location = "add-remove-course?cid="+cid;
        }
}


