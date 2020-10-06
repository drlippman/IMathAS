<template>
  <div v-show="active" class="questionpane introtext">
    <div v-if="html !== ''" v-html="html" ref="introtext"/>
    <resource-pane :showicon="true" style="display:inline-block"/>
  </div>
</template>

<script>
import { pauseVideos } from '@/components/pauseVideos';
import ResourcePane from '@/components/ResourcePane.vue';

export default {
  name: 'IntroText',
  components: {
    ResourcePane
  },
  props: ['html', 'active'],
  mounted () {
    if (this.html !== '') {
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.introtext);
      window.initSageCell(this.$refs.introtext);
      window.initlinkmarkup(this.$refs.introtext);
    }
  },
  watch: {
    active: function (newVal, oldVal) {
      if (newVal === false && this.html !== '') {
        pauseVideos(this.$refs.introtext);
      }
    }
  }
};
</script>
