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
            <button @click="doOk" class="primary" key="okbtn">
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
import A11yDialog from './a11y-dialog';

export default {
  name: 'ConfirmDialog',
  props: ['data', 'lastpos'],
  data: function () {
    return {
      dialog: null
    };
  },
  computed: {
    confirmBody () {
      return this.$t(this.data.body);
    },
    okMessage () {
      return this.$t(this.data.ok ? this.data.ok : 'confirm.ok');
    },
    cancelMessage () {
      return this.$t(this.data.cancel ? this.data.cancel : 'confirm.cancel');
    }
  },
  methods: {
    doCancel () {
      if (typeof this.data.cancelaction === 'function') {
        this.data.cancelaction();
      }
      this.$emit('close');
    },
    doOk () {
      if (typeof this.data.action === 'function') {
        this.data.action();
      }
      this.$emit('close');
    }
  },
  mounted () {
    const lastHeight = this.lastpos || null;
    window.$(document).on('keyup.dialog', (event) => {
      if (event.key === 'Escape') {
        this.doCancel();
      }
    });
    this.dialog = new A11yDialog(this.$refs.wrap);
    this.dialog.show();
    if (window.innerHeight > 2000 && lastHeight !== null) {
      this.$refs.dialog.style.top = Math.max(20, lastHeight - this.$refs.dialog.offsetHeight) + 'px';
    }
  },
  beforeUnmount () {
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
