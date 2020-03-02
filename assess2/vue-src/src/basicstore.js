import Vue from 'vue';
import Router from './router';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  uid: null,
  queryString: '',
  inTransit: false,
  autoSaving: false,
  errorMsg: null,
  confirmObj: null,
  lastLoaded: [],
  inProgress: false,
  autosaveQueue: {},
  autosaveTimeactive: {},
  initValues: {},
  initTimes: {},
  autosaveTimer: null,
  somethingDirty: false,
  noUnload: false,
  timelimit_timer: null,
  timelimit_expired: false,
  timelimit_grace_expired: false,
  timelimit_restricted: 0,
  enddate_timer: null,
  show_enddate_dialog: false,
  inPrintView: false,
  enableMQ: true,
  livepollServer: '',
  livepollSettings: {
    showQuestionDefault: true,
    showResultsLiveDefault: false,
    showResultsAfter: true,
    showAnswersAfter: true,
    useTimer: false,
    questionTimelimit: 60
  },
  livepollStuCnt: 0,
  livepollResults: {}
});

export const actions = {
  loadAssessData (callback, doreset) {
    let qs = store.queryString;
    if (doreset === true) {
      qs += '&reset=1';
    }
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'loadassess.php' + qs,
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
        store.timelimit_expired = false;
        clearTimeout(store.timelimit_timer);
        // parse response
        response = this.processSettings(response);
        store.assessInfo = response;
        if (typeof callback !== 'undefined' && callback !== null) {
          callback();
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  startAssess (dopractice, password, newGroupMembers, callback) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'startassess.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        practice: dopractice,
        password: password,
        in_print: store.inPrintView ? 1 : 0,
        new_group_members: newGroupMembers.join(','),
        cur_group: store.assessInfo.stugroupid,
        has_ltisourcedid: (store.assessInfo.is_lti && store.assessInfo.has_ltisourcedid) ? 1 : 0
      },
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
        // reset drawing handler
        window.imathasDraw.reset();

        // overwrite properties with those from response
        response = this.processSettings(response);
        store.assessInfo = Object.assign({}, store.assessInfo, response);

        // clear out trackers, in case we're retaking
        store.autosaveQueue = {};
        store.autosaveTimeactive = {};
        store.initValues = {};
        store.initTimes = {};
        // route to correct display
        if (response.error) {
          this.handleError(response.error);
        } else if (store.assessInfo.has_active_attempt) {
          store.inProgress = true;
          if (typeof callback !== 'undefined') {
            callback();
            return;
          }
          if (store.assessInfo.displaymethod === 'skip') {
            if (store.assessInfo.intro !== '') {
              Router.push('/skip/0');
            } else {
              Router.push('/skip/1');
            }
          } else if (store.assessInfo.displaymethod === 'full') {
            if (store.assessInfo.hasOwnProperty('interquestion_pages')) {
              if (store.assessInfo.intro !== '') {
                Router.push('/full/page/0');
              } else {
                Router.push('/full/page/1');
              }
            } else {
              Router.push('/full');
            }
          } else if (store.assessInfo.displaymethod === 'video_cued') {
            Router.push('/videocued');
          } else if (store.assessInfo.displaymethod === 'livepoll') {
            Router.push('/livepoll');
          }
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadQuestion (qn, regen, jumptoans) {
    store.inTransit = true;
    if (regen) {
      this.clearInitValue(qn);
      if (store.assessInfo.hasOwnProperty('scoreerrors') &&
        store.assessInfo.scoreerrors.hasOwnProperty(qn)
      ) {
        delete store.assessInfo.scoreerrors[qn];
      }
    }
    window.$.ajax({
      url: store.APIbase + 'loadquestion.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        qn: qn,
        practice: store.assessInfo.in_practice,
        regen: regen ? 1 : 0,
        jumptoans: jumptoans ? 1 : 0
      },
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
        if (regen && store.assessInfo.questions[qn].jsparams) {
          // clear out before overwriting
          window.imathasAssess.clearparams(store.assessInfo.questions[qn].jsparams);
        }
        response = this.processSettings(response);
        this.copySettings(response);
        // clear drawing last answer if regen
        if (regen && store.assessInfo.questions[qn].jsparams) {
          for (let i in store.assessInfo.questions[qn].jsparams) {
            if (store.assessInfo.questions[qn].jsparams[i].qtype === 'draw') {
              window.imathasDraw.clearcanvas(i);
            }
          }
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  submitAssessment () {
    let warnMsg = 'header.confirm_assess_submit';
    if (store.assessInfo.submitby === 'by_assessment') {
      let qAttempted = 0;
      let changedQuestions = this.getChangedQuestions();
      for (let i in store.assessInfo.questions) {
        if (store.assessInfo.questions[i].try > 0 ||
          changedQuestions.hasOwnProperty(i)
        ) {
          qAttempted++;
        }
      }
      let nQuestions = store.assessInfo.questions.length;
      if (qAttempted !== nQuestions) {
        warnMsg = 'header.confirm_assess_unattempted_submit';
      }
      store.confirmObj = {
        body: warnMsg,
        action: () => {
          // TODO: Check if we should always submit all
          if (store.assessInfo.showscores === 'during') {
            // check for dirty questions and submit them
            this.submitQuestion(Object.keys(changedQuestions), true);
          } else {
            // submit them all
            var qns = [];
            for (let k = 0; k < store.assessInfo.questions.length; k++) {
              qns.push(k);
            }
            this.submitQuestion(qns, true);
          }
        }
      };
    }
  },
  submitQuestion (qns, endattempt, timeactive, partnum) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    if (typeof qns !== 'object') {
      qns = [qns];
    }

    for (let k in window.callbackstack) {
      if (qns.indexOf(k < 1000 ? k : (Math.floor(k / 1000) - 1)) > -1) {
        window.callbackstack[k](k);
      }
    }
    if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }

    // figure out non-blank questions to submit
    let lastLoaded = [];
    let changedQuestions = this.getChangedQuestions(qns);

    if (Object.keys(changedQuestions).length === 0 && !endattempt) {
      store.errorMsg = 'nochange';
      return;
    }

    store.inTransit = true;
    window.MQeditor.resetEditor();
    window.imathasAssess.clearTips();

    this.clearAutosave(qns);
    // don't store time active when full-test
    if (store.assessInfo.displaymethod === 'full') {
      timeactive = [];
    } else if (typeof timeactive !== 'object') {
      timeactive = [timeactive];
    }

    let data = new FormData();

    // run any pre-submit routines.  The question type wants to return a value,
    // it will get returned here.
    let valstr;
    for (let qn in changedQuestions) {
      if (changedQuestions[qn].length === 1 && changedQuestions[qn][0] === 0) {
        // one part, might be single part
        valstr = window.imathasAssess.preSubmit(qn);
        if (valstr !== false) {
          data.append('qn' + qn + '-val', valstr);
        }
      }
      // get presubmit for multipart parts
      let subqn;
      for (let k = 0; k < changedQuestions[qn].length; k++) {
        subqn = (parseInt(qn) + 1) * 1000 + changedQuestions[qn][k];
        valstr = window.imathasAssess.preSubmit(subqn);
        if (valstr !== false) {
          data.append('qn' + subqn + '-val', valstr);
        }
      }
    }
    for (let k = 0; k < qns.length; k++) {
      let qn = parseInt(qns[k]);

      // add in regular input fields.
      var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn + 1) + '\\d{3})');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if (el.name.match(regex)) {
          if ((el.type !== 'radio' && el.type !== 'checkbox') || el.checked) {
            if (el.type === 'file' && el.files.length > 0) {
              data.append(el.name, el.files[0]);
            } else if (el.type === 'file') {
              if (document.getElementById(el.name + '-autosave')) {
                data.append(el.name, 'file-autosave');
              }
            } else {
              data.append(el.name, window.imathasAssess.preSubmitString(el.name, el.value));
            }
          }
        }
      });
      lastLoaded[k] = store.lastLoaded[qn].getTime();
    };
    data.append('toscoreqn', JSON.stringify(changedQuestions));
    data.append('timeactive', timeactive.join(','));
    data.append('lastloaded', lastLoaded.join(','));
    data.append('verification', JSON.stringify(this.getVerificationData(changedQuestions)));
    if (endattempt) {
      data.append('endattempt', endattempt);
    }
    if (store.assessInfo.in_practice) {
      data.append('practice', true);
    }

    window.$.ajax({
      url: store.APIbase + 'scorequestion.php' + store.queryString,
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
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        } else {
          store.errorMsg = null;
        }
        // clear out initValues for this question so they get re-set
        for (let k = 0; k < qns.length; k++) {
          let qn = qns[k];
          if (store.assessInfo.hasOwnProperty('scoreerrors') &&
            store.assessInfo.scoreerrors.hasOwnProperty(qn)
          ) {
            delete store.assessInfo.scoreerrors[qn];
          }
          if (store.initValues.hasOwnProperty(qn)) {
            delete store.initValues[qn];
          }
        }

        response = this.processSettings(response);
        this.copySettings(response);

        // update tree reader with score
        if (!store.assessInfo.in_practice && window.inTreeReader) {
          this.updateTreeReader();
        }

        if (endattempt) {
          store.inProgress = false;
          Router.push('/summary');
        } else if (qns.length === 1) {
          // scroll to score result
          Vue.nextTick(() => {
            var el = document.getElementById('questionwrap' + qns[0]).parentNode.parentNode;
            var bounding = el.getBoundingClientRect();
            if (bounding.top < 0 || bounding.bottom > document.documentElement.clientHeight) {
              el.scrollIntoView();
            }
          });
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  doAutosave (qn, partnum, timeactive) {
    store.somethingDirty = false;
    window.clearTimeout(store.autosaveTimer);
    if (!store.autosaveQueue.hasOwnProperty(qn)) {
      Vue.set(store.autosaveQueue, qn, []);
    }
    if (store.autosaveQueue[qn].indexOf(partnum) === -1) {
      store.autosaveQueue[qn].push(partnum);
    }
    Vue.set(store.autosaveTimeactive, qn, timeactive);
    store.autosaveTimer = window.setTimeout(() => { this.submitAutosave(true); }, 2000);
  },
  clearAutosave (qns) {
    for (let i in qns) {
      if (store.autosaveQueue.hasOwnProperty(qns[i])) {
        Vue.delete(store.autosaveQueue, qns[i]);
      }
    }
    if (Object.keys(store.autosaveQueue).length === 0) {
      this.clearAutosaveTimer();
    }
  },
  clearAutosaveTimer () {
    window.clearTimeout(store.autosaveTimer);
  },
  submitAutosave (async) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    if (Object.keys(store.autosaveQueue).length === 0) {
      return;
    }
    store.inTransit = true;
    store.autoSaving = true;
    let lastLoaded = {};
    if (typeof window.tinyMCE !== 'undefined') { window.tinyMCE.triggerSave(); }
    let data = new FormData();
    for (let qn in store.autosaveQueue) {
      // build up regex to match the inputs for all the parts we want to save
      let regexpts = [];
      for (let k in store.autosaveQueue[qn]) {
        let pn = store.autosaveQueue[qn][k];
        if (pn === 0) {
          regexpts.push(qn);
        }
        regexpts.push((qn * 1 + 1) * 1000 + pn * 1);
      }
      var regex = new RegExp('^(qn|tc|qs)(' + regexpts.join('\\b|') + '\\b)');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if (el.name.match(regex)) {
          if ((el.type !== 'radio' && el.type !== 'checkbox') || el.checked) {
            if (el.type === 'file') {
              if (el.files.length === 0) {
                data.append(el.name, '');
              } else {
                data.append(el.name, el.files[0]);
              }
            } else {
              data.append(el.name, window.imathasAssess.preSubmitString(el.name, el.value));
            }
          }
        }
      });
      lastLoaded[qn] = store.lastLoaded[qn].getTime();
    };
    data.append('tosaveqn', JSON.stringify(store.autosaveQueue));
    data.append('lastloaded', JSON.stringify(lastLoaded));
    data.append('verification', JSON.stringify(this.getVerificationData(store.autosaveQueue)));
    if (store.assessInfo.displaymethod === 'full') {
      data.append('timeactive', '');
    } else {
      data.append('timeactive', JSON.stringify(store.autosaveTimeactive));
    }
    if (store.assessInfo.in_practice) {
      data.append('practice', true);
    }
    window.$.ajax({
      url: store.APIbase + 'autosave.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: data,
      async: async,
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
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        }
        // clear autosave queue
        store.autosaveQueue = {};
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
        store.autoSaving = false;
      });
  },
  handleTimelimitUp () {
    if (store.assessInfo.has_active_attempt) {
      // submit dirty questions and end attempt
      store.errorMsg = 'timesup_submitting';
      setTimeout(() => {
        let tosub = Object.keys(this.getChangedQuestions());
        this.submitQuestion(tosub, true);
      }, 1000);
    }
    // store.timelimit_expired = true;
  },
  handleDueDate () { // due date has hit
    actions.submitAutosave();
    store.show_enddate_dialog = true;
  },
  endAssess (callback) {
    store.somethingDirty = false;
    this.clearAutosaveTimer();
    window.MQeditor.resetEditor();
    window.imathasAssess.clearTips();
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
        response = this.processSettings(response);
        this.copySettings(response);
        if (typeof callback === 'function') {
          callback();
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getScores () {
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'getscores.php' + store.queryString,
      type: 'GET',
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
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  redeemLatePass (callback) {
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'uselatepass.php' + store.queryString,
      type: 'POST',
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
        response = this.processSettings(response);
        this.copySettings(response);
        if (typeof callback === 'function') {
          callback();
        } else {
          Router.push('/');
        }
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  routeToStart () {
    Router.push('/');
  },
  setLivepollStatus (data) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'livepollstatus.php' + store.queryString,
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
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .fail((xhr, textStatus, errorThrown) => {
        this.handleError(textStatus === 'parsererror' ? 'parseerror' : 'noserver');
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getVerificationData (qns) {
    let out = {};
    let byQuestion = (store.assessInfo.submitby === 'by_question');
    let assessRegen = store.assessInfo.prev_attempts.length;
    for (let qn in qns) {
      let parttries = [];
      let qdata = store.assessInfo.questions[qn];
      for (let pn = 0; pn < qdata.parts.length; pn++) {
        parttries[pn] = qdata.parts[pn].try;
      }
      out[qn] = {
        tries: parttries,
        regen: byQuestion ? qdata.regen : assessRegen
      };
    }
    return out;
  },
  setInitValue (qn, fieldname, val) {
    if (!store.initValues.hasOwnProperty(qn)) {
      store.initValues[qn] = {};
    }
    // only record initvalue if we don't already have one
    let m = fieldname.match(/^(qs|qn|tc)(\d+)/);
    let qref = m[2];
    let pn = 0;
    if (qref > 1000) {
      pn = qref % 1000;
    }
    if (store.assessInfo.questions[qn].hasOwnProperty('usedautosave') &&
      store.assessInfo.questions[qn].usedautosave.indexOf(pn) !== -1
    ) {
      // was loaded from autosave, so don't record as init initValue
      return;
    }
    // for draw questions, overwrite blank to the expected blank format
    if (store.assessInfo.questions[qn].jsparams[qref].qtype === 'draw' && val === '') {
      val = ';;;;;;;;';
    }
    if (!store.initValues[qn].hasOwnProperty(fieldname)) {
      store.initValues[qn][fieldname] = val;
    }
  },
  getInitValue (qn, fieldname) {
    if (!store.initValues.hasOwnProperty(qn)) {
      return '';
    } else if (!store.initValues[qn].hasOwnProperty(fieldname)) {
      return '';
    } else {
      return store.initValues[qn][fieldname];
    }
  },
  clearInitValue (qn) {
    store.initValues[qn] = {};
  },
  getInitTimeactive (qn) {
    if (store.assessInfo.questions[qn].hasOwnProperty('autosave_timeactive')) {
      var timeactive = store.assessInfo.questions[qn].autosave_timeactive;
      // set to 0 to indicate it's used
      store.assessInfo.questions[qn].autosave_timeactive = 0;
      return timeactive;
    }
    return 0;
  },
  setRendered (qn) {
    store.assessInfo.questions[qn].rendered = true;
  },
  getChangedQuestions (qns) {
    if (typeof qns !== 'object') {
      if (!store.assessInfo.hasOwnProperty('questions')) {
        return {};
      }
      qns = [];
      for (let qn = 0; qn < store.assessInfo.questions.length; qn++) {
        qns.push(qn);
      }
    }
    let changed = {};
    let m;
    for (let k = 0; k < qns.length; k++) {
      let qn = qns[k];
      var regex = new RegExp('^(qn|tc|qs)(' + qn + '\\b|' + (qn * 1 + 1) + '\\d{3})');
      window.$('#questionwrap' + qn).find('input,select,textarea').each(function (i, el) {
        if ((m = el.name.match(regex)) !== null) {
          let thisChanged = false;
          if (el.type === 'radio' || el.type === 'checkbox') {
            if (el.checked && el.value !== actions.getInitValue(qn, el.name)) {
              thisChanged = true;
            } else if (!el.checked && el.value === actions.getInitValue(qn, el.name)) {
              thisChanged = true;
            }
          } else if (el.type === 'file' && document.getElementById(el.name + '-autosave') !== null) {
            thisChanged = true; // file with autosave input
          } else {
            if (el.value.trim() !== actions.getInitValue(qn, el.name) && el.value.trim() !== '') {
              thisChanged = true;
            }
          }
          if (thisChanged) {
            if (!changed.hasOwnProperty(qn)) {
              changed[qn] = [];
            }
            let pn = 0;
            let qidnum = parseInt(m[2]);
            if (qidnum > 1000) {
              pn = qidnum % 1000;
            }
            if (changed[qn].indexOf(pn) === -1) {
              changed[qn].push(pn);
            }
          }
        }
      });
      // look to see if any have submitblank set
      if (store.assessInfo.questions[qn].hasOwnProperty('jsparams')) {
        let curqparams = store.assessInfo.questions[qn].jsparams;
        for (let qref in curqparams) {
          if (curqparams.submitall ||
            (qref.match(/\d/) && curqparams[qref].hasOwnProperty('submitblank'))
          ) {
            let pn = 0;
            if (qref > 1000) {
              pn = qref % 1000;
            }
            if (!changed.hasOwnProperty(qn)) {
              changed[qn] = [];
            }
            if (changed[qn].indexOf(pn) === -1) {
              changed[qn].push(pn);
            }
          }
        }
      }
    }
    return changed;
  },
  handleError (error) {
    if (store.assessInfo.hasOwnProperty('is_lti') &&
      store.assessInfo.is_lti &&
      error === 'no_session'
    ) {
      error = 'lti_no_session';
    }
    store.errorMsg = error;
  },
  updateTreeReader () {
    let qAttempted = 0;
    for (let i in store.assessInfo.questions) {
      if (store.assessInfo.questions[i].try > 0) {
        qAttempted++;
      }
    }
    let status = 0;
    if (qAttempted === store.assessInfo.questions.length) {
      status = 2;
    } else if (qAttempted > 0) {
      status = 1;
    }
    try {
      top.updateTRunans(store.aid, status);
    } catch (e) {}
  },
  enableMQ () {
    store.enableMQ = true;
    window.imathasAssess.clearLivePreviewTimeouts();
    window.$('input[type=button][id^=pbtn],button[id^=pbtn]').hide();
    window.$('span[id^=p] span[id^=lpbuf]').empty();
    window.MQeditor.toggleMQAll('input[data-mq]', true);
  },
  disableMQ () {
    store.enableMQ = false;
    window.$('input[type=button][id^=pbtn],button[id^=pbtn]').show().trigger('click');
    window.MQeditor.toggleMQAll('input[data-mq]', false);
  },
  copySettings (response) {
    // overwrite existing questions with new data
    if (response.hasOwnProperty('questions')) {
      if (!store.assessInfo.hasOwnProperty('questions')) {
        store.assessInfo.questions = [];
      }
      for (let i in response.questions) {
        store.assessInfo.questions[parseInt(i)] = response.questions[i];
      }
      delete response.questions;
    }
    // copy other settings from response to store
    store.assessInfo = Object.assign({}, store.assessInfo, response);
  },
  processSettings (data) {
    if (data.hasOwnProperty('questions')) {
      for (let i in data.questions) {
        let thisq = data.questions[i];

        data.questions[i].canretry = (thisq.try < thisq.tries_max);
        data.questions[i].tries_remaining = thisq.tries_max - thisq.try;
        if (thisq.hasOwnProperty('parts')) {
          let trymin = 1e10;
          let trymax = 0;
          for (let pn in thisq.parts) {
            let remaining = thisq.tries_max - thisq.parts[pn].try;
            if (remaining < trymin) {
              trymin = remaining;
            }
            if (remaining > trymax) {
              trymax = remaining;
            }
          }
          if (trymin !== trymax) {
            data.questions[i].tries_remaining_range = [trymin, trymax];
          }
        }
        if (thisq.hasOwnProperty('regens_max') !== 'undefined' && thisq.regen < thisq.regens_max - 1) {
          data.questions[i].canregen = true;
          data.questions[i].regens_remaining = thisq.regens_max - thisq.regen - 1; // -1 to adjust to current version
        } else {
          data.questions[i].canregen = false;
          data.questions[i].regens_remaining = 0;
        }
        data.questions[i].has_details = (thisq.hasOwnProperty('parts') && (
          thisq.parts.length > 1 || (
            thisq.parts[0].hasOwnProperty('penalties') &&
            thisq.parts[0].penalties.length > 0
          ))
        );
        if (data.questions[i].withdrawn !== 0) {
          data.questions[i].canretry = false;
          data.questions[i].tries_remaining = 0;
          data.questions[i].canregen = false;
          data.questions[i].regens_remaining = 0;
        }

        store.lastLoaded[i] = new Date();
      }
    }
    if (data.hasOwnProperty('showscores')) {
      data['show_scores_during'] = (data.showscores === 'during');
    }
    if (data.hasOwnProperty('regen')) {
      data['regens_remaining'] = (data.regens_max - data.regen - 1);
    }
    if (data.hasOwnProperty('enableMQ')) {
      store.enableMQ = data.enableMQ;
    }
    if (data.hasOwnProperty('enddate_in') && data.enddate_in > 0 &&
      data.enddate_in < 20 * 24 * 60 * 60 // over 20 days causes int overlow
    ) {
      clearTimeout(store.enddate_timer);
      let now = new Date().getTime();
      let dueat = data.enddate_in * 1000;
      data['enddate_local'] = now + dueat;
      store.enddate_timer = setTimeout(() => { this.handleDueDate(); }, dueat);
    }
    if (data.hasOwnProperty('timelimit_expiresin')) {
      clearTimeout(store.timelimit_timer);
      clearTimeout(store.enddate_timer); // no need for it w timelimit timer
      let now = new Date().getTime();
      if (data.hasOwnProperty('timelimit_expires')) {
        if (data.timelimit_expires === data.enddate) {
          store.timelimit_restricted = 1;
        } else if (data.timelimit_grace === data.enddate) {
          store.timelimit_restricted = 2;
        }
      }
      let expires = data.timelimit_expiresin * 1000;
      let grace = data.timelimit_gracein * 1000;

      data['timelimit_local_expires'] = now + expires;
      if (grace > 0) {
        data['timelimit_local_grace'] = now + grace;
      } else {
        data['timelimit_local_grace'] = 0;
      }
      if (expires > 0) {
        if (data.timelimit_gracein > 0) {
          store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, grace);
        } else {
          store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, expires);
        }
        store.timelimit_expired = false;
        store.timelimit_grace_expired = false;
      } else {
        store.timelimit_expired = true;
        store.timelimit_grace_expired = true;
        if (data.timelimit_gracein > 0) {
          if (grace > 0) {
            store.timelimit_timer = setTimeout(() => { this.handleTimelimitUp(); }, grace);
            store.timelimit_grace_expired = false;
          }
        }
      }
    } else if (data.timelimit > 0) { // haven't started timed assessment yet
      if (data.enddate_in < data.timelimit) {
        store.timelimit_restricted = 1;
      } else if (data.enddate_in < data.timelimit + data.overtime_grace) {
        store.timelimit_restricted = 2;
      }
    }
    if (data.hasOwnProperty('interquestion_text')) {
      data.interquestion_pages = [];
      let lastDisplayBefore = 0;
      // ensure proper data type on these
      for (let i in data.interquestion_text) {
        data.interquestion_text[i].displayBefore = parseInt(data.interquestion_text[i].displayBefore);
        data.interquestion_text[i].displayUntil = parseInt(data.interquestion_text[i].displayUntil);
        data.interquestion_text[i].forntype = (parseInt(data.interquestion_text[i].forntype) > 0);
        data.interquestion_text[i].ispage = (parseInt(data.interquestion_text[i].ispage) > 0);
        if (data.interquestion_text[i].ispage) {
          // if a new page, start a new array in interquestion_pages
          // first, add a question list to the previous page
          if (data.interquestion_pages.length > 0) {
            let qs = [];
            for (let j = lastDisplayBefore; j < data.interquestion_text[i].displayBefore; j++) {
              qs.push(j);
            }
            lastDisplayBefore = data.interquestion_text[i].displayBefore;
            data.interquestion_pages[data.interquestion_pages.length - 1][0].questions = qs;
          }
          // now start new page
          data.interquestion_pages.push([data.interquestion_text[i]]);
        } else if (data.interquestion_pages.length > 0) {
          // if we've already started pages, push this to the current page
          data.interquestion_pages[data.interquestion_pages.length - 1].push(data.interquestion_text[i]);
        }
      }
      // if we have pages, add a question list to the last page
      if (data.interquestion_pages.length > 0) {
        let qs = [];
        for (let j = lastDisplayBefore; j < data.questions.length; j++) {
          qs.push(j);
        }
        data.interquestion_pages[data.interquestion_pages.length - 1][0].questions = qs;
        // don't delete, as we may use it for print version
        // delete data.interquestion_text;
      } else {
        delete data.interquestion_pages;
      }
    }
    if (data.hasOwnProperty('noprint') && data.noprint === 1) {
      // want to block printing - inject print styles
      let styleEl = document.createElement('style');
      styleEl.type = 'text/css';
      styleEl.media = 'print';
      styleEl.innerText = 'body { display: none;}';
      document.head.appendChild(styleEl);
    }
    if (data.hasOwnProperty('livepoll_server') && store.livepollServer === '') {
      // inject socket script.
      let scriptEl = document.createElement('script');
      scriptEl.src = 'https://' + data.livepoll_server + ':3000/socket.io/socket.io.js';
      document.head.appendChild(scriptEl);
      // save for later
      store.livepollServer = data.livepoll_server;
    }
    if (data.hasOwnProperty('useMQ')) {
      if (data.useMQ === true && !store.enableMQ) {
        this.enableMQ();
      } else if (data.useMQ === false && store.enableMQ) {
        this.disableMQ();
      }
    }
    return data;
  }
};
