<template>
  <div style="display: inline-block">
    <menu-button :id="'qselect' + qn"
      :options = "navOptions"
      :selected = "selected"
      :noarrow = "navOptions.length == 1"
      searchby = "ver"
    >
      <template v-slot="{ option }">
        <gb-question-list-item :option="option" :total="navOptions.length"/>
      </template>
    </menu-button>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import GbQuestionListItem from '@/gbviewassess/GbQuestionListItem.vue';
export default {
  name: 'GbQuestionSelect',
  props: ['versions', 'selected', 'qn'],
  components: {
    MenuButton,
    GbQuestionListItem
  },
  computed: {
    navOptions () {
      var out = [];
      for (let i = 0; i < this.versions.length; i++) {
        const thisoption = {
          ver: i,
          ptsposs: this.versions[i].points_possible,
          onclick: () => this.$emit('setversion', this.qn, i)
        };
        if (this.versions[i].hasOwnProperty('score')) {
          thisoption.score = this.versions[i].score;
          thisoption.scored = (this.versions[i].hasOwnProperty('scored') === true);
        }
        out.push(thisoption);
      }
      return out;
    }
  }
};
</script>
