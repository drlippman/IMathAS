<template>
  <div class="fullwrap" ref="wrap">
    <div class="dialog-overlay" tabindex="-1" @click="clearError">
      <div
        class="dialog"
        ref="dialog"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="error_hdr"
        aria-describedby="error_body"
        tabindex="-1"
        @click.stop
      >
        <div class="pane-header flexrow" id="error_hdr">
          <div style="flex-grow: 1">
            <icons name="alert" />
            {{ errorTitle }}
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
        <div class="pane-body" id="error_body">
          {{ errorMsg }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import A11yDialog from './a11y-dialog';

export default {
  name: 'ErrorDialog',
  props: ['errormsg', 'lastpos'],
  data: function () {
    return {
      dialog: null
    };
  },
  components: {
    Icons
  },
  computed: {
    isError () {
      return (typeof this.errormsg === 'string');
    },
    errorTitle () {
      return this.isError ? this.$t('error.error') : this.errormsg.title;
    },
    errorMsg () {
      return this.isError ? this.$t('error.' + this.errormsg) : this.errormsg.msg;
    }
  },
  methods: {
    clearError () {
      if (this.errormsg === 'no_session') {
        window.location.reload();
      }
      this.$emit('clearerror');
    }
  },
  mounted () {
    const lastHeight = this.lastpos || null;
    window.$(document).on('keyup.dialog', (event) => {
      if (event.key === 'Escape') {
        this.clearError();
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

</style>
