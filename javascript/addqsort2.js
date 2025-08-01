//IMathAS: Utility JS for reordering addquestions existing questions
//(c) 2007 IMathAS/WAMAP Project
//Must be predefined:
//beentaken, defpoints
//itemarray: array
//	item: array ( questionid, questionsetid, description, type, points, canedit ,withdrawn )
//	group: array (pick n, without (0) or with (1) replacement, array of items)

//output submitted via AHAH is new assessment itemorder in form:
// item,item,n|w/wo~item~item,item

$(document).ready(function () {
    $(window).on("beforeunload", function () {
        if (anyEditorIsDirty()) {
            //This message might not ever be displayed
            return "There are unsaved changes in a question intro text box.  Press Leave Page to discard those changes and continue with the most recent action.  Press Stay on Page to return to the page without taking any action.";
        }
    });

    //attach handler to Edit/Collapse buttons and all that are created in
    // future calls to generateTable()
    $(document).on("click", ".text-segment-button", function (e) {
        handleClickTextSegmentButton(e);
    });
    $(window).on("scroll", function () {
        $(".text-segment-button").each(function (index, element) {
            followButtonLocation("#" + element.id);
        });
    });
});

//find position for collapse button in the middle of the visible editor
// selector is the selector for the button
function followButtonLocation(selector) {
    var i = getIndexForSelector(selector);
    var type = getTypeForSelector(selector);
    //text segment which corresponds to this button
    var text_segment_id = "#textseg" + type + i;

    if ($(text_segment_id).hasClass("collapsingsemaphore")) {
        //don't start any animations that could complete after the
        // current collapsing animation completes (race condition)
        return;
    }

    //if the editor is collapsed or is a global button, don't do anything
    if (
        i === undefined ||
        type === "global" ||
        $(text_segment_id).hasClass("collapsed") ||
        $(text_segment_id).hasClass("collapsedheader")
    ) {
        return;
    }
    var button_div = $(selector).parent();
    var $window = $(window);
    var container = button_div.parent();
    var hasfocus = container.children(".mce-edit-focus").length > 0;
    var container_height = container.height();
    //If the editor uses a significant portion of the page, have
    // the collapse button stay in view
    //if (container_height >= 0.3 * $window.height() ) {
    var offset = button_div.position();
    var sidebar_height = button_div.height();
    var foffset = container.offset();
    var padding = 5;
    // find the middle of the visible portion of the editor
    //var top_limit = Math.max($window.scrollTop(),foffset.top);
    //var bottom_limit = Math.min($window.scrollTop() + $window.height(),
    //					foffset.top + container_height) - sidebar_height;
    //position the button_div by its top rather than bottom
    var initial_top = button_div.css("top");
    button_div.css("top", initial_top);
    button_div.css("bottom", "auto");
    button_div.stop().animate({
        top: Math.max(
            padding,
            //Math.min((bottom_limit + top_limit)/2 - foffset.top,
            //		container_height-sidebar_height - padding) )
            Math.min(
                $window.scrollTop() + (hasfocus ? 60 : 0) + padding - foffset.top,
                container_height - sidebar_height - padding
            )
        )
    });
    //}
}

//When the Edit/collapse button is clicked, call the appropriate function
// with the appropriate selector.
function handleClickTextSegmentButton(e) {
    var i = getIndexForSelector("#" + e.currentTarget.id);
    var type = getTypeForSelector("#" + e.currentTarget.id);

    if (type === "global") {
        var selector = ".textsegment";
    } else {
        var selector = "#textseg" + type + i;
    }

    //toggle expand/collapse based on title of button
    if (
        $("#" + e.currentTarget.id)
            .attr("title")
            .match("Collapse")
    ) {
        collapseAndStyleTextSegment(selector);
    } else {
        expandAndStyleTextSegment(selector);
    }
}

var curqlastfocus = [];
function refreshTable() {
    tinymce.remove();
    document.getElementById("curqtbl").innerHTML = generateTable();
    updateqgrpcookie();
    initeditor("selector", "div.textsegment", null, true /*inline*/, editorSetup);
    tinymce.init({
        selector: "h4.textsegment",
        inline: true,
        menubar: false,
        statusbar: false,
        branding: false,
        plugins: ["charmap"],
        toolbar: "charmap saveclose",
        setup: editorSetup
    });
    $("textarea.textsegment,input.textsegment").on("focus", function () {
        var i = this.id.match(/[0-9]+$/)[0];
        var type = getTypeForSelector("#" + this.id);
        //if the editor is collapsed, expand it

        if ($(this).hasClass("collapsed") || $(this).hasClass("collapsedheader")) {
            expandAndStyleTextSegment("#textseg" + type + i);
        }
    }).on("input", function () {
        $(this).data('dirty', true);
        $(".savebtn").prop("disabled", false);
    });
    activateLastEditorIfBlank();
    $(".dropdown-toggle").dropdown();
    $("#curqtbl input")
        .off("keydown.doblur")
        .on("keydown.doblur", function (e) {
            if (e.which == 13) {
                e.preventDefault();
                $(this).trigger('change');;
            }
        });
    $("[id^=pts],[id^=grppts],#defpts")
        .off("change.pts")
        .on("change.pts", updatePts);
    $("#curqtbl *").off("focus.tracker")
        .on("focus.tracker", function(e) {
            var col = $(this).closest("td,th").index();
            var row = $(this).closest("tr").index();
            var rtype = $(this).closest("tbody,thead")[0].nodeName;
            curqlastfocus = [rtype, row, col];
        });
    if (curqlastfocus.length > 0) {
        $("#curqtbl "+curqlastfocus[0]+ " tr").eq(curqlastfocus[1])
            .find("td,th").eq(curqlastfocus[2]).find("input,button,a").focus();
    }
    $("#noqs").toggle(itemarray.length == 0);
    $("#curqform").toggle(itemarray.length != 0);
    if (usingASCIIMath) {
        rendermathnode(document.getElementById("curqtbl"));
    }
}

//Show the editor toolbar on a newly created text segment
function activateLastEditorIfBlank() {
    last_editor = tinymce.editors[tinymce.editors.length - 1];
    if (last_editor !== undefined && last_editor.getContent() == "") {
        tinyMCE.setActive(last_editor);
        last_editor.fire("focus");
        last_editor.selection.setCursorLocation();
    }
}

//this is called by tinycme during initialization
function editorSetup(editor) {
    var i = this.id.match(/[0-9]+$/)[0];
    editor.addButton("saveclose", {
        text: "Save All",
        title: "Save All",
        icon: "save",
        //icon: "shrink2 mce-i-addquestions-ico",
        classes: "dim saveclose saveclose" + i, // "mce-dim" and "mce-saveclose0"
        //disabled: true,
        onclick: function () {
            highlightSaveButton(false);
            savetextseg(); //Save all text segments
        },
        onPostRender: function () {
            updateSaveButtonDimming();
        }
    });
    editor.on("dirty", function () {
        updateSaveButtonDimming();
    });
    editor.on("focus", function () {
        var i = this.id.match(/[0-9]+$/)[0];
        var type = getTypeForSelector("#" + this.id);
        var max_height = $("#" + this.id).css("max-height");
        //if the editor is collapsed, expand it
        if (
            max_height !== undefined &&
            max_height !== "none" &&
            max_height !== ""
        ) {
            expandAndStyleTextSegment("#textseg" + type + i);
        }
    });
    $(".textsegment").on("mouseleave focusout", function (e) {
        highlightSaveButton(true);
    });
    $(".textsegment").on("mouseenter click", function (e) {
        //if rentering the active editor, un-highlight
        if (
            tinymce.activeEditor &&
            tinymce.activeEditor.id === e.currentTarget.id
        ) {
            highlightSaveButton(false);
        }
    });
}

//Highlight all Save All buttons when the mouse leaves an editor
function highlightSaveButton(leaving) {
    if (anyEditorIsDirty()) {
        var i = tinymce.activeEditor.id.match(/[0-9]+$/)[0];
        if (leaving) {
            $("div.mce-saveclose" + i)
                .css("transition", "background-color 0s")
                .addClass("highlightbackground");
        } else {
            $("div.mce-saveclose" + i)
                .css("transition", "background-color 1s ease-out")
                .removeClass("highlightbackground");
        }
    }
}

