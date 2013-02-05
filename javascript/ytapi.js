//Largely adapted from thumnails.js, part of Class2go, Apache licensed

// Fetch YouTube Player API as script node


//Global settings for video player height and width
var vidPlayerHeight = 430;
var videoHeight = vidPlayerHeight - 30;
var vidPlayerWidth = 710;
var videoWidth = vidPlayerWidth; 

function onYouTubePlayerAPIReady() {
	//called automatically by youtube API when the API is loaded
	//console.log(document.readyState);
	thumbSet.getVidID();
	
} 

function onPlayerReady(event) {
	//called when youtube video is loaded
	
}

function onPlayerError(event) {
	alert('error');
}

function onPlayerStateChange(event) {
	//called on user seek, pause/play, etc
	thumbSet.recordMe=event;
	if (event.data == YT.PlayerState.PLAYING) {
		setTimeout(thumbSet.checkTime, 200);
	} else if (event.data == YT.PlayerState.ENDED) {
		var curTime = Math.floor(ytplayer.getCurrentTime());
		for (var i=curTime;i<curTime+5;i++) {
			if (questions.hasOwnProperty(i)) {
				thumbSet.showQuestion(i);
			}
		}
	}
}

//VidID is string containing YouTube video ID
//breaktimesarray is an object of objects:  
//  {curTime:{qn:qn}} 
var ytplayer;
var skipSecQ = -1;
var initVideoObject = function (VidId, breaktimesarray) {
	
	var thumbSet = {

		// Set up global vars
		questions: {},
		vidName: null,
		globalQTime: -1,
		recordMe: null,
		//skipSecQ: -1,
		lastTime: -1,
		curQ: -1,
	
		getVidID: function() {
		    vidName = VidId;
		    questions = breaktimesarray;
		    
		    setTimeout(function () { thumbSet.createPlayer(); }, 200);
		    // add stuff here that happens after video is loaded
		},
		
		// add player to the page
		createPlayer: function () {
			
		    var pVarsInternal = {'autoplay': 0, 'wmode': 'transparent', 'fs': 0, 'controls':2, 'rel':0, 'modestbranding':1, 'showinfo':0}; 
		  
		    //console.log(pVarsInternal);
	
		    ytplayer = new YT.Player('player', {
			height: vidPlayerHeight,
			width: vidPlayerWidth,
			videoId: vidName,
			playerVars: pVarsInternal,
			events: {
			    'onReady': onPlayerReady,
			    'onStateChange': onPlayerStateChange,
			    'onError': onPlayerError,
			}
		    });
		    
		    //document.getElementById('playerwrapper').style['z-index']=-10;
		   // document.getElementById('playerwrapper').style['-webkit-transform']='translateZ(0)';
		},
	
		stripPx: function (sizeWithPx) {
		    return parseInt(sizeWithPx.substr(0,sizeWithPx.search('px')));
		},
	
		setupQPane: function (qTime) {
			thumbSet.curQ = questions[qTime];
			//document.getElementById("player").style.visibility = "hidden";
			document.getElementById('playerwrapper').style.left = "-5000px";
			document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.visibility = "visible";
			document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.left = "0px";
		},
		
		closeQPane: function (skipahead) {
		    //hide questions
		    if (thumbSet.curQ != -1) {
			    document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.visibility = "hidden";
			    document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.left = "-5000px";
			    document.getElementById('playerwrapper').style.left = "0px";
			    
			    //are we skipping a section of video?
			    if (skipahead && thumbSet.curQ.hasOwnProperty("showAfter")) {
				    skipSecQ = thumbSet.curQ.showAfter;
				    ytplayer.seekTo(thumbSet.curQ.showAfter-0.5, true);
			    }
			    thumbSet.curQ = -1;
		    }
		    //resume playing video
		    ytplayer.playVideo();
		},
	
		timeDisplay: function(timeInSec) {
		    var min = Math.floor(timeInSec/60);
		    var sec = timeInSec - 60*min;
		    if (sec<10) sec = '0'+sec;
		    return ("" + min + ":" + sec);
		},
	
		// called on setTimeout, this watches the time and launches 
		// the questions when called for
		checkTime: function () {    
		    var curTime = Math.floor(ytplayer.getCurrentTime());
		    //console.log(curTime+","+skipSecQ);
		    if (questions.hasOwnProperty(curTime) && skipSecQ!=curTime && 
			    ytplayer.getPlayerState() == YT.PlayerState.PLAYING) {
		    		thumbSet.showQuestion(curTime);
		    } else if (ytplayer.getPlayerState() == YT.PlayerState.PLAYING) {
			   setTimeout(thumbSet.checkTime, 200);
		    }
		     if (!questions.hasOwnProperty(curTime)) {
			skipSecQ=-1;
		    }
	
		    thumbSet.lastTime=curTime;
		},
	
		showQuestion: function (curTime) {
		    if (ytplayer && ytplayer.pauseVideo) {
		    	    ytplayer.pauseVideo();
		    }
	
		    skipSecQ = curTime;
	
		    if (questions.hasOwnProperty(curTime)) {
			questions[curTime].done=true;
			thumbSet.setupQPane(curTime);
		    } else {
			ytplayer.playVideo();
		    }
		    
		},
		
		jumpToTime: function (idxTime, skipQ) {
			if (skipQ) {
				skipSecQ = idxTime; //skip the question at this time
				ytplayer.seekTo(idxTime, true);
			} else {
				skipSecQ = -1;
				ytplayer.seekTo(idxTime-0.5, true);
			}
			
			thumbSet.closeQPane(false);
		},
		
		jumpToQ:  function (idxTime) {
			if (this.curQ != -1) {
			    document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.visibility = "hidden";
			    document.getElementById("embedqwrapper"+thumbSet.curQ.qn).style.left = "-5000px";
			}
			skipSecQ = -1;
			ytplayer.pauseVideo();
			ytplayer.seekTo(idxTime-0.5, true);
			thumbSet.showQuestion(idxTime);
		}

    };  // end of thumbSet object definition

    return thumbSet;

};  // end of initVideoObject definition
