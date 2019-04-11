function Viewer3D(paramObj, targetel) {
	var t = this;
	t.swidth = parseFloat(paramObj['width']);
	t.sheight = parseFloat(paramObj['height']);
	t.dfi = .01;
	t.theta = .5;
	t.phi = 1.2;
	t.centr = [];
	t.vert1 = [];
	t.Norm = [];
	t.Norm1z = [];
	t.Vsort = [];
	t.hue = [];
	t.axes = [];
	t.axes1 = [];
	t.ticks = [];
	t.numticks = [];
	t.axisscl = [];
	t.ticksize = [];
	t.axeslabels = [];
	t.Vsortface = [];
	t.bndbox = [];
	t.bndbox1 = [];
	t.axlbl = [];
	t.iscurve = false;

	t.showaxes = true;
	t.showedges = true;
	t._mouseisdown = false;
	t._inrender = false;
	t._paper = document.getElementById(targetel);
	t._context = t._paper.getContext("2d");
	
	t._context.fillStyle = "#FFFFFF";
	t._context.fillRect(0, 0, t.swidth, t.sheight);

	$(t._paper).on('mousedown', function(e) {t.setMouseDown(e)});
	$(t._paper).on('touchstart', function(e) {t.setTouchDown(e)});
	$(t._paper).on('mouseup touchend', function(e) {t.setMouseUp(e)});
	$(t._paper).on('mousemove touchmove', function(e) {t.doMouseMove(e)});
	$(t._paper).on('keydown', function(e) {t.doKeydown(e)});
	
	t._context.font = "10px sans-serif";
	t._context.textAlign = "center";
	t._context.textBaseline = "middle";
	
	//Parse the parameters
		
	t.vert = paramObj['verts'].split('~');
	if (paramObj['faces'] != undefined) {
		t.face = paramObj['faces'].split('~');
		for (var i=0; i<t.face.length; i++) {
			t.face[i] = t.splitfloat(t.face[i]);
		}
	} else if (paramObj['curves'] != undefined) {
		t.iscurve = true;
	}
	for (i=0; i<t.vert.length; i++) {
		t.vert[i] = t.splitfloat(t.vert[i]);
		t.vert1[i] = [];
	}
	if (paramObj['bounds'] != null) {
		var bndvals = t.splitfloat(paramObj['bounds']);
		t.xmin = bndvals[0];	t.xmax = bndvals[1];
		t.ymin = bndvals[2];	t.ymax = bndvals[3];
		t.zmin = bndvals[4];	t.zmax = bndvals[5];
	} else {
		t.xmin = t.vert[0][0]; t.xmax = t.vert[0][0];
		t.ymin = t.vert[0][1]; t.ymax = t.vert[0][1];
		t.zmin = t.vert[0][2]; t.zmax = t.vert[0][2];
		for (i=0; i<t.vert.length; i++) {
			if (t.vert[i][0]<t.xmin) {t.xmin=t.vert[i][0];}
			if (t.vert[i][0]>t.xmax) {t.xmax=t.vert[i][0];}
			if (t.vert[i][1]<t.ymin) {t.ymin=t.vert[i][1];}
			if (t.vert[i][1]>t.ymax) {t.ymax=t.vert[i][1];}
			if (t.vert[i][2]<t.zmin) {t.zmin=t.vert[i][2];}
			if (t.vert[i][2]>t.zmax) {t.zmax=t.vert[i][2];}
		}
	}
	if (t.xmin==t.xmax) {
		t.xmin -= 1;
		t.xmax += 1;
	}
	if (t.ymin==t.ymax) {
		t.ymin -= 1;
		t.ymax += 1;
	}
	if (t.zmin==t.zmax) {
		t.zmin -= 1;
		t.zmax += 1;
	}
	
	//initialize
	var daxis = [];
	
	t.centr[0] = (t.xmax+t.xmin)/2;
	t.centr[1] = (t.ymax+t.ymin)/2;
	t.centr[2] = (t.zmax+t.zmin)/2;
	
	t.w2 = t.swidth/2;
	t.h2 = t.sheight/2;

	t.scale = 1.0*Math.min(t.w2,t.h2) / (1.8*Math.min(t.xmax-t.centr[0],t.ymax-t.centr[1],t.zmax-t.centr[2]));
	
	//set up bounding box.  Corners:
	// 0:(t.xmin,t.ymin,t.zmin), 1:(t.xmax,t.ymin,t.zmin), 2:(t.xmin,t.ymax,t.zmin), 3:(t.xmax,t.ymax,t.zmin)
	// 4:(t.xmin,t.ymin,t.zmax), 5:(t.xmax,t.ymin,t.zmax), 6:(t.xmin,t.ymax,t.zmax), 7:(t.xmax,t.ymax,t.zmax)
	for (var i=0; i < 8; i++) {
		t.bndbox[i] = [];
		t.bndbox1[i] = [];
		if ((i & 1)==0) {t.bndbox[i][0]=t.xmin;} else {t.bndbox[i][0]=t.xmax;}
		if ((i & 2)==0) {t.bndbox[i][1]=t.ymin;} else {t.bndbox[i][1]=t.ymax;}
		if ((i & 4)==0) {t.bndbox[i][2]=t.zmin;} else {t.bndbox[i][2]=t.zmax;}
	}
	daxis[0] = (t.xmax-t.xmin);
	daxis[1] = (t.ymax-t.ymin);
	daxis[2] = (t.zmax-t.zmin);
	  
	t.axlbl[0] = "x";
	t.axlbl[1] = "y";
	t.axlbl[2] = "z";
	
	
	var basescale = Math.min(daxis[0],daxis[1],daxis[2]);
	for (i=0; i<3;i++) {
		t.axisscl[i] = basescale/daxis[i];
	}
	t.cam = 10*basescale;
	  
	  
	  var start = [];
	  var pow10;
	  var scl;
	  var ticks = [];
	  var powscl = [];
	  for (i=0;i<3;i++) {
		pow10 = Math.floor(Math.log(daxis[i])/Math.LN10);
		scl = daxis[i]/Math.pow(10,pow10);
		if (pow10<1) {
			powscl[i] = Math.pow(10,-pow10 + 1);
		} else {
			powscl[i] = 1;
		}
		if (scl==1) {
			ticks[i] = .2;
		} else if (scl<2) {
			ticks[i] = .2;
		} else if (scl<5) {
			ticks[i] = .5;
		} else {
			ticks[i] = 1;
		}
		ticks[i] *= Math.pow(10,pow10);
		if (pow10>0) {
			ticks[i] = Math.round(ticks[i]);
		}
		if (t.bndbox[0][i]<0) {
			start[i] = Math.ceil(t.bndbox[0][i]/ticks[i]);
		} else {
			start[i] = Math.floor(t.bndbox[0][i]/ticks[i]);
		}
		t.numticks[i] = Math.floor(daxis[i]/ticks[i]);
		if (ticks[i]*(start[i]+t.numticks[i]) < t.bndbox[7][i]) {
			t.numticks[i]++;
		}
		t.ticksize[i] = ticks[i]/10;
	  }
	  
	  for (i=0;i<3;i++) {
		t.axes[i] = [];
		t.axes1[i] = [];
		t.axeslabels[i] = [];
		for (var j=0;j<t.numticks[i];j++) {
			t.axes[i][j] = [];
			t.axes1[i][j] = [];
			for (var k=0; k<4;k++) {
				t.axes[i][j][k] = [];
				t.axes1[i][j][k] = [];
				if (powscl[i]==1) {
					t.axes[i][j][k][i] = ticks[i]*(j+start[i]);
				} else {
					t.axes[i][j][k][i] = Math.round(powscl[i]*ticks[i]*(j+start[i]))/powscl[i];
				}
			}

			t.axeslabels[i][j] = t.axes[i][j][0][i];
		}
	  }
	
	  if (t.iscurve) {
	  	  for (i=0; i<t.vert.length; i++) {
	  	  	  t.hue[i] = t.vert[i][2] - t.zmin;
	  	  	  t.hue[i] /= (t.zmax-t.zmin);
	  	  }
	  } else {
	  	  var mod;
		  for (i=0; i< t.face.length; i++) {
			t.Norm[i] = [];
			t.Norm1z[i] = [];
			t.Norm[i][0] = (t.vert[t.face[i][1]][1] - t.vert[t.face[i][0]][1])*
				(t.vert[t.face[i][2]][2] - t.vert[t.face[i][1]][2]) -
				(t.vert[t.face[i][2]][1] - t.vert[t.face[i][1]][1])*
				(t.vert[t.face[i][1]][2] - t.vert[t.face[i][0]][2]);
			t.Norm[i][1] = -(t.vert[t.face[i][1]][0] - t.vert[t.face[i][0]][0])*
				(t.vert[t.face[i][2]][2] - t.vert[t.face[i][1]][2]) +
				(t.vert[t.face[i][2]][0] - t.vert[t.face[i][1]][0])*
				(t.vert[t.face[i][1]][2] - t.vert[t.face[i][0]][2]);
			t.Norm[i][2] = (t.vert[t.face[i][1]][0] - t.vert[t.face[i][0]][0])*
				(t.vert[t.face[i][2]][1] - t.vert[t.face[i][1]][1]) -
				(t.vert[t.face[i][2]][0] - t.vert[t.face[i][1]][0])*
				(t.vert[t.face[i][1]][1] - t.vert[t.face[i][0]][1]);
			mod = Math.sqrt(t.Norm[i][0]*t.Norm[i][0] + t.Norm[i][1]*t.Norm[i][1] +
				t.Norm[i][2]*t.Norm[i][2]);// / 255.5;
			t.Norm[i][0] /= mod;    t.Norm[i][1] /= mod;    t.Norm[i][2] /= mod;
			t.Norm[i][0] *= t.axisscl[0];    t.Norm[i][1] *= t.axisscl[1];    t.Norm[i][2] *= t.axisscl[2];
			t.hue[i] = (1*t.vert[t.face[i][0]][2] + 1*t.vert[t.face[i][1]][2] + 1*t.vert[t.face[i][2]][2] )/3 - t.zmin;
			t.hue[i] /= (t.zmax-t.zmin);
		  }
	  }
		  
	  t.rotate();
	  t.paint();
} //end init

