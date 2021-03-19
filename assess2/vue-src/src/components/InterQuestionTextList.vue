<template>
  <div v-if="texts.length > 0" class = "questionpane introtext">
    <inter-question-text
      v-for = "(textitem,index) in texts"
      :textobj = "textitem"
      :key = "index"
      :active = "active"
    />
  </div>
</template>

<script>
import InterQuestionText from '@/components/InterQuestionText.vue';

export default {
  name: 'InterQuestionTextList',
  props: ['qn', 'pos', 'active', 'textlist', 'lastq'],
  components: {
    InterQuestionText
  },
  computed: {
    texts () {
      if (this.pos === 'all') {
        return this.allText;
      } else if (this.pos === 'after') {
        return this.postText;
      } else {
        return this.preText;
      }
    },
    allText () {
      const out = [];
      for (const i in this.textlist) {
        const textObj = this.textlist[i];
        out.push({
          html: textObj.text,
          expanded: (textObj.forntype === true || this.qn === textObj.displayBefore)
        });
      }
      return out;
    },
    preText () {
      const out = [];
      for (const i in this.textlist) {
        const textObj = this.textlist[i];
        if ((this.pos === 'beforeexact' && this.qn === textObj.displayBefore) ||
          (this.pos !== 'beforeexact' && this.qn >= textObj.displayBefore && this.qn <= textObj.displayUntil)
        ) {
          out.push({
            html: textObj.text,
            expanded: (textObj.forntype === true || this.qn === textObj.displayBefore)
          });
        }
      }
      return out;
    },
    postText () {
      const out = [];
      if (this.qn === this.lastq) {
        // only show post text if last question
        for (const i in this.textlist) {
          const textObj = this.textlist[i];
          if (this.qn < textObj.displayBefore) {
            out.push({
              html: textObj.text,
              expanded: (textObj.forntype === 1 || this.qn === textObj.displayBefore)
            });
          }
        }
      }
      return out;
    }
  }
};
</script>
