<template>
  <transition name="fade">
    <div
      :class="['scoreresult', status]"
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
      <p>
        <button
          v-if = "qdata.canretry"
          type = "button"
          @click = "expanded = false"
        >
          <icons name="retry" />
          {{ $t('scoreresult.retry') }}
        </button>
        <button
          v-if = "qdata.canregen"
          type = "button"
          @click = "trySimilar"
        >
          <icons name="retake" />
          {{ $t('scoreresult.trysimilar') }}
        </button>
      </p>
    </div>
  </transition>
</template>

<script>
import { store, actions } from '../basicstore';
import Icons from '@/components/Icons.vue';

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
      return (store.assessInfo.showscores === 'during');
    },
    status () {
      if (!this.showScores || !this.qdata.hasOwnProperty('parts')) {
        return 'neutral';
      }
      let correct = 0;
      let incorrect = 0;
      let partial = 0;
      for (let i=0; i < this.qdata.parts.length; i++) {
          if (this.qdata.parts[i].rawscore > .99) {
            correct++;
          } else if (this.qdata.parts[i].rawscore < .01) {
            incorrect++;
          } else {
            partial++;
          }
      }
      if (correct === this.qdata.parts.length) {
        return 'correct';
      } else if (incorrect === this.qdata.parts.length) {
        return 'incorrect';
      } else {
        return 'partial';
      }
    }
  },
  methods: {
    trySimilar () {
      actions.loadQuestion(this.qn, true);
      this.expanded = false;
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
</style>
