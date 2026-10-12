/*
 * Behavior for tinymce8/file_manager.php
 * Page-specific values are passed in via data attributes on <body>:
 *   data-type        "img" if only images are being chosen
 *   data-files-url   url that returns the Existing Files listing
 *   data-inserted    url of a file that was just uploaded (if any)
 * The listing fragment calls fmInitFileList() with its file data.
 */

// translation helper; the same as in general.js, which isn't loaded here
if (typeof window._ !== 'function') {
	window._ = function (txt) {
		var outtxt = (typeof i18njs != "undefined" && i18njs[txt]) ? i18njs[txt] : txt;
		for (var i = 1; i < arguments.length; i++) {
			outtxt = outtxt.replace('$' + i, arguments[i]);
		}
		return outtxt;
	};
}

var FileBrowserDialogue = {
	init: function () {
		var inserted = document.body.getAttribute('data-inserted');
		if (inserted) {
			FileBrowserDialogue.mySubmit(inserted);
		}
	},
	mySubmit: function (filename) {
		// insert information now
		window.parent.postMessage({
			mceAction: 'customAction',
			data: {
				'filename': filename
			}
		}, '*');
		// close popup window
		window.parent.postMessage({mceAction: 'close'});
	}
};

function switchDivs() {
	var fieldvalue = document.getElementById("uploaded_file").value;
	if (fieldvalue == '') {
		alert(_("No file selected"));
		return false;
	}
	var thisext = fieldvalue.substr(fieldvalue.lastIndexOf('.')).toLowerCase();
	if (document.body.getAttribute('data-type') == 'img') {
		if (['.png','.gif','.jpg','.jpeg','.svg'].indexOf(thisext) === -1) {
			alert(_("File must be an image file: .png, .gif, .jpg, .svg"));
			return false;
		}
	} else if ([".php",".php3",".php4",".php5",".bat",".com",".pl",".p",".exe"].indexOf(thisext) !== -1) {
		alert(_("This filetype is not allowed"));
		return false;
	}
	document.getElementById("upload_div").style.display = "none";
	document.getElementById("uploading_div").style.display = "block";
	document.getElementById("uploaded_file_name").value = fieldvalue;
	return true;
}

/* tabs */

var filesLoaded = false;
function selectTab(which) {
	var isFiles = (which == 'files');
	$("#tab_upload").attr({"aria-selected": String(!isFiles), tabindex: isFiles ? -1 : 0});
	$("#tab_files").attr({"aria-selected": String(isFiles), tabindex: isFiles ? 0 : -1});
	$("#panel_upload").prop("hidden", isFiles);
	$("#panel_files").prop("hidden", !isFiles);
	if (isFiles && !filesLoaded) {
		filesLoaded = true;
		$("#panel_files").text(_("Loading...")).load(document.body.getAttribute('data-files-url'), function(resp, status) {
			if (status == "error") {
				filesLoaded = false;
				$("#panel_files").text(_("Error loading files. Switch tabs to retry."));
			}
		});
	}
}

$(function() {
	FileBrowserDialogue.init();
	$("#tab_upload").on("click", function() { selectTab("upload"); });
	$("#tab_files").on("click", function() { selectTab("files"); });
	$(".tabs").on("keydown", "button", function(e) {
		if (e.key == "ArrowLeft" || e.key == "ArrowRight") {
			var t = (this.id == "tab_upload") ? "files" : "upload";
			selectTab(t);
			$("#tab_" + t).focus();
		}
	});
});

/* existing files listing */

var PASTE_PAGE = 12;
var OPEN_ICON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/></svg>';
var EYE_ICON = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';

var userfiles, others, pasted;

function cmpName(a,b) { return a.name.toLowerCase().localeCompare(b.name.toLowerCase()); }
var sorters = {
	name: cmpName,
	ext: function(a,b) { return a.ext.localeCompare(b.ext) || cmpName(a,b); },
	date: function(a,b) { return (b.time - a.time) || cmpName(a,b); }
};

function fmInitFileList(files) {
	userfiles = files;
	others = userfiles.filter(function(f) {return !f.pasted;});
	pasted = userfiles.filter(function(f) {return f.pasted;}).sort(function(a,b) {return b.time - a.time;});
	$("#sortsel").on('change', renderList);
	$("#filterbox").on('input', renderList);
	renderList();
	if (pasted.length) {
		$("#pastedcnt").text(pasted.length);
		$("#pastedbox").one('toggle', function() { initGrid($("#pastedgrid"), $("#pastedmore"), pasted); });
	}
}

