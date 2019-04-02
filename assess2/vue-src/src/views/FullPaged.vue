<template>
  <div class="home">
    <assess-header />
    <pages-header :page="page" />
    <div class="scrollpane">
      <div
        class = "questionpane"
        v-show = "page === 0 && intro !== ''"
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
        :class="{inactive: pagenum !== page, questionpane: true}"
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
              class = "med-left"
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
      </div>
    </div>
  </div>
</template>

<script>
import AssessHeader from '@/components/AssessHeader.vue';
import PagesHeader from '@/components/PagesHeader.vue';
import FullQuestionHeader from '@/components/FullQuestionHeader.vue';
import Question from '@/components/Question.vue';
import InterQuestionTextList from '@/components/InterQuestionTextList.vue';
import { store } from '../basicstore';

export default {
  name: 'FullPaged',
  components: {
    Question,
    AssessHeader,
    PagesHeader,
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
    }
  },
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
  }
};
</script>

<style>
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
</style>
