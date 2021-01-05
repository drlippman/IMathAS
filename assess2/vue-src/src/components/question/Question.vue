<template>
  <div class = "questionwrap questionpane" ref="main">
    <div v-if = "!questionContentLoaded">
      {{ $t('loading') }}
    </div>
    <score-result
      v-if = "showScore && showResults"
      :qdata = "questionData"
      :qn = "qn"
    />
    <div v-else-if = "showScore && questionData.canregen"
      class="scoreresult neutral"
      tabindex = "-1"
    >
      <button
        type = "button"
        @click = "trySimilar"
      >
        <icons name="retake" alt="" />
        {{ $t('scoreresult.trysimilar') }}
      </button>
    </div>
    <p
      v-if="questionData.withdrawn !== 0"
      class="noticetext"
    >
      <icons name="alert" color="warn" size="medium" />
      {{ $t('question.withdrawn') }}
    </p>
    <div v-if = "errorsToShow.length > 0" class="small">
      <ul>
        <li v-for = "(error,index) in errorsToShow" :key="index">
          {{ error }}
        </li>
      </ul>
    </div>
    <div
      v-if = "questionContentLoaded"
      v-html="questionData.html"
      class = "question"
      :id="'questionwrap' + qn"
      ref = "thisqwrap"
    />
    <question-helps
      v-if = "showHelps"
      :qn = "qn"
    />

    <div v-if="showWork && questionContentLoaded">
      <button
        v-if = "getwork !== 2"
        @click = "showWorkInput = !showWorkInput"
      >
        {{ showWorkInput ? $t('work.hide') : $t('work.add') }}
      </button>
      <div v-show="getwork === 2 || showWorkInput">
        <showwork-input
          :id="'sw' + qn"
          :value = "questionData.work"
          rows = "3"
          :active = "getwork === 2 || showWorkInput"
          @input = "updateWork"
          @blur = "workChanged"
          @focus = "workFocused"
        />
      </div>
    </div>
    <div v-if="showSubmit" class="submitbtnwrap">
      <button
        type = "button"
        @click = "submitQuestion"
        :class = "submitClass"
        :disabled = "!canSubmit"
      >
        {{ submitLabel }}
        <span class="sr-only">
          {{ $t('question_n', {n: qn+1}) }}
        </span>
      </button>
      <button
        v-if = "canJumpToAnswer"
        type = "button"
        @click = "jumpToAnswer"
        class = "secondary"
        :disabled = "!canSubmit"
      >
        {{ $t('question.jump_to_answer') }}
      </button>
    </div>
    <div v-else-if="showNext"  class="submitbtnwrap">
      <router-link
        :to="'/skip/'+ (this.qn + 2)"
        tag="button"
        class="secondarybtn"
        :disabled = "!canSubmit"
      >
        <icons name="right" />
        {{ $t('question.next') }}
      </router-link>
    </div>
  </div>
</template>

<script>
import { store, actions } from '../../basicstore';
import ScoreResult from '@/components/question/ScoreResult.vue';
import Icons from '@/components/widgets/Icons.vue';
import QuestionHelps from '@/components/question/QuestionHelps.vue';
import ShowworkInput from '@/components/ShowworkInput.vue';
import { pauseVideos } from '@/components/pauseVideos';

