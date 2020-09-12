<template>
  <div class="showworkwrap">
    <textarea
      :id="computedId"
      ref="inbox"
      class="fbbox swbox"
      :rows="rows"
    ></textarea>
  </div>
</template>

<style>
.showworkwrap {
  max-width: 700px;
}
</style>
<script>

export default {
  name: 'ShowworkInput',
  props: {
    id: { default: null },
    value: { default: '' },
    rows: { default: 2 },
    active: { default: true }
  },
  data: function () {
    return {
      objTinymce: null
    };
  },
  computed: {
    computedId: function () {
      if (this.id === 'editor' || this.id === '' || this.id === null) {
        return 'editor-' + this.guidGenerator(); // put default value on computedId
      } else {
        return this.id;
      }
    }
  },
  mounted: function () {
    this.$refs.inbox.innerHTML = this.value;
    if (this.active) {
      this.initEditor();
    }
  },
  updated: function () {
    if (this.active) {
      this.initEditor();
    }
  },
  methods: {
    guidGenerator: function () {
      function s4 () {
        return Math.random().toString(36).substr(2, 9);
      }
      return 'ed-' + s4() + '-' + s4();
    },
    initEditor () {
      var component = this;
      if (window.tinymce) {
        window.initeditor('exact', this.computedId, null, false, function (ed) {
          ed.on('input change keyup undo redo', function (e) {
            component.updateValue(ed.getContent());
          }).on('blur', function (e) {
            component.$emit('blur', true);
          }).on('focus', function (e) {
            component.$emit('focus', true);
          });
          component.objTinymce = ed;
        });
      } else {
        window.$(this.$refs.inbox).on('focus', function (e) {
          component.$emit('focus', true);
        }).on('blur', function (e) {
          component.$emit('blur', true);
        }).on('input change keyup undo redo', function (e) {
          component.updateValue(e.target.value);
        });
        component.objTinymce = this.$refs.inbox;
      }
    },
    updateValue: function (value) {
      this.$emit('input', value);
    },
    focus: function () {
      this.objTinymce.focus();
    }
  },
  watch: {
    active: function (newValue, oldValue) {
      if (newValue === true && this.objTinymce === null) {
        this.initEditor();
      }
    },
    value: function (newValue, oldValue) {
      if (typeof newValue !== 'string') {
        // handle null and undefined
        newValue = '';
      }
      // if v-model content change programmability
      if (window.tinymce) {
        if (newValue !== this.objTinymce.getContent()) {
          this.objTinymce.setContent(newValue);
        }
      } else {
        if (newValue !== this.objTinymce.value) {
          this.objTinymce.value = newValue;
        }
      }
    }
  }
};
</script>
