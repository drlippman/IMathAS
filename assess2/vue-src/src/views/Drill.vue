<template>
  <div class="home">
    <a href="#" class="sr-only" id="skipnav" @click.prevent="jumpFocus">
      {{ $t('jumptocontent') }}
    </a>
    <assess-header></assess-header>
    <drill-nav-header :qn="qn"/>
    <div
      class="scrollpane"
      role="region"
      ref="scrollpane"
      :aria-label="$t('regions-questions')"
    >
      <intro-text
        :active = "qn == -1"
        :html = "intro"
        key = "-1"
      />
      <feedback
        :active = "qn == -1"
        qn = "general"
      />
      <div v-if="qn > -1 && questionData">
        <h2 class="sr-only">
          {{ dispName }}
        </h2>

        <div
          v-if="statusBoxMessage || progressMessage"
          :class="['scoreresult', statusBoxClass]"
          tabindex="-1"
          ref="statusBox"
        >
          <p v-if="statusBoxMessage">{{ statusBoxMessage }}</p>
          <p v-if="progressMessage">{{ progressMessage }}</p>
        </div>

        <question
          v-if="questionData.drillstatus === 1"
          ref="questionComp"
          :qn="qn"
          :active="true"
          :getwork="0"
        />

        <div v-if="questionData.drillawaitingnext" class="submitbtnwrap">
          <button type="button" ref="nextBtn" class="primary" @click="advanceDrill" :disabled="!canSubmit">
            {{ $t('drill-next') }}
          </button>
        </div>

        <div v-if="questionData.drillcomplete && lastResult.correct > 0" class="scoreresult correct" tabindex="-1">
          <h3>{{ $t('drill-complete') }}</h3>
          <p v-if="lastResult">
            {{ resultSummary }}
          </p>
        </div>
        <div v-if="questionData.drillcomplete" class="submitbtnwrap">
          <button type="button" class="primary" @click="startDrill">
            {{ $t('drill-restart') }}
          </button>
          <router-link
            v-if="showNextQ" 
            :to="'/drill/'+ (this.qn + 2)"
            custom
            v-slot="{ navigate }"
          >
            <button
              type="button"
              @click="navigate"
              @keypress.enter="navigate"
              role="link"
              class="secondarybtn"
            >
              <icons name="right" />
              {{ $t('question-next') }}
            </button>
          </router-link>
        </div>
        
        <div v-if="questionData.drillstatus !== 1 && !questionData.drillcomplete" class="submitbtnwrap">
          <p>{{ drillGoalLabel(drillsettings) }}</p>
          <button type="button" class="primary" @click="startDrill" :disabled="!canSubmit">
            {{ $t('drill-start') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import DrillNavHeader from '@/components/DrillNavHeader.vue';
import Question from '@/components/question/Question.vue';
import IntroText from '@/components/IntroText.vue';
import Feedback from '@/components/Feedback.vue';
import Icons from '@/components/widgets/Icons.vue';
import { drillGoalMixin } from '@/mixins/drillGoalMixin';

import { store, actions } from '@/basicstore';

export default {
  name: 'drill',
  components: {
    DrillNavHeader,
    Question,
    IntroText,
    Feedback,
    AssessHeader,
    Icons
  },
  mixins: [drillGoalMixin],
  computed: {
    qn () {
      return parseInt(this.$route.params.qn) - 1;
    },
    showNextQ () {
      return this.qn < store.assessInfo.questions.length - 1;
    },
    drillsettings () {
      return store.assessInfo.drillsettings;
    },
    intro () {
      return store.assessInfo.intro;
    },
    questionData () {
      if (!store.assessInfo.questions.hasOwnProperty(this.qn)) {
        return null;
      }
      return store.assessInfo.questions[this.qn];
    },
    dispName () {
      return (this.questionData && this.questionData.dispname)
        ? this.questionData.dispname
        : this.$t('question_n', { n: this.qn + 1 });
    },
    lastTryWrong () {
      return !!this.questionData &&
        this.questionData.drillstatus === 1 &&
        this.questionData.try > 0 &&
        this.questionData.status !== 'correct' &&
        this.questionData.try < this.questionData.tries_max;
    },
    statusBoxClass () {
      if (!this.questionData) {
        return 'neutral';
      }
      if (this.questionData.drilllastoutcome === 'correct') {
        return 'correct';
      }
      if (this.questionData.drilllastoutcome === 'partial' ||
        (this.lastTryWrong && this.questionData.status === 'partial')
      ) {
        return 'partial';
      }
      return this.statusBoxMessage ? 'incorrect' : 'neutral';
    },
    statusBoxMessage () {
      if (!this.questionData) {
        return '';
      }
      if (this.questionData.drillawaitingnext) {
        return this.$t('drill-outoftries');
      }
      if (this.lastTryWrong) {
        return (this.questionData.status === 'partial')
          ? this.$t('drill-partial_tryagain')
          : this.$t('drill-incorrect_tryagain');
      }
      if (this.questionData.drilllastoutcome === 'correct') {
        return this.$t('drill-correct_next');
      }
      if (this.questionData.drilllastoutcome === 'partial') {
        return this.$t('drill-partial_next');
      }
      if (this.questionData.drilllastoutcome === 'incorrect') {
        return this.$t('drill-incorrect_next');
      }
      return '';
    },
    progressMessage () {
      if (!this.questionData || this.questionData.drillstatus !== 1 || !this.drillsettings) {
        return '';
      }
      return this.drillProgressLabel(this.drillsettings, this.questionData.drillcount);
    },
    lastResult () {
      if (!this.questionData || !this.questionData.drillresults ||
        this.questionData.drillresults.length === 0
      ) {
        return null;
      }
      return this.questionData.drillresults[this.questionData.drillresults.length - 1];
    },
    resultSummary () {
      return this.drillResultLabel(this.lastResult);
    },
    canSubmit () {
      return !store.inTransit;
    },
    drillAwaitingNext () {
      return !!this.questionData && !!this.questionData.drillawaitingnext;
    },
    drawalt () {
      return !!store.assessInfo.drawalt;
    },
    questionRendered () {
      return !!this.questionData && !!this.questionData.rendered;
    }
  },
  methods: {
    startDrill () {
      actions.startDrill(this.qn);
    },
    advanceDrill () {
      actions.advanceDrill(this.qn);
    },
    jumpFocus () {
      this.$refs.scrollpane.setAttribute('tabindex', '-1');
      this.$refs.scrollpane.focus();
      this.$refs.scrollpane.removeAttribute('tabindex');
    }
  },
  watch: {
    drillAwaitingNext (newVal) {
      if (newVal && !this.drawalt) {
        this.$nextTick(() => {
          if (this.$refs.nextBtn) {
            this.$refs.nextBtn.focus();
          }
        });
      }
    },
    // for screenreader users (drawalt), send focus to the status box
    // (rather than straight into the answer field) whenever a question is
    // freshly displayed or just submitted - both go through the same
    // rendered-reset-then-set-true cycle in Question.vue's renderAndTrack()
    questionRendered (newVal) {
      if (newVal && this.drawalt) {
        this.$nextTick(() => {
          if (this.$refs.statusBox) {
            this.$refs.statusBox.focus();
          } else if (this.$refs.questionComp && this.$refs.questionComp.$el) {
            this.$refs.questionComp.$el.focus();
          }
        });
      }
    }
  }
};
</script>

<style>
</style>
