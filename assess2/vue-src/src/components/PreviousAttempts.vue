<template>
  <div class="pane-body">
    <table class="scorelist med-below">
      <caption>{{ caption }}</caption>
      <thead>
        <tr>
          <th>
            {{ $t('prev.date') }}
          </th>
          <th v-if="prevAttempts[0].hasOwnProperty('score')">
            {{ $t('prev.score') }}
          </th>
        </tr>
      </thead>
      <tbody>
      <tr
        v-for="(prev,index) in prevAttempts"
        :key = "index"
      >
        <td>
          {{ prev.date }}
        </td>
        <td v-if="prev.hasOwnProperty('score')" class="med-left">
          {{ Math.round(1000*prev.score/totPoss)/ 10 }}%
        </td>
      </tr>
      </tbody>
    </table>
    <p v-if="showGbLink">
      <a :href="gbUrl">{{ $t('prev.viewingb') }}</a>
    </p>
  </div>
</template>

<script>
import { store } from '../basicstore';

export default {
  name: 'PreviousAttempts',
  props: ['caption'],
  computed: {
    prevAttempts () {
      return store.assessInfo.prev_attempts;
    },
    totPoss () {
      return store.assessInfo.points_possible;
    },
    ainfo () {
      return store.assessInfo;
    },
    showGbLink () {
      const viewingb = store.assessInfo.viewingb;
      return ((viewingb === 'immediately' || viewingb === 'after_take') &&
        this.prevAttempts.length > 0 &&
        !store.assessInfo.can_view_all
      );
    },
    gbUrl () {
      let url = 'gbviewassess.php?';
      url += 'cid=' + store.cid;
      url += '&aid=' + store.aid;
      url += '&uid=' + store.uid;
      return url;
    }
  }
};
</script>

<style>
.scorelist caption {
  border-bottom: 1px solid #ddd;
  text-align: left;
  font-weight: bold;
  padding-bottom: 16px;
}
</style>
