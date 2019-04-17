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
  </div>
</template>

<script>
import { store, actions } from '../../basicstore';
import ScoreResult from '@/components/question/ScoreResult.vue';
import Icons from '@/components/widgets/Icons.vue';
import QuestionHelps from '@/components/question/QuestionHelps.vue';

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
    canJumpToAnswer () {
      return (this.questionData.jump_to_answer);
    },
    questionContentLoaded () {
      return (this.questionData.html !== null);
    },
    showSubmit () {
      return (store.inProgress &&
        this.questionContentLoaded &&
        !store.inPrintView &&
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
        store.assessInfo.show_results &&
        (this.questionData.try > 0 ||
          this.questionData.hasOwnProperty('tries_remaining_range')) &&
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
        actions.loadQuestion(this.qn, false, false);
      }
    },
    submitQuestion () {
      this.updateTime(false);
      actions.submitQuestion(this.qn, false, this.timeActive);
    },
    jumpToAnswer () {
      if (confirm(this.$t('question.jump_warn'))) {
        actions.loadQuestion(this.qn, false, true);
      }
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
      .off('focus.dirtytrack').off('change.dirtytrack')
      .on('focus.dirtytrack', function() {
        // TODO: Does this work for checkboxes/radios?
        if (this.type === 'radio' || this.type === 'checkbox') {
          window.$(this).attr('data-lastval', this.checked?1:0);
        } else {
          window.$(this).attr('data-lastval', window.$(this).val());
        }

        actions.clearAutosaveTimer();
      })
      .on('change.dirtytrack', function() {
        let val = window.$(this).val();
        let changed = false;
        if (this.type === 'radio' || this.type === 'checkbox') {
          if ((this.checked === true) !== (this.getAttribute('data-lastval') === '1')) {
            changed = true;
          }
        } else if (this.type !== 'file' && val != window.$(this).attr('data-lastval')) {
          changed = true;
        }
        if (changed) {
          let name = window.$(this).attr("name");
          let m = name.match(/^(qs|qn|tc)(\d+)/);
          if (m !== null) {
            var qn = m[2]*1;
            var pn = 0;
            if (qn>1000) {
              pn = qn%1000;
              qn = Math.floor(qn/1000 + .001)-1;
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
      if (this.questionData.rendered) {
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(document.getElementById('questionwrap' + this.qn));
      this.updateTime(true);
      this.setInitValues();
      this.addDirtyTrackers();
      this.initShowAnswer();
      window.imathasAssess.init(this.questionData.jsparams);
      actions.setRendered(this.qn);
    },
    setInitValues() {
      var regex = new RegExp("^(qn|tc|qs)\\d");
      var thisqn = this.qn;
      window.$('#questionwrap' + this.qn).find('input,select,textarea')
        .each(function(index, el) {
          if (el.name.match(regex)) {
            if (el.type === 'radio' || el.type === 'checked') {
              actions.setInitValue(thisqn, el.name, el.checked?1:0);
            } else {
              actions.setInitValue(thisqn, el.name, $(el).val());
            }
          }
        });
    },
    initShowAnswer() {
      let $ = window.$;
    	$("input.sabtn + span.hidden").attr("aria-hidden",true).attr("aria-expanded",false);
    	$("input.sabtn").each(function() {
    		var idnext = $(this).siblings("span:first-of-type").attr("id");
    		$(this).attr("aria-expanded",false).attr("aria-controls",idnext)
    		  .off("click.sashow").on("click.sashow", function() {
    			$(this).attr("aria-expanded",true)
    		  	  .siblings("span:first-of-type")
    				.attr("aria-expanded",true).attr("aria-hidden",false)
    				.removeClass("hidden");
    		});
    	});
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
