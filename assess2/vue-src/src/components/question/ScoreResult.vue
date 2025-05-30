<template>
  <transition name="fade">
    <div
      :class="['scoreresult', status.general]"
      tabindex = "-1"
      v-if="expanded"
    >
      <p v-if="showScores">
        {{ $t('scoreresult.scorelast') }}
        <strong>
          {{ $tc('scoreresult.scorepts', qdata.points_possible, {
            pts: qdata.score, poss: qdata.points_possible }) }}.
        </strong>
        {{ $t('scoreresult.see_details') }}
      </p>
      <p v-else>
        {{ $t('scoreresult.submitted') }}
      </p>
      <p v-if="hasManualScore">
        <icons name="info"/>
        {{ $t('scoreresult.manual_grade') }}
      </p>
      <p v-if="showScores && status.general !== 'correct' && status.partcount > 1 && (status.firstincorrect > -1 || status.untried > 0)">
        {{ partStatusMessage }}
        <a v-if="status.firstincorrect > -1"
          href="#" @click.prevent="jumpToIncorrect">
          {{ $t('scoreresult.jumptoincorrect') }}.
        </a>
        <a v-if="status.untried > 0" href="#" @click.prevent="jumpToLastTried">
          {{ $t('scoreresult.jumptolast') }}.
        </a>
      </p>
      <p v-if="showRetryButtons">
        <router-link
          v-if = "showNext"
          :to="'/skip/' + (this.qn + 2)"
          custom
          v-slot="{ navigate }"
        >
          <button
            type="button"
            @click="navigate"
            @keypress.enter="navigate"
            role="link"
          >
            <icons name="right" alt="" />
            {{ $t('scoreresult.next') }}
          </button>
        </router-link>
        <button
          v-if = "showSubmit"
          type = "button"
          class = "primary"
          @click = "submitAssess"
        >
          {{ $t('header.assess_submit') }}
        </button>
        <button
          v-if = "qdata.canregen"
          type = "button"
          @click = "trySimilar"
        >
          <icons name="retake" alt="" />
          {{ $t('scoreresult.trysimilar') }}
        </button>
        <span v-if = "qdata.canretry_primary">
          {{ $t('scoreresult.retryq') }}
        </span>
      </p>
    </div>
  </transition>
</template>

