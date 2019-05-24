<template>
  <div class="scoredetails">
    <div>
      {{ $t('gradebook.score') }}:
      <span
        v-for="(poss,i) in partPoss"
        :key="i"
      >
        <input
          type="text"
          size="4"
          v-model="curScores[i]"
          @input="updateScore(i, $event)"
        />/{{ poss }}
      </span>
      <button
        v-if="showfeedback === false"
        type="button"
        class="slim"
        @click="showfeedback = true"
      >
        {{ $t('gradebook.add_feedback') }}
      </button>
    </div>
    <div
      v-show="showfeedback"
    >
      {{ $t('gradebook.feedback') }}:<br/>
      <textarea
        v-if="!useEditor"
        class="fbbox"
        rows="2"
        cols="60"
        @input="updateFeedback"
      >{{ qdata.feedback }}</textarea>
      <div
        v-else
        rows="2"
        class="fbbox"
        v-html="qdata.feedback"
        @input="updateFeedback"
      />
    </div>
    <div>
      <button
        type="button"
        @click="allFull"
        class="slim"
      >
        {{ fullCreditLabel }}
      </button>
    </div>
    <div v-if="qdata.timeactive.total > 0">
      {{ $t('gradebook.time_on_version') }}:
      {{ timeSpent }}
    </div>


  </div>
</template>

<script>
export default {
  name: 'GbScoreDetails',
  props: ['qdata', 'qn'],
  data: function() {
    return {
      curScores: false,
      showfeedback: false
    }
  },
  computed: {
    answeights() {
      if (!this.qdata.answeights) { // if answeights not generated yet
        return [1];
      } else {
        return this.qdata.answeights;
      }
    },
    partPoss() {
      var out = [];
      for (let i=0; i<this.answeights.length; i++) {
        out[i] = Math.round(1000*this.qdata.points_possible*this.answeights[i])/1000;
      }
      return out;
    },
    initScores() {
      var out = [];
      let partscore;
      for (let i=0; i<this.answeights.length; i++) {
        // handle the case of a single override
        if (this.qdata.scoreoverride && typeof this.qdata.scoreoverride !== 'object') {
          let partscore = this.qdata.scoreoverride * this.answeights[i]/qdata.points_possible;
          partscore = Math.round(1000*partscore)/1000;
          out.push(partscore);
        } else if (this.qdata.scoreoverride) {
          out.push(this.qdata.scoreoverride[i]);
        } else {
          out.push(this.qdata.parts[i].score);
        }
      }
      return out;
    },
    fullCreditLabel() {
      if (this.answeights.length > 1) {
        return this.$t('gradebook.full_credit_parts');
      } else {
        return this.$t('gradebook.full_credit');
      }
    },
    timeSpent() {
      let out = this.$tc('minutes', Math.round(10*this.qdata.timeactive.total/60)/10);
      // TODO: Add per-try average?
      return out;
    },
    useEditor() {
      return (typeof window.tinyMCE !== 'undefined');
    }
  },
  methods: {
    updateScore(pn, evt) {
      this.$emit('updatescore', this.qn, pn, this.curScores[pn]);
    },
    updateFeedback(evt) {
      let content;
      if (this.useEditor) {
        content = window.tinymce.activeEditor.getContent();
      } else {
        content = evt.target.value;
      }
      this.$emit('updatefeedback', this.qn, content);
    },
    allFull() {
      for (let i=0; i<this.answeights.length; i++) {
        this.$set(this.curScores, i, this.partPoss[i]);
        this.$emit('updatescore', this.qn, i, this.curScores[i]);
      }
    },
    initCurScores () {
      this.$set(this, 'curScores', this.initScores);
      this.showfeedback = (this.qdata.feedback !== null && this.qdata.feedback.length > 0);
      if (this.useEditor) {
        window.initeditor("divs","fbbox",null,true);
      }
    }
  },
  mounted () {
    this.initCurScores();
  },
  watch: {
    qdata: function (newVal, oldVal) {
      this.initCurScores();
    }
  }
}
</script>

<style>
.scoredetails {
  border: 1px solid #ccc;
  padding: 10px;
  margin-bottom:16px;
}
</style>
