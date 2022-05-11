<template>
  <div class="home print">
    <div class="assess-header headerpane">
      <h1 style="flex-grow:1">
        {{ ainfo.name }}
      </h1>
      <div>
        <span class="pii-full-name">{{ ainfo.userfullname }}</span>
      </div>
    </div>
    <p class="hideonprint headerpane">
      <strong>
        {{ $t('print.print_version') }}
      </strong>
      <button
        type = "button"
        class = "primary"
        @click = "doPrint"
      >
        {{ $t('print.print') }}
      </button>
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
      <intro-text
        v-if = "intro !== ''"
        :active = "showTexts"
        :html = "intro"
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
            :textlist = "textList"
            :lastq = "lastQ"
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
            :disabled = "true"
          />
        </div>

        <inter-question-text-list
          pos="after"
          :qn="lastQ"
          :active = "showTexts"
          v-show = "showTexts"
          :textlist = "textList"
          :lastq = "lastQ"
        />

      </div>
    </div>
  </div>
</template>

<script>
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import IntroText from '@/components/IntroText.vue';
import { store } from '../basicstore';

export default {
  name: 'Print',
  components: {
    Question,
    FullQuestionHeader,
    InterQuestionTextList,
    IntroText
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
      const qnArray = {};
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
    },
    textList () {
      if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
        return [];
      } else {
        return store.assessInfo.interquestion_text;
      }
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
</style>