//If any editor is dirty, undim the Save All button and
// highlight that editor
function updateSaveButtonDimming(dim) {
    var save_buttons = $("div.mce-saveclose");
    if (tinyMCE.activeEditor && tinyMCE.activeEditor.isDirty()) {
        $("div.mce-saveclose").removeClass("mce-dim");
        //update tinymce data structure in case other editors haven't
        // been activated
        for (index in tinymce.editors) {
            var editor = tinymce.editors[index];
            editor.buttons["saveclose"].classes = editor.buttons[
                "saveclose"
            ].classes.replace(/dim ?/g, "");
            //could switch save to collapse icon
            var editor_id = tinymce.activeEditor.id;
            $("#" + editor_id)
                .css("transition", "border 0s")
                .removeClass("intro")
                .parent()
                .addClass("highlightborder");
        }
        var i = getIndexForSelector("#" + tinymce.activeEditor.id);
        var type = getTypeForSelector("#" + tinymce.activeEditor.id);
        $("#edit-button" + type + i).fadeOut();
        //$("#edit-buttonglobal").fadeOut();
        $("#collapse-buttonglobal").fadeOut();
    }
    //TODO if tinyMCE's undo is correctly reflected in isDirty(), we could
    // re-dim the Save All button after checking all editors
}

function expandAndStyleTextSegment(selector) {
    var i = getIndexForSelector(selector);
    var type = getTypeForSelector(selector);

    $(selector).each(function (index, element) {
        expandTextSegment("#" + element.id);
    });
    //$("#collapsedtextfade"+i).removeClass("collapsedtextfade");

    //change the exit/collapse button for the corresponding editor
    if (i === undefined || type === "global") {
        //expand all
        //$("#edit-buttonglobal").attr("title","Collapse All");
        //$("#edit-button-spanglobal").removeClass("icon-pencil")
        //							.addClass("icon-shrink2");
        $("span.text-segment-icon")
            .removeClass("icon-pencil")
            .addClass("icon-shrink2");
        $(".text-segment-button:not(.text-segment-button-global)").attr(
            "title",
            "Collapse"
        );
    } else {
        var editor = getEditorForSelector(selector);
        if (editor !== undefined && editor.isDirty()) {
            $("#edit-button" + type + i).fadeOut();
        }
        $("#edit-button" + type + i).attr("title", "Collapse");
        $("#edit-button-span" + type + i)
            .removeClass("icon-pencil")
            .addClass("icon-shrink2");
    }
}

function collapseAndStyleTextSegment(selector) {
    var i = getIndexForSelector(selector);
    var type = getTypeForSelector(selector);

    if (i !== undefined && useed) {
        //Deactivate the editor
        tinymce.editors["textseg" + type + i].fire("focusout");
    }

    collapseTextSegment(selector);
    //$("#collapsedtextfade"+i).removeClass("collapsedtextfade");

    //toggle the button
    if (i === undefined || type === "global") {
        //collapse all
        //$(".text-segment-button").attr("title","Expand and Edit");
        //$("#edit-buttonglobal").attr("title","Expand All");
        //this is sudden but better than letting the button
        // float out of the editor (poss: use jQueryUI .removeClass(...,200) )
        $(".text-segment-button:not(.text-segment-button-global)")
            .parent()
            .css({ top: "", bottom: "" });
        $(".text-segment-button:not(.text-segment-button-global)").attr(
            "title",
            _("Expand and Edit")
        );

        $("span.text-segment-icon")
            .removeClass("icon-shrink2")
            .removeClass("icon-enlarge2")
            .addClass("icon-pencil");
        //$("#edit-button-spanglobal").removeClass("icon-shrink2")
        //							.removeClass("icon-pencil")
        //							.addClass("icon-enlarge2");
    } else {
        $("#edit-button" + type + i).attr("title", "Expand and Edit");
        $("#edit-button" + type + i)
            .parent()
            .css({ top: "", bottom: "" });
        $("#edit-button-span" + type + i)
            .removeClass("icon-shrink2")
            .addClass("icon-pencil");
    }
}

//adjust the height/width smoothly (could replace with jquery-ui)
function expandTextSegment(selector) {
    var type = getTypeForSelector(selector);
    //copy max-height/max-width to height/width temporarily
    var max_height = $(selector).css("max-height");
    var max_width = parseInt($(selector).css("max-width"));

    //temporarily override the max-height/max-width from class style
    //Note: broswer doesn't reflow yet-- happens during .animate()
    $(selector).css("max-height", "none");
    $(selector).css("max-width", "none");

    //remove wrapping for correct height measurement
    $(selector).css("white-space", "normal");

    //Get the unconstrained height/width of the div
    var natural_height = parseInt($(selector).css("height"));
    var natural_width = parseInt($(selector).css("width"));
    $(selector).css("height", max_height);
    $(selector).css("width", max_width);

    //TODO while expanding, also gradually move collapse button to
    // middle height and avoid race condition

    //smoothly set the height to the natural height
    $(selector)
        .stop(true)
        .animate({ height: natural_height, width: natural_width }, 200, function () {
            // when complete...
            var i = getIndexForSelector(selector);
            var type = getTypeForSelector(selector);

            //when animation completes...
            // remove temporary width/max-width and other styles
            $(selector).css("height", "");
            $(selector).css("width", "");
            $(selector).css("max-width", "");
            $(selector).css("max-height", "");

            $(selector).removeClass("collapsed" + type);
            $(selector).css("white-space", "");

            //ensure the collapse button is visible
            followButtonLocation("#edit-button" + type + i);

            //If a single editor was expanded, activate the editor
            //TODO remember whether this was a global expand
            //     if available, also scroll to keep global button fixed
            var i = getIndexForSelector(selector);
            var type = getTypeForSelector(selector);
            if (i !== undefined && type !== "global") {
                $("#textseg" + type + i).focus();
            }
        });
}

function collapseTextSegment(selector) {
    var i = getIndexForSelector(selector);
    var type = getTypeForSelector(selector);
    var collapsed_height = "1.7em"; //must match .collapsed style
    if (i === undefined || type === "global") {
        var button = $("#edit-buttonglobal");
    } else {
        var button = $("#edit-button" + type + i);
    }
    var initialdistfromtop = button.offset().top - $(window).scrollTop();
    $(selector).addClass("collapsingsemaphore");

    //smoothly set the height to the collapsed height
    $(selector)
        .stop(true)
        .animate({ height: collapsed_height }, 200, function () {
            //when animation completes, set max-height
            $(selector).css("max-height", collapsed_height);
            $(selector).css("height", "");
            $(selector)
                .removeClass("collapsingsemaphore")
                .addClass("collapsed" + type);
            //could this be gradual?
            $(window).scrollTop(button.offset().top - initialdistfromtop);

            if (i === undefined || type === "global") {
                $(".text-segment-button")
                    .parent()
                    .css({ top: "", bottom: "" });
                $(".text-segment-button").each(function (index, element) {
                    followButtonLocation("#" + element.id);
                });
            }
        });
}

function getIndexForSelector(selector) {
    var match = selector.match(/[0-9]+$/);
    if (match) {
        var i = match[0];
    }
    //return undefined if the selector doesn't end with a digit
    return i;
}

//returns "header" if the selector contains "header"
// can be used to find a corresponding class name
// e.g. textsegesheader3 -> edit-buttonheader3
function getTypeForSelector(selector) {
    if (selector.match("global")) {
        var type = "global";
    } else if (selector.match("header")) {
        var type = "header";
    } else {
        var type = "";
    }
    return type;
}

//translates a selector to the corresponding editor if possible
function getEditorForSelector(selector) {
    var i = getIndexForSelector(selector);
    var type = getTypeForSelector(selector);

    if (i !== undefined && i.length > 0) {
        var editor = tinymce.editors["textseg" + type + i];
    }
    //return undefined if the selector didn't end in a digit
    return editor;
}

function anyEditorIsDirty() {
    var any_dirty = false;
    if (useed) {
        for (index in tinymce.editors) {
            if (tinymce.editors[index].isDirty()) {
                any_dirty = true;
                break;
            }
        }
    } else {
        any_dirty = ($(".savebtn:enabled").length > 0);
    }
    return any_dirty;
}

