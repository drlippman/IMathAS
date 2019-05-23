import Vue from 'vue';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  uid: null,
  queryString: '',
  inTransit: false,
  errorMsg: null,
  curAver: 0,
  curQver: []
});

export const actions = {
  loadGbAssessData (callback) {
    if (store.assessInfo === null && window.gbAssessData) {
      store.assessInfo = window.gbAssessData;
      if (typeof callback !== 'undefined') {
        callback();
      }
    } else {
      store.inTransit = true;
      store.errorMsg = null;
      window.$.ajax({
        url: store.APIbase + 'gbloadassess.php' + store.queryString,
        dataType: 'json',
        xhrFields: {
          withCredentials: true
        },
        crossDomain: true
      })
        .done(response => {
          if (response.hasOwnProperty('error')) {
            this.handleError(response.error);
            return;
          }
          // reset store
          store.inProgress = false;
          store.assessInfo = response;
          // set current versions to scored versions
          store.curAver = response.scored_version;
          let qdata = response.assess_versions[store.curAver].questions;
          let qv;
          for (let i=0; i < qdata.length; i++) {
            for (qv=0; qv < qdata[i].length; qv++) {
              if (qdata[i][qv].hasOwnProperty('scored')) {
                store.curQver[i] = qv;
              }
            }
          }
          if (typeof callback !== 'undefined') {
            callback();
          }
        })
        .always(response => {
          store.inTransit = false;
        });
    }
  },
  handleError (error) {
    store.errorMsg = error;
  },
};
