export function pauseVideos (el) {
  const iframes = el.getElementsByTagName('iframe');
  for (const frame of iframes) {
    if (frame.src.match(/enablejsapi/)) {
      frame.contentWindow.postMessage('{"event":"command","func":"pauseVideo"}', '*');
    } else if (frame.src) {
      const origsrc = frame.src;
      frame.src = origsrc;
    }
  }
}