function generateMoveSelect2(num) {
    var thisistxt = itemarray[num][0] == "text";
    num++; //adjust indexing
    var sel = "<select id=" + num + ' onChange="moveitem2(' + num + ')" aria-label="' + _('Move question ') + num + '">';
    var qcnt = 1;
    var tcnt = 1;
    var curistxt = false;
    for (var i = 1; i <= itemarray.length; i++) {
        curistxt = itemarray[i - 1][0] == "text";
        sel += '<option value="' + i + '" ';
        if (i == num) {
            sel += "selected";
        }
        if (curistxt) {
            sel += ">Text" + tcnt + "</option>";
        } else if (itemarray[i - 1].length < 5 && itemarray[i - 1][0] > 1) {
            sel += ">Q" + qcnt + "-" + (qcnt + itemarray[i - 1][0] - 1) + "</option>";
        } else {
            sel += ">Q" + qcnt + "</option>";
        }

        if (!curistxt) {
            if (itemarray[i - 1].length < 5) {
                //is group
                qcnt += parseInt(itemarray[i - 1][0]);//itemarray[i-1][2].length;
            } else {
                qcnt++;
            }
        } else {
            tcnt++;
        }
		/*
		curistxt = (itemarray[i-1][0]=="text");
		if (thisistxt) { //moveselect for text item
			sel += "<option value=\""+i+"\" ";
			if (i==num) {
				sel += "selected";
			}
			if (curistxt) {
				sel += ">Text"+tcnt+"</option>";
			} else {
				if (i==itemarray.length) {
					sel += ">End</option>";
				} else {
					sel += ">Q"+qcnt+"</option>";
				}
			}
		} else if (!curistxt) { //if moveselect for question, skip text items
			sel += "<option value=\""+i+"\" ";
			if (i==num) {
				sel += "selected";
			}
			if (itemarray[i-1].length<5) {
				sel += ">Q"+qcnt+"-"+(qcnt+itemarray[i-1][2].length-1)+"</option>";
			} else {
				sel += ">Q"+qcnt+"</option>";
			}
		}
		if (!curistxt) {
			if (itemarray[i-1].length<5) { //is group
				qcnt += itemarray[i-1][2].length;
			} else {
				qcnt++;
			}
		} else {
			tcnt++;
		}
		*/
    }
    sel += "</select>";
    return sel;
}

function generateMoveSelect(num, itemarray) {
    num++; //adjust indexing
    var sel = "<select id=" + num + ' onChange="moveitem2(' + num + ')">';
    for (var i = 1; i <= cnt; i++) {
        sel += '<option value="' + i + '" ';
        if (i == num) {
            sel += "selected";
        }
        sel += ">" + i + "</option>";
    }
    sel += "</select>";
    return sel;
}

function generateShowforSelect(num) {
    var n = 0,
        i = num;
    if (i > 0 && itemarray[i - 1][0] == "text") {
        //no select unless first in list
        return "";
    }
    while (i < itemarray.length && itemarray[i][0] == "text") {
        i++;
    }
    while (i < itemarray.length && itemarray[i][0] != "text") {
        if (itemarray[i].length < 5) {
            //is group
            n += itemarray[i][0]; //pick n from group
        } else {
            n++;
        }
        i++;
    }
    if (!(5 in itemarray[num])) {
        itemarray[num][5] = 0;
    }
    if (n == 0) {
        return "";
    } else {
        out =
            '<label>' + _('Show for') + ' <select id="showforn' +
            num +
            '" onchange="updateTextShowN(' +
            num +
            "," +
            itemarray[num][2] +
            ')">';
        for (j = 1; j <= n; j++) {
            out += '<option value="' + j + '"';
            if (itemarray[num][2] == j) {
                out += " selected";
            }
            out += ">" + j + "</option>";
        }
        out += "</select></label>";
        if (itemarray[num][2] > 1) {
            out +=
                '<br/><select id="showforntype' +
                num +
                '" onchange="updateTextShowNType(' +
                num +
                "," +
                itemarray[num][5] +
                ')" aria-label="' + _('Show behavior') + '">';
            out += "<option value=0";
            if (itemarray[num][5] == 0) {
                out += " selected";
            }
            out += ">" + ("Closed after 1st") + "</option>";
            out += "<option value=1";
            if (itemarray[num][5] == 1) {
                out += " selected";
            }
            out += ">" + _("Expanded for all") + "</option></select>";
        }
        return out;
    }
}

function moveitem2(from) {
    if (!confirm_textseg_dirty()) {
        //if aborted restore the original value and don't save
        document.getElementById(from).value = from;
    } else {
        var todo = 0;//document.getElementById("group").value;
        var to = document.getElementById(from).value;
        var tomove = itemarray.splice(from - 1, 1);
        if (todo == 0) {
            //rearrange
            itemarray.splice(to - 1, 0, tomove[0]);
        } else if (todo == 1) {
            //group
            if (from < to) {
                to--;
            }
            if (itemarray[to - 1].length < 5) {
                //to is already group
                if (tomove[0].length < 5) {
                    //if grouping a group
                    for (var j = 0; j < tomove[0][2].length; j++) {
                        itemarray[to - 1][2].push(tomove[0][2][j]);
                    }
                } else {
                    itemarray[to - 1][2].push(tomove[0]);
                }
            } else {
                //to is not group
                var existing = itemarray[to - 1];
                if (tomove[0].length < 5) {
                    //if grouping a group
                    tomove[0][2].push(existing);
                    itemarray[to - 1] = tomove[0];
                } else {
                    itemarray[to - 1] = [1, 0, [existing, tomove[0]], 1];
                }
            }
        }
        submitChanges();
    }
    return false;
}

function ungroupitem(from) {
    if (confirm_textseg_dirty()) {
        locparts = from.split("-");
        var tomove = itemarray[locparts[0]][2].splice(locparts[1], 1);
        if (itemarray[locparts[0]][2].length == 1) {
            itemarray[locparts[0]] = itemarray[locparts[0]][2][0];
        }
        itemarray.splice(++locparts[0], 0, tomove[0]);
        submitChanges();
    }
    return false;
}
function removeitem(loc) {
    if (loc.indexOf("-") > -1 || itemarray[loc][0] != "text") {
        var msg = _("Are you sure you want to remove this question?");
    } else {
        var msg = _("Are you sure you want to remove this text segment?");
    }
    if (confirm(msg)) {
        if (confirm_textseg_dirty()) {
            doremoveitem(loc);
            submitChanges();
        }
    }
    return false;
}

function removegrp(loc) {
    if (
        confirm(_("Are you sure you want to remove ALL questions in this group?"))
    ) {
        if (confirm_textseg_dirty()) {
            doremoveitem(loc);
            submitChanges();
        }
    }
    return false;
}

function fullungroup(loc) {
    if (confirm_textseg_dirty()) {
        itemarray = itemarray.slice(0,loc).concat(itemarray[loc][2]).concat(itemarray.slice(loc+1));
        submitChanges();
    }
    return false;
}

function togglegroupEC(loc) {
    var newec = 1 - itemarray[loc][2][0][9];
    for (var i=0; i<itemarray[loc][2].length; i++) {
        itemarray[loc][2][i][9] = newec;
    } 
    submitChanges();
    return false;
}

function doremoveitem(loc) {
    if (loc.indexOf("-") > -1) {
        locparts = loc.split("-");
        if (itemarray[locparts[0]].length < 5) {
            //usual
            itemarray[locparts[0]][2].splice(locparts[1], 1);
            if (itemarray[locparts[0]][2].length == 1) {
                itemarray[locparts[0]] = itemarray[locparts[0]][2][0];
            }
        } else {
            //group already removed
            itemarray.splice(locparts[0], 1);
        }
    } else {
        itemarray.splice(loc, 1);
    }
}

function removeSelected() {
    if (confirm(_("Are you sure you want to remove these questions?"))) {
        if (confirm_textseg_dirty()) {
            var form = document.getElementById("curqform");
            var chgcnt = 0;
            for (var e = form.elements.length - 1; e > -1; e--) {
                var el = form.elements[e];
                if (
                    el.type == "checkbox" &&
                    el.checked &&
                    el.value != "ignore" &&
                    el.id.match("qc")
                ) {
                    val = el.value.split(":");
                    doremoveitem(val[0]);
                    chgcnt++;
                }
            }
            if (chgcnt > 0) {
                submitChanges();
            }
        }
    }
}

