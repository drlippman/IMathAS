<template>
  <div v-show="active" class="questionpane introtext">
    <div v-html="html" ref="introtext"/>
  </div>
</template>

<script>
import { pauseVideos } from '@/components/pauseVideos';

export default {
  name: 'IntroText',
  props: ['html', 'active'],
  mounted () {
    setTimeout(window.drawPics, 100);
    window.rendermathnode(this.$refs.introtext);
    window.initlinkmarkup(this.$refs.introtext);
  },
  watch: {
    active: function (newVal, oldVal) {
      if (newVal === false) {
        pauseVideos(this.$refs.introtext);
      }
    }
  }
};
</script>
