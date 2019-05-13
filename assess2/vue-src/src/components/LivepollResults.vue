<template>
  <div>
    {{ numResults }} results received
  </div>
</template>

<script>
import { store } from '../basicstore';

export default {
  name: 'LivepollResults',
  props: ['qn'],
  computed: {
    numResults: {
      if (!store.livepollResults.hasOwnProperty(this.qn)) {
        return 0;
      } else {
        return Object.keys(store.livepollResults[this.qn]).length;
      }
    }
  }
};
</script>