function groupSelected() {
    if (!confirm_textseg_dirty()) {
        return; //user wants to abort this call
    }
    var grplist = new Array();
    var form = document.getElementById("curqform");
    var grppoints = 0;
    var grpextracredit = 0;
    for (var e = form.elements.length - 1; e > -1; e--) {
        var el = form.elements[e];
        if (
            el.type == "checkbox" &&
            el.checked &&
            el.value != "ignore" &&
            !el.value.match(":text") &&
            el.id.match("qc")
        ) {
            val = el.value.split(":")[0];
            if (val.indexOf("-") > -1) {
                //is group
                val = val.split("-")[0];
                grppoints = itemarray[val][2][0][4]; //point values from first in group
                grpextracredit = itemarray[val][2][0][9];
            } else {
            }
            isnew = true;
            for (i = 0; i < grplist.length; i++) {
                if (grplist[i] == val) {
                    isnew = false;
                }
            }
            if (isnew) {
                grplist.push(val);
            }
        }
    }
    if (grplist.length < 2) {
        $("#curqtbl input[type=checkbox]").prop("checked", false);
        return;
    }
    var to = grplist[grplist.length - 1];
    var existingcnt = 0;
    if (itemarray[to].length < 5) {
        //moving to existing group
        existingcnt = itemarray[to][2].length;
        if (grppoints == 0) {
            grppoints = itemarray[to][2][0][4]; //point values from first in group
            grpextracredit = itemarray[to][2][0][9];
        }
    } else {
        var existing = itemarray[to];
        if (grppoints == 0) {
            grppoints = existing[4]; //point values from this question
            grpextracredit = existing[9];
        }
        itemarray[to] = [1, 0, [existing], 1];
        existingcnt = 1;
    }
    for (i = 0; i < grplist.length - 1; i++) {
        //going from last in current to first in current
        tomove = itemarray.splice(grplist[i], 1);
        if (tomove[0].length < 5) {
            //if grouping a group
            for (var j = 0; j < tomove[0][2].length; j++) {
                //itemarray[to][2].push(tomove[0][2][j]);
                itemarray[to][2].splice(existingcnt + j, 0, tomove[0][2][j]);
            }
        } else {
            //itemarray[to][2].push(tomove[0]);
            itemarray[to][2].splice(existingcnt, 0, tomove[0]);
        }
    }
    for (i = 0; i < itemarray[to][2].length; i++) {
        itemarray[to][2][i][4] = grppoints;
        itemarray[to][2][i][9] = grpextracredit;
    }
    submitChanges();
}

function updatePts() {
    if (!confirm_textseg_dirty()) {
        $("[id^=pts-],[id^=grppts],#defpts").each(function () {
            $(this).val($(this).attr("data-lastval"));
        });
    } else {
        var newdefpts = Math.ceil($("#defpts").val());
        $("#defpts").val(newdefpts);
        var olddefpts = $("#defpts").attr("data-lastval");
        if (newdefpts == "" || newdefpts <= 0) {
            newdefpts = olddefpts;
            $("#defpts").val(olddefpts);
        }
        var qparts, curval;
        $("[id^=pts-]").each(function () {
            qparts = $(this).attr("id").split("-");
            curval = $(this).val().replace(/\s/g, "");
            if (curval == "" || !curval.match(/^\d+$/) || 1.0 * curval < 0) {
                curval = $(this).attr("data-lastval");
            }
            if (newdefpts != olddefpts && curval == olddefpts) {
                //update pts to match new default
                curval = newdefpts;
            }
            itemarray[qparts[1]][4] = curval == newdefpts ? 9999 : curval;
        });
        $("[id^=grppts-]").each(function () {
            qparts = $(this).attr("id").split("-");
            curval = $(this).val().replace(/\s/g, "");
            if (curval == "" || !curval.match(/^\d+$/) || 1.0 * curval < 0) {
                curval = $(this).attr("data-lastval");
            }
            if (newdefpts != olddefpts && curval == olddefpts) {
                //update pts to match new default
                curval = newdefpts;
            }
            for (var i = 0; i < itemarray[qparts[1]][2].length; i++) {
                itemarray[qparts[1]][2][i][4] = curval == newdefpts ? 9999 : curval;
            }
        });
        submitChanges();
    }
}

function updateGrpN(num, old_num) {
    if (!confirm_textseg_dirty()) {
        //if aborted, restore old value
        $("#grpn" + num).val(old_num);
    } else {
        var nval = Math.floor(document.getElementById("grpn" + num).value * 1);
        if (nval < 1 || isNaN(nval)) {
            nval = 1;
        }
        document.getElementById("grpn" + num).value = nval;
        if (nval != itemarray[num][0]) {
            itemarray[num][0] = nval;
            submitChanges();
        }
    }
}

function updateGrpT(num, old_type) {
    if (!confirm_textseg_dirty()) {
        //if aborted, restore old value
        $("#grptype" + num).val(old_type);
    } else if (
        document.getElementById("grptype" + num).value != itemarray[num][1]
    ) {
        itemarray[num][1] = document.getElementById("grptype" + num).value;
        submitChanges();
    }
}

function confirmclearattempts() {
    return confirm(
        _("Are you sure you want to clear all attempts on this question?")
    );
}

function edittextseg(i) {
    tinyMCE.get("textseg" + i).setContent(itemarray[i][1]);

    if (itemarray[i][3] == 1) {
        tinyMCE.get("textsegheader" + i).setContent(itemarray[i][4]);
    }
}

function savetextseg(i) {
    var any_dirty = false;
    if (useed) {
        for (index in tinymce.editors) {
            var editor = tinymce.editors[index];
            if (editor.isDirty()) {
                var i = editor.id.match(/[0-9]+$/)[0];
                var i = getIndexForSelector("#" + editor.id);
                var type = getTypeForSelector("#" + editor.id);
                if (type === "") {
                    itemarray[i][1] = editor.getContent();
                    any_dirty = true;
                } else if (editor.id.match("textsegheader")) {
                    itemarray[i][4] = strip_tags(editor.getContent());
                    any_dirty = true;
                }
            }
        }
    } else {
        $("textarea.textsegment,input.textsegment").each(function(i,el) {
            if ($(el).data('dirty') === true) {
                var i = getIndexForSelector("#" + el.id);
                var type = getTypeForSelector("#" + el.id);
                if (type === "") {
                    itemarray[i][1] = el.value;
                    any_dirty = true;
                } else if (el.id.match("textsegheader")) {
                    itemarray[i][4] = strip_tags(el.value);
                    any_dirty = true;
                }
            }
        });
    }
    if (any_dirty) {
        if (useed) {
            tinymce.activeEditor.hide();
        }
        submitChanges();
    }
}
function updateTextShowN(i, old_i) {
    if (!confirm_textseg_dirty()) {
        //if aborted, restore old value
        $("#showforn" + i).val(old_i);
    } else {
        itemarray[i][2] = 1.0 * $("#showforn" + i).val();
        submitChanges();
    }
}
function updateTextShowNType(i, old_i) {
    if (!confirm_textseg_dirty()) {
        //if aborted, restore old value
        $("#showforntype" + i).val(old_i);
    } else {
        itemarray[i][5] = $("#showforntype" + i).val() * 1;
        submitChanges();
    }
}

function chgpagetitle(i) {
    if (!confirm_textseg_dirty()) {
        //if aborted, toggle back to previous state
        $("#ispagetitle" + i).prop(
            "checked",
            !$("#ispagetitle" + i).prop("checked")
        );
    } else {
        if ($("#ispagetitle" + i).is(":checked")) {
            itemarray[i][3] = 1;
            if (itemarray[i][4] == "") {
                var words = strip_tags(itemarray[i][1]).split(/\s+/);
                if (words.length > 2) {
                    itemarray[i][4] = words.slice(0, 3).join(" ");
                } else {
                    itemarray[i][4] = "Page title (click to edit)";
                }
            }
        } else {
            itemarray[i][3] = 0;
        }
        submitChanges();
    }
}
function strip_tags(txt) {
    //return $("<div/>").html(txt).text();
    return txt
        .replace(/<[^>]+>/gi, " ")
        .replace(/^\s+/, "")
        .replace(/\s+$/, "");
}
/*
function updateTextseg(i) {
	itemarray[i][1] = $("#textseg"+i).val();
}
*/

function generateOutput() {
    var out = "";
    var text_segments = [];
    var pts = {};
    var extracredit = {};
    var qcnt = 0;
    for (var i = 0; i < itemarray.length; i++) {
        if (itemarray[i][0] == "text") {
            //is text item
            //itemarray[i] is ['text',text,displayforN]
            text_segments.push({
                displayBefore: qcnt,
                displayUntil: qcnt + itemarray[i][2] - 1,
                text: itemarray[i][1],
                ispage: itemarray[i][3],
                pagetitle: itemarray[i][4],
                forntype: itemarray[i][5]
            });
        } else if (itemarray[i].length < 5) {
            //is group
            if (itemarray[i][2].length > 0) { // skip if group is empty; shouldn't happen
                if (out.length > 0) {
                    out += ",";
                }
                out += itemarray[i][0] + "|" + itemarray[i][1];
                for (var j = 0; j < itemarray[i][2].length; j++) {
                    out += "~" + itemarray[i][2][j][0];
                    pts["qn" + itemarray[i][2][j][0]] = itemarray[i][2][j][4];
                    extracredit["qn" + itemarray[i][2][j][0]] = itemarray[i][2][j][9];
                }
                qcnt += itemarray[i][0];
            }
        } else {
            if (out.length > 0) {
                out += ",";
            }
            out += itemarray[i][0];
            pts["qn" + itemarray[i][0]] = itemarray[i][4];
            extracredit["qn" + itemarray[i][0]] = itemarray[i][9];
            qcnt++;
        }
    }
    return [out, text_segments, pts, extracredit];
}

