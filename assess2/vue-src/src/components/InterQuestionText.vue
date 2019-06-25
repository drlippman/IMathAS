<template>
  <div v-show="expanded" v-html="textobj.html" />

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
//import Icons from '@/components/widgets/Icons.vue';

export default {
  name: 'InterQuestionText',
  props: ['textobj', 'active'],
  components: {
    //Icons
  },
  data: function () {
    return {
      expanded: true, //false,
      rendered: false
    };
  },
  methods: {
    renderMath () {
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.main);
      this.rendered = true;
    }
  },
  updated () {
    if (this.active && this.expanded && !this.rendered) {
      this.renderMath();
    }
  },
  mounted () {
    //this.expanded = this.textobj.expanded;
    if (this.active && this.expanded) {
      this.renderMath();
    }
  },
  watch: {
    active: function (newVal, oldVal) {
      if (this.active && this.expanded && !this.rendered) {
        this.renderMath();
      }
    }
  }
};
</script>
