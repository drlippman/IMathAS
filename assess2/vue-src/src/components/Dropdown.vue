<template>
  <span
    @keydown.esc = "triggerOpen(false)"
    class="dropdown-wrap"
    @focusin = "handleFocusin"
    @focusout = "handleFocusout"
  >
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
    <div
      class = "dropdown-pane"
      :id = "id + '_pane'"
      ref = "pane"
      v-if = "open"
    >
      <slot />
    </div>
  </span>
</template>

<script>
// based on https://www.w3.org/TR/wai-aria-practices/#disclosure

export default {
  name: 'Dropdown',
  props: ['id', 'position'],
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
          let bndbox = this.$refs.pane.getBoundingClientRect();
          let pageWidth = document.documentElement.clientWidth;
          if (bndbox.right > pageWidth) {
            this.$refs.pane.style.right = '12px';
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
  box-shadow: 0 2px 16px 0 rgba(33, 43, 54, 0.08), 0 0 0 1px rgba(6, 44, 82, 0.1);
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
