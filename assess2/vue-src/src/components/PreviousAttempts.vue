<template>
  <div class="pane-body">
    <table class="scorelist med-below">
      <caption>{{ caption }}</caption>
      <tr>
        <th>
          {{ $t('prev.date') }}
        </th>
        <th v-if="prevAttempts[0].hasOwnProperty('score')">
          {{ $t('prev.score') }}
        </th>
      </tr>
      <tr
        v-for="(prev,index) in prevAttempts"
        :key = "index"
      >
        <td>
          {{ $d(new Date(prev.date * 1000), 'long')}}
        </td>
        <td v-if="prev.hasOwnProperty('score')" class="med-left">
          {{ Math.round(1000*prev.score/totPoss)/ 10 }}%
        </td>
      </tr>
    </table>
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
    }
  }
}
</script>

<style>
.scorelist caption {
  border-bottom: 1px solid #ddd;
  text-align: left;
  font-weight: bold;
  padding-bottom: 16px;
}
</style>
