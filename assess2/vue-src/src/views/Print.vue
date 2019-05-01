<template>
  <div class="home print">
    <div class="assess-header headerpane">
      <h1 style="flex-grow:1">
        {{ ainfo.name }}
      </h1>
      <div>
        {{ ainfo.userfullname }}
      </div>
    </div>
    <p class="loudnotice hideonprint">
      {{ $t('print.print_version') }}
      <button
        type = "button"
        @click = "doPrint"
      >
        {{ $t('print.print') }}
      </button>
    </p>
    <p class="hideonprint">
      <button
        type = "button"
        @click = "showTexts = !showTexts"
      >
        {{ textToggleLabel }}
      </button>
      <button
        type = "button"
        @click = "showQs = !showQs"
      >
        {{ qToggleLabel }}
      </button>
    </p>
    <div class="scrollpane">
      <div
        v-if = "intro != '' && showTexts"
        v-html = "intro"
      />
      <div>
        <div
          v-for="curqn in questionArray" :key="curqn"
        >
          <inter-question-text-list
            pos="beforeexact"
            :qn="curqn"
            :key="'iqt'+curqn"
            v-show = "showTexts"
            :active = "showTexts"
          />
          <full-question-header
            :qn = "curqn"
            v-if = "showQs"
          />
          <question
            v-show = "showQs"
            class = "med-left"
            :qn="curqn"
            :active = "showQs"
            :key="'q'+curqn"
          />
        </div>

        <inter-question-text-list
          pos="after"
          :qn="lastQ"
          :active = "showTexts"
          v-show = "showTexts"
        />

      </div>
    </div>
  </div>
</template>

<script>
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import { store } from '../basicstore';

export default {
  name: 'Print',
  components: {
    Question,
    FullQuestionHeader,
    InterQuestionTextList
  },
  data: function () {
    return {
      showTexts: true,
      showQs: true
    };
  },
  computed: {
    ainfo () {
      return store.assessInfo;
    },
    intro () {
      return store.assessInfo.intro;
    },
    questionArray () {
      let qnArray = {};
      for (let i = 0; i < store.assessInfo.questions.length; i++) {
        qnArray[i] = i;
      }
      return qnArray;
    },
    lastQ () {
      return store.assessInfo.questions.length - 1;
    },
    textToggleLabel () {
      return this.showTexts ? this.$t('print.hide_text') : this.$t('print.show_text');
    },
    qToggleLabel () {
      return this.showQs ? this.$t('print.hide_qs') : this.$t('print.show_qs');
    }
  },
  methods: {
    doPrint () {
      window.print();
    }
  }
};
</script>

<style>
@media print {
  .hideonprint, .togglebtn, .question-details {
    display: none;
  }
}
.inactive {
  visibility: hidden;
  position: absolute;
}
.questionpane {
  margin: 15px 15px;
  max-width: 700px;
  overflow: visible;
}
.scrollpane {
  width: 100%;
  overflow-x: auto;
}
.loudnotice {
  background-color: #369;
  color: #fff;
  padding: 4px 8px;
}
</style>
