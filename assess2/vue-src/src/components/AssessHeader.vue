<template>
  <div :class="{'assess-header': true, 'headerpane': true, 'practice': ainfo.in_practice}">
    <div style="flex-grow: 1">
      <h1>{{ ainfo.name }}</h1>
      <div>
        <span
          :class="{practicenotice: ainfo.in_practice}"
        >
          {{ curScorePoints }}
        </span>
        <span class="answeredinfo">{{ curAnswered }}</span>
      </div>
    </div>

    <timer v-if="ainfo.timelimit > 0"
      :total="ainfo.timelimit"
      :end="ainfo.timelimit_expires">
    </timer>

    <button
      v-if = "assessSubmitLabel != ''"
      :class="{primary: ainfo.submitby === 'by_assessment' }"
      @click="handleSubmit"
      :disabled = "!canSubmit"
    >
      {{ assessSubmitLabel }}
    </button>

    <menu-button
      v-if="ainfo.resources.length > 0"
      id="resource-dropdown" position="right"
      :header = "$t('header.resources_header')"
      nobutton = "true"
      noarrow = "true"
      :options = "ainfo.resources"
      searchby = "title"
    >
      <template v-slot:button>
        <icons name="file" size="medium"/>
      </template>
    </menu-button>

    <div>
      <icons name="print" size="medium"/>
    </div>
  </div>
</template>

<script>
import Timer from '@/components/Timer.vue';
import MenuButton from '@/components/MenuButton.vue';
import Icons from '@/components/Icons.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'AssessHeader',
  components: {
    Timer,
    MenuButton,
    Icons
  },
  data: function () {
    return {
      resourceMenuShowing: false
    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    canSubmit () {
      return (!store.inTransit);
    },
    curScorePoints () {
      let pointsPossible = 0;
      let pointsEarned = 0;
      for (let i in this.ainfo.questions) {
        pointsPossible += this.ainfo.questions[i].points_possible * 1;
        if (this.ainfo.show_scores_during) {
          if (this.ainfo.questions[i].hasOwnProperty('gbscore')) {
            pointsEarned += this.ainfo.questions[i].gbscore * 1;
          }
        }
      }
      if (this.ainfo.in_practice) {
        return this.$t('header.practicescore', { pts: pointsEarned, poss: pointsPossible });
      } else if (this.ainfo.show_scores_during) {
        return this.$t('header.score', { pts: pointsEarned, poss: pointsPossible });
      } else {
        return this.$t('header.possible', { poss: pointsPossible });
      }
    },
    qAttempted () {
      let qAttempted = 0;
      for (let i in this.ainfo.questions) {
        if (this.ainfo.questions[i].try > 0) {
          qAttempted++;
        }
      }
      return qAttempted;
    },
    curAnswered () {
      let nQuestions = this.ainfo.questions.length;
      return this.$t('header.answered', { n: this.qAttempted, tot: nQuestions });
    },
    assessSubmitLabel () {
      if (this.ainfo.submitby === 'by_assessment') {
        return this.$t('header.assess_submit');
      } else {
        // don't have
        return '';
        //return this.$t('header.done');
      }
    }
  },
  methods: {
    handleSubmit () {
      if (this.ainfo.submitby === 'by_assessment') {
        let qAttempted = 0;
        for (let i in this.ainfo.questions) {
          if (this.ainfo.questions[i].try > 0 ||
            store.assessFormIsDirty.indexOf(i*1) !== -1
          ) {
            qAttempted++;
          }
        }
        let nQuestions = this.ainfo.questions.length;
        if (qAttempted === nQuestions ||
          confirm(this.$t('header.warn_unattempted'))
        ) {
          if (this.ainfo.showscores === 'during') {
            // check for dirty questions and submit them
            actions.submitQuestion(store.assessFormIsDirty, true);
          } else {
            // submit them all
            var qns = [];
            for (let k=0; k < this.ainfo.questions.length; k++) {
              qns.push(k);
            }
            actions.submitQuestion(qns, true);
          }
        }
      } else {
        // don't want to submit if by_question
        //actions.submitQuestion(-1, true);
      }
    }
  }
};
</script>

<style>
.assess-header {
  display: flex;
  flex-flow: row wrap;
  justify-content: space-between;
  align-items: center;
}
.assess-header.practice {
  border-top: 2px solid #900;
  border-bottom: 2px solid #900;
}
.assess-header > * {
  margin-right: 10px;
}
.assess-header h1 {
  margin: .4em 0 .2em;
}
.answeredinfo {
  color: #666666;
  margin-left: 20px;
}
.bigicon {
  font-size: 130%;
}
.practicenotice {
  color: #C00;
  font-style: italic;
}
</style>
