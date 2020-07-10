export function pauseVideos (el) {
  const iframes = el.getElementsByTagName('iframe');
  for (const frame of iframes) {
    if (frame.src.match(/enablejsapi/)) {
      frame.contentWindow.postMessage('{"event":"command","func":"pauseVideo"}', '*');
    } else {
      const origsrc = frame.src;
      frame.src = origsrc;
    }
  }
}
