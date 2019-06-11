<template>
  <span
    @keydown.esc = "triggerOpen(false)"
    class="dropdown-wrap"
    @mouseover = "triggerOpen(true)"
    @mouseleave = "triggerOpen(false)"
    @touchstart = "triggerOpen"
  >
    <slot></slot>
    <transition name="fade">
      <div
        class = "dropdown-pane tooltip-pane"
        ref = "pane"
        v-if = "open"
      >
        {{ tip }}
      </div>
    </transition>
  </span>
</template>

<script>
export default {
  name: 'TooltipSpan',
  props: ['tip', 'show'],
  data: function () {
    return {
      open: false
    };
  },
  methods: {
    triggerOpen (val) {
      if (typeof val === 'boolean') {
        this.open = val;
      } else {
        this.open = !this.open;
      }
      if (this.open) {
        this.$nextTick(() => {
          this.$refs.pane.style.right = '';
          let bndbox = this.$refs.pane.getBoundingClientRect();
          let pageWidth = document.documentElement.clientWidth;
          if (bndbox.right >= pageWidth) {
            this.$refs.pane.style.right = '12px';
          }
        });
      }
    }
  }
};
</script>

<style>
.tooltip-pane {
  border-radius: 8px;
  padding: 8px;
  max-width: 300px;
}
</style>
