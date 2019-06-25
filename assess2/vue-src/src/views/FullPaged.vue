<template>
  <div class="home">
    <assess-header />
    <full-paged-nav :page="page" />
    <div class="scrollpane" role="region" :aria-label="$t('regions.questions')">
      <div
        class = "questionpane introtext"
        v-show = "page === -1 && intro !== ''"
      >
        <h2>{{ $t('intro') }}</h2>
        <div
          v-html = "intro"
          ref = "introtext"
        />
      </div>
      <div
        v-for = "(pageData,pagenum) in allPages"
        :key = "pagenum"
        :class="{inactive: pagenum !== page}"
        :aria-hidden = "pagenum !== page"
      >
        <div v-if = "pageData[0].questions.length === 0">
          <inter-question-text-list
            pos="all"
            :page="pagenum"
            :active = "pagenum === page"
          />
        </div>
        <div v-else>
          <div
            v-for="curqn in pageData[0].questions" :key="curqn"
          >
            <inter-question-text-list
              pos="beforeexact"
              :qn="curqn"
              :key="'iqt'+curqn"
              :page="pagenum"
              :active = "pagenum === page"
            />
            <full-question-header :qn = "curqn" />
            <question
              :qn="curqn"
              :active = "pagenum === page"
              :key="'q'+curqn"
            />
          </div>

          <inter-question-text-list
            pos="after"
            :qn="pageData[0].questions[pageData[0].questions.length - 1]"
            :page="pagenum"
            :active = "pagenum === page"
          />
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
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import FullPagedNav from '@/components/FullPagedNav.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/question/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import { store, actions } from '../basicstore';

export default {
  name: 'FullPaged',
  components: {
    Question,
    AssessHeader,
    FullPagedNav,
    FullQuestionHeader,
    InterQuestionTextList
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
    showSubmit () {
      return (store.assessInfo.submitby === 'by_assessment');
    }
  },
  methods: {
    submitAssess () {
      actions.submitAssessment();
    }
  },
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
  }
};
</script>

<style>

</style>
