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

    <div v-if = "showPrint">
      <a
        :href="printLink"
        class = "noextlink"
        target = "_blank"
        :aria-label = "$t('print.print_version')"
      >
        <icons name="print" size="medium"/>
      </a>
    </div>
    <button
      @click="toggleMQuse"
      :class="{plain:true, 'switch-toggle':true}"
      :title="MQenabled?$t('header.disable_mq'):$t('header.enable_mq')"
      :aria-label="MQenabled?$t('header.disable_mq'):$t('header.enable_mq')"
      :aria-pressed="MQenabled"
    >
      <icons
        :name="MQenabled ? 'eqned' : 'eqnedoff'"
        :color="MQenabled ? '#060' : '#600'"
        size="medium"
      />
      <span class="switch-toggle__ui"></span>
      </span>
    </button>
  </div>
</template>

<script>
import Timer from '@/components/Timer.vue';
import MenuButton from '@/components/widgets/MenuButton.vue';
import Icons from '@/components/widgets/Icons.vue';
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
        // return this.$t('header.done');
      }
    },
    showPrint () {
      return (this.ainfo.noprint !== 1);
    },
    printLink () {
      return window.location.pathname + window.location.search + '#/print';
    },
    MQenabled () {
      return store.enableMQ;
    }
  },
  methods: {
    handleSubmit () {
      if (this.ainfo.submitby === 'by_assessment') {
        let qAttempted = 0;
        let changedQuestions = actions.getChangedQuestions();
        for (let i in this.ainfo.questions) {
          if (this.ainfo.questions[i].try > 0 ||
            changedQuestions.hasOwnProperty(i)
          ) {
            qAttempted++;
          }
        }
        let nQuestions = this.ainfo.questions.length;
        if (qAttempted === nQuestions ||
          confirm(this.$t('header.warn_unattempted'))
        ) {
          // TODO: Check if we should always submit all
          if (this.ainfo.showscores === 'during') {
            // check for dirty questions and submit them
            actions.submitQuestion(Object.keys(changedQuestions), true);
          } else {
            // submit them all
            var qns = [];
            for (let k = 0; k < this.ainfo.questions.length; k++) {
              qns.push(k);
            }
            actions.submitQuestion(qns, true);
          }
        }
      } else {
        // don't want to submit if by_question
        // actions.submitQuestion(-1, true);
      }
    },
    toggleMQuse () {
      if (store.enableMQ) {
        actions.disableMQ();
      } else {
        actions.enableMQ();
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

/* FROM  https://scottaohara.github.io/a11y_styled_form_controls/src/toggle-button-switch/*/
.switch-toggle {
	display: inline-block;
	position: relative;
  padding-right: 0px;
  padding-left: 4px;
}

.switch-toggle:focus {
	outline: none;
}

/* negate 'flash' of text color when pressing a button in some browsers */
.switch-toggle:active {
	color: inherit;
}

.switch-toggle__ui {
  position: relative;
  display: inline-block;
  width: 2em;
  height: 1em;
}
/* using the before/after pseudo elements of the span to create the "switch" */
.switch-toggle__ui:before,
.switch-toggle__ui:after {
	border: 1px solid #565656;
	content: "";
	position: absolute;
  top: 0;
}

/* styling specific to the knob of the switch */
.switch-toggle__ui:after {
	background: #fff;
	border-radius: 100%;
	height: 1em;
	right: .75em;
	transition: right .1825s ease-in-out;
	width: 1em;
}

/* styling specific to the knob "container" */
.switch-toggle__ui:before {
	background: #caa;
	border-radius: 1em;
	height: 1em;
	right: 0em;
	transition: background .2s ease-in-out;
	width: 1.75em;
}

.switch-toggle span {
	pointer-events: none;
}

.switch-toggle:focus .switch-toggle__ui:before {
	outline: 2px solid #2196f3;
	outline-offset: 2px;
}

/* change the position of the knob to indicate it has been checked*/
.switch-toggle[aria-pressed="true"] .switch-toggle__ui:after {
	right: 0em;
}

/* update the color of the "container" to further visually indicate state */
.switch-toggle[aria-pressed="true"] .switch-toggle__ui:before {
	background: #090;
}
</style>
