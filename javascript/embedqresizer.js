window.addEventListener("message", function(e) {
    if (typeof e.data === 'string' && e.data.match(/lti\.frameResize/)) {
        var edata = JSON.parse(e.data);
        if ("frame_id" in edata) {
            var frame = document.getElementById(edata["frame_id"]);
            if (frame) {
                frame.style.height = edata.height + "px";
            }
            
            var frameWrap = document.getElementById(edata["frame_id"] + "wrap");
            if (frameWrap && ("wrapheight" in edata)) {
                frameWrap.style.height = edata.wrapheight + "px";
            }
        } else if ("iframe_resize_id" in edata) {
            var frame = document.getElementById(edata["iframe_resize_id"]);
            if (frame) {
                frame.style.height = edata.height + "px";
            }
        } else {
            var frames = document.getElementsByTagName('iframe');
            for (var i = 0; i < frames.length; i++) {
                if (frames[i].contentWindow === e.source &&
                        !frames[i].hasAttribute('data-noresize')
                ) {
                    frames[i].style.height = edata.height + "px"; 
                    break;
                }
            }
        }
    }
});