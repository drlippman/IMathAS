<template>
  <div>
    <menu-button id="assess_select"
      :options = "navOptions"
      :selected = "selected"
      :noarrow = "navOptions.length == 1"
      searchby = "ver"
    >
      <template v-slot="{ option }">
        <gb-assess-list-item :option="option" :submitby="submitby"/>
      </template>
    </menu-button>
  </div>
</template>

<script>
import MenuButton from '@/components/widgets/MenuButton.vue';
import GbAssessListItem from '@/gbviewassess/GbAssessListItem.vue';
export default {
  name: 'GbAssessSelect',
  props: ['versions', 'selected', 'submitby', 'haspractice'],
  components: {
    MenuButton,
    GbAssessListItem
  },
  computed: {
    navOptions () {
      var out = [];
      for (let i = 0; i < this.versions.length; i++) {
        const thisoption = {
          ver: i,
          status: this.versions[i].status,
          lastchange_disp: this.versions[i].lastchange_disp,
          onclick: () => this.$emit('setversion', i)
        };
        if (this.versions[i].hasOwnProperty('score')) {
          thisoption.score = this.versions[i].score;
        }
        out.push(thisoption);
      }
      return out;
    }
  },
  methods: {

  }
};
</script>