function collapseqgrp(i) {
    itemarray[i][3] = 0;
    updateqgrpcookie();
    refreshTable();
}
function expandqgrp(i) {
    itemarray[i][3] = 1;
    updateqgrpcookie();
    refreshTable();
}
function updateqgrpcookie() {
    var closegrp = [];
    var qcnt = 0;
    for (var i = 0; i < itemarray.length; i++) {
        if (itemarray[i][0] == "text") {
            continue;
        }
        if (itemarray[i].length < 5) {
            //is group
            if (itemarray[i][3] == 0) {
                closegrp.push(qcnt);
            }
        }
        qcnt++;
    }
    document.cookie = "closeqgrp-" + curaid + "=" + closegrp.join(",");
}

function generateTable() {
    olditemarray = itemarray.slice();
    itemcount = itemarray.length;
    var alt = 0;
    var ln = 0;
    var pttotal = 0;
    var html = "";
    var totalcols = 10;

    html += "<table cellpadding=5 class='gb questions-in-assessment'><thead><tr>";
    if (!beentaken) {
        html += "<th><span class='sr-only'>Select</span></th>";
    }
    html += "<th>" + _("Order") + "</th>";
    //return "<span onclick=\"toggleCollapseTextSegments();//refreshTable();\" style=\"color: grey; font-weight: normal;\" >[<span id=\"collapseexpandsymbol\">"+this.getCollapseExpandSymbol()+"</span>]</span>";
    html += "<th>" + _("Description");
    html +=
        "</th><th><span class=\"sr-only\">" +
        _("Features") +
        "</span></th><th>ID</th><th>" +
        _("Preview") +
        "</th><th>" +
        _("Type") +
        "</th><th>" +
        _("Avg Time") +
        "</th>";
    html += "<th>" + _("Points");
    if (!beentaken) {
        html +=
            "<br/><span class=small><label>" +
            _("Default") +
            ': <input id="defpts" type=number min=0 step=1 size=2 value="' +
            defpoints +
            '" data-lastval="' +
            defpoints +
            '"/></label></span>';
    }
    html += "</th>";
    html += "<th>" + _("Actions") + "</th>";
    html += "</thead><tbody>";
    var text_segment_count = 0;
    var curqnum = 0;
    var curqitemloc = 0;
    var curgrppoints = 0;
    var badgrppoints = false;
    var badthisgrppoints = false;
    var grppoints = -1;
    var grpextracredit = -1;
    var ECmark = ' <span onmouseover="tipshow(this,\'' + _('Extra Credit') + '\')" onmouseout="tipout()">' + _('EC') + '</span>';
    for (var i = 0; i < itemcount; i++) {
        curistext = 0;
        curisgroup = 0;
        if (itemarray[i][0] == "text") {
            var curitems = new Array();
            curitems[0] = itemarray[i];
            curistext = 1;
        } else if (itemarray[i].length < 5) {
            //is group
            curitems = itemarray[i][2];
            curisgroup = 1;
        } else {
            //not group
            var curitems = new Array();
            curitems[0] = itemarray[i];
        }
        curqitemloc = i - text_segment_count;
        //var ms = generateMoveSelect(i,itemcount);
        var ms = generateMoveSelect2(i);
        grppoints = -1;
        grpextracredit = -1;
        badthisgrppoints = false;
        for (var j = 0; j < curitems.length; j++) {
            if (alt == 0) {
                curclass = "even";
            } else {
                curclass = "odd";
            }
            if (curistext == 1) {
                curclass += " textsegmentrow skipmathrender";
            }
            html += "<tr class='" + curclass + "'>";
            if (curisgroup) {
                if (curitems[0][4] == 9999) {
                    //points
                    curgrppoints = defpoints;
                } else {
                    curgrppoints = curitems[0][4];
                }
            }
            if (beentaken) {
                if (curisgroup) {
                    if (j == 0) {
                        html +=
                            "<td>Q" +
                            (curqnum + 1) +
                            "</td><td colspan=" +
                            (totalcols - 4) +
                            "><b>" +
                            _("Group") +
                            "</b>, " +
                            _("choosing ") +
                            itemarray[i][0];
                        if (itemarray[i][1] == 0) {
                            html += _(" without");
                        } else if (itemarray[i][1] == 1) {
                            html += _(" with");
                        }
                        html += _(" replacement") + "</td>";
                        //html += "<td class=\"c nowrap\"><input size=2 class=c id=\"grppts-"+i+"\" value=\""+curgrppoints+"\" data-lastval=\""+curgrppoints+"\"/>";
                        html += '<td class="c nowrap">' + curgrppoints;
                        if (itemarray[i][0] > 1) {
                            html += "ea";
                        }
                        html += (curitems[0][9] > 0 ? ECmark : '');
                        html +=
                            '</td><td class=c><div class="dropdown"><button tabindex=0 class="dropdown-toggle plain" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                        html += '⋮</button><ul role="menu" class="dropdown-menu dropdown-menu-right">';
                        html += '<li><a href="#" onclick="return togglegroupEC(' + i + ');">' +
                            _("Toggle Extra Credit") +
                            "</a></li>";
                        html += '</ul></div></td>';
                        html += "</tr><tr class=" + curclass + ">";
                    }
                    html += "<td>&nbsp;Q" + (curqnum + 1) + "-" + (j + 1);
                } else if (curistext) {
                    //html += "<td>Text"+(text_segment_count+1);
                    html += "<td>" + ms;
                } else {
                    html += "<td>Q" + (curqnum + 1);
                }
                html +=
                    '<input type=hidden id="qc' +
                    ln +
                    '" name="checked[]" value="' +
                    (curisgroup ? i + "-" + j : i) +
                    ":" +
                    curitems[j][0] +
                    '"/>';
                html += "</td>";
            } else {
                html += "<td>";
                if (j == 0) {
                    if (!curisgroup) {
                        html +=
                            '<input type=checkbox id="qc' +
                            ln +
                            '" name="checked[]" value="' +
                            i +
                            ":" +
                            curitems[j][0] +
                            ":" +
                            curqnum +
                            '" ' + 
                            (curitems[j][0]=="text" ? 'aria-label="' + _('Text segment') + '"' : '') +
                            '/></td><td>';
                    } else {
                        if (itemarray[i][3] == 1) {
                            html +=
                                '<img src="' +
                                staticroot +
                                '/img/collapse.gif" onclick="collapseqgrp(' +
                                i +
                                ')" alt="' +
                                _("Collapse") +
                                '"/>';
                        } else {
                            html +=
                                '<img src="' +
                                staticroot +
                                '/img/expand.gif" onclick="expandqgrp(' +
                                i +
                                ')" alt="' +
                                _("Expand") +
                                '"/>';
                        }
                        html += "</td><td>";
                    }
                    html += ms;
                    if (curisgroup) {
                        html +=
                            "</td><td colspan=" +
                            (totalcols - 4) +
                            "><b>" +
                            _("Group") +
                            "</b> ";
                        html +=
                            '<label>' + 
                            _("Select") +
                            " <input type='text' size='3' id='grpn" +
                            i +
                            "' value='" +
                            itemarray[i][0] +
                            "' onchange='updateGrpN(" +
                            i +
                            "," +
                            itemarray[i][0] +
                            ")'/> " +
                            _("from group of ") +
                            curitems.length + 
                            '</label>';
                        html +=
                            " <label><select id='grptype" +
                            i +
                            "' onchange='updateGrpT(" +
                            i +
                            "," +
                            itemarray[i][1] +
                            ")'><option value=0 ";
                        if (itemarray[i][1] == 0) {
                            html += "selected=1";
                        }
                        html += ">" + _("Without") + "</option><option value=1 ";
                        if (itemarray[i][1] == 1) {
                            html += "selected=1";
                        }
                        html += ">" + _("With") + "</option></select>" + _(" replacement") + '</label>';
                        html += "</td>";
                        html +=
                            '<td class="nowrap c"><input size=2 type=number min=0 step=1 id="grppts-' +
                            i +
                            '" value="' +
                            curgrppoints +
                            '" data-lastval="' +
                            curgrppoints +
                            '" aria-label="' + _('Points for questions in group') + '"/>';
                        if (itemarray[i][0] > 1) {
                            html += "ea";
                        }
                        html += (curitems[0][9] > 0 ? ECmark : '');

                        html +=
                            '</td><td class=c><div class="dropdown"><button tabindex=0 class="dropdown-toggle plain" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                        html += '⋮</button><ul role="menu" class="dropdown-menu dropdown-menu-right">';

                        html +=
                            '<li><a href="#" onclick="return removegrp(\'' +
                            i +
                            "');\">" +
                            _("Remove Group and Questions") +
                            "</a></li>";
                        html +=
                            '<li><a href="#" onclick="return fullungroup(' + i + ');">' +
                            _("Ungroup all Questions") +
                            "</a></li>";
                        html +=
                            '<li><a href="#" onclick="return togglegroupEC(' + i + ');">' +
                            _("Toggle Extra Credit") +
                            "</a></li>";
                        html += '</ul></div></td></tr>';

                        if (itemarray[i][3] == 0) {
                            //collapsed group
                            if (curitems[0][4] == 9999) {
                                //points
                                curpt = defpoints;
                            } else {
                                curpt = curitems[0][4];
                            }
                            break;
                        }
                        html += "<tr class=" + curclass + "><td>";
                    }
                }
                if (curisgroup) {
                    html +=
                        '<input type=checkbox id="qc' +
                        ln +
                        '" name="checked[]" value="' +
                        (i + "-" + j) +
                        ":" +
                        curitems[j][0] +
                        ":" +
                        (curqnum + "-" + j) +
                        '" ' +
                        (curitems[j][0]=="text" ? 'aria-label="' + _('Text segment') + '"' : '') +
                        '/></td><td>';
                    html +=
                        '<a href="#" onclick="return ungroupitem(\'' +
                        i +
                        "-" +
                        j +
                        "');\">Ungroup</a>"; //FIX
                }
                html += "</td>";
            }
            if (curistext == 1) {
                text_segment_count++;
                //html += "<td colspan=7><input type=\"text\" id=\"textseg"+i+"\" onkeyup=\"updateTextseg("+i+")\" value=\""+curitems[j][1]+"\" size=40 /></td>"; //description
                //html += '<td>Show for <input type="text" id="showforn'+i+'" size="1" value="'+curitems[j][2]+'"/></td>';
                if (displaymethod == "Embed" || displaymethod == "full") {
                    html +=
                        "<td colspan=" +
                        (totalcols - 4) +
                        ' id="textsegdescr' +
                        i +
                        '" class="description-cell">';
                    if (curitems[j][3] == 1) { // is page
                        var header_contents = curitems[j][4];
                        if (useed) {
                            html +=
                                '<div style="position: relative"><h4 id="textsegheader' +
                                i +
                                '" class="textsegment collapsedheader">' +
                                header_contents +
                                "</h4>";
                        } else {
                            html +=
                                '<div style="position: relative"><input id="textsegheader' +
                                i +
                                '" class="textsegment collapsedheader" value="' +
                                header_contents +
                                '"/>' +
                                '<button type=button class="savebtn slim" onclick="savetextseg();" disabled=true>' + _('Save All') + '</button>';
                        }
                        html +=
                            '<div class="text-segment-icon"><button id="edit-buttonheader' +
                            i +
                            '" type="button" title="' +
                            _("Expand and Edit") +
                            '" class="text-segment-button"><span id="edit-button-spanheader' +
                            i +
                            '" class="icon-pencil text-segment-icon"></span></button></div></div>';
                    }
                    var contents = curitems[j][1];
                    if (useed) {
                        html +=
                            '<div class="intro intro-like"><div id="textseg' +
                            i +
                            '" class="textsegment collapsed">' +
                            contents +
                            "</div>";
                    } else {
                        html +=
                            '<div class="intro intro-like"><textarea id="textseg' +
                            i +
                            '" class="textsegment collapsed">' +
                            contents +
                            "</textarea>" +
                            '<button type=button class="savebtn slim" onclick="savetextseg();" disabled=true>' + _('Save All') + '</button>';
                    }
                    html +=
                        '<div class="text-segment-icon"><button id="edit-button' +
                        i +
                        '" type="button" title="' +
                        _("Expand and Edit") +
                        '" class="text-segment-button"><span id="edit-button-span' +
                        i +
                        '" class="icon-pencil text-segment-icon"></span></button></div></div></div></td>';
                    html += '<td><input type="hidden" id="showforn' + i + '" value="1"/>';
                    html +=
                        '<label><input type="checkbox" id="ispagetitle' +
                        i +
                        '" onchange="chgpagetitle(' +
                        i +
                        ')" ';
                    if (curitems[j][3] == 1) {
                        html += "checked";
                    }
                    html += ">" + _("New page") + "</label></td>";
                } else {
                    var contents = curitems[j][1];
                    html +=
                        "<td colspan=" +
                        (totalcols - 5) +
                        ' id="textsegdescr' +
                        i +
                        '" class="description-cell">'; //description
                    if (useed) {
                        html +=
                            '<div class="intro intro-like"><div id="textseg' +
                            i +
                            '" class="textsegment collapsed">' +
                            contents +
                            "</div>";
                    } else {
                        html +=
                            '<div class="intro intro-like"><textarea id="textseg' +
                            i +
                            '" class="textsegment collapsed">' +
                            contents +
                            "</textarea>" +
                            '<button type=button class="savebtn slim" onclick="savetextseg();" disabled=true>' + _('Save All') + '</button>';
                    }
                    html +=
                        '<div class="text-segment-icon"><button id="edit-button' +
                        i +
                        '" type="button" title="' +
                        _("Expand and Edit") +
                        '" class="text-segment-button"><span id="edit-button-span' +
                        i +
                        '" class="icon-pencil text-segment-icon"></span></button></div></div></div></td>';
                    html += "<td colspan=2>" + generateShowforSelect(i) + "</td>";
                }
                //if (beentaken) {
                //	html += "<td></td>";
                //} else {
                html +=
                    '<td class=c><a href="#" onclick="return removeitem(\'' +
                    i +
                    "');\">" +
                    _("Remove") +
                    "</a></td>";
                //}
            } else {
                var tdtitle = '';
                var tdclass = '';
                var descricon = '';
                if (beentaken && curitems[j][6] == 1) {
                    tdclass = 'greystrike';
                    tdtitle = _("Question Withdrawn");
                } else if (curitems[j][10] == 1) {
                    tdclass = 'qbroken';
                }
                if (curitems[j][10] == 1) {
                    descricon = '<span title="' + _('Marked as broken') + '">' + 
                    '<svg role="img" viewBox="0 0 24 24" width="16" height="16" stroke="#f66" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><title>' + _('Marked as broken') + '</title><path d="M19.7 1.3 19.6 9 16.2 6.3 13.8 11.3 10.5 8.3 7 11.7 3.6 9.2l0-7.9z" class="a"></path><path d="m19.7 22.9 0-7.8-2-1.4-3.1 4-3.3-3-3.8 3.8-4-3.9v8.4z" class="a"></path></svg>' + 
                    '</span> ';
                }
                html += '<td';
                if (tdclass !== '') {
                    html += ' class="' + tdclass + '"';
                }
                if (tdtitle !== '') {
                    html += ' title="' + tdtitle + '"';
                }
                html += '>' + descricon;
                html +=
                    '<input type=hidden name="curq[]" id="oqc' +
                    ln +
                    '" value="' +
                    curitems[j][1] +
                    '"/>';
                
                html += '<label for="qc'+ln+'" id="qsd'+ln+'">' + curitems[j][2] + "</label></td>"; //description
                html += '<td class="nowrap">';
                if ((curitems[j][7] & 32) == 32) {
                    html += '<span title="' + _('Show Work') + '">' + 
                    '<svg role="img" viewBox="0 0 24 24" width="14" height="14" stroke="black" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><title>' + _('Show Work') + '</title><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>'
                    + '</span>';
                }
                if ((curitems[j][7] & 64) == 64) {
                    html += '<span title="' + _('Has Rubric') + '">' + 
                    '<svg role="img" viewBox="0 0 24 24" width="14" height="14" stroke="black" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><title>' + _('Has Rubric') + '</title><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>'
                    + '</span>';
                }
                if ((curitems[j][7] & 256) == 256) {
                    html += '<span title="' + _('Not Randomized') + '">' + 
                    '<svg role="img" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1"><title>' + _('Not Randomized') + '</title><polyline points="17 1 21 5 17 9"></polyline><path d="M3 11V9a4 4 0 0 1 4-4h14"></path><polyline points="7 23 3 19 7 15"></polyline><path d="M21 13v2a4 4 0 0 1-4 4H3"></path><line stroke="#f00" x1="5" y1="1" x2="19" y2="23"></line></svg>' +
                    '</span>';
                }
                if ((curitems[j][7] & 1) == 1) {
                    var showicons = "";
                    var altadd = "";
                } else {
                    var showicons = "_no";
                    var altadd = _(" disabled");
                }
                if ((curitems[j][7] & 128) == 128) {
                    var showiconsWE = "";
                    var altaddWE = "";
                } else {
                    var showiconsWE = "_no";
                    var altaddWE = _(" disabled");
                }
                if ((curitems[j][7] & 4) == 4) {
                    if ((curitems[j][7] & 16) == 16) {
                        html += '<div class="ccvid inlinediv"';
                        var altbase = _("Captioned video");
                    } else {
                        html += '<div class="inlinediv"';
                        var altbase = _("Video");
                    }
                    
                    html += 'title="'+altbase+altadd+'">';
                    html +=
                        '<img src="' +
                        staticroot +
                        "/img/video_tiny" +
                        showicons +
                        '.png" alt="' +
                        altbase +
                        altadd +
                        '"/>';
                    html += '</div>';
                }
                if ((curitems[j][7] & 2) == 2) {
                    html +=
                        '<img src="' +
                        staticroot +
                        "/img/html_tiny" +
                        showicons +
                        '.png" alt="'+_('Help Resource') +
                        altadd +
                        '" title="'+_('Help Resource') +
                        altadd +
                        '"/>';
                }
                if ((curitems[j][7] & 8) == 8) {
                    html +=
                        '<img src="' +
                        staticroot +
                        "/img/assess_tiny" +
                        showiconsWE +
                        '.png" alt="'+('Written example') +
                        altadd +
                        '" title="'+('Written example') +
                        altaddWE +
                        '"/>';
                }
                html += "</td>";
                html += "<td>" + curitems[j][1] + "</td>";
                if (beentaken) {
                    html +=
                        "<td><button type='button' onClick=\"previewq('curqform','qc" +
                        ln +
                        "'," +
                        curitems[j][1] +
                        ',false,false)">' +
                        _("Preview") +
                        "</button></td>"; //Preview
                } else {
                    html +=
                        "<td><button type='button' onClick=\"previewq('curqform','qc" +
                        ln +
                        "'," +
                        curitems[j][1] +
                        ',true,false)">' +
                        _("Preview") +
                        "</button></td>"; //Preview
                }
                html += "<td>" + curitems[j][3] + "</td>"; //question type
                html += "<td class=c>";
                if (curitems[j][8][3] > 4) {   
                    html +=
                        "<span onmouseover=\"tipshow(this,'"+('Avg score on first try: ') +
                        curitems[j][8][1] +
                        "%";
                    html +=
                        "<br/>"+_("Avg time on first try: ") +
                        curitems[j][8][2] +
                        _(" min")+"<br/>N=" +
                        curitems[j][8][3] +
                        '\')" onmouseout="tipout()">';
                    
                    html += curitems[j][8][0];
                    html += "</span>";
                }
                html += "</td>";
                if (curitems[j][4] == 9999) {
                    //points
                    curpt = defpoints;
                } else {
                    curpt = curitems[j][4];
                }
                if (curisgroup) {
                    if (grppoints == -1) {
                        grppoints = curpt;
                    } else if (curpt != grppoints) {
                        badgrppoints = true;
                        //fix it
                        if (grppoints == defpoints) {
                            itemarray[i][2][j][4] = 9999;
                        } else {
                            itemarray[i][2][j][4] = grppoints;
                        }
                    }
                    if (grpextracredit == -1) {
                        grpextracredit = curitems[j][9];
                    } else if (curitems[j][9] != grpextracredit) {
                        //fix it
                        itemarray[i][2][j][9] = grpextracredit;
                    }
                }
                if (curisgroup) {
                    html += "<td></td>";
                    //} else if (badthisgrppoints) {
                    //	html += "<td class=c><span class=noticehighlight>"+curpt+"</span></td>"; //points
                } else {
                    if (beentaken) {
                        html += "<td>" + curpt + 
                            (curitems[j][9] > 0 ? ECmark : '') +
                            "</td>";
                    } else {
                        html +=
                            '<td><input size=2 type=number min=0 step=1 id="pts-' +
                            i +
                            '" value="' +
                            curpt +
                            '" data-lastval="' +
                            curpt +
                            '" ' + 
                            'aria-labelledby="qsd' + ln + '"' +
                            '/>' +
                            (curitems[j][9] > 0 ? ECmark : '') +
                            '</td>'; //points
                    }
                }

                html +=
                    '<td class=c><div class="dropdown"><button tabindex=0 class="dropdown-toggle plain" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                html += '⋮</button><ul role="menu" class="dropdown-menu dropdown-menu-right">';
                html +=
                    ' <li><a href="modquestion' +
                    (assessver > 1 ? "2" : "") +
                    ".php?id=" +
                    curitems[j][0] +
                    "&aid=" +
                    curaid +
                    "&cid=" +
                    curcid +
                    "&loc=" +
                    (curisgroup ? curqnum + 1 + "-" + (j + 1) : curqnum + 1) +
                    '&from=addq2">' +
                    _("Change Settings") +
                    "</a></li>";
                if (curitems[j][5] == 1) {
                    html +=
                        '<li><a href="moddataset.php?id=' +
                        curitems[j][1] +
                        "&qid=" +
                        curitems[j][0] +
                        "&aid=" +
                        curaid +
                        "&cid=" +
                        curcid +
                        '&from=addq2&viewonly=1">' +
                        _("View Code") +
                        "</a></li>"; //edit
                    html +=
                        '<li><a href="moddataset.php?id=' +
                        curitems[j][1] +
                        "&qid=" +
                        curitems[j][0] +
                        "&aid=" +
                        curaid +
                        "&cid=" +
                        curcid +
                        '&from=addq2">' +
                        _("Edit Code") +
                        "</a></li>"; //edit
                } else {
                    html +=
                        '<li><a href="moddataset.php?id=' +
                        curitems[j][1] +
                        "&aid=" +
                        curaid +
                        "&cid=" +
                        curcid +
                        '&from=addq2">' +
                        _("View Code") +
                        "</a></li>";
                    html +=
                        '<li><a href="moddataset.php?id=' +
                        curitems[j][1] +
                        "&template=true&makelocal=" +
                        curitems[j][0] +
                        "&aid=" +
                        curaid +
                        "&cid=" +
                        curcid +
                        '&from=addq2">' +
                        _("Edit Personal Copy") +
                        "</a></li>"; //edit makelocal
                }
                if (beentaken) {
                    html +=
                        '<li><a href="addquestions2.php?aid=' +
                        curaid +
                        "&cid=" +
                        curcid +
                        "&clearqattempts=" +
                        curitems[j][0] +
                        '" ';
                    html +=
                        'onclick="return confirmclearattempts()">Clear Attempts</a></li>'; //add link
                    if (curitems[j][6] != 1) {
                        html +=
                            '<li><a href="addquestions2.php?aid=' +
                            curaid +
                            "&cid=" +
                            curcid +
                            "&withdraw=" +
                            (curisgroup ? curqitemloc + "-" + j : curqitemloc) +
                            '">' +
                            _("Withdraw") +
                            "</a></li>";
                    } else {
                        html +=
                            "<li><span><span class=noticetext>" +
                            _("Withdrawn") +
                            "</span></span></li>";
                    }
                    html +=
                        '<li><a href="gb-rescoreq' +
                        (assessver > 1 ? "2" : "") +
                        ".php?cid=" +
                        curcid +
                        "&aid=" +
                        curaid +
                        "&qid=" +
                        curitems[j][0] +
                        "&qsid=" +
                        curitems[j][1] +
                        '&from=addq2">' +
                        _("Re-score Question") +
                        "</a></li>";
                } else {
                    html +=
                        '<li><a href="moddataset.php?id=' +
                        curitems[j][1] +
                        "&template=true&aid=" +
                        curaid +
                        "&cid=" +
                        curcid +
                        '&from=addq2">' +
                        _("Template") +
                        "</a></li>"; //add link
                    html +=
                        '<li><a href="#" onclick="return removeitem(' +
                        (curisgroup ? "'" + i + "-" + j + "'" : "'" + i + "'") +
                        ');">' +
                        _("Remove") +
                        "</a></li>"; //add link and checkbox
                }
                if (j == 0) {
                    html += '<li><a href="#" onclick="addtextsegment(' + i + '); return false;">' + _('Add Text Before') + '</a></li>';
                }
                html += '</ul></div>';
            }
            html += "</tr>";
            ln++;
        }
        if (curistext == 0) {
            if ((curisgroup && itemarray[i][2][0][9] == 0) || 
                (!curisgroup && itemarray[i][9] == 0)
            ) {
                pttotal += curpt * (curisgroup ? itemarray[i][0] : 1);
            }
            curqnum += curisgroup ? itemarray[i][0] : 1;
        }
        alt = 1 - alt;
    }
    if (beentaken) {
        html += "<tr><td></td>";
    } else {
        html += "<tr><td></td><td></td>";
    }
    html +=
        '<td colspan=8><button type="button" onclick="addtextsegment()" title="' +
        _("Insert Instructions or Video for Question") +
        '" id="add-text-button"><span class="icon-plus" style="font-size:0.8em"></span> Text</button>';
    if (text_segment_count > 1) {
        html +=
            ' <div class="text-segment-icon text-segment-iconglobal"><button id="edit-buttonglobal" type="button" title="' +
            _("Expand All") +
            '" class="text-segment-button text-segment-button-global"><span id="edit-button-spanglobal" class="icon-enlarge2"></span></button></div>';
        html +=
            ' <div class="text-segment-icon text-segment-iconglobal"><button id="collapse-buttonglobal" type="button" title="' +
            _("Collapse All") +
            '" class="text-segment-button text-segment-button-global"><span id="collapse-button-spanglobal" class="icon-shrink2"></span></button></div>';
    }
    html +=
        '<div class="text-segment-iconglobal"><img src="' +
        staticroot +
        '/img/help.gif" alt="Help" onClick="window.open(\'' +
        imasroot +
        "/help.php?section=addingquestionstoanassessment','help','top=0,width=400,height=500,scrollbars=1,left=" +
        (screen.width - 420) +
        "')\"/></div>";
    html += "</td><td></td><td></td></tr>";

    html += "</tbody></table>";
    if (badgrppoints) {
        submitChanges();
        html +=
            "<p class=noticetext>" +
            _(
                "WARNING: All question in a group should be given the same point values"
            ) +
            ".</p>";
    }
    document.getElementById("pttotal").innerHTML = pttotal;

    return html;
}

