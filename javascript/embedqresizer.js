window.addEventListener("message", function(e) {
    if (typeof e.data === 'string' && e.data.match(/lti\.frameResize/)) {
        var edata = JSON.parse(e.data);
        var found = false;
        if ("frame_id" in edata) {
            var frame = document.getElementById(edata["frame_id"]);
            if (frame) {
                found = true;
                setEmbedqHeight(frame, edata);
            }
        }
        if (!found && "iframe_resize_id" in edata) {
            var frame = document.getElementById(edata["iframe_resize_id"]);
            if (frame) {
                found = true;
                setEmbedqHeight(frame, edata);
            }
        } 
        if (!found) {
            var frames = document.getElementsByTagName('iframe');
            for (var i = 0; i < frames.length; i++) {
                if (frames[i].contentWindow === e.source &&
                        !frames[i].hasAttribute('data-noresize')
                ) {
                    setEmbedqHeight(frames[i], edata, i, frames.length);
                    break;
                }
            }
        }
    }
});

function setEmbedqHeight(frame, edata, loc, cnt) {
    if (cnt === undefined) {
        var frames = document.getElementsByTagName('iframe');
        cnt = frames.length;
        for (var i = 0; i < frames.length; i++) {
            if (frames[i] === frame) {
                loc = i;
                break;
            }
        }
    }
    var parent = frame.parentNode;
    if (frame.style.position === 'absolute' && parent.style.overflow === 'visible') {
        parent.style.height = edata.wrapheight + "px";
    } else {
        var wrapdiv = document.createElement('div');
        wrapdiv.style.overflow = 'visible';
        wrapdiv.style.position = 'relative';
        wrapdiv.style.height = edata.wrapheight + "px";
        parent.insertBefore(wrapdiv, frame);
        wrapdiv.appendChild(frame);
        frame.style.position = 'absolute';
        frame.style.zIndex = cnt - loc;
    }
    frame.style.height = edata.height + "px";
}