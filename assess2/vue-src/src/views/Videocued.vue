<template>
  <div class="home">
    <assess-header></assess-header>
    <videocued-nav :cue="cue" :toshow="toshow" />
    <div class="scrollpane">
      <div
        class = "questionpane"
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
            :style = "{'padding-top': aspectRatioPercent + '%'}"
          >
            <div id = "player"></div>
          </div>
        </div>
      </div>
      <div
        v-for="curqn in questionArray"
        :key="curqn"
        :aria-hidden = "curqn != qn"
        :class="{inactive: curqn != qn, questionpane: true}"
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
import Question from '@/components/question/Question.vue';
import { store } from '../basicstore';

export default {
  name: 'videocued',
  components: {
    FullQuestionHeader,
    VideocuedNav,
    Question,
    InterQuestionTextList,
    AssessHeader
  },
  data: function () {
    return {
      youtubeApiLoaded: false,
      videoWidth: 600,
      aspectRatioPercent: 56.2
    }
  },
  computed: {
    cue () {
      return parseInt(this.$route.params.cue) - 1;
    },
    toshow () {
      if (this.$route.params.hasOwnProperty('toshow')) {
        return this.$route.params.toshow;
      } else {
        return '';
      }
    },
    qn () {
      if (this.toshow === 'q') {
        return parseInt(store.assessInfo.videocues[this.cue].qn);
      } else {
        return -1;
      }
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
    createPlayer() {
      let supportsFullScreen = !!(document.exitFullscreen || document.mozCancelFullScreen || document.webkitExitFullscreen || document.msExitFullscreen);
      let pVarsInternal = {'autoplay': 0, 'wmode': 'transparent', 'fs': supportsFullScreen?1:0, 'controls':2, 'rel':0, 'modestbranding':1, 'showinfo':0};
      let ar = store.assessInfo.videoar.split(":");
      let videoHeight = window.innerHeight - 50;
      this.videoWidth = ar[0]/ar[1] * videoHeight;
      this.aspectRatioPercent = Math.round(1000*videoHeight/this.videoWidth)/10;
      this.ytplayer = new YT.Player('player', {
        height: videoHeight,
        width: this.videoWidth,
        videoId: store.assessInfo.videoid,
        playerVars: pVarsInternal,
        events: {
          'onReady': () => handlePlayerReady,
          'onStateChange': () => handlePlayerStateChange,
          'onError': () => handlePlayerError,
        }
      });
    }
  },
  watch: {
    '$route' (to, from) {
      if (to.params.toshow == 'q') {
        // if showing a question, pause the video
        
      }
    }
  },
  created () {
    //async load YouTube API
    window.onYouTubePlayerAPIReady = () => {
      this.youtubeApiLoaded = true;
    }
    let tag = document.createElement('script');
    tag.src = "//www.youtube.com/player_api";
    document.head.appendChild(tag);
  },
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
    this.createPlayer();
  }
};
</script>

<style>
.inactive {
  visibility: hidden;
  position: absolute;
}
.questionpane {
  margin: 15px 15px;
  max-width: 700px;
  overflow: visible;
}
.scrollpane {
  width: 100%;
  overflow-x: auto;
}
</style>