function addtextsegment(n) {
    if (confirm_textseg_dirty()) {
        if (typeof n === 'number') {
            itemarray.splice(n, 0, ["text", "", 1, 0, "", 1]);
        } else {
            itemarray.push(["text", "", 1, 0, "", 1]);
        }
        refreshTable();
    }
}

function check_textseg_itemarray() {
    var lastwastext = false,
        numq,
        j,
        firstpageloc = -1;
    for (var i = 0; i < itemarray.length; i++) {
        if (itemarray[i][0] == "text") {
            //this is text item
            if (lastwastext) {
                //make sure showN matches
                itemarray[i][2] = itemarray[i - 1][2];
            }
            if (itemarray[i][3] == 1 && firstpageloc == -1) {
                firstpageloc = i;
            }
            numq = 0;
            j = i + 1;
            while (j < itemarray.length && itemarray[j][0] != "text") {
                if (itemarray[j].length < 5) {
                    //is group
                    numq += parseInt(itemarray[j][0]);
                } else {
                    numq++;
                }
                j++;
            }
            //make sure isn't bigger than number of q, but is at least 1
            itemarray[i][2] = Math.max(1, Math.min(itemarray[i][2], numq));

            lastwastext = true;
        } else {
            lastwastext = false;
        }
    }
    if (firstpageloc > 0) {
        alert(
            _(
                "If you are using page titles, you need to have a page title at the beginning."
            )
        );
        if (itemarray[0][0] == "text") {
            itemarray[0][3] = 1;
            itemarray[0][4] = _("First Page Title");
        } else {
            itemarray.unshift(["text", "", 1, 1, _("First Page Title"), 1]);
        }
    }
}

