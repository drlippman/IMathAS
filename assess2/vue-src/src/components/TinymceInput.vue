<template>
  <div
    :id="computedId"
    ref="inbox"
    class="fbbox skipmathrender"
    role="textbox"
    :rows="rows"
  ></div>
</template>

<script>

// based on Project: https://github.com/m3esma/vue-easy-tinymce
// Copyright (c) 2018-present Mehdi Esmaeili (@m3esma)
// Released under the MIT License.

export default {
  name: 'TinymceInput',
  props: {
    id: { default: null },
    value: { default: '' },
    rows: { default: 2 }
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
    this.initEditor();
  },
  updated: function () {
    this.initEditor();
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
      window.initeditor('exact', this.computedId, null, true, function (ed) {
        ed.on('input change keyup undo redo', function (e) {
          component.updateValue(ed.getContent());
        }).on('blur', function (e) {
          component.$emit('blur', true);
        }).on('focus', function (e) {
          component.$emit('focus', true);
        });
        component.objTinymce = ed;
      });
    },
    updateValue: function (value) {
      this.$emit('input', value);
    },
    focus: function () {
      this.objTinymce.focus();
    }
  },
  watch: {
    value: function (newValue, oldValue) {
      if (typeof newValue !== 'string') {
        // handle null and undefined
        newValue = '';
      }
      // if v-model content change programmability
      if (newValue !== this.objTinymce.getContent()) {
        this.objTinymce.setContent(newValue);
      }
    }
  }
};
</script>
