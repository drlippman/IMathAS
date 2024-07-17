export function mapInterquestionTexts (data, questions) {
  let lasttext = -1;
  const origtexts = data.interquestion_text;
  const newtexts = [];
  for (const i in questions) {
    if (questions[i].hasOwnProperty('text')) {
      const thistext = questions[i].text;
      if (JSON.stringify(thistext) === JSON.stringify(lasttext)) { // same one
        for (let j = 0; j < thistext.length; j++) {
          newtexts[newtexts.length - 1 - j].displayUntil = i;
        }
      } else {
        for (let j = 0; j < thistext.length; j++) {
          newtexts.push(Object.assign({ orig: 1e5 }, origtexts[thistext[j]]));
          newtexts[newtexts.length - 1].displayBefore = i;
          newtexts[newtexts.length - 1].displayUntil = i;
        }
        lasttext = thistext.slice();
      }
    } else {
      lasttext = -1;
    }
  }
  for (const i in origtexts) {
    if (origtexts[i].hasOwnProperty('displayBefore')) {
      newtexts.push(Object.assign({ orig: i }, origtexts[i]));
    }
    if (origtexts[i].hasOwnProperty('atend')) {
      newtexts.push(Object.assign({ orig: i }, origtexts[i]));
      newtexts[newtexts.length - 1].displayBefore = questions.length;
      newtexts[newtexts.length - 1].displayUntil = questions.length;
    }
  }
  if (newtexts.length > 0) {
    newtexts.sort(function (a, b) {
      if (parseInt(a.displayBefore) === parseInt(b.displayBefore)) {
        return (a.orig - b.orig);
      } else {
        return a.displayBefore - b.displayBefore;
      }
    });
    data.interquestion_text = newtexts;
  }
}

export function mapInterquestionPages (data, questions) {
  data.interquestion_pages = [];
  let lastDisplayBefore = 0;
  // ensure proper data type on these
  for (const i in data.interquestion_text) {
    data.interquestion_text[i].displayBefore = parseInt(data.interquestion_text[i].displayBefore);
    data.interquestion_text[i].displayUntil = parseInt(data.interquestion_text[i].displayUntil);
    data.interquestion_text[i].forntype = (parseInt(data.interquestion_text[i].forntype) > 0);
    data.interquestion_text[i].ispage = (parseInt(data.interquestion_text[i].ispage) > 0);
    if (data.interquestion_text[i].ispage) {
      // if a new page, start a new array in interquestion_pages
      // first, add a question list to the previous page
      if (data.interquestion_pages.length > 0) {
        const qs = [];
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
  if (data.interquestion_pages.length > 0 && !!questions) {
    const qs = [];
    for (let j = lastDisplayBefore; j < questions.length; j++) {
      qs.push(j);
    }
    data.interquestion_pages[data.interquestion_pages.length - 1][0].questions = qs;
    // don't delete, as we may use it for print version
    // delete data.interquestion_text;
  } else {
    delete data.interquestion_pages;
  }
}
