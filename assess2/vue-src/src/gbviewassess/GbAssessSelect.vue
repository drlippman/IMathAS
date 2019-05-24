<template>
  <div>
    <menu-button id="assess_select"
      :options = "navOptions"
      :selected = "selected"
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
        out.push({
          ver: i,
          score: this.versions[i].score,
          status: this.versions[i].status,
          lastchange: this.versions[i].lastchange,
          onclick: () => this.$emit("setversion", i)
        });
      }
      if (this.haspractice) {
        out.push({
          ver: this.versions.length,
          status: 3,
          onclick: () => this.$emit("setversion", this.versions.length)
        });
      }
      return out;
    }
  },
  methods: {

  }
}
</script>
