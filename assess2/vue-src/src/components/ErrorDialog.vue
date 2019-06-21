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
        <div class="pane-body" id="error_body">
          {{ errorMsg }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Icons from '@/components/widgets/Icons.vue';
import './a11y-dialog';

export default {
  name: 'ErrorDialog',
  props: ['errormsg'],
  data: function () {
    return {
      dialog: null
    };
  },
  components: {
    Icons
  },
  computed: {
    errorMsg () {
      return this.$t('error.' + this.errormsg);
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
    window.$(document).on('keyup.dialog', (event) => {
      if (event.key === 'Escape') {
        this.clearError();
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

</style>
