<template>
  <div
    v-show="active"
    tabindex="0"
    :id="hash"
    :aria-labelledby="hash + '_tab'"
    class="vuetabpanel"
  >
    <slot :active="delayedactive"></slot>
  </div>
</template>

<script>
export default {
  name: 'VueTab',
  data: function () {
    return {
      active: false,
      delayedactive: false,
      hash: ''
    };
  },
  props: ['name'],
  inject: ['addTab', 'activeTabHash'],
  created () {
    this.hash = 'vuetab_' + this.name.toLowerCase().replace(/ /g, '-');
    this.addTab({
      name: this.name,
      hash: this.hash
    });
  },
  watch: {
    activeTabHash () {
      this.active = (this.activeTabHash === this.hash);
      if (this.active) {
        // delay display briefly, to ensure contents are visible before
        // they run their internal rendering. need for MQ layout issues
        this.$nextTick(() => { this.delayedactive = true; });
      } else {
        this.delayedactive = false;
      }
    }
  }
};
</script>

<style>
.vuetabpanel {
  padding: 8px;
}
</style>
