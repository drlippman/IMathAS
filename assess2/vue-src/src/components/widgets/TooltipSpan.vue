<template>
  <span
    @keydown.esc = "triggerOpen($event,false)"
    class="dropdown-wrap"
    @mouseover = "triggerOpen($event,true)"
    @mouseleave = "triggerOpen($event,false)"
    @touchstart = "triggerOpen"
    @focusin = "triggerOpen($event,true)"
    @focusout = "triggerOpen($event,false)"
    tabindex = "-1"
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
    triggerOpen (event, val) {
      if (typeof this.tip === 'undefined' || this.tip === '') {
        return;
      } else if (this.show === false) {
        this.open = false;
      } else if (typeof val === 'boolean') {
        this.open = val;
      } else {
        this.open = !this.open;
      }
      if (this.open) {
        this.$nextTick(() => {
          this.$refs.pane.style.right = '';
          const bndbox = this.$refs.pane.getBoundingClientRect();
          const pageWidth = document.documentElement.clientWidth;
          if (bndbox.right >= pageWidth) {
            this.$refs.pane.style.right = '12px';
          }
        });
      }
      if (event.type === 'touchstart' && this.open) {
        event.currentTarget.focus();
      }
      if (event.type === 'touchstart' && event.cancelable) {
        // Disabled - was preventing activating menus inside tooltipspans
        // event.preventDefault();
      }
    }
  }
};
</script>

<style>
.dropdown-pane.tooltip-pane {
  border-radius: 8px;
  padding: 8px;
  max-width: 300px;
}
</style>