function renderList() {
	var filt = $("#filterbox").val().toLowerCase();
	var $list = $("#filelist").empty();
	var thumbview = ($("#sortsel").val() == 'thumbs');
	$list.toggle(!thumbview);
	$("#filegridbox").toggle(thumbview);
	// pasted images aren't searched, so hide them while filtering
	$("#pastedbox").toggle(pasted.length > 0 && filt === '');
	var matches = others.filter(function(f) {return f.name.toLowerCase().indexOf(filt) !== -1;});
	if (thumbview) {
		initGrid($("#filegrid"), $("#filemore"), matches.filter(function(f) {return f.isimg;}).sort(sorters.date));
		return;
	}
	matches.sort(sorters[$("#sortsel").val()])
		.forEach(function(f) {
			var $row = $('<div class="frow">');
			// split off extension so the middle of long names can be ellipsized
			var dot = f.name.lastIndexOf('.');
			var base = dot > 0 ? f.name.substr(0, dot) : f.name;
			var extn = dot > 0 ? f.name.substr(dot) : '';
			$('<a href="#" class="file">').attr('title', f.name)
				.append($('<span class="fbase">').text(base), $('<span class="fext">').text(extn))
				.on('click', function(e) { e.preventDefault(); FileBrowserDialogue.mySubmit(f.url); }).appendTo($row);
			if (f.time) {
				$('<span class="fdate">').text(new Date(f.time*1000).toLocaleDateString()).appendTo($row);
			}
			if (!f.isimg) {
				$('<a class="prevbtn" target="_blank" rel="noopener" title="' + _("Open in new tab") + '" aria-label="' + _("Open in new tab") + '">')
					.attr('href', f.url).html(OPEN_ICON).appendTo($row);
			} else {
				$('<a href="#" class="prevbtn" title="' + _("Preview") + '" aria-label="' + _("Preview") + '">').html(EYE_ICON).on('click', function(e) {
					e.preventDefault();
					var $p = $row.children(".prev");
					if ($p.length) { $p.remove(); return; }
					$('<div class="prev">').append($('<img alt="">').attr('src', f.url)).appendTo($row);
				}).appendTo($row);
			}
			$('<a href="#" class="delbtn" title="' + _("Delete file") + '" aria-label="' + _("Delete file") + '">').text('\u00d7')
				.on('click', function(e) { e.preventDefault(); delete_file(f, $row); }).appendTo($row);
			$list.append($row);
		});
	if (!$list.children().length) {
		$list.text(filt ? _('No matching files') : _('No other files'));
	}
}

// thumbnail grid that shows PASTE_PAGE images at a time, with a More button
function initGrid($grid, $more, items) {
	$grid.empty().data({items: items, shown: 0});
	$more.off('click').on('click', function() { gridMore($grid, $more); });
	gridMore($grid, $more);
}

function gridMore($grid, $more) {
	var items = $grid.data('items');
	var shown = $grid.data('shown');
	items.slice(shown, shown + PASTE_PAGE).forEach(function(f) {
		var $t = $('<div class="thumb">');
		$('<a href="#" class="delbtn" title="' + _("Delete file") + '" aria-label="' + _("Delete file") + '">').text('\u00d7')
			.on('click', function(e) { e.preventDefault(); delete_file(f, $t); }).appendTo($t);
		$('<a href="#" class="thumbimg">').attr('title', f.name)
			.append($('<img loading="lazy" alt="">').attr('src', f.url))
			.on('click', function(e) { e.preventDefault(); FileBrowserDialogue.mySubmit(f.url); }).appendTo($t);
		if (f.time) {
			$('<span class="fdate">').text(new Date(f.time*1000).toLocaleDateString()).appendTo($t);
		}
		$grid.append($t);
	});
	shown = Math.min(shown + PASTE_PAGE, items.length);
	$grid.data('shown', shown);
	$more.toggle(shown < items.length);
}

function removeFromList(list, f) {
	var i = list.indexOf(f);
	if (i !== -1) { list.splice(i, 1); }
}

function delete_file(f, $el) {
	if (confirm(_("Are you sure you want to delete this file?  If you delete it, it will no longer be accessible or viewable from anywhere you've used it."))) {
		$el.addClass("deleting");
		$.ajax({
			url: "file_manager.php",
			data: { action: "delete_file", item_name: f.name }
		}).done(function(msg) {
			if (msg == 'OK') {
				var $g = $el.closest('.thumbgrid');
				if ($g.length) {
					removeFromList($g.data('items'), f);
					$g.data('shown', $g.data('shown') - 1);
				}
				removeFromList(userfiles, f);
				removeFromList(f.pasted ? pasted : others, f);
				if (f.pasted) {
					$("#pastedcnt").text(pasted.length);
				}
				$el.remove();
			} else {
				alert(_("Error deleting file"));
				$el.removeClass("deleting");
			}
		});
	}
}
