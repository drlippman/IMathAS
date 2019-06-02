<template>
  <div style="display: inline-block">
    <menu-button :id="'qselect' + qn"
      :options = "navOptions"
      :selected = "selected"
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
        out.push({
          ver: i,
          score: this.versions[i].score,
          scored: i == this.selected,
          onclick: () => this.$emit("setversion", this.qn, i)
        });
      }
      return out;
    }
  }
}
</script>
