<template>
  <transition name="fade">
    <div
      :class="['scoreresult', status]"
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
      <p v-if="showRetryButtons">
        <router-link
          v-if = "showNext"
          :to="'/skip/' + (this.qn + 2)"
          tag="button"
        >
          <icons name="right" alt=""/>
          {{ $t('scoreresult.next') }}
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
      if (!this.showScores || !this.qdata.hasOwnProperty('parts') ||
        (this.qdata.parts.length === 1 && this.qdata.parts[0].req_manual)
      ) {
        return 'neutral';
      }
      if (this.qdata.singlescore) {
        if (this.qdata.rawscore > 0.99) {
          return 'correct';
        } else if (this.qdata.rawscore < 0.01) {
          return 'incorrect';
        } else {
          return 'partial';
        }
      }
      let correct = 0;
      let incorrect = 0;
      for (let i = 0; i < this.qdata.parts.length; i++) {
        if (!this.qdata.parts[i].hasOwnProperty('rawscore')) {
          continue; // neither correct or incorrect - untried
        } else if (this.qdata.parts[i].rawscore > 0.99) {
          correct++;
        } else if (this.qdata.parts[i].rawscore < 0.01) {
          incorrect++;
        }
      }
      if (correct === this.qdata.parts.length) {
        return 'correct';
      } else if (incorrect === this.qdata.parts.length) {
        return 'incorrect';
      } else {
        return 'partial';
      }
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
