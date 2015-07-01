$(document).ready(function () {
    var courseId = $(".course-info").val();
    var userId = $(".user-info").val();
    var allMessage = {courseId: courseId, userId:userId};
    jQuerySubmit('display-gradebook-ajax', allMessage, 'showGradebookSuccess');
    selectCheckBox();
});
var gradebookData;
var data;
var hidePast;

function showGradebookSuccess(response){
console.log(response);
    var result = JSON.parse(response);
    //console.log(result.data)
    gradebookData = result.data.gradebook;
    data = result.data;
    if(data.availShow == 4){
        data.availShow = 1;
        hidePast = true;
    }

    createTableHeader();
}
function selectCheckBox() {
    $('.check-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', true);
        })
    });

    $('.uncheck-all').click(function () {
        $('.gradebook-table-body input:checkbox').each(function () {
            $(this).prop('checked', false);
        })
    });
}

function createTableHeader() {
    var html = "<table id = 'gradebook-table display-gradebook-table' class = 'gradebook-table display-gradebook-table table table-bordered table-striped table-hover data-table'>";
    html += "<thead><tr>";
    var sortArray = [];
    for(i=0;i<gradebookData[0][0].length;i++){   //biographical headers
        if(i == 1){
            html += "<th><div>&nbsp;</div></th>";
            sortArray.push('false');
        }
        if(i == 1 && gradebookData[0][0][1]!='ID'){
            continue;
        }
        if(gradebookData[0][0][i] == 'Section' || gradebookData[0][0][i] == 'Code' || gradebookData[0][0][i] == 'Last Login'){
            html += "<th class = 'nocolorize'><div>"
        }else{
            html += '<th><div>'
        }
        html += gradebookData[0][0][i];
        if((gradebookData[0][0][i] == 'Section' || (data.isDiagnostic != undefined && i==4)) && (data.isTutor == undefined || data.tutorSection == "")){
            html += "<br/><select id='sec-filter-sel' class='form-control dropdown-auto'><option value='-1'";
            if(data.secFilter == -1){
                html += "selected = 1";
            }
            html += ">All</option>"
            $.each(data.sections, function (index, section){
                html += "<option value="+section+">"+section+"</option>";
            });
            html += "</select>";

        } else if(gradebookData[0][0][i] == 'Name'){
            html += "<br/><span class='small'>N = "+(gradebookData.length-2)+"</span><br/>";
            html += "<select class='form-control dropdown-auto'><option value='0'>Show Locked</option><option value='2'>Hide Locked</option></select>";
        }
        html += "</div></th>";
        if(gradebookData[0][0][i] == 'Last Login'){
            sortArray.push("'D'");
        }else if(i != 1){
            sortArray.push("'S'");
        }
    }
    var n = 0;
    //get collapsed gb cat info
    if((gradebookData[0][2].length) > 1){
        var collapseGbCat = [];
        for(i=0;i<gradebookData[0][2].length;i++){
            if(data.overrideCollapse[gradebookData[0][2][i][10]] != undefined){
                collapseGbCat[gradebookData[0][2][i][1]] = data.overrideCollapse[gradebookData[0][2][i][10]];
            } else {
                collapseGbCat[gradebookData[0][2][i][1]] = gradebookData[0][2][i][12];
            }
        }
    }

    if(data.totOnLeft != undefined && hidePast != true){
        //total totals
        if(data.catFilter < 0) {
            if(gradebookData[0][3][0] != undefined || gradebookData[0][3][0] != null){
                html += "<th><div><span class='cat-tot-hdr'>Total<br/>"+gradebookData[0][3][data.availShow]+"&nbsp;pts</span></div></th>";
                html += "<th><div>%</div></th>";
                n += 2;
            } else {
                html += "<th><div><span class='cat-tot-hdr'>Weighted Total %</span></div></th>";
                n++;
            }
        }
        if(gradebookData[0][2].length > 1 || data.catFilter != -1){ //want to show cat headers?
            for(i=0;i<gradebookData[0][2].length;i++){ // category headers
                if((data.availShow<2 || data.availShow==3) && gradebookData[0][2][i][2]>1){
                    continue;
                } else if(data.availShow==2 && gradebookData[0][2][i][2]==3){
                    continue;
                }
                html += "<th class='cat'"+gradebookData[0][2][i][1]+"><div><span class='cat-tot-hdr'>";
                if(data.availShow<3){//using points based
                    html += gradebookData[0][2][i][0]+"<br/>";
                    if(gradebookData[0][3][0] != null || gradebookData[0][3][0] != undefined){
                        html += gradebookData[0][2][i][3+data.availShow]+"&nbsp;pts";
                    } else {
                        html += gradebookData[0][2][i][11]+"%";
                    }
                } else if (data.availShow == 3){//past and attempted
                    html += gradebookData[0][2][i][0];
                    if(gradebookData[0][2][i][11] != null || gradebookData[0][2][i][11] != undefined){
                        html += "<br>"+gradebookData[0][2][i][11]+"%";
                    }
                }
                if(collapseGbCat[gradebookData[0][2][i][1]]==0){
                    html += "<br/><a class='small' href='#'>[Collapse]</a>";
                } else{
                    html += "<br/><a class='small' href='#'>[Expand]</a>";
                }
                html += "</span></div></th>";
                n++;
            }
        }
    }
    if(data.catFilter>-2){
        for (i=0;i<gradebookData[0][1].length;i++){//assessment headers
            if(!data.isTeacher && !data.isTutor && gradebookData[0][1][i][4]==0){//skip if hidden
                continue;
            }
            if(data.hideNC == 1 && gradebookData[0][1][i][4] == 0){//skip NC
                continue;
            } else if(data.hideNC == 2 && (gradebookData[0][1][i][4] == 0 || gradebookData[0][1][i][4] == 3)){//skip all NC
                continue;
            }
            if (gradebookData[0][1][i][3]>data.availShow) {
                continue;
            }
            if (hidePast && gradebookData[0][1][i][3]==0) {
                continue;
            }
            if (collapseGbCat[gradebookData[0][1][i][1]]==2) {
                continue;
            }
            //name and points
            html += "<th class='cat'"+gradebookData[0][1][i][1]+"><div>"+gradebookData[0][1][i][0]+"<br/>";
            if(gradebookData[0][1][i][4]==0 || gradebookData[0][1][i][4]==3) {
                html += gradebookData[0][1][i][2]+"&nbsp;pts (Not Counted)";
            } else {
                html += gradebookData[0][1][i][2]+"&nbsp;pts";
                if(gradebookData[0][1][i][4]==2){
                    html += "(EC)";
                }
            }
            if(gradebookData[0][1][i][5]==1 && gradebookData[0][1][i][6]==0){
                html += "(PT)";
            }
            if(data.includeDueDate && gradebookData[0][1][i][11] < 2000000000 && gradebookData[0][1][i][11]>0) {
                html += "<br/><span class='small'>date('n/j/y&\n\b\s\p;g:ia',"+gradebookData[0][1][i][11]+")</span> "
            }
            //links
            if(gradebookData[0][1][i][6] == 0){ //online
                if(data.isTeacher){
                    html += "<br/><a class='small' href='#'>[Settings]</a> ";
                    html += "<br/><a class='small' href='#'>[Isolate]</a> ";
                    if(gradebookData[0][1][i][10]==true){
                        html += "<br/><a class='small'href='#'>[By Group]</a> ";
                    }
                } else {
                    html += "<br/><a class='small' href='#'>[Isolate]</a> ";
                }
            } else if(gradebookData[0][1][i][6]==1 && (data.isTeacher || (data.isTutor && gradebookData[0][1][i][8]==1))){//offline
                if(data.isTeacher){
                    html += "<br/><a class='small' href='#'>[Settings]</a> ";
                    html += "<br/><a class='small' href='#'>[Isolate]</a> ";
                } else {
                    html += "<br/><a class='small' href='#'>[Scores]</a> ";
                }
            } else if(gradebookData[0][1][i][6]==2 && data.isTeacher){ //discussion
                html += "<br/><a class='small' href='#'>[Settings]</a>";
            } else if(gradebookData[0][1][i][6]==3 && data.isTeacher){ //exttool
                html += "<br/><a class='small' href='#'>[Settings]</a> ";
                html += "<br/><a class='small' href='#'>[Isolate]</a> ";
            }
            html += "</div></th>";
            n++;
        }
    }

    //html += "<th></th><th>Name<br>N= <br><select class='form-control dropdown-auto'><option value='0'>Show Locked</option><option value='1'>Hide Locked</option></select></th><th>Section<br><select class='form-control dropdown-auto'><option value='0'>All</option> </select></th><th>Code</th>";
    html += "</tr></thead><tbody class='gradebook-table-body'></tbody></table>";
    $('.gradebook-div').append(html);
}
