<template>
  <div>
    <inter-question-text
      v-for = "(textitem,index) in texts"
      :textobj = "textitem"
      :key = "index"
      :active = "active"
    />
  </div>
</template>

<script>
import { store } from '../basicstore';
import InterQuestionText from '@/components/InterQuestionText.vue';

export default {
  name: 'InterQuestionTextList',
  props: ['qn', 'pos', 'active'],
  components: {
    InterQuestionText
  },
  computed: {
    texts () {
      if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
        return [];
      } else if (this.pos === 'after') {
        return this.postText;
      } else {
        return this.preText;
      }
    },
    preText () {
      let out = [];
      for (let  i in store.assessInfo.interquestion_text) {
        let textObj = store.assessInfo.interquestion_text[i];
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
      let out = [];
      if (this.qn === store.assessInfo.questions.length - 1) {
        // only show post text if last question
        for (let i in store.assessInfo.interquestion_text) {
          let textObj = store.assessInfo.interquestion_text[i];
          if (this.qn < textObj.displayBefore) {
            out.push({
              html: textObj.text,
              expanded: (textObj.forntype == 1 || this.qn == textObj.displayBefore)
            });
          }
        }
      }
      return out;
    }
  }
}
</script>
