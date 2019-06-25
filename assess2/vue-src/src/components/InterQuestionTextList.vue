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
import { store } from '../basicstore';
import InterQuestionText from '@/components/InterQuestionText.vue';

export default {
  name: 'InterQuestionTextList',
  props: ['qn', 'pos', 'active', 'page'],
  components: {
    InterQuestionText
  },
  computed: {
    textList () {
      if (typeof this.page === 'undefined' || this.page < 0) {
        if (!store.assessInfo.hasOwnProperty('interquestion_text')) {
          return [];
        } else {
          return store.assessInfo.interquestion_text;
        }
      } else {
        if (!store.assessInfo.hasOwnProperty('interquestion_pages') ||
          !store.assessInfo.interquestion_pages.hasOwnProperty(this.page)
        ) {
          return [];
        } else {
          return store.assessInfo.interquestion_pages[this.page];
        }
      }
    },
    lastQuestion () {
      if (typeof this.page === 'undefined' || this.page < 0) {
        return store.assessInfo.questions.length - 1;
      } else {
        let qlist = store.assessInfo.interquestion_pages[this.page][0].questions;
        return qlist[qlist.length - 1];
      }
    },
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
      let out = [];
      for (let i in this.textList) {
        let textObj = this.textList[i];
        out.push({
          html: textObj.text,
          expanded: (textObj.forntype === true || this.qn === textObj.displayBefore)
        });
      }
      return out;
    },
    preText () {
      let out = [];
      for (let i in this.textList) {
        let textObj = this.textList[i];
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
      if (this.qn === this.lastQuestion) {
        // only show post text if last question
        for (let i in this.textList) {
          let textObj = this.textList[i];
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