function confirm_textseg_dirty() {
    if (anyEditorIsDirty()) {
        var discard_other_changes = confirm(
            _(
                "There are unsaved changes in a question intro text box.  Press OK to discard those changes and continue with the most recent action.  Press Cancel to return to the page without taking any action."
            )
        );
    } else {
        var discard_other_changes = true;
    }
    return discard_other_changes;
}

function submitChanges() {
    var target = "submitnotice";
    check_textseg_itemarray();
    document.getElementById(target).innerHTML = _(" Saving Changes... ");
    document.getElementById("statusmsg").textContent = _("Saving Changes");
    data = generateOutput();
    var outdata = {
        order: data[0],
        text_order: JSON.stringify(data[1]),
        lastitemhash: lastitemhash
    };
    if (!beentaken) {
        outdata["pts"] = JSON.stringify(data[2]);
        outdata["extracredit"] = JSON.stringify(data[3]);
        outdata["defpts"] = $("#defpts").val();
    } else {
        outdata["extracredit"] = JSON.stringify(data[3]);
    }
    $.ajax({
        type: "POST",
        url: AHAHsaveurl,
        data: outdata
    })
        .done(function (msg) {
            if (msg.match(/^error:/)) {
                document.getElementById(target).innerHTML = msg;
                document.getElementById("statusmsg").textContent = msg;
                itemarray = olditemarray.slice();
                refreshTable();
                return;
            }
            if (!beentaken) {
                defpoints = $("#defpts").val();
            }
            lastitemhash = msg;
            document.getElementById(target).innerHTML = "";
            document.getElementById("statusmsg").textContent = _("Done");
            refreshTable();
            updateInAssessMarkers();
            updateSaveButtonDimming();
            //scroll to top if save action puts the curqtbl out of view
            if (
                $(window).scrollTop() >
                $("#curqtbl").position().top + $("#curqtbl").height()
            ) {
                $(window).scrollTop(0);
            }
        })
        .fail(function (xhr, status, errorThrown) {
            document.getElementById(target).innerHTML =
                " Couldn't save changes:\n" +
                status +
                "\n" +
                req.statusText +
                "\nError: " +
                errorThrown;
            document.getElementById("statusmsg").textContent = _("Error saving");
            itemarray = olditemarray.slice();
            refreshTable();
        });
}

