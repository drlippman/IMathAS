/*************************************************************
 *
 *  MathJax/extensions/InputToDataAttr.js
 *
 *  Implements an extension that takes the input TeX or AsciiMath
 *  and inserts it as a data-tex or data-asciimath attribute
 *
 *  ---------------------------------------------------------------------
 *
 *  Copyright (c) 2016 David Lippman
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

(function (AJAX,CALLBACK,HUB,HTML) {

  var InputToDataAttr = MathJax.Extension["InputToDataAttr"] = {
    version: "2.7.9",

    Config: function () {
      HUB.Register.MessageHook("End Math",function (msg) {
        return InputToDataAttr.AddInputDataAttr(msg[1]);
      });
    },

    //
    //  This sets up a state object that lists the jax and index into the jax,
    //    and a dummy callback that is used to synchronizing with MathJax.
    //    It will be called when the jax are all processed, and that will
    //    let the MathJax queue continue (it will block until then).
    //
    AddInputDataAttr: function (node) {
      var state = {
        jax: HUB.getAllJax(node), i: 0,
        callback: MathJax.Callback({})
      };
      this.HandleJax(state);
      return state.callback;
    },

    //
    //  For each jax in the state, look up the frame.
    //  If the input jax was TeX or AsciiMath, then add a
    //    data-asciimath or data-tex attribute to the frame
    //  When all the jax are processed, call the callback.
    //
    HandleJax: function (state) {
      var m = state.jax.length, jax, frame, inputjax;
      while (state.i < m) {
        jax = state.jax[state.i];
        frame = document.getElementById(jax.inputID+"-Frame");
        inputjax = jax.inputJax.toLowerCase();
        if (frame && (inputjax=='asciimath' || inputjax=='tex')) {
          frame.setAttribute("data-"+inputjax, jax.originalText);
        }
        state.i++;
      }
      state.callback();
    }

  };

  MathJax.Hub.Register.StartupHook("End Config", InputToDataAttr.Config);

  HUB.Startup.signal.Post("InputToDataAttr Ready");

  HUB.Register.StartupHook("AsciiMath Jax Ready", function () {
    AM = MathJax.InputJax.AsciiMath.AM;
    AM.newsymbol({ input: "~", tag: "mo", output: "\u223C", ttype: AM.TOKEN.CONST });
    AM.newsymbol({ input: "sim", tag: "mo", output: "\u223C", ttype: AM.TOKEN.CONST });
  });

})(MathJax.Ajax,MathJax.Callback,MathJax.Hub,MathJax.HTML);

MathJax.Ajax.loadComplete("[Local]/InputToDataAttrCDN.js");