Viewer3D.prototype.splitfloat = function(s) {
	var a = s.split(',');
	for (var i=0;i<a.length;i++) {
		a[i] = parseFloat(a[i]);
	}
	return a;
}

Viewer3D.prototype.setMouseDown = function(e) {
	var t = this;
	t._eventtype = "mouse";
	$(t._paper).css("cursor","move");
	t._mouseisdown = true;
	var offset = $(t._paper).offset();
	t.mx0 = e.pageX - offset.left;
	t.my0 = e.pageY - offset.top;
}
Viewer3D.prototype.setTouchDown = function(e) {;
	var t = this;
	t._eventtype = "touch";
	t._mouseisdown = true;
	var offset = $(t._paper).offset();
	var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
	t.mx0 = touch.pageX - offset.left;
	t.my0 = touch.pageY - offset.top;
}

Viewer3D.prototype.setMouseUp = function(e) {
	$(this._paper).css("cursor","");
	this._mouseisdown = false;
}
Viewer3D.prototype.doMouseMove = function(e) {
	var t = this;
	if (t._mouseisdown && !t._inrender) {
		var offset = $(t._paper).offset();
		if (t._eventtype == "mouse") {
			var mouseX = e.pageX - offset.left;
			var mouseY = e.pageY - offset.top;
		} else {
			var touch = e.originalEvent.changedTouches[0] || e.originalEvent.touches[0];
			var mouseX = touch.pageX - offset.left;
			var mouseY = touch.pageY - offset.top;
		}
		t.phi += -t.dfi*(mouseY - t.my0);
		t.theta += -t.dfi*(mouseX-t.mx0);
		t.mx0 = mouseX;
		t.my0 = mouseY;
		t._inrender = true;
		t.rotate();
		t.paint();
		t._inrender = false;
		e.preventDefault();
	}
}
Viewer3D.prototype.doKeydown = function(e) {
	var t = this;
	switch (e.keyCode) {
		case 37:
			t.theta += 5*t.dfi;
			break;
		case 39:
			t.theta -= 5*t.dfi;
			break;
		case 38:
			t.phi += 5*t.dfi;
			break;
		case 40:
			t.phi -= 5*t.dfi;
			break;
		default:
			return;
	}
	t._inrender = true;
	t.rotate();
	t.paint();
	t._inrender = false;
	e.preventDefault();
}
Viewer3D.prototype.rotate = function() {
	var t = this;
	var ct = Math.cos(t.theta);
	var st = Math.sin(t.theta);
	var cf = Math.cos(t.phi);
	var sf = Math.sin(t.phi);
	var m00 = t.scale*ct*sf;
	var m01 = t.scale*st*sf;
	var m02 = t.scale*cf;
	var m10 = -1*t.scale*st;
	var m11 = t.scale*ct;
	var m12 = 0;
	var m20 = -1*t.scale*ct*cf;
	var m21 = -1*t.scale*st*cf;
	var m22 = t.scale*sf;
	var x; 
	var y;
	var z;
	
	//rotate and project the geometry
	for (var i=0; i<t.vert.length; i++) {
		x = t.vert[i][0] - t.centr[0];
		y = t.vert[i][1]-t.centr[1];
		z = t.vert[i][2]-t.centr[2];
		x = x*t.axisscl[0];
		y = y*t.axisscl[1];
		z = z*t.axisscl[2];
	            
		t.vert1[i][2] = (m00*x + m01*y + m02*z)/t.scale;
		t.vert1[i][0] = (m10*x + m11*y + m12*z)*(1/(1-t.vert1[i][2]/t.cam));
		t.vert1[i][1] = (m20*x + m21*y + m22*z)*(1/(1-t.vert1[i][2]/t.cam));
	}

	//rotate bounding box
	var maxbbyv = -1*t.h2;
	var maxbbxv = -1*t.w2;
	var maxbby = 0; var maxbbx = 0;
	for (i=0; i<8; i++) {
		x = t.bndbox[i][0]-t.centr[0];
	        y = t.bndbox[i][1]-t.centr[1];
		z = t.bndbox[i][2]-t.centr[2];
		x = x*t.axisscl[0];
		y = y*t.axisscl[1];
		z = z*t.axisscl[2];
		
		t.bndbox1[i][2] = (m00*x + m01*y + m02*z)/t.scale;
		t.bndbox1[i][0] = (m10*x + m11*y + m12*z)*(1/(1-t.bndbox1[i][2]/t.cam));
		t.bndbox1[i][1] = (m20*x + m21*y + m22*z)*(1/(1-t.bndbox1[i][2]/t.cam));
		if (t.bndbox1[i][0]>maxbbxv) { maxbbx = i; maxbbxv = t.bndbox1[i][0];}
		if (t.bndbox1[i][1]>maxbbyv) { maxbby = i; maxbbyv = t.bndbox1[i][1];}
	}
	//adjust side of bounding box t.axes are on
	if ((t.phi>0 && t.phi%3.1415>1.55)||(t.phi<0 && t.phi%3.1415>-1.55)) {
	        maxbby = maxbby^7;
	}
			
	for (i=0; i<3;i++) {
	    for (var j=0; j<t.numticks[i]; j++) {
		for (var k =0; k<3; k++) {
		    if (k!=i) { //
			if (i==2) {
			    t.axes[i][j][0][k] = t.bndbox[maxbbx][k];
			    t.axes[i][j][1][k] = t.bndbox[maxbbx][k]+((t.bndbox[maxbbx][k]>t.centr[k])?-t.ticksize[k]:t.ticksize[k]);
			    t.axes[i][j][2][k] = t.bndbox[maxbbx][k]+2*((t.bndbox[maxbbx][k]>t.centr[k])?t.ticksize[k]:-t.ticksize[k]);
			    t.axes[i][j][3][k] = t.bndbox[maxbbx][k]+9*((t.bndbox[maxbbx][k]>t.centr[k])?t.ticksize[k]:-t.ticksize[k]);
			} else {
			    t.axes[i][j][0][k] = t.bndbox[maxbby][k];
			    t.axes[i][j][1][k] = t.bndbox[maxbby][k]+((t.bndbox[maxbby][k]>t.centr[k])?-t.ticksize[k]:t.ticksize[k]);
			    t.axes[i][j][2][k] = t.bndbox[maxbby][k]+3*((t.bndbox[maxbby][k]>t.centr[k])?t.ticksize[k]:-t.ticksize[k]);
			    t.axes[i][j][3][k] = t.bndbox[maxbby][k]+9*((t.bndbox[maxbby][k]>t.centr[k])?t.ticksize[k]:-t.ticksize[k]);
			}
		    }
		}
	    }
	}
	//rotate axes
	for (i=0; i<3;i++) {
	    for (j=0; j<t.numticks[i]; j++) {
		for (k=0; k<4; k++) {
		    x = t.axes[i][j][k][0]-t.centr[0];
		    y = t.axes[i][j][k][1]-t.centr[1];
		    z = t.axes[i][j][k][2]-t.centr[2];
		    x = x*t.axisscl[0];
		    y = y*t.axisscl[1];
		    z = z*t.axisscl[2];
		    t.axes1[i][j][k][2] = (m00*x + m01*y + m02*z)/t.scale;
		    t.axes1[i][j][k][0] = (m10*x + m11*y + m12*z)*(1/(1-t.axes1[i][j][k][2]/t.cam));
		    t.axes1[i][j][k][1] = (m20*x + m21*y + m22*z)*(1/(1-t.axes1[i][j][k][2]/t.cam));
		}
	    }
	}
	var centerv;
	if (!t.iscurve) {
		for (i = 0; i < t.face.length; i++) {
			t.Norm1z[i] = ((5*m00+m10-m20)*t.Norm[i][0] + (5*m01+m11-m21)*t.Norm[i][1] + (5*m02+m12-m22)*t.Norm[i][2])/(t.scale*Math.sqrt(27));
			//do t.Vsort -- crappy insertion sort
			centerv = (t.vert1[t.face[i][0]][2] + t.vert1[t.face[i][1]][2] + t.vert1[t.face[i][2]][2] + t.vert1[t.face[i][3]][2])/4;
			if (i>0) {
			    for (j=i-1; j>=0;j--) {
				if (centerv>t.Vsort[j]) {
				    t.Vsort[j+1] = centerv;
				    t.Vsortface[j+1] = i;
				    break;
				} else {
				    t.Vsort[j+1] = t.Vsort[j];
				    t.Vsortface[j+1] = t.Vsortface[j];
				    if (j==0) {t.Vsort[0] = centerv; t.Vsortface[0]=i;}
				}
			    }
			} else {
			    t.Vsort[0] = centerv;
			    t.Vsortface[0] = i;
			}
		}
	}	
} //end rotate()
		
