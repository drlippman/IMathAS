<template>
  <div
    v-if="active"
    tabindex="0"
    :id="id"
    :aria-labelledby="control"
    class="vuetabpanel"
  >
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: 'VueTab',
  data: function () {
    return {
      active: false,
      id: '',
      control: ''
    }
  },
  props: ['name']
}
</script>

<style>
.vuetabpanel {
  padding: 8px;
}
</style>
