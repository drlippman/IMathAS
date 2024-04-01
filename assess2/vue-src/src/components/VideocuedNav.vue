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
  props: ['cue', 'toshow', 'showfollowup'],
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
        const cuedata = store.assessInfo.videocues[i];
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
        if (cuedata.hasOwnProperty('followuptime') &&
          (cuedata.followuplink || this.showfollowup.includes(i))) {
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
      const curCue = parseInt(this.cue);
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
