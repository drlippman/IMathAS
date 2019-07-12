<template>
  <div class="subheader" role="navigation" :aria-label="$t('regions.qvidnav')">
    <menu-button id="qnav"
      :options = "navOptions"
      :selected = "curOption"
      searchby = "title"
    >
      <template v-slot="{ option, selected }">
        <videocued-nav-list-item :option="option" :selected="selected" />
      </template>
    </menu-button>
    <slot></slot>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import VideocuedNavListItem from '@/components/VideocuedNavListItem.vue';
import { store } from '../basicstore';

export default {
  name: 'VideocuedNav',
  props: ['cue', 'toshow'],
  components: {
    MenuButton,
    VideocuedNavListItem
  },
  computed: {
    hasIntro () {
      return (store.assessInfo.intro !== '');
    },
    navOptions () {
      var out = [];
      if (this.hasIntro) {
        out.push({
          // internallink: '/videocued/0',
          onclick: () => this.$emit('jumpto', -1, 'i'),
          title: this.$t('intro'),
          type: 'i'
        });
      }
      /*
       foreach viddata
       link using segment title, jumps to previous segment end time
       if has a qn,
        link to jump to question
        if has followup with showlink==true
         show link with followuptitle, jumps to main segment end time
      */
      for (let i = 0; i < store.assessInfo.videocues.length; i++) {
        let cuedata = store.assessInfo.videocues[i];
        out.push({
          // internallink: '/videocued/' + cuen + '/v',
          onclick: () => this.$emit('jumpto', i, 'v'),
          type: 'v',
          title: cuedata.title,
          cue: i
        });
        if (cuedata.hasOwnProperty('qn')) {
          out.push({
            // internallink: '/videocued/' + cuen + '/q',
            onclick: () => this.$emit('jumpto', i, 'q'),
            type: 'q',
            title: this.$t('question_n', { n: parseInt(cuedata.qn) + 1 }),
            qn: parseInt(cuedata.qn),
            cue: i,
            subitem: true
          });
        }
        if (cuedata.hasOwnProperty('followuptime')) {
          out.push({
            // internallink: '/videocued/' + cuen + '/f',
            onclick: () => this.$emit('jumpto', i, 'f'),
            type: 'f',
            title: cuedata.followuptitle,
            cue: i,
            subitem: true
          });
        }
      }
      return out;
    },
    curOption () {
      let curCue = parseInt(this.cue);
      if (curCue === -1 && this.hasIntro) {
        return 0;
      }
      for (let i = this.hasIntro ? 1 : 0; i < this.navOptions.length; i++) {
        if (this.navOptions[i].cue === curCue &&
          this.navOptions[i].type === this.toshow
        ) {
          return i;
        }
      }
      return -1;
    },
    showNextPrev () {
      return (Object.keys(this.navOptions).length > 1);
    },
    prevLink () {
      if (this.curOption <= 0) {
        return '';
      }
      return this.navOptions[this.curOption - 1].internallink;
    },
    nextLink () {
      if (this.curOption >= this.navOptions.length - 1) {
        return '';
      }
      return this.navOptions[this.curOption + 1].internallink;
    }
  }
};
/* Next/Prev buttons, removed since they don't make much sense in this view

<router-link
  :to="prevLink"
  tag="button"
  :disabled="curOption <= 0"
  class="secondarybtn"
  id="qprev"
  :aria-label="$t('previous')"
  v-if = "showNextPrev"
>
  <icons name="left"/>
</router-link>
<router-link
  :to="nextLink"
  tag="button"
  :disabled="curOption>=navOptions.length-1"
  class="secondarybtn"
  id="qnext"
  :aria-label="$t('next')"
  v-if = "showNextPrev"
>
  <icons name="right" />
</router-link>
 */
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
