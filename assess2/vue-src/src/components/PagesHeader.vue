<template>
  <div class="subheader">
    <menu-button id="qnav"
      :options = "navOptions"
      :selected = "disppage"
      searchby = "title"
    >
      <template v-slot="{ option }">
        <pages-list-item :option="option" />
      </template>
    </menu-button>

    <router-link
      :to="'/full/page/'+ (disppage-1) + queryString"
      tag="button"
      :disabled="page < (this.hasIntro ? 0 : 1)"
      class="secondarybtn"
      id="qprev"
      :aria-label="$t('previous')"
      v-if = "showNextPrev"
    >
      <icons name="left"/>
    </router-link>
    <router-link
      :to="'/full/page/'+ (disppage+1) + queryString"
      tag="button"
      :disabled="page>=pagesData.length-1"
      class="secondarybtn"
      id="qnext"
      :aria-label="$t('next')"
      v-if = "showNextPrev"
    >
      <icons name="right" />
    </router-link>
  </div>
</template>

<script>
import MenuButton from '@/components/MenuButton.vue';
import PagesListItem from '@/components/PagesListItem.vue';
import Icons from '@/components/Icons.vue';
import { store } from '../basicstore';

export default {
  name: 'PagesHeader',
  props: ['page'],
  components: {
    Icons,
    MenuButton,
    PagesListItem
  },
  data: function () {
    return {

    };
  },
  computed: {
    disppage () {
      return parseInt(this.page) + 1;
    },
    pagesData () {
      return store.assessInfo.interquestion_pages;
    },
    queryString () {
      return store.queryString;
    },
    hasIntro () {
      return (store.assessInfo.intro !== '');
    },
    navOptions () {
      var out = {};
      if (this.hasIntro) {
        out[0] = {
          internallink: '/full/page/0' + this.queryString,
          disppage: 0
        };
      }
      for (let pn in this.pagesData) {
        let disppage = parseInt(pn) + 1;
        let numAttempted = 0;
        for (let i = 0; i < this.pagesData[pn][0].questions.length; i++) {
          let qn = this.pagesData[pn][0].questions[i];
          if (store.assessInfo.questions[qn].status !== 'unattempted') {
            numAttempted++;
          }
        }
        out[disppage] = {
          disppage: disppage,
          title: this.pagesData[pn][0].pagetitle,
          internallink: '/full/page/' + disppage + this.queryString,
          numquestions: this.pagesData[pn][0].questions.length,
          numattempted: numAttempted
        };
      }
      return out;
    },
    showNextPrev () {
      return (Object.keys(this.navOptions).length > 1);
    }
  }
};
</script>

<style>
.subheader {
  display: flex;
  flex-flow: row wrap;
  border-bottom: 1px solid #ccc;
}
.subheader > * {
  margin: 4px 0;
}
</style>
