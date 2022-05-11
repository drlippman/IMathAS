<template>
  <div class="home">
    <a href="#" class="sr-only" id="skipnav" @click.prevent="$refs.scrollpane.focus()">
      {{ $t('jumptocontent') }}
    </a>
    <assess-header />
    <full-paged-nav :page="page" />
    <div
      class="scrollpane"
      role="region"
      ref="scrollpane"
      tabindex="-1"
      :aria-label="$t('regions.questions')"
    >
      <intro-text
        :active = "page === -1 && hasIntro"
        :html = "intro"
      />
      <div
        v-for = "(pageData,pagenum) in allPages"
        :key = "pagenum"
        :class="{inactive: pagenum !== page}"
        :aria-hidden = "pagenum !== page"
      >
        <div v-if = "pageData[0].questions.length === 0" class="noqtext">
          <inter-question-text-list
            pos="all"
            :textlist = "pageData"
            :lastq = "lastQ[pagenum]"
            :active = "pagenum === page"
            :key="'iqtp'+pagenum"
          />
        </div>
        <div v-else>
          <div
            v-for="curqn in pageData[0].questions" :key="curqn"
            class="fullpaged"
          >
            <inter-question-text-list
              pos="beforeexact"
              :qn="curqn"
              :key="'iqt'+curqn"
              :textlist = "pageData"
              :lastq = "lastQ[pagenum]"
              :active = "pagenum === page"
            />
            <div>
              <full-question-header :qn = "curqn" />
              <question
                :qn="curqn"
                :active = "pagenum === page"
                :key="'q'+curqn"
                :getwork="1"
              />
            </div>
          </div>

          <inter-question-text-list
            id="aftertext"
            pos="after"
            :qn="pageData[0].questions[pageData[0].questions.length - 1]"
            :key="'iqte'+pagenum"
            :textlist = "pageData"
            :lastq = "lastQ[pagenum]"
            :active = "pagenum === page"
          />
        </div>
      </div>
      <div v-if = "page < allPages.length - 1">
        <p>&nbsp;</p>
        <p>
          <router-link
            :to="'/full/page/'+ (page+2)"
          >
            {{ $t('pages.next') }}
          </router-link>
        </p>
      </div>
      <p v-else-if = "showSubmit">
        <button
          type = "button"
          class = "primary"
          @click = "submitAssess"
        >
          {{ $t('header.assess_submit') }}
        </button>
      </p>
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import FullPagedNav from '@/components/FullPagedNav.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import IntroText from '@/components/IntroText.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'FullPaged',
  components: {
    Question,
    AssessHeader,
    FullPagedNav,
    FullQuestionHeader,
    InterQuestionTextList,
    IntroText
  },
  computed: {
    page () {
      return parseInt(this.$route.params.page) - 1;
    },
    allPages () {
      return store.assessInfo.interquestion_pages;
    },
    intro () {
      return store.assessInfo.intro;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0);
    },
    showSubmit () {
      return (store.assessInfo.submitby === 'by_assessment');
    },
    lastQ () {
      const out = [];
      let qlist;
      for (const i in this.allPages) {
        qlist = this.allPages[i][0].questions;
        out[i] = qlist[qlist.length - 1];
      }
      return out;
    }
  },
  methods: {
    submitAssess () {
      actions.submitAssessment();
    }
  }
};
</script>

<style>

</style>
