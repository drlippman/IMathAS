<template>
  <div class="home">
    <a href="#" class="sr-only" id="skipnav" @click.prevent="$refs.scrollpane.focus()">
      {{ $t('jumptocontent') }}
    </a>
    <assess-header></assess-header>
    <p v-if="isPreviewAll" class="headerpane noticetext">
      {{ $t("header.preview_all") }}
      <button
        type="button"
        class = "secondary"
        @click="showAllAns"
      >
        {{ $t("gradebook.show_all_ans") }}
      </button>
      <button
        type="button"
        class = "secondary"
        @click="showTexts = !showTexts"
      >
        {{ textToggleLabel }}
      </button>
      <a :href="settingsLink">{{ $t("links.settings") }}</a>&nbsp;
      <a :href="questionsLink">{{ $t("links.questions") }}</a>
    </p>
    <div
      class="scrollpane fulldisp"
      role="region"
      ref="scrollpane"
      tabindex="-1"
      :aria-label="$t('regions.questions')"
    >
      <intro-text
        v-if = "hasIntro"
        :active = "showTexts"
        :html = "intro"
      />

      <div
        v-for="curqn in questionArray" :key="curqn"
      >
        <inter-question-text-list
          v-show="showTexts"
          pos="beforeexact"
          :qn="curqn"
          :key="'iqt'+curqn"
          :textlist = "textList"
          :lastq = "lastQ"
          :active = "showTexts"
        />
        <full-question-header :qn = "curqn" />
        <question
          :qn="curqn"
          active="true"
          :key="'q'+curqn"
          :getwork="1"
        />
      </div>
      <inter-question-text-list
        v-show="showTexts"
        pos="after"
        :qn="lastQ"
        :textlist = "textList"
        :lastq = "lastQ"
        :active = "showTexts"
      />
    </div>
    <p v-if = "showSubmit">
      <button
        type = "button"
        class = "primary"
        @click = "submitAssess"
      >
        {{ $t('header.assess_submit') }}
      </button>
    </p>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import IntroText from '@/components/IntroText.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'Full',
  data: function () {
    return {
      showTexts: true
    };
  },
  components: {
    Question,
    AssessHeader,
    FullQuestionHeader,
    InterQuestionTextList,
    IntroText
  },
  computed: {
    intro () {
      return store.assessInfo.intro;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0);
    },
    isPreviewAll () {
      return !!store.assessInfo.preview_all;
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
    showSubmit () {
      return (store.assessInfo.submitby === 'by_assessment');
    },
    textToggleLabel () {
      return this.showTexts ? this.$t('print.hide_text') : this.$t('print.show_text');
    },
    textList () {
      if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
        return [];
      } else {
        return store.assessInfo.interquestion_text;
      }
    },
    settingsLink () {
      return '../course/addassessment2.php?id=' + store.aid + '&cid=' + store.cid;
    },
    questionsLink () {
      return '../course/addquestions2.php?aid=' + store.aid + '&cid=' + store.cid;
    }
  },
  methods: {
    submitAssess () {
      actions.submitAssessment();
    },
    showAllAns () {
      window.$("span[id^='ans']").removeClass('hidden').toggle();
      window.$('.keybtn').attr('aria-expanded', function (i, v) { return !JSON.parse(v); });
    }
  }
};
</script>

<style>

</style>