export default {
  name: 'Question',
  props: ['qn', 'active', 'state', 'seed', 'disabled', 'getwork'],
  components: {
    ScoreResult,
    QuestionHelps,
    ShowworkInput,
    Icons
  },
  data: function () {
    return {
      work: '',
      lastWorkVal: '',
      showWorkInput: false
    };
  },
  computed: {
    questionData () {
      return store.assessInfo.questions[this.qn];
    },
    canSubmit () {
      return (!store.inTransit);
    },
    canJumpToAnswer () {
      return (this.questionData.jump_to_answer);
    },
    questionContentLoaded () {
      return (this.questionData.html !== null);
    },
    hasSeqNext () {
      return (this.questionData.jsparams &&
        this.questionData.jsparams.hasseqnext);
    },
    hasSubmitAll () {
      return (this.questionData.jsparams &&
        this.questionData.jsparams.submitall === 1);
    },
    buttonsOk () {
      return (store.inProgress &&
        this.questionContentLoaded &&
        !store.inPrintView &&
        !this.disabled &&
        this.questionData.withdrawn === 0 &&
        this.questionData.canretry);
    },
    showSubmit () {
      return (this.buttonsOk && (
        store.assessInfo.submitby === 'by_question' ||
          store.assessInfo.showscores === 'during' ||
          this.hasSeqNext
      ) && (
      // if livepoll, only show if state is 2
        store.assessInfo.displaymethod !== 'livepoll' ||
          this.state === 2
      )
      );
    },
    showNext () {
      return (this.buttonsOk && !this.showSubmit &&
        store.assessInfo.displaymethod === 'skip' &&
        this.qn < store.assessInfo.questions.length - 1);
    },
    submitClass () {
      return (store.assessInfo.submitby === 'by_assessment')
        ? 'secondary' : 'primary';
    },
    showScore () {
      return (store.inProgress &&
        !store.inPrintView &&
        !this.disabled &&
        this.questionData.hadSeqNext !== true &&
        (this.questionData.hasOwnProperty('score') ||
         this.questionData.status === 'attempted'
        ) &&
        (this.questionData.try > 0 ||
          this.questionData.hasOwnProperty('tries_remaining_range')) &&
        this.questionData.withdrawn === 0
      );
    },
    showResults () {
      return store.assessInfo.show_results;
    },
    submitLabel () {
      let label = 'question.';
      if (store.assessInfo.submitby === 'by_question') {
        // by question submission
        label += 'submit';
      } else if (store.assessInfo.showscores === 'during') {
        // by assessment, show scores
        label += 'checkans';
      } else {
        // by assessment, with one try
        label += 'saveans';
      }
      if (this.hasSeqNext) {
        label += '_seqnext';
      } else if (this.hasSubmitAll) {
        label += '_submitall';
      }
      return this.$t(label);
    },
    showHelps () {
      return ((store.assessInfo.hasOwnProperty('help_features') && (
        store.assessInfo.help_features.message === true ||
        store.assessInfo.help_features.forum > 0)) ||
        (this.questionData.jsparams && this.questionData.jsparams.helps.length > 0));
    },
    errorsToShow () {
      let errors = [];
      if (store.assessInfo.hasOwnProperty('scoreerrors') &&
        store.assessInfo.scoreerrors.hasOwnProperty(this.qn)
      ) {
        errors = errors.concat(store.assessInfo.scoreerrors[this.qn]);
      }
      if (this.questionData.hasOwnProperty('errors')) {
        errors = errors.concat(this.questionData.errors);
      }
      return errors;
    },
    showWork () {
      return ((this.getwork === 1 && store.assessInfo.questions[this.qn].showwork & 1) ||
        (this.getwork === 2 && store.assessInfo.questions[this.qn].showwork & 2));
    }
  },
  methods: {
    loadQuestionIfNeeded (skiprender) {
      if (!this.questionContentLoaded && this.active && store.errorMsg === null) {
        actions.loadQuestion(this.qn, false, false);
      } else if (this.questionContentLoaded && this.active &&
        !this.questionData.rendered && skiprender !== true) {
        this.renderAndTrack();
      }
    },
    submitQuestion () {
      actions.submitQuestion(this.qn, false);
      // reset timeactive counter
      store.timeActive[this.qn] = 0;
      store.timeActivated[this.qn] = new Date();
    },
    jumpToAnswer () {
      store.confirmObj = {
        body: 'question.jump_warn',
        action: () => actions.loadQuestion(this.qn, false, true)
      };
    },
    updateTime (goingActive) {
      if (!store.timeActivated.hasOwnProperty(this.qn) || goingActive) {
        store.timeActivated[this.qn] = new Date();
      } else if (store.timeActivated.hasOwnProperty(this.qn)) {
        const now = new Date();
        store.timeActive[this.qn] += (now - store.timeActivated[this.qn]);
        delete store.timeActivated[this.qn]; // so it doesn't add more on submitall
      }
    },
    addDirtyTrackers () {
      window.$('#questionwrap' + this.qn).find('input[name],select[name],textarea[name]')
        .off('focus.dirtytrack').off('change.dirtytrack').off('input.dirtytrack')
        .on('focus.dirtytrack', function () {
          if (this.type === 'radio' || this.type === 'checkbox') {
            // focus doesn't make sense here
          } else {
            window.$(this).attr('data-lastval', window.$(this).val());
          }
          // actions.clearAutosaveTimer();
        })
        .on('input.dirtytrack', function () {
          store.somethingDirty = true;
        })
        .on('change.dirtytrack', function () {
          const val = window.$(this).val().trim();
          let changed = false;
          if (this.type === 'radio' || this.type === 'checkbox') {
            changed = true;
          } else if (val !== window.$(this).attr('data-lastval') && val !== '') {
            changed = true;
          }
          if (changed) {
            store.somethingDirty = true;
            const name = window.$(this).attr('name');
            const m = name.match(/^(qs|qn|tc)(\d+)/);
            if (m !== null) {
              var qn = m[2] * 1;
              var pn = 0;
              if (qn >= 1000) {
                pn = qn % 1000;
                qn = Math.floor(qn / 1000 + 0.001) - 1;
              }

              // autosave value
              const now = new Date();
              const timeactive = store.timeActive[qn] + (now - store.timeActivated[qn]);
              actions.doAutosave(qn, pn, timeactive);
            }
          }
        });
    },
    disableOutOfTries () {
      const trymax = this.questionData.tries_max;
      for (const pn in this.questionData.parts) {
        var regex;
        if (this.questionData.parts[pn].try >= trymax) {
          // out of tries - disable inputs
          if (Object.keys(this.questionData.parts).length === 1 && Object.keys(this.questionData.jsparams).length > 1) {
            // Only one "part" listed, but multiple input boxes.
            // Probably conditional. Disable all boxes
            regex = new RegExp('^(qn|tc|qs)(' + (this.qn) + '\\b|' + (this.qn + 1) + '\\d{3}\\b)');
          } else if (pn === 0) {
            regex = new RegExp('^(qn|tc|qs)(' + (this.qn) + '\\b|' + ((this.qn + 1) * 1000 + pn * 1) + '\\b)');
          } else {
            regex = new RegExp('^(qn|tc|qs)' + ((this.qn + 1) * 1000 + pn * 1) + '\\b');
          }
          window.$('#questionwrap' + this.qn).find('input,select,textarea').each(function (i, el) {
            if (el.name.match(regex)) {
              el.disabled = true;
            }
          });
        }
      }
    },
    renderAndTrack () {
      if (this.questionData.rendered || !this.active) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.thisqwrap);
      window.initSageCell(this.$refs.thisqwrap);
      window.initlinkmarkup(this.$refs.thisqwrap);
      this.updateTime(true);
      this.setInitValues();
      // add in timeactive from autosave, if exists
      store.timeActive[this.qn] += actions.getInitTimeactive(this.qn);
      this.addDirtyTrackers();
      // set work
      this.work = this.questionData.work;
      window.$('#questionwrap' + this.qn).find('.seqsep')
        .attr('aria-level', store.assessInfo.displaymethod === 'full' ? 3 : 2);
      if (this.disabled) {
        window.$('#questionwrap' + this.qn).find('input,select,textarea').each(function (i, el) {
          if (el.name.match(/^(qn|tc|qs)\d/)) {
            el.disabled = true;
          }
        });
      };

      window.imathasAssess.init(this.questionData.jsparams, store.enableMQ, this.$refs.thisqwrap);

      actions.setRendered(this.qn, true);
    },
    setInitValues () {
      var regex = new RegExp('^(qn|tc|qs)\\d');
      var thisqn = this.qn;
      window.$('#questionwrap' + this.qn).find('input,select,textarea')
        .each(function (index, el) {
          if (el.name.match(regex)) {
            if (el.type === 'radio' || el.type === 'checkbox') {
              if (el.checked) {
                actions.setInitValue(thisqn, el.name, el.value);
              }
            } else {
              actions.setInitValue(thisqn, el.name, window.$(el).val());
            }
          }
        });
      if (this.showWork) {
        actions.setInitValue(thisqn, 'sw' + this.qn, this.questionData.work);
        this.work = this.questionData.work;
      }
    },
    updateWork (val) {
      this.work = val;
    },
    workChanged () {
      // changed - cue for autosave
      if (this.work !== this.lastWorkVal) {
        store.work[this.qn] = this.work;
        // autosave value
        if (this.getwork === 1) {
          const now = new Date();
          const timeactive = store.timeActive[this.qn] + (now - store.timeActivated[this.qn]);
          actions.doAutosave(this.qn, 'sw', timeactive);
        } else if (this.getwork === 2) {
          this.$emit('workchanged', this.work);
        }
      }
    },
    workFocused () {
      this.lastWorkVal = this.work;
    },
    trySimilar () {
      actions.loadQuestion(this.qn, true);
    }
  },
  updated () {
    if (this.questionContentLoaded) {
      this.disableOutOfTries();
      this.renderAndTrack();
    } else {
      this.loadQuestionIfNeeded();
    }
  },
  created () {
    this.loadQuestionIfNeeded(true);
    if (!store.timeActive.hasOwnProperty(this.qn)) {
      store.timeActive[this.qn] = 0;
    }
  },
  mounted () {
    if (this.questionContentLoaded) {
      this.disableOutOfTries();
      this.renderAndTrack();
    }
  },
  beforeDestroy () {
    actions.setRendered(this.qn, false);
  },
  watch: {
    active: function (newVal, oldVal) {
      this.loadQuestionIfNeeded();
      this.updateTime(newVal);
      if (newVal === false) {
        pauseVideos(this.$refs.main);
      }
    },
    state: function (newVal, oldVal) {
      if ((newVal > 1 && oldVal <= 1) ||
          (newVal === 4 && oldVal < 4) ||
          (newVal === 3 && oldVal === 4)
      ) {
        // force reload
        actions.loadQuestion(this.qn, false, false);
      }
    },
    qn: function (newVal, oldVal) {
      actions.setRendered(oldVal, false);
    },
    seed: function (newVal, oldVal) {
      actions.loadQuestion(this.qn, false, false);
    }
  }
};
</script>
<style>
input[type=text] {
  height: 20px;
}
.haseqneditor {
  margin-right: 0;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  height: 20px;
}
.eqneditortrigger {
  margin: 0;
  border-left: 0;
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  height: 30px;
  padding: 4px;
  vertical-align: bottom;
}
input.green {
  margin-left: 0;
  border-color: #090;
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
input.red {
  margin-left: 0;
  border-color: #900;
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
.scoremark {
  display: inline-block;
  height: 20px;
  padding: 4px;
  margin-right: 0;
  border: 1px solid;
  border-right: 0;
  border-radius: 4px 0 0 4px;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  height: 20px;
}
.scoremark.red {
  border-color: #900;
  color: #900;
}
.scoremark.green {
  border-color: #090;
  color: #090;
}
.submitbtnwrap {
  margin: 16px 0;
}
.ansgrn {
  border: 1px solid #090 !important;
}
.ansred {
  border: 1px solid #900 !important;
}
.ansyel {
  border: 1px solid #fb0 !important;
}
.ansorg {
  border: 1px solid #f50 !important;
}
div.ansgrn, div.ansred, div.ansyel, div.ansorg {
  margin: -1px;
}
input[type=text].ansgrn, .mathquill-math-field.ansgrn,
input[type=text].ansred, .mathquill-math-field.ansred,
input[type=text].ansyel, .mathquill-math-field.ansyel,
input[type=text].ansorg, .mathquill-math-field.ansorg {
  background-repeat: no-repeat;
  background-position: right;
}
input[type=text].ansgrn, .mathquill-math-field.ansgrn {
  padding-right: 17px;
  background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBzdHJva2U9ImdyZWVuIiBzdHJva2Utd2lkdGg9IjMiIGZpbGw9Im5vbmUiPjxwb2x5bGluZSBwb2ludHM9IjIwIDYgOSAxNyA0IDEyIj48L3BvbHlsaW5lPjwvc3ZnPg==");
}
input[type=text].ansred, .mathquill-math-field.ansred {
  padding-right: 17px;
  background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBzdHJva2U9InJnYigxNTMsMCwwKSIgc3Ryb2tlLXdpZHRoPSIzIiBmaWxsPSJub25lIj48cGF0aCBkPSJNMTggNiBMNiAxOCBNNiA2IEwxOCAxOCIgLz48L3N2Zz4=");
}
input[type=text].ansyel, .mathquill-math-field.ansyel {
  padding-right: 17px;
  background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBzdHJva2U9InJnYigyNTUsMTg3LDApIiBzdHJva2Utd2lkdGg9IjMiIGZpbGw9Im5vbmUiPjxwYXRoIGQ9Ik0gNS4zLDEwLjYgOSwxNC4yIDE4LjUsNC42IDIxLjQsNy40IDksMTkuOCAyLjcsMTMuNSB6IiAvPjwvc3ZnPg==");
}
input[type=text].ansorg, .mathquill-math-field.ansorg {
  padding-right: 17px;
  background: right no-repeat url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBzdHJva2U9InJnYigyNTUsODUsMCkiIHN0cm9rZS13aWR0aD0iMyIgZmlsbD0ibm9uZSI+PHBhdGggZD0iTTE4IDYgTDYgMTggTTYgNiBMMTggMTgiIC8+PC9zdmc+");
}
</style>