Viewer3D.prototype.paint = function() {
	var t = this;
	
	t._context.fillStyle = "#FFFFFF";
	t._context.fillRect(0, 0, t.swidth, t.sheight);
            
	var bbmin = 0;
	for (var i=0; i<8;i++) {
		if (t.bndbox1[i][2]<t.bndbox1[bbmin][2]) { bbmin = i;}
	}	
        t._context.strokeStyle = "#0000FF";
        t._context.lineWidth = 1;

        t._context.beginPath();

	for (i=0;i<7; i++) {
		for (var j=i+1; j<8; j++) {
			if ((i^j)==1 || (i^j)==2 || (i^j)==4) {
				t._context.moveTo(t.w2+t.bndbox1[i][0],t.h2-t.bndbox1[i][1]);
				t._context.lineTo(t.w2+t.bndbox1[j][0],t.h2-t.bndbox1[j][1]);
			}
		}
	}
	t._context.stroke();
	
        t._context.fillStyle = "#000000";
        for (i=0; i<3; i++) {
        	t._context.fillText(t.axlbl[i], 
        		t.w2+t.axes1[i][Math.floor(t.numticks[i]/2)][3][0],
            		t.h2-t.axes1[i][Math.floor(t.numticks[i]/2)][3][1]);
            	//need to add axis labels
            	if (t.showaxes) {
            		for (j=0; j<t.numticks[i]; j++) {
            			t._context.beginPath();
            			t._context.moveTo(t.w2+t.axes1[i][j][0][0],t.h2-t.axes1[i][j][0][1]);
            			t._context.lineTo(t.w2+t.axes1[i][j][1][0],t.h2-t.axes1[i][j][1][1]);
            			t._context.stroke();
            			if (i==2) {
            				t._context.textAlign = "left";
            			} 
            			t._context.fillText(t.axeslabels[i][j],
            				t.w2+t.axes1[i][j][2][0],
            				t.h2-t.axes1[i][j][2][1]);
            		}
            	} 
         }
         t._context.textAlign = "center";
         
         var sf;
         var bright;
         var rgb;
         if (t.iscurve) {
         	 for (i=1; i<t.vert.length; i++) {
         	 	rgb = t.hsbtorgb(t.hue[i],1.0,0.7);
         	 	t._context.strokeStyle = 'rgb('+rgb.join(',')+')';
         	 	t._context.beginPath();
         	 	t._context.moveTo(t.w2 + t.vert1[i-1][0],t.h2 - t.vert1[i-1][1]);
         	 	t._context.lineTo(t.w2 + t.vert1[i][0],t.h2 - t.vert1[i][1]);
         	 	t._context.stroke();
         	 }
         } else {
		 t._context.strokeStyle = "#000000";
		 for (i=0; i<t.face.length; i++) {
			sf = t.Vsortface[i];
			bright = .3*Math.abs(t.Norm1z[sf])+.7;
			//t._context.beginFill(255*t.hue[i],1);
			t._context.beginPath();
			t._context.moveTo(t.w2 + t.vert1[t.face[sf][0]][0],t.h2 - t.vert1[t.face[sf][0]][1]);
			rgb = t.hsbtorgb(t.hue[sf],1.0,bright);
			t._context.fillStyle = 'rgb('+rgb.join(',')+')';
			for (j=1; j<t.face[sf].length; j++) {
				t._context.lineTo(t.w2 + t.vert1[t.face[sf][j]][0],t.h2 - t.vert1[t.face[sf][j]][1]);
			}
			t._context.closePath();
			t._context.fill();
			t._context.stroke();
		    }
	}
            
	//redraw front of t.bndbox
	t._context.strokeStyle = "#0000FF";
	bbmin = bbmin^7;
	t._context.beginPath();
	for (i=0; i<3; i++) {
		t._context.moveTo(t.w2+t.bndbox1[bbmin][0],t.h2-t.bndbox1[bbmin][1]);
		t._context.lineTo(t.w2+t.bndbox1[bbmin^(1<<i)][0],t.h2-t.bndbox1[bbmin^(1<<i)][1]);
	}
	t._context.stroke();
            
} //end paint()
		
//hsbtorgb from http://www.flashguru.co.uk/downloads/ColorConversion.as
Viewer3D.prototype.hsbtorgb = function(hue,saturation,brightness) {
	var red, green, blue;
	hue *= 360;
	if(brightness==0)
	{
		return [0,0,0];
	}
	hue/=60;
	var i = Math.floor(hue);
	var f = hue-i;
	var p = brightness*(1-saturation);
	var q = brightness*(1-(saturation*f));
	var t = brightness*(1-(saturation*(1-f)));
	switch(i)
	{
		case 0:
			red=brightness; green=t; blue=p;
			break;
		case 1:
			red=q; green=brightness; blue=p;
			break;
		case 2:
			red=p; green=brightness; blue=t;
			break;
		case 3:
			red=p; green=q; blue=brightness;
			break;
		case 4:
			red=t; green=p; blue=brightness;
			break;
		case 5:
			red=brightness; green=p; blue=q;
			break;
	}
	red=Math.round(red*255)
	green=Math.round(green*255)
	blue=Math.round(blue*255)
	return [red,green,blue];
}

