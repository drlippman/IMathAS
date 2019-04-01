import Vue from 'vue';
import Router from './router';

export const store = Vue.observable({
  assessInfo: null,
  APIbase: null,
  aid: null,
  cid: null,
  queryString: '',
  errorMsg: null,
  lastLoaded: [],
  inProgress: false,
  autosaveQueue: {},
  autosaveTimer: null,
  timelimit_timer: null,
  timelimit_expired: false
});

export const actions = {
  loadAssessData (callback) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'loadassess.php' + store.queryString,
      dataType: 'json',
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
        // reset store
        store.inProgress = false;
        store.timelimit_expired = false;
        clearTimeout(store.timelimit_timer);
        // parse response
        response = this.processSettings(response);
        store.assessInfo = response;
        if (typeof callback !== 'undefined') {
          callback();
        }
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  startAssess (dopractice, password, newGroupMembers) {
    store.inTransit = true;
    store.errorMsg = null;
    window.$.ajax({
      url: store.APIbase + 'startassess.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        practice: dopractice,
        password: password,
        new_group_members: newGroupMembers.join(','),
        cur_group: store.assessInfo.stugroupid
      },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
      // overwrite properties with those from response
        response = this.processSettings(response);
        store.assessInfo = Object.assign({}, store.assessInfo, response);

        // route to correct display
        if (response.error) {
          store.errorMsg = response.error;
        } else if (store.assessInfo.has_active_attempt) {
          store.inProgress = true;
          if (store.assessInfo.displaymethod === 'skip') {
            if (store.assessInfo.intro != '') {
              Router.push('/skip/0' + store.queryString);
            } else {
              Router.push('/skip/1' + store.queryString);
            }
          } else if (store.assessInfo.displaymethod === 'full') {
            if (store.assessInfo.hasOwnProperty('interquestion_pages')) {
              if (store.assessInfo.intro != '') {
                Router.push('/full/page/0' + store.queryString);
              } else {
                Router.push('/full/page/1' + store.queryString);
              }
            } else {
              Router.push('/full' + store.queryString);
            }
          }
        }
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  loadQuestion (qn, regen, jumptoans) {
    store.inTransit = true;
    window.$.ajax({
      url: store.APIbase + 'loadquestion.php' + store.queryString,
      type: 'POST',
      dataType: 'json',
      data: {
        qn: qn,
        practice: store.assessInfo.in_practice,
        regen: regen?1:0,
        jumptoans: jumptoans?1:0
      },
      xhrFields: {
        withCredentials: true
      },
      crossDomain: true
    })
      .done(response => {
        if (response.hasOwnProperty('error')) {
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  submitQuestion (qns, endattempt, timeactive, partnum) {
    if (typeof qns !== 'object') {
      qns = [qns];
    }
    this.clearAutosave(qns);
    // don't store time active when full-test
    if (store.assessInfo.displaymethod === 'full') {
      timeactive = [];
    } else if (typeof timeactive !== 'object') {
      timeactive = [timeactive];
    }
    if (typeof window.tinyMCE != "undefined") {window.tinyMCE.triggerSave();}
    store.inTransit = true;

    // figure out non-blank questions to submit
    let lastLoaded = [];
    let changedQuestions = this.getChangedQuestions(qns);
    let data = new FormData();
    for (let k=0; k<qns.length; k++) {
      let qn = qns[k];
      var regex = new RegExp("^(qn|tc|qs)("+qn+"\\b|"+(qn+1)+"\\d{3})");
      window.$("#questionwrap" + qn).find("input,select,textarea").each(function(i,el) {
        if (el.name.match(regex)) {
          let fieldBlank = true;
          if ((el.type!=='radio' && el.type!=='checkbox') || el.checked) {
            if (el.type==='file') {
              data.append(el.name, el.files[0]);
            } else {
              data.append(el.name, el.value);
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
          store.errorMsg = response.error;
          if (response.error === 'already_submitted') {
            console.log("here");
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        }

        response = this.processSettings(response);
        this.copySettings(response);
        if (endattempt) {
          store.inProgress = false;
          Router.push('/summary' + store.queryString);
        }

      })
      .always(response => {
        store.inTransit = false;
      });
  },
  doAutosave (qn, partnum) {
    window.clearTimeout(store.autosaveTimer);
    if (!store.autosaveQueue.hasOwnProperty(qn)) {
      store.autosaveQueue[qn] = [];
    }
    if (store.autosaveQueue[qn].indexOf(partnum) === -1) {
      store.autosaveQueue[qn].push(partnum);
    }
    store.autosaveTimer = window.setTimeout(() => {this.submitAutosave(true);}, 2000)
  },
  clearAutosave(qns) {
    for (let i in qns) {
      if (store.autosaveQueue.hasOwnProperty(qns[i])) {
        delete store.autosaveQueue[qns[i]];
      }
    }
    if (Object.keys(store.autosaveQueue).length === 0) {
      window.clearTimeout(store.autosaveTimer);
    }
  },
  clearAutosaveTimer() {
    window.clearTimeout(store.autosaveTimer);
  },
  submitAutosave (async) {
    window.clearTimeout(store.autosaveTimer);
    if (Object.keys(store.autosaveQueue).length === 0) {
      return;
    }
    store.inTransit = true;
    let lastLoaded = {};
    if (typeof window.tinyMCE != "undefined") {window.tinyMCE.triggerSave();}
    let data = new FormData();
    for (let qn in store.autosaveQueue) {
      // build up regex to match the inputs for all the parts we want to save
      let regexpts = [];
      for (let k in store.autosaveQueue[qn]) {
        let pn = store.autosaveQueue[qn][k];
        if (pn === 0) {
          regexpts.push(qn);
        }
        regexpts.push((qn*1+1)*1000 + pn*1);
      }
      var regex = new RegExp('^(qn|tc|qs)(' + regexpts.join('\\b|') + '\\b)');
      window.$("#questionwrap" + qn).find("input,select,textarea").each(function(i,el) {
        if (el.name.match(regex)) {
          if ((el.type!=='radio' && el.type!=='checkbox') || el.checked) {
            if (el.type==='file') {
              // don't autosave files
            } else {
              data.append(el.name, el.value);
            }
          }
        }
      });
      lastLoaded[qn] = store.lastLoaded[qn].getTime();
    };
    data.append('tosaveqn', JSON.stringify(store.autosaveQueue));
    data.append('lastloaded', JSON.stringify(lastLoaded));
    data.append('verification', JSON.stringify(this.getVerificationData(store.autosaveQueue)));
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
          store.errorMsg = response.error;
          if (response.error === 'already_submitted') {
            response = this.processSettings(response);
            this.copySettings(response);
          }
          return;
        }
        // clear autosave queue
        store.autosaveQueue = {};
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  handleTimelimitUp () {
    if (store.assessInfo.has_active_attempt) {
      // submit dirty questions and end attempt
      let tosub = Object.keys(this.getChangedQuestions());
      if (tosub.length === 0) {
        tosub = -1;
      }
      this.submitQuestion(tosub, true);
    }
    //store.timelimit_expired = true;
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
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
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
          store.errorMsg = response.error;
          return;
        }
        response = this.processSettings(response);
        this.copySettings(response);
      })
      .always(response => {
        store.inTransit = false;
      });
  },
  getVerificationData(qns) {
    let out = {};
    for (let qn in qns) {
      let parttries = [];
      console.log(qn);
      let qdata = store.assessInfo.questions[qn];
      for (let pn=0; pn < qdata.parts.length; pn++) {
        parttries[pn] = qdata.parts[pn].try;
      }
      out[qn] = {
        tries: parttries,
        regen: qdata.regen
      };
    }
    return out;
  },
  getChangedQuestions(qns) {
    if (typeof qns !== 'object') {
      qns = [];
      for (let qn=0; qn<store.assessInfo.questions.length; qn++) {
        qns.push(qn);
      }
    }
    let changed = {};
    let m;
    for (let k=0; k < qns.length; k++) {
      let qn = qns[k];
      var regex = new RegExp("^(qn|tc|qs)("+qn+"\\b|"+(qn+1)+"\\d{3})");
      window.$("#questionwrap" + qn).find("input,select,textarea").each(function(i,el) {
        if (m = el.name.match(regex)) {
          let thisChanged = false;
          if (el.type === 'radio' || el.type === 'checkbox') {
            if ((el.checked === true) !== (el.getAttribute('data-initval') === '1')) {
              thisChanged = true;
            }
          } else {
            if (el.value !== el.getAttribute('data-initval')) {
              thisChanged = true;
            }
          }
          if (thisChanged) {
            if (!changed.hasOwnProperty(qn)) {
              changed[qn] = [];
            }
            let pn = 0;
            if (m[2]>1000) {
              pn = m[2]%1000;
            }
            if (changed[qn].indexOf(pn) === -1) {
              changed[qn].push(pn);
            }
          }
        }
      });
    }
    return changed;
  },
  copySettings(response) {
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
    // hack job temporary fix until we can do something better
    let svgchk = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="green" stroke-width="3" fill="none"><title>correct</title>';
    svgchk += '<polyline points="20 6 9 17 4 12"></polyline></svg>';
    let svgx = '<svg viewBox="0 0 24 24" width="16" height="16" stroke="red" stroke-width="3" fill="none"><title>correct</title>';
    svgx += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
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
        if (thisq.hasOwnProperty('regens_max') !== 'undefined' && thisq.regen < thisq.regens_max) {
          data.questions[i].canregen = true;
          data.questions[i].regens_remaining = thisq.regens_max - thisq.regen;
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
        // TODO: remove this hack
        if (data.questions[i].html !== null) {
          data.questions[i].html = data.questions[i].html
            .replace(/<img[^>]*gchk.gif[^>]*>/g, svgchk)
            .replace(/<img[^>]*redx.gif[^>]*>/g, svgx);
        }
        store.lastLoaded[i] = new Date();
        if (data.questions[i].hasOwnProperty('usedautosave')) {
          //TODO: add these question parts to the dirty tracker
          // need to track dirty by-part for this to work
          delete data.questions[i].usedautosave;
        }
      }
    }
    if (data.hasOwnProperty('showscores')) {
      data['show_scores_during'] = (data.showscores === 'during');
    }
    if (data.hasOwnProperty('regen')) {
      data['regens_remaining'] = (data.regens_max - data.regen);
    }
    if (data.hasOwnProperty('timelimit_expires')) {
      clearTimeout(store.timelimit_timer);
      let now = new Date();
      let expires = new Date(data.timelimit_expires * 1000);
      if (expires > now) {
        store.timelimit_timer = setTimeout(()=>{this.handleTimelimitUp();}, expires - now);
        store.timelimit_expired = false;
      } else {
        store.timelimit_expired = true;
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
            for (let j=lastDisplayBefore; j<data.interquestion_text[i].displayBefore; j++) {
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
        for (let j=lastDisplayBefore; j<data.interquestion_text[data.interquestion_text.length - 1].displayBefore; j++) {
          qs.push(j);
        }
        data.interquestion_pages[data.interquestion_pages.length - 1][0].questions = qs;
        delete data.interquestion_text;
      } else {
        delete data.interquestion_pages;
      }
    }
    return data;
  }
};
