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
  confirmObj: null,
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
  loadGbAssessData (callback, keepversion) {
    if (store.inTransit) {
      window.setTimeout(() => this.loadGbAssessData(callback, keepversion), 20);
      return;
    }
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
          // initialize editor and answerbox highlighting
          Vue.nextTick(() => {
            window.initAnswerboxHighlights();
            if (window.location.hash) {
              const el = document.getElementById(window.location.hash.substring(1).replace(/\//, ''));
              if (el) {
                el.scrollIntoView();
              }
            }
          });
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
    if (store.inTransit) {
      window.setTimeout(() => this.loadGbAssessVersion(ver, practice), 20);
      return;
    }
    const qs = store.queryString + '&ver=' + ver + '&practice=' + (practice ? 1 : 0);
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
        Vue.set(store.assessInfo.assess_versions, ver, response);

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

        // initialize editor and answerbox highlighting
        Vue.nextTick(() => {
          window.initAnswerboxHighlights();
        });
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadGbQuestionVersion (qn, ver, forceload, beforeSet) {
    if (store.inTransit) {
      window.setTimeout(() => this.loadGbQuestionVersion(qn, ver, forceload, beforeSet), 20);
      return;
    }
    let qs = store.queryString + '&ver=' + ver + '&qn=' + qn;
    qs += '&practice=' + (store.ispractice ? 1 : 0);
    if (store.assessInfo.assess_versions[store.curAver].questions[qn][ver].html !== null &&
      forceload !== true
    ) {
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
        if (beforeSet) {
          beforeSet();
        }
        Vue.set(store.assessInfo.assess_versions[store.curAver].questions[qn],
          ver,
          Object.assign(store.assessInfo.assess_versions[store.curAver].questions[qn][ver], response)
        );
        // set current versions to this version
        Vue.set(store.curQver, qn, ver);

        // initialize answerbox highlighting
        Vue.nextTick(() => {
          window.initAnswerboxHighlights();
        });
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  saveChanges (exit) {
    if (store.inTransit) {
      window.setTimeout(() => this.saveChanges(exit), 20);
      return;
    }
    if (Object.keys(store.scoreOverrides).length === 0 &&
      Object.keys(store.feedbacks).length === 0
    ) {
      store.saving = 'saved';
      if (exit) {
        window.location = window.exiturl;
      }
      return;
    }
    const qs = store.queryString;
    store.inTransit = true;
    store.saving = 'saving';
    store.errorMsg = null;
    const data = new FormData();
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
        if (exit) {
          store.scoreOverrides = {};
          store.feedbacks = {};
          window.location = window.exiturl;
          return;
        }
        // update store.assessInfo with the new scores so it
        // can tell if we change anything
        for (const key in store.scoreOverrides) {
          if (key === 'gen') {
            if (store.scoreOverrides.gen === '') {
              delete store.assessInfo.scoreoverride;
            } else {
              store.assessInfo.gbscore = store.scoreOverrides.gen;
              store.assessInfo.scoreoverride = store.scoreOverrides.gen;
            }
            continue;
          }
          // Update part score
          const pts = key.split(/-/);
          const qdata = store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]];
          if (qdata.scoreoverride && store.scoreOverrides[key] === '') {
            if (typeof qdata.scoreoverride === 'number') {
              Vue.delete(qdata, 'scoreoverride');
            } else {
              Vue.delete(qdata.scoreoverride, pts[3]);
            }
          }
          if (store.scoreOverrides[key]) { // set or re-set scoreoverride on question part
            if (!qdata.scoreoverride) {
              store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]].scoreoverride = {};
            }
            Vue.set(store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]].scoreoverride,
              pts[3],
              store.scoreOverrides[key]
            );
          }
        }
        // update question scores
        for (const key in response.newscores) {
          const pts = key.split(/-/);
          Vue.set(
            store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]],
            'score',
            response.newscores[key][0]
          );
          // update part info
          for (let i = 0; i < response.newscores[key][1].length; i++) {
            Vue.set(
              store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]].parts,
              i,
              response.newscores[key][1][i]
            );
          }
        }
        // update feedbacks in store
        for (const key in store.feedbacks) {
          const pts = key.split(/-/);
          if (pts[1] === 'g') { // general feedback
            Vue.set(
              store.assessInfo.assess_versions[pts[0]],
              'feedback',
              store.feedbacks[key]
            );
          } else { // question feedback
            Vue.set(
              store.assessInfo.assess_versions[pts[0]].questions[pts[1]][pts[2]],
              'feedback',
              store.feedbacks[key]
            );
          }
        }

        store.assessInfo.gbscore = response.gbscore;
        store.assessInfo.scored_version = response.scored_version;
        // Update question scored version
        for (let an = 0; an < response.assess_info.length; an++) {
          store.assessInfo.assess_versions[an].score = response.assess_info[an].score;
          for (let qn = 0; qn < response.assess_info[an].scoredvers.length; qn++) {
            if (!store.assessInfo.assess_versions[an].hasOwnProperty('questions')) {
              continue; // questions not loaded for this version
            }
            const qvers = store.assessInfo.assess_versions[an].questions[qn];
            for (let qv = 0; qv < qvers.length; qv++) {
              if (qv === response.assess_info[an].scoredvers[qn]) {
                qvers[qv].scored = true;
              } else if (qvers[qv].scored) {
                Vue.delete(qvers[qv], 'scored');
              }
            }
          }
        }
        // Update assessment scores
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
  clearLPblock () {
    store.clearAttempts.type = 'practiceview';
    this.clearAttempt(true);
  },
  clearAttempt (keepver) {
    const data = {
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
        if (store.clearAttempts.type === 'all' && data.keepver === 0) {
          // cleared all - exit
          window.location = window.exiturl;
        } else if (store.clearAttempts.type === 'all') {
          // reload whole mess
          actions.loadGbAssessData();
        } else if (store.clearAttempts.type === 'practiceview') {
          store.assessInfo.latepass_blocked_by_practice = data.latepass_blocked_by_practice;
        } else {
          store.assessInfo.gbscore = response.gbscore;
          store.assessInfo.scored_version = response.scored_version;
          if (store.clearAttempts.type === 'attempt') {
            // clear out any score overrides associated with this version
            const regex = new RegExp('^' + data.aver + '-');
            for (const key in store.scoreOverrides) {
              if (key.match(regex)) {
                Vue.delete(store.scoreOverrides, key);
              }
            }
            if (response.hasOwnProperty('newver')) {
              // replace assessment attempt
              Vue.set(store.assessInfo.assess_versions, data.aver, response.newver);
            } else {
              // delete version
              store.assessInfo.assess_versions.splice(data.aver, 1);
              actions.loadGbAssessVersion(response.scored_version, false);
            }
            if (data.aver > 0) {
              store.curAver = data.aver - 1;
            }
          } else if (store.clearAttempts.type === 'qver') {
            // clear out any score overrides associated with this version
            const regex = new RegExp('^' + data.aver + '-' + data.qn + '-' + data.qver + '-');
            for (const key in store.scoreOverrides) {
              if (key.match(regex)) {
                Vue.delete(store.scoreOverrides, key);
              }
            }
            Vue.set(store.assessInfo.assess_versions[data.aver], 'score', response.assessinfo.score);
            Vue.set(store.assessInfo.assess_versions[data.aver], 'status', response.assessinfo.status);
            if (response.hasOwnProperty('newver')) {
              // replace assessment attempt
              Vue.set(store.assessInfo.assess_versions[data.aver].questions[data.qn], data.qver, response.newver);
              // set scored
              Vue.set(store.assessInfo.assess_versions[data.aver].questions[data.qn][response.qinfo.scored_version], 'scored', true);
            } else {
              // update curQver to new scored version, and set that version as scored
              // use callback to delete this version on response
              actions.loadGbQuestionVersion(data.qn, response.qinfo.scored_version, true,
                () => {
                  store.assessInfo.assess_versions[data.aver].questions[data.qn].splice(data.qver, 1);
                  Vue.set(store.assessInfo.assess_versions[data.aver].questions[data.qn][response.qinfo.scored_version], 'scored', true);
                }
              );
            }
          }
        }
        // clear out any affected score overrides
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
    if (store.inTransit) {
      window.setTimeout(() => this.endAssess(), 20);
      return;
    }
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
    const qdata = store.assessInfo.assess_versions[aver].questions;
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
    const av = store.curAver;
    const qv = store.curQver[qn];

    // compare new score against existing value
    const qdata = store.assessInfo.assess_versions[av].questions[qn][qv];
    const key = av + '-' + qn + '-' + qv + '-' + pn;
    if (score === '') {
      store.scoreOverrides[key] = '';
    } else {
      let scoreChanged = true;
      if (qdata.singlescore) {
        scoreChanged = (Math.abs(score - qdata.score / qdata.points_possible) > 0.001);
      } else if (qdata.parts[pn]) {
        scoreChanged = (Math.abs(score - qdata.parts[pn].score / qdata.parts[pn].points_possible) > 0.001);
      }
      if (qdata.parts[pn] && qdata.parts[pn].try > 0 &&
        (score === '' || !scoreChanged)
      ) {
        // same as existing - don't submit as an override
        delete store.scoreOverrides[key];
      } else {
        // different score - submit as override. Save raw score (0-1)?.
        store.scoreOverrides[key] = Math.round(10000 * score) / 10000;
      }
    }
    store.saving = '';
  },
  setFeedback (qn, feedback) {
    // get current assess and question versions
    const av = store.curAver;
    let key = av;
    let isNew = true;
    if (qn === null) {
      // assessment-level feedback
      key += '-g';
      if (feedback === store.assessInfo.assess_versions[store.curAver].feedback) {
        isNew = false;
      }
    } else {
      const qv = store.curQver[qn];
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
