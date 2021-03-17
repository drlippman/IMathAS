<template>
  <div v-show="show">
    {{ !username ?
      $t('gradebook.feedback') :
      $t('gradebook.feedback_for', {name: username})
    }}:<br/>
    <textarea
      v-if="canedit && !useeditor"
      class="fbbox"
      :id="'fb' + qn"
      ref = "fbbox"
      rows="2"
      cols="60"
      :value = "value"
      @input="updateFeedback"
    ></textarea>
    <tinymce-input
      v-else-if="canedit"
      ref = "fbbox"
      :id="'fb' + qn"
      :value = "value"
      @input = "updateFeedback"
    ></tinymce-input>
    <div
      v-else
      ref = "fbbox"
      v-html="value"
    />
  </div>
</template>

<script>
import TinymceInput from '@/components/TinymceInput.vue';

export default {
  name: 'GbFeedback',
  props: ['show', 'canedit', 'useeditor', 'value', 'qn', 'username'],
  components: {
    TinymceInput
  },
  data: function () {
    return {
      rendered: false
    };
  },
  methods: {
    updateFeedback (evt) {
      let content;
      if (this.useeditor) {
        content = evt;
      } else {
        content = evt.target.value;
      }
      this.$emit('update', content);
    },
    renderInit () {
      if (this.rendered || this.canedit) {
        // only need to render for student viewers
        return;
      }
      setTimeout(window.drawPics, 100);
      window.rendermathnode(this.$refs.fbbox);
    },
    focus () {
      this.$refs.fbbox.focus();
    }
  },
  mounted () {
    this.renderInit();
  },
  watch: {
    value: function (newVal, oldVal) {
      this.rendered = false;
      this.$nextTick(this.renderInit);
    }
  }
};
</script>
