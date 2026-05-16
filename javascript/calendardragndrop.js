var dragState = null; // Tracks current drag operation
var ghostEl = null;   // Visual drag ghost element
function createGhost(sourceEl, x, y) {
    var ghost = sourceEl.cloneNode(true);
    var rect = sourceEl.getBoundingClientRect();
    ghost.style.cssText = 
        'position:fixed;' +
        'left:' + rect.left + 'px;' +
        'top:' + rect.top + 'px;' +
        'width:' + rect.width + 'px;' +
        'opacity:0.7;' +
        'pointer-events:none;' +
        'z-index:9999;' +
        'margin:0;';
    ghost.classList.add('dragging');
    document.body.appendChild(ghost);
    return ghost;
}

function getTdUnderPointer(x, y) {
    // Temporarily hide ghost so elementFromPoint can see through it
    if (ghostEl) ghostEl.style.display = 'none';
    var el = document.elementFromPoint(x, y);
    if (ghostEl) ghostEl.style.display = '';
    return el ? $(el).closest("table.cal td") : $();
}
function initcaldragreorder() {
    // Set titles
    $("span.calitem[id^=CD]").attr("title", _("Calendar Event Date"));
    $("span.calitem[id^=AS]").attr("title", _("Assessment Available After"));
    $("span.calitem[id^=AE]").attr("title", _("Assessment Due Date"));
    $("span.calitem[id^=AR]").attr("title", _("Assessment Review Date"));
    $("span.calitem[id^=IS]").attr("title", _("Inline Text Available After"));
    $("span.calitem[id^=IE]").attr("title", _("Inline Text Available Until"));
    $("span.calitem[id^=IO]").attr("title", _("Inline Text On Calendar Date"));
    $("span.calitem[id^=LS]").attr("title", _("Link Available After"));
    $("span.calitem[id^=LE]").attr("title", _("Link Available Until"));
    $("span.calitem[id^=LO]").attr("title", _("Link On Calendar Date"));
    $("span.calitem[id^=DS]").attr("title", _("Drill Available After"));
    $("span.calitem[id^=DE]").attr("title", _("Drill Available Until"));
    $("span.calitem[id^=FS]").attr("title", _("Forum Available After"));
    $("span.calitem[id^=FE]").attr("title", _("Forum Available Until"));
    $("span.calitem[id^=FP]").attr("title", _("Forum Post By"));
    $("span.calitem[id^=FR]").attr("title", _("Forum Reply By"));

    // Make calitems draggable with Pointer Events
    $("span.calitem").each(function() {
        this.style.touchAction = 'none'; // Prevent scroll hijacking
        this.style.userSelect = 'none';
        this.style.cursor = 'grab';

        this.addEventListener('pointerdown', function(e) {
            e.preventDefault();
            this.setPointerCapture(e.pointerId);

            var originalParent = $(this).closest("td").attr("id");
            $(this).data("originalParent", originalParent);

            dragState = {
                el: this,
                id: this.id,
                originalParent: originalParent,
                currentTd: null,
                offsetX: e.clientX - this.getBoundingClientRect().left,
                offsetY: e.clientY - this.getBoundingClientRect().top,
            };

            ghostEl = createGhost(this, e.clientX, e.clientY);
        });

        this.addEventListener('pointermove', function(e) {
            if (!dragState) return;
            e.preventDefault();

            // Move the ghost
            ghostEl.style.left = (e.clientX - dragState.offsetX) + 'px';
            ghostEl.style.top  = (e.clientY - dragState.offsetY) + 'px';

            // Highlight the td under the pointer
            var $td = getTdUnderPointer(e.clientX, e.clientY);
            if (dragState.currentTd && (!$td.length || $td[0] !== dragState.currentTd[0])) {
                dragState.currentTd.removeClass('drag-over');
            }
            if ($td.length) {
                $td.addClass('drag-over');
                dragState.currentTd = $td;
            } else {
                dragState.currentTd = null;
            }
        });

        this.addEventListener('pointerup', function(e) {
            if (!dragState) return;

            // Clean up ghost and highlighting
            if (ghostEl) { ghostEl.remove(); ghostEl = null; }
            if (dragState.currentTd) dragState.currentTd.removeClass('drag-over');

            var $td = getTdUnderPointer(e.clientX, e.clientY);
            if ($td.length) {
                handleDrop($td, dragState.el, dragState.id, dragState.originalParent);
            }

            dragState = null;
        });

        this.addEventListener('pointercancel', function(e) {
            if (!dragState) return;
            if (ghostEl) { ghostEl.remove(); ghostEl = null; }
            if (dragState.currentTd) dragState.currentTd.removeClass('drag-over');
            dragState = null;
        });
    });
    // add item highlight of item and bookend pair on hover
    $("span.calitem").on('pointerenter', function(ev) {
        var id = this.id.substr(2);
        $("span.calitem[id$='"+id+"']").addClass("calitemhighlight");
    }).on('pointerleave', function(ev) {
        var id = this.id.substr(2);
        $("span.calitem[id$='"+id+"']").removeClass("calitemhighlight");
    });
}
function handleDrop(droppedOn, droppedEl, draggedId, originalParent) {
    var dropped = $(droppedEl);

    // Move the element
    dropped.detach().appendTo(droppedOn.find("div.center"));

    // Unhighlight it
    var id = draggedId.substr(2);
    $("span.calitem[id$='"+id+"']").removeClass("calitemhighlight");

    // Check if actually moved to a different cell
    if (droppedOn.attr("id") != originalParent) {
        $(".calupdatenotice").html('<img src="'+staticroot+'/img/updating.gif" alt="Saving"/> ' + _("Saving..."));

        $.ajax({
            "url": "savecalendardrag.php",
            data: {
                cid: cid,
                item: draggedId,
                dest: droppedOn.attr("id")
            }
        }).done(function(msg) {
            if (msg.res == "error") {
                console.log("ERROR: " + msg.error);
                $(".calupdatenotice").html(_("Error saving change"));
                dropped.detach().appendTo($("#" + originalParent).find("div.center"));
            } else {
                $(".calupdatenotice").html("");
                var daycaldata = caleventsarr[originalParent].data;
                for (var i = 0; i < daycaldata.length; i++) {
                    if (daycaldata[i].type + daycaldata[i].typeref == draggedId) {
                        var thisrec = daycaldata.splice(i, 1);
                        if (caleventsarr[droppedOn.attr("id")].hasOwnProperty("data")) {
                            caleventsarr[droppedOn.attr("id")].data.push(thisrec[0]);
                        } else {
                            caleventsarr[droppedOn.attr("id")].data = thisrec;
                        }
                        if ($("table.cal td.today").length > 0) {
                            showcalcontents($("table.cal td.today")[0]);
                        }
                        break;
                    }
                }
            }
        }).fail(function() {
            dropped.detach().appendTo($("#" + originalParent).find("div.center"));
        });
    }
}

$(function() {
    initcaldragreorder();
});