<script>
import { store, actions } from '../../basicstore';
import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'ScoreResult',
  props: ['qdata', 'qn'],
  data: function () {
    return {
      expanded: true
    };
  },
  watch: {
    qdata: function (val) {
      this.expanded = true;
    }
  },
  components: {
    Icons
  },
  computed: {
    showScores () {
      // don't show score on single manual-scored part
      if (this.qdata.hasOwnProperty('parts') &&
        (this.qdata.parts.length === 1 && this.qdata.parts[0].req_manual)
      ) {
        return false;
      }
      return (store.assessInfo.showscores === 'during');
    },
    status () {
      let lasttried = -1;
      let firstincorrect = -2;
      let untried = 0;
      const partcount = this.qdata.parts.length;
      let statusgeneral;
      if (!this.showScores || !this.qdata.hasOwnProperty('parts') ||
        (this.qdata.parts.length === 1 && this.qdata.parts[0].req_manual)
      ) {
        statusgeneral = 'neutral';
      } else {
        let correct = 0;
        let incorrect = 0;
        let zeroweight = 0;
        for (let i = 0; i < this.qdata.parts.length; i++) {
          if (parseFloat(this.qdata.answeights[i]) === 0) {
            zeroweight++;
          } else if (!this.qdata.parts[i].hasOwnProperty('rawscore')) {
            untried++;
          } else if (this.qdata.parts[i].rawscore > 0.99) {
            lasttried = i;
            correct++;
          } else {
            lasttried = i;
            if (this.qdata.parts[i].rawscore < 0.01) {
              incorrect++; // only count as totally incorrect if 0
            }
            if (firstincorrect < 0 && !this.qdata.parts[i].req_manual) {
              if (this.qdata.parts[i].try < this.qdata.tries_max) {
                firstincorrect = i;
              } else {
                firstincorrect = -1; // note there's an incorrect part, but don't indicate specific part
              }
            }
          }
        }

        if (this.qdata.singlescore) {
          if (this.qdata.rawscore > 0.99) {
            statusgeneral = 'correct';
          } else if (this.qdata.rawscore < 0.01) {
            statusgeneral = 'incorrect';
          } else {
            statusgeneral = 'partial';
          }
        } else {
          statusgeneral = 'partial';
          if (correct === this.qdata.parts.length - zeroweight) {
            statusgeneral = 'correct';
          } else if (incorrect === this.qdata.parts.length - zeroweight) {
            statusgeneral = 'incorrect';
          }
        }
      }
      return {
        general: statusgeneral,
        firstincorrect: firstincorrect,
        lasttried: lasttried,
        untried: untried,
        partcount: partcount
      };
    },
    partStatusMessage () {
      // if multipart and should show details;
      if (this.status.partcount > 1 && this.status.general !== 'neutral') {
        if (this.status.firstincorrect === -2) {
          return this.$t('scoreresult.allpartscorrect');
        } else {
          return this.$t('scoreresult.onepartincorrect');
        }
      }
      return '';
    },
    hasManualScore () {
      if (store.assessInfo.showscores !== 'during' ||
        !this.qdata.hasOwnProperty('parts')
      ) {
        return false;
      }
      for (let i = 0; i < this.qdata.parts.length; i++) {
        if (this.qdata.parts[i].hasOwnProperty('req_manual') &&
          this.qdata.parts[i].req_manual
        ) {
          return true;
        }
      }
      return false;
    },
    showRetryButtons () {
      return (store.assessInfo.displaymethod !== 'livepoll');
    },
    showNext () {
      return (store.assessInfo.displaymethod === 'skip' &&
        this.qn < store.assessInfo.questions.length - 1
      );
    },
    showSubmit () {
      return (store.assessInfo.submitby === 'by_assessment' &&
        this.qn === store.assessInfo.questions.length - 1
      );
    }
  },
  methods: {
    trySimilar () {
      actions.loadQuestion(this.qn, true);
    },
    submitAssess () {
      actions.submitAssessment();
    },
    jumpToIncorrect (event) {
      this.focusOnPart(this.status.firstincorrect, false);
    },
    jumpToLastTried (event) {
      this.focusOnPart(this.status.lasttried, true);
    },
    getQuestionEl (pn) {
      /*
      look to see if there is qnwrap+qref element; give tabindex=-1 and focus.
        this is for draw, a11ydraw, matrix, calcmatrix
      look for $("input[id^=mqinput-qn"+qref+"]").  If found, focus first
      Look for $('input[name^=qn' + qref + '],select[name^=qn' + qref + ']'. If found, focus first
      */
      const qref = (parseInt(this.qn) + 1) * 1000 + pn;
      const containers = window.$('#qnwrap' + qref);
      if (containers.length > 0) {
        return [containers.attr('tabindex', '-1')[0], false];
      }
      const mqs = window.$('[id^=mqinput-qn' + qref + ']');
      if (window.MQ && mqs.length > 0) {
        return [mqs[0], true];
      }
      const reg = window.$('input[name^=qn' + qref + '],select[name^=qn' + qref + '],textarea[name^=qn' + qref + ']');
      if (reg.length > 0) {
        return [reg[0], false];
      }
    },
    focusOnPart (pn, after) {
      const tofocus = this.getQuestionEl(pn);
      if (after) {
        window.$(tofocus[0]).nextAll('.afterquestion').first().attr('tabindex', '-1')[0].focus();
      } else {
        if (tofocus[1]) { // if MQ
          window.MQ(tofocus[0]).focus();
        } else {
          tofocus[0].focus();
        }
      }
    }
  }
};
</script>

<style>
.scoreresult {
  padding: 8px 16px;
  margin-bottom: 16px;
}
.scoreresult.correct {
  background-color: #f3fff3;
  border-top: 2px solid #9f9;
}
.scoreresult.incorrect {
  background-color: #fff3f3;
  border-top: 2px solid #f99;
}
.scoreresult.partial {
  background-color: #fff9dd;
  border-top: 2px solid #fa3;
}
.scoreresult.neutral {
  background-color: #f3f3f3;
  border-top: 2px solid #ddd;
}
</style>
