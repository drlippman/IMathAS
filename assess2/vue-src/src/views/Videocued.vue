<template>
  <div class="home">
    <assess-header></assess-header>
    <videocued-nav
      :cue="cue"
      :toshow="toshow"
      @jumpto="jumpTo"
    >
      <videocued-result-nav
        class="med-left"
        v-if = "qn != -1"
        :qn = "qn"
        :cue = "cue"
        @jumpto="jumpTo"
      />
    </videocued-nav>
    <div class="scrollpane" role="region" :aria-label="$t('regions.q_and_vid')">
      <div
        class = "questionpane introtext"
        v-show = "cue == -1"
        key = "-1"
        v-html = "intro"
        ref = "introtext"
      />
      <div id = "playerwrapper" v-show = "cue > -1 && qn === -1">
        <div
          class="video-wrapper-wrapper"
          :style = "{'max-width': videoWidth + 'px'}"
        >
          <div
            class="fluid-width-video-wrapper"
            :style = "{'padding-bottom': aspectRatioPercent + '%'}"
          >
            <div id = "player"></div>
          </div>
        </div>
      </div>
      <div
        v-for="curqn in questionArray"
        :key="curqn"
        :aria-hidden = "curqn != qn"
        :class="{inactive: curqn != qn}"
      >
        <inter-question-text-list
          pos = "before"
          :qn = "curqn"
          :active="curqn == qn"
        />
        <full-question-header
          v-show = "curqn == qn"
          :qn = "curqn"
        />
        <question
          :qn="curqn"
          :active="curqn == qn"
        />
        <inter-question-text-list
          pos = "after"
          :qn = "curqn"
          :active="curqn == qn"
        />
      </div>
    </div>
  </div>
</template>

<script>
/*
videocued/0     Intro
videocued/#/v   Video segment #-1
videocued/#/q   Question following video segment #-1
videocued/#/f   Followup after question following video segment #-1

On question submission: (put in scoreresult?  Or add above <question> here?)
 if has followup:
  show "Continue to followuptitle" option
  if correct and has next segment, show "Jump video to nextsegmentitle" button
 else if has next segment
  show "Continue video to nextsegmenttitle" button

Will need to rebuild ytapi.js in the Vue code.

*/
import AssessHeader from '@/components/AssessHeader.vue';
import VideocuedNav from '@/components/VideocuedNav.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import VideocuedResultNav from '@/components/VideocuedResultNav.vue';
import Question from '@/components/question/Question.vue';
import { store } from '../basicstore';

