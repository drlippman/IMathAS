$(document).ready(function () {
    var courseId = $(".course-info").val();
    var userId = $(".user-info").val();
    studentMessage();
    studentEmail();
    var allMessage = {courseId: courseId, userId:userId};
    jQuerySubmit('display-gradebook-ajax', allMessage, 'showGradebookSuccess');
    selectCheckBox();
    studentLock();
    studentCopyEmail();
    teacherMakeException();
});
var gradebookData;
var data;
var hidePast;
var showPics = 0;
function chgtoggle(){
    showPics = $('#toggle4').val();
    $('.gradebook-table').remove();
    displayGradebook();
}

function showGradebookSuccess(response){
//console.log(response);
    var result = JSON.parse(response);
    //console.log(result.data)
    gradebookData = result.data.gradebook;
    document.getElementById("gradebook-id").value = gradebookData;
    data = result.data;
    if(data.availShow == 4){
        data.availShow = 1;
        hidePast = true;
    }
    displayGradebook();
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

function displayGradebook() {
    var html = "<table id = 'gradebook-table display-gradebook-table' class = 'gradebook-table display-gradebook-table table table-bordered table-striped table-hover data-table'>";
    html += "<thead><tr>";
    var sortArray = [];
    for(i=0;i<gradebookData[0][0].length;i++){   //biographical headers
        if(i == 1 && showPics !=0){
            html += "<th><div>Picture</div></th>";
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
            html += ">All</option>";
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

    if(data.totOnLeft != undefined && data.totOnLeft != null && hidePast != undefined){
        //total totals
        if(data.catFilter < 0) {
            if(gradebookData[0][3][0] != undefined || gradebookData[0][3][0] != null){
                html += "<th><div><span class='cattothdr'>Total<br/>"+gradebookData[0][3][data.availShow]+"&nbsp;pts</span></div></th>";
                html += "<th><div>%</div></th>";
                n += 2;
            } else {
                html += "<th><div><span class='cattothdr'>Weighted Total %</span></div></th>";
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
                html += "<th class='cat'"+gradebookData[0][2][i][1]+"><div><span class='cattothdr'>";
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
            }o
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
                html += "<br/><span class='small'>date('n/j/y g:ia',"+gradebookData[0][1][i][11]+")</span> "
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
    if(!data.totOnLeft && !hidePast){
        if(gradebookData[0][2].length > 1 || data.catFilter != -1){ //want to show cat headers?
            for(i = 0;i < gradebookData[0][2].length;i++){  //category headers
                if((data.availShow < 2 || data.availShow == 3) && gradebookData[0][2][i][2] > 1){
                    continue;
                } else if (data.availShow == 2 && gradebookData[0][2][i][2] == 3){
                    continue;
                }
                html += "<th class='cat'"+gradebookData[0][2][i][1]+"><div><span class='cattothdr'>";
                if(data.availShow < 3){
                    html += gradebookData[0][2][i][0]+"<br/>";
                    if(gradebookData[0][3][0] != undefined || gradebookData[0][3][0] != null){ //using points based
                        html += gradebookData[0][2][i][3+data.availShow]+"&nbsp;pts";
                    } else {
                        html += gradebookData[0][2][i][11]+"%";
                    }
                } else if (data.availShow == 3) { //past and attempted
                    html += gradebookData[0][2][i][0];
                }
                if (collapseGbCat[gradebookData[0][2][i][1]] == 0){
                    html += "<br/><a class='small' href='#'>[Collapse]</a>";
                } else {
                    html += "<br/><a class='small' href='#'>[Expand]</a>";
                }
                html += "</span></div></th>";
                n++;
            }
        }
        //total totals
        if(data.catFilter < 0){
            if(gradebookData[0][3][0] != null || gradebookData[0][3][0] != undefined){ //using points based
                html += "<th><div><span class='cattothdr'>Total<br/>"+gradebookData[0][3][data.availShow]+"pts</span></div></th>";
                html += "<th><div>%</div></th>";
                n+=2;
            } else {
                html += "<th><div><span class='cattothdr'>Weighted Total %</span> </div> </th>";
                n++;
            }
        }
    }
    html += "</tr></thead><tbody class='gradebook-table-body'>";
    //Create student rows
    for(i=1;i<gradebookData.length;i++){
        if(i==1){
            var insideDiv = "<div>";
            var endDiv = "</div>";
        }
        else{
            var insideDiv = "";
            var endDiv = "";
        }
        if(i%2 != 0){
            html += "<tr class='even' onmouseover='highlightrow(this)' onmouseout='unhighlightrow(this)'>";
        } else {
            html += "<tr class='odd' onmouseover='highlightrow(this)' onmouseout='unhighlightrow(this)'>";
        }
        html += "<td class='locked' scope='row'><div class='trld'>";
        if(gradebookData[i][0][0] != "Averages" && data.isTeacher){
            html += "<input type=\"checkbox\" name='checked' value='"+gradebookData[i][4][0]+"'/>&nbsp;";
        }
        html += "<a href='#'>";
        if(gradebookData[i][4][1] > 0) {
            html += "<span class='greystrike'>"+gradebookData[i][0][0]+"</span>";
        } else {
            html += gradebookData[i][0][0];
        }
        html += "</a>";
        if(gradebookData[i][4][3] == 1){
            html += "<sup>*</sup>";
        }
        html += "</div></td>";
        if(showPics !=0){
        if(showPics == 1 && gradebookData[i][4][2] == 1){
            html += "<td>"+insideDiv+"<div class='trld'><img class='images circular-image' src='../../Uploads/" +gradebookData[i][4][0]+".jpg'></div></td>";
        } else if (showPics == 2 && gradebookData[i][4][2] == 1){
            html += "<td>"+insideDiv+"<div class='trld'><img class='images circular-image big-image' src='../../Uploads/" +gradebookData[i][4][0]+".jpg'></div></td>";
        }else if(showPics == 1 && gradebookData[i][4][2] == 0) {
            html += "<td>"+insideDiv+"<div class='trld'><img class='images circular-image' src='../../Uploads/dummy_profile.jpg'></div></td>";
        }else if(showPics == 2 && gradebookData[i][4][2] == 0) {
            html += "<td>"+insideDiv+"<div class='trld'><img class='images circular-image big-image' src='../../Uploads/dummy_profile.jpg'></div></td>";
        }
        else {
            html += "<td>"+insideDiv+"<div class='trld'>&nbsp;</div></td>";
        }
        }
        for (j=(gradebookData[0][0][1] == 'ID'?1:2);j<gradebookData[0][0].length;j++){
            if(gradebookData[i][0][j]){
                if(j == 2){
                    html += "<td class='c section-class' id='"+gradebookData[i][4][0]+"'>"+insideDiv+gradebookData[i][0][j]+endDiv+"</td>";
                }else{
                    html += "<td class='c'>"+insideDiv+gradebookData[i][0][j]+endDiv+"</td>";
                }
            }else{
                if(j == 2){
                    html += "<td section-class></td>";
                }else{
                    html += "<td></td>";
                }
            }
        }

        if(data.totOnLeft && !hidePast){
            //total totals
            if(data.catFilter < 0) {
                if(data.availShow == 3) {
                    if(gradebookData[i][0][0] == 'Averages') {
                        if(gradebookData[i][3][8] != undefined || gradebookData[i][3][8] != null){
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                        }
                        html += "<td class ='c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                    } else {
                        if (gradebookData[i][3][8] != undefined || gradebookData[i][3][8] != null){
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"/"+gradebookData[i][3][7]+endDiv+"</td>";
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][8]+endDiv+"</td>";
                        } else {
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                        }
                    }
                } else {
                    if(gradebookData[0][3][0] != undefined || gradebookData[0][3][0] != null){
                        html += "<td class='c'>"+insideDiv+gradebookData[i][3][data.availShow]+endDiv+"</td>";
                        html += "<td class='c'>"+insideDiv+gradebookData[i][3][data.availShow+3]+"%"+endDiv+"</td>";
                    } else {
                        html += "<td class='c'>"+insideDiv+gradebookData[i][3][data.availShow]+"%"+endDiv+"</td>";
                    }
                }
            }
            //category total
            if(gradebookData[0][2].length > 1 || data.catFilter != -1){ //want to show category header?
                for(j=0;j<gradebookData[0][2].length;j++){ //category headers
                    if((data.availShow < 2 || data.availShow == 3) && gradebookData[0][2][j][2] > 1){
                        continue;
                    } else if(data.availShow == 2 && gradebookData[0][2][j][2] == 3) {
                        continue;
                    }
                    if (data.catFilter != -1 && data.availShow <3 && gradebookData[0][2][j][data.availShow+3]>0){
                        html += "<td class ='c'>"+insideDiv;
                        if(gradebookData[i][0][0] == 'Averages' && data.availShow != 3){
                            html += "<span onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][2][j][6+data.availShow]+"')\" onmouseout=\"tipout()\">";
                        }
                        html += gradebookData[i][2][j][data.availShow]+"("+Math.round(100*gradebookData[i][2][j][data.availShow]/gradebookData[0][2][j][data.availShow+3])+"%";
                        if(gradebookData[i][0][0] == 'Averages' && data.availShow != 3){
                            html += "</span>";
                        }
                        html += endDiv+"</td>";
                    } else {
                        html += "<td class ='c'>"+insideDiv;
                        if(gradebookData[i][0][0] == 'Averages'){
                            html += "<span onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][2][j][6+data.availShow]+"')\" onmouseout=\"tipout()\">";
                        }
                        if(data.availShow == 3) {
                            if(gradebookData[i][0][0] == 'Averages') {
                                html += gradebookData[i][2][j][3]+"%";
                            } else {
                                html += gradebookData[i][2][j][3]+"/"+gradebookData[i][2][j][4];
                            }
                        } else {
                            if(gradebookData[i][3][8] != null || gradebookData != undefined){
                                html += gradebookData[i][2][j][data.availShow];
                            } else {
                                if(gradebookData[0][2][j][3+data.availShow]>0){
                                    html += Math.round(100*$gbt[$i][2][$j][$availshow]/$gbt[0][2][$j][3+$availshow],1)+"%";
                                } else {
                                    html += "0%";
                                }
                            }
                        }
                        if(gradebookData[i][0][0] == 'Averages'){
                            html += "</span>";
                        }
                        html += endDiv+"</td>";
                    }

                }
            }
        }
        //assessment values
        if(data.catFilter > -2){
            for (j=0;j<gradebookData[0][1].length;j++){
                if(!data.isTeacher && !data.isTutor && gradebookData[0][1][j][4] == 0) { // skip if hidden
                    continue;
                }
                if(data.hideNC == 1 && gradebookData[0][1][j][4] == 0){ //skip NC
                    continue;
                } else if (data.hideNC == 2 && (gradebookData[0][1][j][4] == 0 || gradebookData[0][1][j][4] == 3)){ // skip all NC
                    continue;
                }
                if(gradebookData[0][1][j][3] > data.availShow) {
                    continue;
                }
                if(hidePast && gradebookData[0][1][j][3] == 0){
                    continue;
                }
                if(collapseGbCat[gradebookData[0][1][j][1]] == 2){
                    continue;
                }
                if(isKeyPresent(gradebookData[i][1][j],4)== true){
                    if(gradebookData[0][1][j][6]==0 && gradebookData[i][1][j][4]!='average' && ((gradebookData[i][1][j][3]!=undefined && gradebookData[i][1][j][3] > 9) || (!gradebookData[i][1][j][3] && gradebookData[0][1][j][3]==1))){
                        html += "<td class='c isact'>"+insideDiv;
                    }
                    else{
                        html += "<td class='c isact'>"+insideDiv;
                    }
                } else{
                    html += "<td class='c isact'>"+insideDiv;
                }

                if(isKeyPresent(gradebookData[i][1][j],5)== true) {
                    if (gradebookData[i][1][j][5] && (gradebookData[i][1][j][5] & (1 << data.availShow)) && hidePast != undefined) {
                        html += "<span style='font-style:italic'>";
                    }
                }
                if(gradebookData[0][1][j][6]==0){//online
                    if(isKeyPresent(gradebookData[i][1][j],0)){
                        if (data.isTutor && gradebookData[i][1][j][4] == 'average') {

                        }else if (gradebookData[i][1][j][4]=='average') {
                            html += "<a href='#'onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][1][j][9]+"')\" onmouseout=\"tipout()\">";
                        } else {
                            html += "<a href ='#'>"
                        }
                        if (gradebookData[i][1][j][3] > 9) {
                            gradebookData[i][1][j][3] -= 10;
                        }
                        html += gradebookData[i][1][j][0];
                        if (gradebookData[i][1][j][3] == 1) {
                            html += " (NC)";
                        } else if (gradebookData[i][1][j][3] == 2) {
                            html += " (IP)";
                        } else if (gradebookData[i][1][j][3] == 3) {
                            html += " (OT)";
                        } else if (gradebookData[i][1][j][3] == 4) {
                            html += " (PT)";
                        }
                        if (data.isTutor && gradebookData[i][1][j][4] == 'average') {
                        } else {
                            html += "</a>";
                        }
                        if (gradebookData[i][1][j][1] == 1) {
                            html += "<sup>*</sup>";
                        }
                    } else { //no score
                        if (gradebookData[i][0][0]=='Averages') {
                            html += "-";
                        } else if (data.isTeacher) {
                            html += "<a href = '#'>-</a>";
                        } else {
                            html += "-";
                        }
                    }
                    if(isKeyPresent(gradebookData[i][1][j],6)){
                        if (gradebookData[i][1][j][6] != undefined || gradebookData[i][1][j][6] != null) {
                            if (gradebookData[i][1][j][6] > 1) {
                                if (gradebookData[i][1][j][6]>2) {
                                    html += "<sup>LP("+(gradebookData[i][1][j][6]-1)+")</sup>";
                                } else {
                                    html += "<sup>LP</sup>";
                                }
                            } else {
                                html += "<sup>e</sup>";
                            }
                        }
                    }
                } else if (gradebookData[0][1][j][6]==1) { //offline
                    if (data.isTeacher) {
                        if (gradebookData[i][0][0] == 'Averages') {
                            html += "<a href='#'onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][1][j][9]+"')\" onmouseout=\"tipout()\">";
                        } else {
                            html += "<a href ='#'>"
                        }
                    } else if (data.isTutor && gradebookData[0][1][j][8] == 1) {
                        if (gradebookData[i][0][0] == 'Averages') {
                            html += "<a href='#'>";
                        } else {
                            html += "<a href='#'>";
                        }
                    }
                    if(isKeyPresent(gradebookData[i][1][j],0)){
                    if (gradebookData[i][1][j][0]) {
                        html += gradebookData[i][1][j][0];
                        if (gradebookData[i][1][j][3]==1) {
                            html += " (NC)";
                        }
                    }
                    else {
                        html += "-";
                    }
                    }else {
                        html += "-";
                    }
                    if (data.isTeacher || (data.isTutor && gradebookData[0][1][j][8]==1)) {
                        html += "</a>";
                    }
                    if(isKeyPresent(gradebookData[i][1][j],1)) {
                        if (gradebookData[i][1][j][1] == 1) {
                            html += "<sup>*</sup>";
                        }
                    }
                } else if (gradebookData[0][1][j][6] == 2) { //discuss
                    if (gradebookData[i][1][j][0] != undefined || gradebookData[i][1][j][0] != null) {
                        if ( gradebookData[i][0][0] != 'Averages') {
                            html += "<a href=\"#\">";
                            html += gradebookData[i][1][j][0];
                            html += "</a>";
                        } else {
                            html += "<span onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][1][j][9]+"')\" onmouseout=\"tipout()\"> ";
                            html += gradebookData[i][1][j][0];
                            html += "</span>";
                        }
                        if (gradebookData[i][1][j][1] == 1) {
                            html += "<sup>*</sup>";
                        }
                    } else {
                        if (data.isTeacher && gradebookData[i][0][0] != 'Averages') {
                            html += "<a href=\"#\">-</a>";
                        } else {
                            html += "-";
                        }
                    }
                } else if (gradebookData[0][1][j][6] == 3) { //exttool
                    if (data.isTeacher) {
                        if (gradebookData[i][0][0] == 'Averages') {
                            html += "<a href=\"#\"";
                            html += "onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][1][j][9]+"')\" onmouseout=\"tipout()\" ";
                            html += ">";
                        } else {
                            html += "<a href=\"#\">";
                        }
                    } else if (data.isTutor && gradebookData[0][1][j][8] == 1) {
                        if (gradebookData[i][0][0] == 'Averages') {
                            html += "<a href=\"#\">";
                        } else {
                            html += "<a href=\"#\">";
                        }
                    }
                    if (gradebookData[i][1][j][0] != undefined || gradebookData[i][1][j][0] != null) {
                        html += gradebookData[i][1][j][0];
                        if (gradebookData[i][1][j][3] == 1) {
                            html += " (NC)";
                        }
                    } else {
                        html += "-";
                    }
                    if (data.isTeacher || (data.isTutor && gradebookData[0][1][j][8] == 1)) {
                        html += "</a>";
                    }
                    if (gradebookData[i][1][j][1] == 1) {
                        html += "<sup>*</sup>";
                    }
                }
                //if ((gradebookData[i][1][j][5] != undefined || gradebookData[i][1][j][5] != null) && (gradebookData[i][1][j][5]&(1<<data.availShow)) && !hidePast) {
                //    html += "<sub>d</sub></span>";
                //}
                html += endDiv+"</td>";
            }
        }
        if (!data.totOnLeft && !hidePast) {
            //category totals
            if (gradebookData[0][2].length > 1 || data.catFilter != -1) { //want to show cat headers?
                for (j=0;j < gradebookData[0][2].length;j++) { //category headers
                    if ((data.availShow < 2 || data.availShow == 3) && gradebookData[0][2][j][2] > 1) {
                        continue;
                    } else if (data.availShow==2 && gradebookData[0][2][j][2] == 3) {
                        continue;
                    }
                    if (data.catFilter != -1 && data.availShow < 3 && gradebookData[0][2][j][data.availShow+3] > 0) {
                        html += "<td class = 'c'>"+insideDiv;
                        if (gradebookData[i][0][0] == 'Averages' && data.availShow != 3) {
                            html += "<span onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][2][j][6+data.availShow]+"')\" onmouseout=\"tipout()\" >";
                        }
                        html += gradebookData[i][2][j][data.availShow]+" ("+Math.round(100*gradebookData[i][2][j][data.availShow]/gradebookData[0][2][j][data.availShow+3])+"%)";

                        if (gradebookData[i][0][0] == 'Averages' && data.availShow != 3) {
                            html += "</span>";
                        }
                        html += endDiv+"</td>";
                    } else {
                        html += "<td class = 'c'>"+insideDiv;
                        if (gradebookData[i][0][0] == 'Averages' && data.availShow < 3) {
                            html += "<span onmouseover=\"tipshow(this,'5-number summary:"+gradebookData[0][2][j][6+data.availShow]+"')\" onmouseout=\"tipout()\" >";
                        }
                        if (data.availShow == 3) {
                            if (gradebookData[i][0][0] == 'Averages') {
                                html += gradebookData[i][2][j][3]+"%";
                            } else {
                                html += gradebookData[i][2][j][3]+"/"+gradebookData[i][2][j][4];
                            }
                        } else {
                            if (gradebookData[i][3][8] != undefined || gradebookData[i][3][8] != null) { //using points based
                                html += gradebookData[i][2][j][data.availShow];
                            } else {
                                if (gradebookData[0][2][j][3+data.availShow] > 0) {
                                    html += Math.round(100*gradebookData[i][2][j][data.availShow]/gradebookData[0][2][j][3+data.availShow],1)+"%";
                                } else {
                                    html += "0%";
                                }
                            }
                        }
                        if (gradebookData[i][0][0] == 'Averages' && data.availShow < 3) {
                            html += "</span>";
                        }
                        html += endDiv+"</td>";
                    }
                }
            }
            //total totals
            if (data.catFilter < 0) {
                if (data.availShow == 3) {
                    if (gradebookData[i][0][0] == 'Averages') {
                        if (gradebookData[i][3][8] != undefined || gradebookData[i][3][8] != null) { //using points based
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                        }
                       html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                    } else {
                        if (gradebookData[i][3][8]) { //using points based
                            html += "<td class = 'c'>"+insideDiv.gradebookData[i][3][6]+"/"+gradebookData[i][3][7]+endDiv+"</td>";
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][8] +"%"+endDiv+"</td>";
                        } else {
                            html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][6]+"%"+endDiv+"</td>";
                        }
                    }
                } else {
                    if (gradebookData[0][3][0] != undefined || gradebookData[0][3][0] != null) { //using points based
                        html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][data.availShow]+endDiv+"</td>";
                        html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][data.availShow+3] +"%"+endDiv+"</td>";
                    } else {
                        html += "<td class = 'c'>"+insideDiv+gradebookData[i][3][data.availShow]+"%"+endDiv+"</td>";
                    }
                }
            }
        }
        html += "</tr>";
    }
    html += "</tbody></table>";
    $('.gradebook-div').append(html);
}

