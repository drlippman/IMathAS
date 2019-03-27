<template>
  <div class="fullwrap">
    <div class="overlay" />
    <div class="dialog">
      <div class="pane-header flexrow">
        <div style="flex-grow: 1">
          <icons name="alert" />
          {{ $t('error.error') }}
        </div>
        <button
          type = "button"
          class = "plain slim"
          :aria-label = "$t('close')"
          @click = "clearError"
        >
          <icons name="close" />
        </button>
      </div>
      <div class="pane-body">
        {{ errorMsg }}
      </div>
    </div>
  </div>
</template>

<script>
import { store } from '../basicstore';
import Icons from '@/components/Icons.vue';

export default {
  name: 'ErrorDialog',
  components: {
    Icons
  },
  computed: {
    errorMsg () {
      return this.$t('error.' + store.errorMsg);
    }
  },
  methods: {
    clearError () {
      store.errorMsg = null;
    }
  }
}
</script>

<style>
.fullwrap, .overlay {
  position: fixed;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
}
.overlay {
  position: fixed;
  background-color: #000;
  opacity: .5;
}
.dialog {
  position: fixed;
  z-index: 10;
  background-color: #fff;
  left: 50%;
  top: 30%;
  transform: translate(-50%, -30%);
}
.dialog button.slim {
  margin-right: -12px;
  margin-top: -3px;
}
</style>
