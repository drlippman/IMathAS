import Vue from 'vue';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  uid: null,
  stu: 0,
  queryString: '',
  exitUrl: '',
  inTransit: false,
  saving: '',
  errorMsg: null,
  curAver: 0,
  ispractice: false,
  curQver: [],
  orig_submitby: null,
  scoreOverrides: {},
  feedbacks: {},
  clearAttempts: {
    show: false,
    type: '',
    qn: 0
  }
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
          store.assessInfo = response;
          // set current versions to scored versions
          store.curAver = response.scored_version;
          this.setQverAsScored(response.scored_version);

          if (typeof callback !== 'undefined') {
            callback();
          }
        })
        .fail((xhr, textStatus, errorThrown) => {
          this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
        })
        .always(response => {
          store.inTransit = false;
        });
    }
  },
  loadGbAssessVersion (ver, practice) {
    let qs = store.queryString + '&ver=' + ver + '&practice=' + (practice ? 1 : 0);
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'gbloadassessver.php' + qs,
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

        if (practice) {
          // practice gets scored as last ver
          ver = store.assessInfo.assess_versions.length - 1;
        }
        // set into store
        store.assessInfo.assess_versions[ver] = response;
        // set current versions to scored versions
        store.curAver = ver;
        this.setQverAsScored(ver);
        store.ispractice = practice;
        if (practice) {
          if (store.orig_submitby === null) {
            store.orig_submitby = store.assessInfo.submitby;
          }
          store.assessInfo.submitby = 'by_question';
        } else if (store.orig_submitby !== null) {
          store.assessInfo.submitby = store.orig_submitby;
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadGbQuestionVersion (qn, ver) {
    let qs = store.queryString + '&ver=' + ver + '&qn=' + qn;
    qs += '&practice=' + (store.ispractice ? 1 : 0);
    if (store.assessInfo.assess_versions[store.curAver].questions[qn][ver].html !== null) {
      // already have html loaded - just switch displayed version
      Vue.set(store.curQver, qn, ver);
      return;
    }
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'gbloadquestionver.php' + qs,
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
        store.assessInfo.assess_versions[store.curAver].questions[qn][ver] =
          Object.assign(store.assessInfo.assess_versions[store.curAver].questions[qn][ver], response);
        // set current versions to scored versions
        Vue.set(store.curQver, qn, ver);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  saveChanges () {
    let qs = store.queryString;
    store.inTransit = true;
    store.saving = 'saving';
    store.errorMsg = null;
    let data = new FormData();
    data.append('scores', JSON.stringify(store.scoreOverrides));
    data.append('feedback', JSON.stringify(store.feedbacks));
    data.append('practice', store.ispractice ? 1 : 0);
    window.$.ajax({
      url: store.APIbase + 'gbsave.php' + qs,
      type: 'POST',
      dataType: 'json',
      data: data,
      processData: false,
      contentType: false,
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
        store.saving = 'saved';
        // update store.assessInfo with the new scores so it
        // can tell if we change anything
        for (let key in store.scoreOverrides) {
          if (key === 'gen') {
            if (store.scoreOverrides['gen'] === '') {
              delete store.assessInfo.scoreoverride;
            } else {
              store.assessInfo.gbscore = store.scoreOverrides['gen'];
              store.assessInfo.scoreoverride = store.scoreOverrides['gen'];
            }
            continue;
          }
          // TODO: this isn't enough. Need to update qdata.gbscore too
          let pts = key.split(/-/);
          let qdata = store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]];
          if (qdata.parts[pts[3]]) {
            qdata.parts[pts[3]].score = Math.round(1000 * store.scoreOverrides[key] * qdata.parts[pts[3]].points_possible) / 1000;
          }
        }
        store.assessInfo.gbscore = response.gbscore;
        store.assessInfo.scored_version = response.scored_version;
        store.scoreOverrides = {};
        store.feedbacks = {};
      })
      .fail(response => {
        store.saving = 'save_fail';
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  clearAttempt (keepver) {
    let data = {
      type: store.clearAttempts.type,
      keepver: keepver
    };
    if (store.clearAttempts.type === 'attempt' ||
        store.clearAttempts.type === 'qver'
    ) {
      data.aver = store.curAver;
    }
    if (store.clearAttempts.type === 'qver') {
      data.qn = store.clearAttempts.qn;
      data.qver = store.curQver[data.qn];
    }
    window.$.ajax({
      url: store.APIbase + 'gbclearattempt.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
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
        // TODO: update displayed data rather than just exiting
        if (store.clearAttempts.type === 'all' && keepver === 0) {
          // cleared all - exit
          window.location = store.exitUrl;
        } else {
          // TODO: be more surgical.  For now, we'll just reload everything
          store.assessInfo = null;
          actions.loadGbAssessData();
          /*
            If we can return the new version:

          store.assessInfo = response;
          // set current versions to scored versions
          store.curAver = response.scored_version;
          this.setQverAsScored(response.scored_version);

           */
        }
      })
      .fail(response => {
        this.handleError('send_fail');
      })
      .always(response => {
        store.inTransit = false;
        store.clearAttempts.show = false;
      });
  },
  endAssess () {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'endassess.php' + store.queryString,
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
        // TODO: be more surgical.  For now, we'll just reload everything
        store.assessInfo = null;
        actions.loadGbAssessData();
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  setQverAsScored (aver) {
    let qdata = store.assessInfo.assess_versions[aver].questions;
    let qv;
    qloop: for (let i = 0; i < qdata.length; i++) {
      for (qv = 0; qv < qdata[i].length; qv++) {
        if (qdata[i][qv].hasOwnProperty('scored')) {
          Vue.set(store.curQver, i, qv);
          continue qloop;
        }
        // if no scored found, show last
        Vue.set(store.curQver, i, qdata[i].length - 1);
      }
    }
  },
  setScoreOverride (qn, pn, score) {
    // get current assess and question versions
    let av = store.curAver;
    let qv = store.curQver[qn];

    // compare new score against existing value
    let qdata = store.assessInfo.assess_versions[av].questions[qn][qv];
    let key = av + '-' + qn + '-' + qv + '-' + pn;
    if (qdata.parts[pn] && (score === '' || Math.abs(score - qdata.parts[pn].rawscore) < 0.001)) {
      // same as existing - don't submit as an override
      delete store.scoreOverrides[key];
    } else {
      // different score - submit as override. Save raw score (0-1)?.
      store.scoreOverrides[key] = Math.round(10000 * score) / 10000;
    }
    store.saving = '';
  },
  setFeedback (qn, feedback) {
    // get current assess and question versions
    let av = store.curAver;
    let key = av;
    let isNew = true;
    if (qn === null) {
      // assessment-level feedback
      key += '-g';
      if (feedback === store.assessInfo.assess_versions[store.curAver].feedback) {
        isNew = false;
      }
    } else {
      let qv = store.curQver[qn];
      key += '-' + qn + '-' + qv;
      if (feedback === store.assessInfo.assess_versions[store.curAver].questions[qn][qv].feedback) {
        isNew = false;
      }
    }
    if (isNew) {
      store.feedbacks[key] = feedback;
    } else {
      delete store.feedbacks[key];
    }
    store.saving = '';
  },
  handleError (error) {
    store.errorMsg = error;
  }
};
