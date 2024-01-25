<template>
  <div
    v-show="active"
    tabindex="0"
    :id="hash"
    :aria-labelledby="hash + '_tab'"
    class="vuetabpanel"
  >
    <slot :active="active"></slot>
  </div>
</template>

<script>
export default {
  name: 'VueTab',
  data: function () {
    return {
      active: false,
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
    }
  }
};
</script>

<style>
.vuetabpanel {
  padding: 8px;
}
</style>
