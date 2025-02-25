<template>
  <table class="scorelist">
    <caption class="sr-only">Category Scores</caption>
    <thead>
      <tr>
        <th>{{ $t('catlist.category') }}</th>
        <th>{{ $t('catlist.score') }}</th>
      </tr>
    </thead>
    <tbody>
    <tr v-for="(cat,index) in catScores" :key="index">
      <td>
        {{ cat.name }}
      </td>
      <td>
        {{ cat.pct }}%
        <span class="subdued med-left">
          {{ $tc('catlist.pts', cat.poss, {pts: cat.tot, poss: cat.poss}) }}
        </span>
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>

export default {
  name: 'SummaryCategories',
  props: ['data'],
  computed: {
    catScores () {
      const questions = this.data;
      const cats = [];
      for (const i in questions) {
        if (!questions[i].hasOwnProperty('category') || questions[i].category === '') {
          // skip if no category
          continue;
        }
        let found = false;
        for (let k = 0; k < cats.length; k++) {
          if (cats[k].name === questions[i].category) {
            cats[k].tot += questions[i].score;
            cats[k].poss += questions[i].points_possible;
            found = true;
            break;
          }
        }
        if (!found) {
          cats.push({
            name: questions[i].category,
            tot: questions[i].score,
            poss: questions[i].points_possible
          });
        }
      }
      for (let k = 0; k < cats.length; k++) {
        cats[k].pct = Math.round(1000 * cats[k].tot / cats[k].poss) / 10;
      }
      cats.sort(function (a, b) { return a.name < b.name; });
      return cats;
    }
  }
};
</script>

<style>
.scorelist tr {
  border-bottom: 1px solid #ddd;
}
</style>
