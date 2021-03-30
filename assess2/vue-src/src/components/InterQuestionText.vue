<template>
  <div v-if="rendered" v-show="expanded" v-html="textobj.html" ref="main" />

</template>

<script>
/* hidable version:

<div :class="{'interqtext': true, 'right': !expanded}" ref="main">
  <button
    type = "button"
    :class = "{plain: true, floatright: expanded, togglebtn: true}"
    :aria-label = "expanded ? $t('text.hide') : $t('text.show')"
    :aria-expanded = "expanded ? 'true' : 'false'"
    @click = "expanded = !expanded"
  >
    <icons v-if="expanded" name="close" />
    <span v-else>{{ $t('text.show') }}</span>
  </button>

</div>
*/
// import Icons from '@/components/widgets/Icons.vue';
import { pauseVideos } from '@/components/pauseVideos';

export default {
  name: 'InterQuestionText',
  props: ['textobj', 'active'],
  components: {
    // Icons
  },
  data: function () {
    return {
      expanded: true, // false,
      rendered: false
    };
  },
  methods: {
    renderMath () {
      this.rendered = true;
      this.$nextTick(() => {
        setTimeout(window.drawPics, 100);
        window.initlinkmarkup(this.$refs.main);
        window.initSageCell(this.$refs.main);
        window.rendermathnode(this.$refs.main);
      });
    }
  },
  updated () {
    if (this.active && this.expanded && !this.rendered) {
      this.renderMath();
    }
  },
  mounted () {
    // this.expanded = this.textobj.expanded;
    if (this.active && this.expanded) {
      this.renderMath();
    }
  },
  watch: {
    active: function (newVal, oldVal) {
      if (this.active && this.expanded && !this.rendered) {
        this.renderMath();
      }
      if (newVal === false) {
        pauseVideos(this.$refs.main);
      }
    }
  }
};
</script>
