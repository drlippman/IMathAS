<template>
  <div
    :id="computedId"
    v-html="value"
    class="fbbox"
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
    id: {default: null},
    value: {default: ''},
    rows: {default: 2}
  },
  data: function() {
    return {
      objTinymce: null
    }
  },
  computed: {
    computedId: function () {
      if (this.id === 'editor' || this.id === '' || this.id === null) {
        return 'editor-' + this.guidGenerator(); //put default value on computedId
      } else {
        return this.id;
      }
    }
  },
  mounted: function () {
    var component = this;
    window.initeditor("exact", this.computedId, null, true, function(ed) {
      ed.on('change keyup undo redo', function (e) {
        component.updateValue(ed.getContent());
      });
      component.objTinymce = ed;
    });
  },
  updated: function () {
    var component = this;
    window.initeditor("exact", this.computedId, null, true, function(ed) {
      ed.on('change keyup undo redo', function (e) {
        component.updateValue(ed.getContent());
      });
      component.objTinymce = ed;
    });
  },
  methods: {
    guidGenerator: function () {
      function s4() {
        return Math.random().toString(36).substr(2, 9);
      }
      return 'ed-' + s4() + '-' + s4();
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
      // if v-model content change programmability
      if (this.value !== this.objTinymce.getContent()) {
        this.objTinymce.setContent(this.value);
      }
    }
  }
}
</script>