function addusingdefaults(asgroup) {
    if (beentaken) { return; }
    curqlastfocus = [];
    var checked = [];
    $("#selq input[type=checkbox]:checked").each(function() {
        checked.push(this.value);
    });
    if (checked.length == 0) { return; }
    document.getElementById("statusmsg").textContent = _("Adding questions");
    $.ajax({
        type: "POST",
        url: AHAHsaveurl,
        async: false,
        data: {addnewdef: checked, asgroup: asgroup ? 1 : 0, lastitemhash: lastitemhash},
        dataType: 'json'
    }).done(function (msg) {
        if (msg.hasOwnProperty('error')) {
            document.getElementById("submitnotice").innerHTML = msg.error;
            document.getElementById("statusmsg").textContent = _("Error adding");
        } else {
            document.getElementById("statusmsg").textContent = _("Done adding");
            doneadding(msg);
        }
    }).fail(function () {
        alert("Error adding questions");
        document.getElementById("statusmsg").textContent = _("Error adding");
    });
}

function doneadding(newq,addedqs) {
    itemarray = newq.itemarray;
    lastitemhash = newq.lastitemhash;
    refreshTable();
    updateInAssessMarkers();
    $("#selq input[type=checkbox]:checked").prop("checked", false);
    $("#addbar.footerbar").addClass("sr-only");
}

function addwithsettings() {
    var checked = [];
    $("#selq input[type=checkbox]:checked").each(function() {
        checked.push(this.value);
    });
    if (checked.length == 0) { return; }
    GB_show('Question Settings',qsettingsaddr + '&toaddqs=' + encodeURIComponent(checked.join(';')) + '&lih=' + lastitemhash,900,500);
}

function modsettings() {
    var checked = [];
    $("#curqform input[type=checkbox][id^=qc]:checked").each(function() {
        if (!this.value.match(/:text:/)) {
            checked.push(this.value);
        }
    });
    if (checked.length == 0) { return; }
    GB_show('Question Settings',qsettingsaddr + '&modqs=' + encodeURIComponent(checked.join(';')) + '&lih=' + lastitemhash,900,500);
}

/*
function submitChanges() {
  url = AHAHsaveurl + '&order='+generateOutput();
  var target = "submitnotice";
  document.getElementById(target).innerHTML = ' Saving Changes... ';
  if (window.XMLHttpRequest) {
    req = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    req = new ActiveXObject("Microsoft.XMLHTTP");
  }
  if (typeof req != 'undefined') {
    req.onreadystatechange = function() {ahahDone(url, target);};
    req.open("GET", url, true);
    req.send("");
  }
}

function ahahDone(url, target) {
  if (req.readyState == 4) { // only if req is "loaded"
    if (req.status == 200) { // only if "OK"
	    if (req.responseText=='OK') {
		    document.getElementById(target).innerHTML='';
		    refreshTable();
	    } else {
		    document.getElementById(target).innerHTML=req.responseText;
		    itemarray = olditemarray;
	    }
    } else {
	    document.getElementById(target).innerHTML=" Couldn't save changes:\n"+ req.status + "\n" +req.statusText;
	    itemarray = olditemarray;
    }
  }
}
*/