export default {
  name: 'videocued',
  components: {
    FullQuestionHeader,
    VideocuedNav,
    Question,
    VideocuedResultNav,
    InterQuestionTextList,
    AssessHeader
  },
  data: function () {
    return {
      youtubeApiLoaded: false,
      videoWidth: 600,
      aspectRatioPercent: 56.2,
      ytplayer: null,
      timer: null,
      cue: 0,
      toshow: 'v'
    };
  },
  computed: {
    curCue () {
      if (this.cue > -1) {
        return store.assessInfo.videocues[this.cue];
      } else {
        return {};
      }
    },
    qn () {
      if (this.toshow === 'q') {
        return parseInt(this.curCue.qn);
      } else {
        return -1;
      }
    },
    timeCues () {
      let out = {};
      for (let i in store.assessInfo.videocues) {
        if (store.assessInfo.videocues[i].hasOwnProperty('qn')) {
          out[store.assessInfo.videocues[i].time] = parseInt(i);
        }
      }
      return out;
    },
    nextVidTimes () {
      // we'll want to update the nav when a followup ends, or when a
      // video segment with a question ends
      let out = {};
      for (let i = 0; i < store.assessInfo.videocues.length; i++) {
        if (store.assessInfo.videocues[i].hasOwnProperty('followuptime') &&
          store.assessInfo.videocues.hasOwnProperty(i + 1)
        ) {
          out[store.assessInfo.videocues[i].followuptime] = i;
        } else if (!store.assessInfo.videocues[i].hasOwnProperty('qn') &&
          store.assessInfo.videocues.hasOwnProperty(i + 1)
        ) {
          out[store.assessInfo.videocues[i].time] = i;
        }
      }
      return out;
    },
    intro () {
      return store.assessInfo.intro;
    },
    questionArray () {
      let qnArray = {};
      for (let i = 0; i < store.assessInfo.questions.length; i++) {
        qnArray[i] = i;
      }
      return qnArray;
    }
  },
  methods: {
    createPlayer () {
      let supportsFullScreen = !!(document.exitFullscreen || document.mozCancelFullScreen || document.webkitExitFullscreen || document.msExitFullscreen);
      let pVarsInternal = { 'autoplay': 0, 'wmode': 'transparent', 'fs': supportsFullScreen ? 1 : 0, 'controls': 2, 'rel': 0, 'modestbranding': 1, 'showinfo': 0 };
      let ar = store.assessInfo.videoar.split(':');
      let videoHeight = window.innerHeight - 50;
      this.videoWidth = ar[0] / ar[1] * videoHeight;
      this.aspectRatioPercent = Math.round(1000 * videoHeight / this.videoWidth) / 10;
      this.ytplayer = new window.YT.Player('player', {
        height: videoHeight,
        width: this.videoWidth,
        videoId: store.assessInfo.videoid,
        playerVars: pVarsInternal,
        events: {
          'onReady': () => this.handlePlayerReady(),
          'onStateChange': (event) => this.handlePlayerStateChange(event),
          'onError': (event) => this.handlePlayerError(event)
        }
      });
    },
    exitFullscreen () {
      let isInFullScreen = (
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement);
      if (isInFullScreen) {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
          document.msExitFullscreen();
        }
      }
    },
    checkTime () {
      let curTime = Math.floor(this.ytplayer.getCurrentTime());
      // If there's a queue for this time,
      // But not if we jumped to a video and the queue is for the previous
      // or if we jump to followup
      if (this.timeCues.hasOwnProperty(curTime) &&
        !(this.toshow === 'v' && this.cue === this.timeCues[curTime] + 1) &&
        !(this.toshow === 'f' && this.cue === this.timeCues[curTime]) &&
        this.ytplayer.getPlayerState() === window.YT.PlayerState.PLAYING
      ) {
        this.jumpTo(parseInt(this.timeCues[curTime]), 'q');
      } else {
        if (this.nextVidTimes.hasOwnProperty(curTime) &&
          this.cue === this.nextVidTimes[curTime]
        ) {
          this.cue = this.cue + 1;
          this.toshow = 'v';
        }
        // wait again
        this.timer = window.setTimeout(() => { this.checkTime(); }, 200);
      }
    },
    handlePlayerReady () {
      // remove cruft to allow autofit to work
      window.$('iframe#player').removeAttr('height').removeAttr('width')
        .css('height', '').css('width', '');
    },
    handlePlayerStateChange (event) {
      if (event.data === window.YT.PlayerState.PLAYING) {
        // started playing video.  Start listing for the times
        this.timer = window.setTimeout(() => { this.checkTime(); }, 200);
      } else if (event.data === window.YT.PlayerState.ENDED) {
        // video ended - check to see if there's a question to show.
        if (this.toshow === 'v' && this.curCue.hasOwnProperty('qn')) {
          window.clearTimeout(this.timer);
          this.jumpTo(this.cue, 'q');
        }
      }
    },
    handlePlayerError (event) {
      store.errorMsg = event.data;
    },
    jumpTo (newCueNum, newToshow) {
      if (newCueNum === -1 || newToshow === 'q') {
        // if showing a question, pause the video
        this.exitFullscreen();
        if (this.ytplayer) {
          this.ytplayer.pauseVideo();
        }
      } else {
        let newCue = store.assessInfo.videocues[newCueNum];
        let seektime = 0;
        if (newToshow === 'v') {
          if (newCueNum > 0) {
            let prevCue = store.assessInfo.videocues[newCueNum - 1];
            if (prevCue.hasOwnProperty('followuptime')) {
              // if previous cue had followup, skip to end of that
              seektime = prevCue.followuptime;
            } else {
              // otherwise, skip to end of previous main vid seg
              seektime = prevCue.time;
            }
          }
        } else if (newToshow === 'f') {
          // start of followup is end of video segment
          seektime = newCue.time;
        }
        // seek to right place in video
        this.ytplayer.seekTo(seektime, true);
        this.ytplayer.playVideo();
      }
      this.cue = newCueNum;
      this.toshow = newToshow;
    }
  },
  created () {
    // don't show intro if it's empty
    if (store.assessInfo.intro !== '') {
      this.cue = -1;
      this.toshow = 'i';
    }
    // async load YouTube API
    window.onYouTubePlayerAPIReady = () => {
      this.youtubeApiLoaded = true;
      this.createPlayer();
    };
    let tag = document.createElement('script');
    tag.src = '//www.youtube.com/player_api';
    document.head.appendChild(tag);
  },
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
  }
};
</script>

<style>

</style>
