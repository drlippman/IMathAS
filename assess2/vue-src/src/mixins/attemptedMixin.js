import { store } from '../basicstore';

export const attemptedMixin = {
  computed: {
    qsAttempted () {
      let qAttempted = [];
      for (let i in store.assessInfo.questions) {
        qAttempted[i] = 0;
        if (store.assessInfo.submitby === 'by_assessment' &&
          store.assessInfo.questions[i].tries_max === 1 &&
          store.assessInfo.questions[i].hasOwnProperty('parts_entered')
        ) {
          let min = 1;
          let max = 0;
          for (let k in store.assessInfo.questions[i].parts_entered) {
            if (store.assessInfo.questions[i].parts_entered[k] < min) {
              min = store.assessInfo.questions[i].parts_entered[k];
            }
            if (store.assessInfo.questions[i].parts_entered[k] > max) {
              max = store.assessInfo.questions[i].parts_entered[k];
            }
          }
          if (min > 0) {
            qAttempted[i] = 1;
          } else if (max > 0) {
            qAttempted[i] = 0.5;
          }
        } else if (store.assessInfo.questions[i].try > 0) {
          qAttempted[i] = 1;
        }
      }
      return qAttempted;
    }
  }
};
