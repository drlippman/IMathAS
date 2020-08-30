<template>
  <span
    @keydown.esc = "triggerOpen(false)"
    class="dropdown-wrap"
    @focusin = "handleFocusin"
    @focusout = "handleFocusout"
  >
    <tooltip-span :show="!open && tip" :tip="tip">
      <span
        :id = "id"
        ref = "button"
        role = "button"
        tabindex = "0"
        @click = "triggerOpen"
        @keydown.enter.prevent = "triggerOpen"
        @keydown.space.prevent = "triggerOpen"
        class = "dropdown-button noselect"
        :aria-controls = "id + '_pane'"
        :aria-expanded = "open?'true':'false'"
      >
        <slot name=button></slot>
      </span>
    </tooltip-span>

    <transition name="fade">
      <div
        class = "dropdown-pane"
        :id = "id + '_pane'"
        ref = "pane"
        v-if = "open"
      >
        <slot />
      </div>
    </transition>
  </span>
</template>

<script>
// based on https://www.w3.org/TR/wai-aria-practices/#disclosure
import TooltipSpan from '@/components/widgets/TooltipSpan.vue';

export default {
  name: 'Dropdown',
  props: ['id', 'position', 'tip'],
  components: {
    TooltipSpan
  },
  data: function () {
    return {
      open: false,
      closetimer: null
    };
  },
  methods: {
    triggerOpen (val) {
      if (typeof val === 'boolean') {
        this.open = val;
      } else {
        this.open = !this.open;
      }
      if (!this.open) {
        this.$refs.button.focus();
      } else {
        this.$nextTick(() => {
          this.$refs.pane.style.right = '';
          this.$refs.pane.style.left = '';
          const bndbox = this.$refs.pane.getBoundingClientRect();
          const pageWidth = document.documentElement.clientWidth;
          if (bndbox.right > pageWidth) {
            this.$refs.pane.style.right = '12px';
          } else if (bndbox.left < 0) {
            this.$refs.pane.style.left = '12px';
          }
        });
      }
    },
    handleFocusout () {
      this.closetimer = setTimeout(() => { this.open = false; }, 50);
    },
    handleFocusin () {
      clearTimeout(this.closetimer);
    }
  }
};
</script>

<style>
.dropdown-wrap {
  /*position: relative;*/
  display: inline-block;
}
.dropdown-button {
  border: none;
  background: none;
  cursor: default;
}
.dropdown-pane {
  position: absolute;
  box-shadow: 1px 3px 8px 0 rgba(0,0,0,0.25);
  border: 1px solid #ccc;
  background-color: #fff;
  padding: 0;
  margin: 0;
  z-index: 1000;
  max-width: 70vw;
  /*min-width: 30vw;*/
}
.dropdown-right {
  /*right: 0px;*/
}
</style>
