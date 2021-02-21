<template>
  <div class="subheader pagenav" role="navigation" :aria-label="$t('regions.pagenav')">
    <menu-button id="qnav"
      :options = "navOptions"
      :selected = "disppage"
      searchby = "title"
    >
      <template v-slot="{ option, selected }">
        <full-paged-list-item :option="option" :selected="selected"/>
      </template>
    </menu-button>

    <router-link
      :to="'/full/page/'+ (disppage-1)"
      custom
      v-slot="{ navigate }"
      v-if = "showNextPrev"
    >
      <button
        type="button"
        @click="navigate"
        @keypress.enter="navigate"
        role="link"
        :disabled="page < (this.hasIntro ? 0 : 1)"
        class="secondarybtn"
        id="qprev"
        :aria-label="$t('previous')"
      >
        <icons name="left"/>
      </button>
    </router-link>
    <router-link
      :to="'/full/page/'+ (disppage+1)"
      custom
      v-slot="{ navigate }"
      v-if = "showNextPrev"
    >
      <button
        type="button"
        @click="navigate"
        @keypress.enter="navigate"
        role="link"
        :disabled="page>=pagesData.length-1"
        class="secondarybtn"
        id="qnext"
        :aria-label="$t('next')"
      >
        <icons name="right" />
      </button>
    </router-link>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import FullPagedListItem from '@/components/FullPagedListItem.vue';
import Icons from '@/components/widgets/Icons.vue';
import { attemptedMixin } from '@/mixins/attemptedMixin';
import { store } from '../basicstore';

export default {
  name: 'FullPagedNav',
  props: ['page'],
  components: {
    Icons,
    MenuButton,
    FullPagedListItem
  },
  mixins: [attemptedMixin],
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
    hasIntro () {
      return (store.assessInfo.intro !== '' || store.assessInfo.resources.length > 0);
    },
    navOptions () {
      var out = [];
      if (this.hasIntro) {
        out[0] = {
          internallink: '/full/page/0',
          title: this.$t('intro'),
          disppage: 0
        };
      }
      for (const pn in this.pagesData) {
        const disppage = parseInt(pn) + 1;
        let numAttempted = 0;
        for (let i = 0; i < this.pagesData[pn][0].questions.length; i++) {
          const qn = this.pagesData[pn][0].questions[i];
          if (store.assessInfo.questions[qn].status !== 'unattempted') {
            numAttempted++;
          } else if (this.qsAttempted[qn] === 1) {
            numAttempted++;
          }
        }
        out[disppage] = {
          disppage: disppage,
          title: this.pagesData[pn][0].pagetitle,
          internallink: '/full/page/' + disppage,
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
  flex-flow: row nowrap;
  align-items: stretch;
  border-bottom: 1px solid #ccc;
}
.subheader > * {
  margin: 4px 0;
}
.pagenav > .menubutton {
  flex-grow: 1;
  max-width: 650px;
}
</style>
