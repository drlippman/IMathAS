function checkContrast(c1, c2, islink) {
    var L1 = getL(c1),
        L2 = getL(c2),
        ratio = (Math.max(L1, L2) + 0.05) / (Math.min(L1, L2) + 0.05);
    
    return (ratio >= (islink ? 3 : 4.5));
}

function getRGB(c) {
	try {
		var c = parseInt(c, 16);
	} catch (err) {
		var c = false;
	}
	return c;
}
function getsRGB(c) {
	c = getRGB(c) / 255;
	c = (c <= 0.03928) ? c / 12.92 : Math.pow(((c + 0.055) / 1.055), 2.4);
	return c;
}

function getL(c) {
	return (0.2126 * getsRGB(c.substr(1, 2)) + 0.7152 * getsRGB(c.substr(3, 2)) + 0.0722 * getsRGB(c.substr(-2)));
}