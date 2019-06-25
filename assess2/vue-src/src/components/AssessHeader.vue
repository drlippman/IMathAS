<template>
  <div :class="{'assess-header': true, 'headerpane': true, 'practice': ainfo.in_practice}"
    role="region" :aria-label="$t('regions.aheader')"
  >
    <div style="flex-grow: 1">
      <h1>{{ ainfo.name }}</h1>
      <div>
        <span
          :class="{practicenotice: ainfo.in_practice}"
        >
          {{ curScorePoints }}
        </span>
        <span class="med-left subdued">{{ curAnswered }}</span>
      </div>
    </div>

    <timer v-if="ainfo.timelimit > 0"
      :total="ainfo.timelimit"
      :end="ainfo.timelimit_expires"
      :grace="ainfo.timelimit_grace">
    </timer>

    <div class="flexgroup" v-if = "assessSubmitLabel != ''">
      <button
        v-if = "saveWorkLabel != ''"
        class = "secondary"
        @click = "handleSaveWork"
        :disabled = "!canSubmit"
      >
        {{ saveWorkLabel }}
      </button>
      <button
        v-if = "assessSubmitLabel != ''"
        :class="{primary: ainfo.submitby === 'by_assessment' }"
        @click="handleSubmit"
        :disabled = "!canSubmit"
      >
        {{ assessSubmitLabel }}
      </button>
    </div>

    <div class="assess-header">
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

      <tooltip-span v-if = "showPrint" :tip="$t('print.print_version')">
        <a
          :href="printLink"
          class = "noextlink"
          target = "_blank"
          :aria-label = "$t('print.print_version')"
        >
          <icons name="print" size="medium"/>
        </a>
      </tooltip-span>

      <tooltip-span
        :tip="MQenabled?$t('header.disable_mq'):$t('header.enable_mq')"
        style="display: inline-block"
      >
        <button
          @click="toggleMQuse"
          :class="{plain:true, 'switch-toggle':true}"
          :aria-label="MQenabled?$t('header.disable_mq'):$t('header.enable_mq')"
          :aria-pressed="MQenabled"
        >
          <icons
            :name="MQenabled ? 'eqned' : 'eqnedoff'"
            :color="MQenabled ? '#060' : '#600'"
            size="medium"
          />
          <span class="switch-toggle__ui"></span>
        </button>
      </tooltip-span>
      <lti-menu v-if="ainfo.is_lti" />
    </div>

  </div>
</template>

<script>
import Timer from '@/components/Timer.vue';
import MenuButton from '@/components/widgets/MenuButton.vue';
import Icons from '@/components/widgets/Icons.vue';
import LtiMenu from '@/components/LtiMenu.vue';
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';

import { store, actions } from '../basicstore';

export default {
  name: 'AssessHeader',
  components: {
    Timer,
    MenuButton,
    TooltipSpan,
    Icons,
    LtiMenu
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
    saveWorkLabel () {
      if (this.ainfo.submitby === 'by_assessment') {
        if (store.autoSaving) {
          return this.$t('header.work_saving');
        } else if (Object.keys(store.autosaveQueue).length === 0 &&
          !store.somethingDirty
        ) {
          return this.$t('header.work_saved');
        } else {
          return this.$t('header.work_save');
        }
      } else {
        return '';
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
      actions.submitAssessment();
    },
    handleSaveWork () {
      if (Object.keys(store.autosaveQueue).length === 0) {
        // nothing to save, so fake it
        store.autoSaving = true;
        setTimeout(() => { store.autoSaving = false; }, 300);
      } else {
        actions.submitAutosave();
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
  /*justify-content: space-between;*/
  align-items: center;
}
.assess-header.practice {
  border-top: 2px solid #900;
  border-bottom: 2px solid #900;
}
.assess-header > * {
  margin-right: 10px;
}
.assess-header > *:last-child {
  margin-right: 0;
}
.assess-header h1 {
  margin: .4em 0 .2em;
}

.practicenotice {
  color: #C00;
  font-style: italic;
}

/* FROM  https://scottaohara.github.io/a11y_styled_form_controls/src/toggle-button-switch/*/

.switch-toggle2__ui {
  margin-left: 2px;
  display: inline-block;
  position: relative;
  width: 27px;
  height: 16px;
  border: 1px solid #444;
  background-color: #f99;
  border-radius: 4px;
}
.switch-toggle[aria-pressed="true"] .switch-toggle2__ui {
  background-color: #9f9
}
.switch-toggle2__ui:after {
  border-radius: 4px;
  height: 12px;
  font-size: 10px;
  border: 1px solid #444;
  position: absolute;
  content: "Off";
  color: #600;
  left: 0;
  background-color: #fff;
  vertical-align: -50%;
  line-height: 10px;
  padding: 1px 2px;
}
.switch-toggle[aria-pressed="true"] .switch-toggle2__ui:after {
  content: "On";
  color: #040;
  right: 0;
  left: auto;
}

.switch-toggle {
  display: inline-block;
  position: relative;
  padding-right: 0px;
  padding-left: 4px;
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
  transition: right .1s ease-in-out;
  width: 1em;
  box-shadow: 1px 2px 2px 0px rgba(0,0,0,0.25);
}

/* styling specific to the knob "container" */
.switch-toggle__ui:before {
  background: #baa;
  border-radius: 1em;
  height: .5em;
  top: .25em;
  right: 0em;
  transition: background .1s ease-in-out;
  width: 1.75em;
}

.switch-toggle span {
  pointer-events: none;
}

/* change the position of the knob to indicate it has been checked*/
.switch-toggle[aria-pressed="true"] .switch-toggle__ui:after {
  right: 0em;
}

/* update the color of the "container" to further visually indicate state */
.switch-toggle[aria-pressed="true"] .switch-toggle__ui:before {
  background: #0c0;
}
.switch-toggle[aria-pressed="true"] .switch-toggle__ui:after {
  background: #090;
}
</style>
