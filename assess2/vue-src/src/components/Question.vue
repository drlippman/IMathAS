<template>
  <div class = "questionwrap">
    <div v-if = "!questionContentLoaded">
      {{ $t('loading') }}
    </div>
    <score-result
      v-if = "showScore"
      :qdata = "questionData"
      :qn = "qn"
    />
    <p
      v-if="questionData.withdrawn !== 0"
      class="noticetext"
    >
      <icons name="alert" color="warn" size="medium" />
      {{ $t('question.withdrawn') }}
    </p>
    <div
      v-if = "questionContentLoaded"
      v-html="questionData.html"
      class = "question"
      :id="'questionwrap' + qn"
    />
    <question-helps
      v-if = "questionData.hasOwnProperty('help_features')"
      :qn = "qn"
    />
    <div v-if="showSubmit" class="submitbtnwrap">
      <button
        type = "button"
        @click = "submitQuestion"
        class = "primary"
        :disabled = "!canSubmit"
      >
        {{ submitLabel }}
      </button>
    </div>
  </div>
</template>

<script>
import { store, actions } from '../basicstore';
import ScoreResult from '@/components/ScoreResult.vue';
import Icons from '@/components/Icons.vue';
import QuestionHelps from '@/components/QuestionHelps.vue';

export default {
  name: 'Question',
  props: ['qn', 'active'],
  components: {
    ScoreResult,
    QuestionHelps,
    Icons
  },
  data: function () {
    return {
        timeActivated: null,
        timeActive: 0
    }
  },
  computed: {
    questionData () {
      return store.assessInfo.questions[this.qn];
    },
    canSubmit () {
      return (!store.inTransit);
    },
    questionContentLoaded () {
      return (this.questionData.html !== null);
    },
    showSubmit () {
      return (store.inProgress &&
        this.questionContentLoaded &&
        this.questionData.withdrawn === 0 &&
        this.questionData.canretry && (
        store.assessInfo.submitby === 'by_question' ||
          this.questionData.tries_max > 1
        )
      );
    },
    showScore () {
      return (store.inProgress &&
        this.questionData.hasOwnProperty('score') &&
        this.questionData.status !== 'unattempted' &&
        this.questionData.withdrawn === 0
      );
    },
    submitLabel () {
      if (store.assessInfo.submitby === 'by_question') {
        // by question submission
        return this.$t('question.submit');
      } else if (this.questionData.tries_max === 1) {
        // by assessment, with one try
        return this.$t('question.saveans');
      } else {
        // by assessment, can retry
        return this.$t('question.checkans');
      }
    }
  },
  methods: {
    loadQuestionIfNeeded () {
      if (!this.questionContentLoaded && this.active && store.errorMsg===null) {
        actions.loadQuestion(this.qn, false);
      }
    },
    submitQuestion () {
      this.updateTime(false);
      actions.submitQuestion(this.qn, false, this.timeActive);
    },
    updateTime (goingActive) {
      if (this.timeActivated === null || goingActive) {
        this.timeActivated = new Date();
      } else if (this.timeActivated !== null) {
        let now = new Date();
        this.timeActive += (now - this.timeActivated);
      }
    },
    addDirtyTrackers () {
      window.$('#questionwrap' + this.qn).find('input,select,textarea')
      .off('focus.dirtrytrack').off('change.dirtrytrack')
      .on('focus.dirtrytrack', function() {
        window.$(this).attr('data-lastval', window.$(this).val());
        actions.clearAutosaveTimer();
      })
      .on('change.dirtrytrack', function() {
        let val = window.$(this).val();
        if (val != window.$(this).attr('data-lastval')) {
          let name = window.$(this).attr("name");
          let m = name.match(/^(qs|qn|tc)(\d+)/);
          if (m !== null) {
            var qn = m[2]*1;
            var pn = 0;
            if (qn>1000) {
              pn = qn%1000;
              qn = Math.floor(qn/1000 + .001)-1;
            }
            // mark as dirty for later submission
            if (store.assessFormIsDirty.indexOf(qn)==-1) {
              store.assessFormIsDirty.push(qn);
            }
            // autosave value
            actions.doAutosave(qn, pn);
          }
        }
      });
    },
    disableOutOfTries () {
      let trymax = this.questionData.tries_max;
      for (let pn in this.questionData.parts) {
        var regex;
        if (this.questionData.parts[pn].try >= trymax) {
          // out of tries - disable inputs
          if (pn === 0) {
            regex = new RegExp("^(qn|tc|qs)("+(this.qn)+"\\b|"+((this.qn+1)*1000 + pn*1)+"\\b)");
          } else {
            regex = new RegExp("^(qn|tc|qs)"+((this.qn+1)*1000 + pn*1)+"\\b");
          }
          window.$("#questionwrap" + this.qn).find("input,select,textarea").each(function(i,el) {
            if (el.name.match(regex)) {
              el.disabled = true;
            }
          });
        }
      }
    },
    renderAndTrack () {
      setTimeout(window.drawPics, 100);
      window.rendermathnode(document.getElementById('questionwrap' + this.qn));
      this.updateTime(true);
      this.addDirtyTrackers();
    }
  },
  updated () {
    if (this.questionContentLoaded) {
      this.renderAndTrack();
      this.disableOutOfTries();
    } else {
      this.loadQuestionIfNeeded();
    }
  },
  created () {
    this.loadQuestionIfNeeded();
  },
  mounted () {
    if (this.questionContentLoaded) {
      this.renderAndTrack();
      this.disableOutOfTries();
    }
  },
  watch: {
    active: function (newVal, oldVal) {
      this.loadQuestionIfNeeded();
      this.updateTime(newVal);
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
.questionwrap .question {
  border: 0;
  background-color: #fff;
  margin: 12px 0;
}
.submitbtnwrap {
  margin: 16px 0;
}
.ansgrn {
  border: 1px solid #090;
}
.ansred {
  border: 1px solid #900;
}
.ansyel {
  border: 1px solid #fb0;
}
</style>
