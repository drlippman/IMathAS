<template>
  <div class="fullwrap" ref="wrap">
    <div class="dialog-overlay" tabindex="-1">
      <div
        class="dialog"
        ref="dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm_hdr"
        aria-describedby="confirm_body"
        tabindex="-1"
        @click.stop
      >
        <div class="pane-body" id="confirm_body">
          <p>
            {{ confirmBody }}
          </p>
          <div class="flexrow flexright">
            <button @click="doOk" class="primary">
              {{ okMessage }}
            </button>
            <button
              v-if="cancelMessage !== ''"
              @click="doCancel"
              class="secondary"
            >
              {{ cancelMessage }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import './a11y-dialog';
import { store } from '../basicstore';

export default {
  name: 'ConfirmDialog',
  data: function () {
    return {
      dialog: null
    };
  },
  components: {
    Icons
  },
  computed: {
    confirmBody () {
      return this.$t(store.confirmObj.body);
    },
    okMessage () {
      return store.confirmObj.ok ? store.confirmObj.ok : this.$t('confirm.ok');
    },
    cancelMessage () {
      return store.confirmObj.cancel ? store.confirmObj.cancel : this.$t('confirm.cancel');
    }
  },
  methods: {
    doCancel () {
      store.confirmObj = null;
    },
    doOk () {
      if (typeof store.confirmObj.action === 'function') {
        store.confirmObj.action();
      }
      store.confirmObj = null;
    }
  },
  mounted () {
    window.$(document).on('keyup.dialog', (event) => {
      if (event.key === 'Escape') {
        this.doCancel();
      }
    });
    this.dialog = new window.A11yDialog(this.$refs.wrap);
    this.dialog.show();
  },
  beforeDestroy () {
    window.$(document).off('keyup.dialog');
    this.dialog.destroy();
  }
};
</script>

<style>
.flexright {
  justify-content: flex-end;
}
</style>
