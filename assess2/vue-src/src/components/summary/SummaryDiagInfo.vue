<template>
  <div v-if="showDiagInfo">
    <p class="big">
      <span class="pii-full-name">{{ diagUserName }}</span>
    </p>
    <p>
      {{ $d(new Date(), 'long') }}
    </p>
  </div>
</template>

<script>
import { store } from '../../basicstore';

export default {
  name: 'SummaryDiagInfo',
  computed: {
    showDiagInfo () {
      return store.assessInfo.is_diag;
    },
    diagUserName () {
      return store.assessInfo.userfullname + ': ' + store.assessInfo.diag_userid;
    }
  }
};
</script>
