<template>
  <span>
    <span
      :id = "id"
      ref = "button"
      role = "button"
      tabindex = "0"
      @click = "triggerOpen"
      @keydown.enter.prevent = "triggerOpen"
      @keydown.space.prevent = "triggerOpen"
      class = "dropdown-button"
      :aria-controls = "id + '_pane'"
      :aria-expanded = "open?'true':'false'"
    >
      <slot name=button></slot>
    </span>
    <div
      :id = "id + '_pane'"
      v-if = "open"
    >
      <slot />
    </div>
  </span>
</template>

<script>

export default {
  name: 'ClickToShow',
  props: ['id'],
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
      }
    }
  }
};
</script>
