<template>
  <div class="home">
    <assess-header></assess-header>
    <videocued-header :cue="cue" :toshow="toshow" />
    <div class="scrollpane">
      <div
        class = "questionpane"
        v-show = "cue == -1"
        key = "-1"
        v-html = "intro"
        ref = "introtext"
      />
      <div id = "playerwrapper">
        <div id = "player">
        </div>
      </div>
      <div
        v-for="curqn in questionArray"
        :key="curqn"
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

viddata: currently serialized (switch to JSON?)
First element is either video ID or array (videoid, aspect ratio); default "16:9"

Each viddata element is:
[
 title,
 endtime, // unless final segment title
 qn, // unless segment title only
 followuptime, // this and the rest are optional
 showlink, // whether to show a link in nav for "Answer" or whatever
 followuptitle
]

On question submission:
 if has followup:
  show "Continue to followuptitle" option
  if correct and has next segment, show "Jump video to nextsegmentitle" button
 else if has next segment
  show "Continue video to nextsegmenttitle" button

async load youtube API
tag = document.createElement('script');
tag.src = "//www.youtube.com/player_api";

Will need to rebuild ytapi.js in the Vue code.

Nav:
foreach viddata
 link using segment title, jumps to previous segment end time
 if has a qn,
  link to jump to question
  if has followup with showlink==true
   show link with followuptitle, jumps to main segment end time
*/
import AssessHeader from '@/components/AssessHeader.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import Question from '@/components/question/Question.vue';
import { store } from '../basicstore';

export default {
  name: 'videocued',
  components: {
    FullQuestionHeader,
    Question,
    InterQuestionTextList,
    AssessHeader
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
        return parseInt(store.assessInfo.videocues[cue].qn);
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
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
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
