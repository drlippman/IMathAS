<template>
  <ul class="listpane">
    <li v-for="(penalty, index) in penalties" :key="index">
      {{ penalty.pct }}% {{ $t("penalties." + penalty.type) }}
    </li>
  </ul>
</template>

<script>

export default {
  name: 'PreviousAttempts',
  props: ['part', 'submitby'],
  data: function () {
    return {
      expanded: false
    };
  },
  computed: {
    penalties () {
      const byQuestion = (this.submitby === 'by_question');
      const penalties = this.part.penalties;
      for (const i in penalties) {
        if (penalties[i].type === 'regen' && byQuestion) {
          penalties[i].type = 'trysimilar';
        }
      }
      return penalties;
    }
  }
};
</script>

<style>
.listpane {
  list-style-type: none;
  margin: 0;
  padding: 0;
}
.listpane li {
  padding: 8px 0;
}
.inline {
  display: inline-block;
}
</style>