function highlightrow(el) {
    el.setAttribute("lastclass",el.className);
    el.className = "highlight";
}
function unhighlightrow(el) {
    el.className = el.getAttribute("lastclass");
}

function studentLock() {
    $('#lock-btn').click(function (e) {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            dataArray.push($(this).parent().text());
        });

        if (markArray.length != 0) {
            var html = '<div><p>Are you SURE you want to lock the selected students out of the course?</p></div><p>';
            $.each(dataArray, function (index, studentData) {
                html += studentData + '<br>';
            });
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Yes, Lock Out Student": function () {
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedStudents: markArray, courseId: course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        return false;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        }
        else {
            var msg = "Select atleast one student.";
            CommonPopUp(msg);
        }
    });
}
function markLockSuccess(response){
    location.reload();
}

function studentUnenroll() {
        var course_id = $("#course-id").val();
        var markArray = [];
        var dataArray = [];
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            dataArray.push($(this).parent().text());
        });
        if (markArray.length != 0) {

            var html = '<div><p><b style = "color: red">Warning!</b>:&nbsp;This will delete ALL course data about these students. This action cannot be undone. ' +
                'If you have a student who isn\'t attending but may return, use the Lock Out of course option instead of unenrolling them.</p><p>Are you SURE' +
                ' you want to unenroll the selected students?</p></p></div>';
            $.each(dataArray, function (index, studentData) {
                html += studentData + '<br>';
            });
            var cancelUrl = $(this).attr('href');
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: '730', resizable: false,
                closeText: "hide",
                buttons: {
                    "Unenroll": function () {
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedStudents: markArray, courseId: course_id};
                        jQuerySubmit('mark-unenroll-ajax', data, 'markUnenrollSuccess');
                        return true;
                    },
                    "Lock Students Out Instead": function () {
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        $(this).dialog("close");
                        var data = {checkedStudents: markArray, courseId: course_id};
                        jQuerySubmit('mark-lock-ajax', data, 'markLockSuccess');
                        return true;
                    },
                    "Cancel": function () {

                        $(this).dialog('destroy').remove();
                        $('.gradebook-table input[name = "checked"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        return false;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        }
        else {
            var msg = "Select atleast one student.";
            CommonPopUp(msg);
        }
}
function markUnenrollSuccess(response) {
    location.reload();
}

function createStudentList(appendId, e){
    var markArray = [];
    $('.gradebook-table input[name = "checked"]:checked').each(function () {
        markArray.push($(this).val());
    });
    if (markArray.length != 0) {
        appendId.value = markArray;
    } else {
        var msg = "Select atleast one student.";
        CommonPopUp(msg);
        e.preventDefault();
    }
}

function studentMessage() {
    $('#roster-message').click(function (e) {
        var appendId =  document.getElementById("message-id");
        createStudentList(appendId, e);
    });
}

function studentEmail() {
    $('#roster-email').click(function (e) {
        var appendId =  document.getElementById("student-id");
        createStudentList(appendId, e);
    });
}

function studentCopyEmail() {
    $('#roster-copy-emails').click(function (e) {
        var appendId =  document.getElementById("email-id");
        createStudentList(appendId, e);
    });
}

function teacherMakeException() {
    $('#gradebook-makeExc').click(function (e) {
        var markArray = [];
        var sectionName;
        $('.gradebook-table input[name = "checked"]:checked').each(function () {
            markArray.push($(this).val());
            sectionName = document.getElementById($(this).val()).textContent;
        });
        if (markArray.length != 0) {
            document.getElementById("exception-id").value = markArray;
            document.getElementById("section-name").value = sectionName;
        } else {
            var msg = "Select atleast one student.";
            CommonPopUp(msg);
            e.preventDefault();
        }
    });
}


