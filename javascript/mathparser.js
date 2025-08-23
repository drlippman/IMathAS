"use strict";
/**
 * Utility front-end for Parser
 * Returns a function that can be evaluated
 */
function makeMathFunction(str, vars, allowedfuncs, fvlist, docomplex) {
  vars = vars || '';
  allowedfuncs = allowedfuncs || [];
  fvlist = fvlist || '';
  docomplex = (docomplex === undefined) ? false : docomplex;
  if (str.trim() === '') {
    return false;
  }
  try {
    var parser = new MathParser(vars, allowedfuncs, fvlist, docomplex);
    parser.parse(str);
    return function (varvals) {
      try {
        return parser.evaluate(varvals);
      } catch (error) {
        return Math.sqrt(-1); // NaN
      }
    };
  } catch (error) {
    console.error("Parse error on: " + str + ". Error: " + error.message);
    return false;
  }
}

/**
 * Front-end for math parser. Evaluates numerical mathematical expression.
 * Returns NaN on parse or eval error
 */
function evalMathParser(str, docomplex) {
  docomplex = (docomplex === undefined) ? false : docomplex;

  if (str.trim() === '') {
    return Math.sqrt(-1); // NaN
  }
  try {
    var parser = new MathParser('', [], '', docomplex);
    parser.parse(str);
    return parser.evaluate();
  } catch (error) {
    console.error("Parse error on: " + str + ". Error: " + error.message);
    return Math.sqrt(-1); // NaN
  }
}

/**
 * Math expression parser and evaluator.
 * Ported from PHP version by David Lippman
 */
function MathParser(variables, allowedfuncs, fvlist, docomplex) {
  this.functions = [];
  this.variables = [];
  this.funcvariables = [];
  this.operators = {};
  this.tokens = [];
  this.operatorStack = [];
  this.operandStack = [];
  this.AST = [];
  this.regex = '';
  this.funcregex = '';
  this.numvarregex = '';
  this.variableValues = {};
  this.origstr = '';
  this.docomplex = docomplex;
  this.allowEscinot = true;

  // Parse variables
  if (variables !== '') {
    this.variables = variables.split('|').map(function (v) {
      return v.trim();
    }).filter(function (v) {
      return v.length > 0;
    });
  }
  if (fvlist !== '') {
    this.funcvariables = fvlist.split('|').map(function (v) {
      return v.trim();
    }).filter(function (v) {
      return v.length > 0;
    });
  }

  // Add built-in constants
  this.variables.push('pi', 'e');
  if (docomplex) {
    this.docomplex = true;
    this.variables.push('i');
  }

  // Sort variables by length (longest first)
  this.variables.sort(function (a, b) { return b.length - a.length });
  this.allowEscinot = (this.variables.indexOf('E') === -1); // no E

  // Define functions
  if (allowedfuncs.length > 0) {
    this.functions = allowedfuncs;
  } else {
    this.functions = 'funcvar,arcsinh,arccosh,arctanh,arcsech,arccsch,arccoth,arcsin,arccos,arctan,arcsec,arccsc,arccot,root,sqrt,sign,sinh,cosh,tanh,sech,csch,coth,abs,sin,cos,tan,sec,csc,cot,exp,log,div,ln'.split(',');
  }

  // Build regex patterns
  var allwords = this.functions.concat(this.variables, ['degree', 'degrees']);
  allwords.sort(function (a, b) { return b.length - a.length });
  this.regex = new RegExp('^(' + allwords.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') }).join('|') + ')');
  this.funcregex = new RegExp('(' + this.functions.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') }).join('|') + ')', 'i');
  this.numvarregex = new RegExp('^(\\d+\\.?\\d*|' + this.variables.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') }).join('|') + ')');

  this.initializeOperators();
}

MathParser.prototype.initializeOperators = function () {
  var _this = this;
  this.operators = {
    '+': {
      precedence: 11,
      assoc: 'left',
      evalfunc: function (a, b) {
        if (_this.docomplex) {
          return [a[0] + b[0], a[1] + b[1]];
        } else {
          return a + b;
        }
      }
    },
    '-': {
      precedence: 11,
      assoc: 'left',
      evalfunc: function (a, b) {
        if (_this.docomplex) {
          return [a[0] - b[0], a[1] - b[1]];
        } else {
          return a - b;
        }
      }
    },
    '*': {
      precedence: 12,
      assoc: 'left',
      evalfunc: function (a, b) {
        if (_this.docomplex) {
          return [a[0] * b[0] - a[1] * b[1], a[0] * b[1] + a[1] * b[0]];
        } else {
          return a * b;
        }
      }
    },
    '/': {
      precedence: 12,
      assoc: 'left',
      evalfunc: function (a, b) {
        if (_this.docomplex) {
          if (Math.abs(b[0]) < 1e-50 && Math.abs(b[1]) < 1e-50) {
            throw new Error("Division by zero");
          }
          var den = b[0] * b[0] + b[1] * b[1];
          return [
            (a[0] * b[0] + a[1] * b[1]) / den,
            (a[1] * b[0] - a[0] * b[1]) / den
          ];
        } else {
          if (Math.abs(b) < 1e-50) {
            throw new Error("Division by zero");
          }
          return a / b;
        }
      }
    },
    '^': {
      precedence: 18,
      assoc: 'right',
      evalfunc: function (a, b) {
        if (_this.docomplex) {
          if (b[1] === 0) {
            var m = safepow(a[0] * a[0] + a[1] * a[1], b[0] / 2);
            var t = Math.atan2(a[1], a[0]);
            return [m * Math.cos(t * b[0]), m * Math.sin(t * b[0])];
          } else {
            var arg = Math.atan2(a[1], a[0]);
            var m = safepow(a[0] * a[0] + a[1] * a[1], b[0] / 2) * Math.exp(-b[1] * arg);
            var inVal = b[0] * arg + 0.5 * b[1] * Math.log(a[0] * a[0] + a[1] * a[1]);
            return [m * Math.cos(inVal), m * Math.sin(inVal)];
          }
        } else {
          return safepow(a, b);
        }
      }
    },
    '!': { precedence: 20, assoc: 'right' },
    '~': { precedence: 16, assoc: 'left' },
    'not': { precedence: 16, assoc: 'right' },
    '&&': {
      precedence: 8,
      assoc: 'left',
      evalfunc: function (a, b) { return a && b; }
    },
    '||': {
      precedence: 7,
      assoc: 'left',
      evalfunc: function (a, b) { return a || b; }
    },
    '#a': {
      precedence: 8,
      assoc: 'right',
      evalfunc: function (a, b) { return a && b; }
    },
    '#x': {
      precedence: 7,
      assoc: 'right',
      evalfunc: function (a, b) { return !!(a ^ b); } // XOR
    },
    '#o': {
      precedence: 7,
      assoc: 'right',
      evalfunc: function (a, b) { return a || b; }
    },
    '#m': {
      precedence: 7,
      assoc: 'right',
      evalfunc: function (a, b) { return a && (!b); }
    },
    '#i': {
      precedence: 6,
      assoc: 'right',
      evalfunc: function (a, b) { return (!a) || b; }
    },
    '#b': {
      precedence: 6,
      assoc: 'right',
      evalfunc: function (a, b) { return (a && b) || (!a && !b); }
    },
    '<': {
      precedence: 6,
      assoc: 'left',
      evalfunc: function (a, b) { return (a < b) ? 1 : 0; }
    },
    '>': {
      precedence: 6,
      assoc: 'left',
      evalfunc: function (a, b) { return (a > b) ? 1 : 0; }
    },
    '<=': {
      precedence: 6,
      assoc: 'left',
      evalfunc: function (a, b) { return (a <= b) ? 1 : 0; }
    },
    '>=': {
      precedence: 6,
      assoc: 'left',
      evalfunc: function (a, b) { return (a >= b) ? 1 : 0; }
    },
    '(': true,
    ')': true
  };
}

MathParser.prototype.parse = function (str) {
  this.origstr = str;
  str = str.replace(/Math\./g,'');
  str = str.replace(/(ar|arg)(sinh|cosh|tanh|sech|csch|coth)/g, 'arc$2');
  str = str.replace(/[\\[\]`]/g, '').replace(/[\[\]]/g, '()');
  // Handle |x| as abs(x)
  str = str.replace(/(?<!\|)\|([^|]+?)\|(?!\|)/g, 'abs($1)');

  this.tokenize(str);
  this.handleImplicit();
  this.buildTree();
  return this.AST;
}

MathParser.prototype.evaluate = function (variableValues) {
  if (typeof variableValues === 'undefined') {
    variableValues = {};
  }
  for (var i = 0; i < this.variables.length; i++) {
    var v = this.variables[i];
    if (v === 'pi' || v === 'e' || (this.docomplex && v === 'i')) continue;
    if (!(v in variableValues)) {
      throw new Error('Missing value for variable ' + v);
    } else if (typeof variableValues[v] !== 'number') {
      throw new Error('Invalid input value for variable ' + v);
    }
  };
  this.variableValues = variableValues;
  if (this.AST.length === 0) {
    return '';
  }
  let out = this.evalNode(this.AST);
  if (this.docomplex && !Array.isArray(out)) {
    out = [out, 0];
  }
  return out;
}

MathParser.prototype.evaluateQuiet = function (variableValues) {
  try {
    return this.evaluate(variableValues);
  } catch (error) {
    return Math.sqrt(-1);
  }
}

MathParser.prototype.tokenize = function (str) {
  str = str.replace(this.funcregex, function (match) { return match.toLowerCase(); });
  var tokens = [];
  var len = str.length;
  let lastTokenType = '';

  for (let n = 0; n < len; n++) {
    var c = str[n];

    if (/\s/.test(c)) {
      continue;
    } else if (/[\d.]/.test(c)) {
      let pattern;
      if (this.allowEscinot) {
        pattern = /^(\d*\.?\d*(E[+-]?\d+(?!\.))?)/;
      } else {
        pattern = /^(\d*\.?\d*)/;
      }
      var matches = str.substr(n).match(pattern);

      if (matches[1] === '.') continue;

      tokens.push({
        type: 'number',
        symbol: parseFloat(matches[1])
      });
      lastTokenType = 'number';
      n += matches[1].length - 1;
      continue;
    } else if (['|', '&', '#', '<', '>'].indexOf(c) !== -1 &&
      this.operators[str.substr(n, 2)]) {
      tokens.push({
        type: 'operator',
        symbol: str.substr(n, 2)
      });
      n++;
      lastTokenType = 'operator';
      continue;
    } else if (this.operators[c]) {
      tokens.push({
        type: 'operator',
        symbol: c
      });
      lastTokenType = 'operator';
      continue;
    } else {
      var matches = str.substr(n).match(this.regex);
      if (matches) {
        var nextSymbol = matches[1];

        if (this.funcvariables.indexOf(nextSymbol) !== -1 &&
          n + nextSymbol.length < str.length &&
          str[n + nextSymbol.length] === '(') {
          tokens.push({
            type: 'function',
            symbol: 'funcvar',
            input: null,
            index: { type: 'variable', symbol: nextSymbol }
          });
          lastTokenType = 'function';
        } else if (this.variables.indexOf(nextSymbol) !== -1) {
          tokens.push({
            type: 'variable',
            symbol: nextSymbol
          });
          lastTokenType = 'variable';
        } else if (nextSymbol === 'div') {
          tokens.push({
            type: 'operator',
            symbol: '/'
          });
          lastTokenType = 'operator';
        } else {
          if (nextSymbol === 'log') {
            tokens.push({
              type: 'function',
              symbol: 'log',
              input: null,
              index: { type: 'number', symbol: 10 }
            });
          } else if (nextSymbol === 'ln') {
            tokens.push({
              type: 'function',
              symbol: 'log',
              input: null,
              index: { type: 'number', symbol: Math.E }
            });
          } else if (nextSymbol === 'degree' || nextSymbol === 'degrees') {
            tokens.push({
              type: 'number',
              symbol: Math.PI / 180
            });
          } else {
            tokens.push({
              type: 'function',
              symbol: nextSymbol,
              input: null
            });
          }
          lastTokenType = 'function';
        }

        n += nextSymbol.length - 1;

        // Handle special cases like log_2(x) and sin^2(x)
        if (lastTokenType === 'function' && n < len - 2) {
          var peek = str[n + 1];

          if (nextSymbol === 'log' && peek === '_') {
            var sub = str.substr(n + 2).match(this.numvarregex);
            if (sub) {
              tokens[tokens.length - 1].index = {
                type: isNaN(sub[1]) ? 'variable' : 'number',
                symbol: sub[1]
              };
              n += sub[1].length + 1;
            } else if (str[n + 2] === '(') {
              tokens[tokens.length - 1].symbol += '_';
              n += 1;
            }
          } else if (peek === '^') {
            var sub = str.substr(n + 2).match(/^(-?\d+|\((-?\d+)\))/);
            if (sub) {
              tokens[tokens.length - 1].symbol += '^' + (sub[2] || sub[1]);
              n += sub[1].length + 1;
            }
          } else if (nextSymbol === 'root') {
            if (peek === '(') {
              tokens[tokens.length - 1].symbol += '(';
            } else {
              var sub = str.substr(n + 1).match(/^[\(\[]*(-?\d+)[\)\]]*/);
              if (sub) {
                tokens[tokens.length - 1] = {
                  type: 'function',
                  symbol: 'nthroot',
                  input: null,
                  index: {
                    type: 'number',
                    symbol: parseInt(sub[1])
                  }
                };
                n += sub[0].length;
              } else {
                throw new Error("Invalid root index");
              }
            }
          }
        }
        continue;
      }
      throw new Error('Don\'t know how to handle symbol: ' + c);
    }
  }

  this.tokens = tokens;
  return tokens;
}

MathParser.prototype.handleImplicit = function () {
  var out = [];
  let lastToken = { type: '', symbol: '' };

  for (var i=0; i < this.tokens.length; i++) {
    var token = this.tokens[i];
    if ((lastToken.type === 'number' ||
      lastToken.type === 'variable' ||
      lastToken.symbol === '!' ||
      lastToken.symbol === ')') &&
      (token.type === 'number' ||
        token.type === 'variable' ||
        token.type === 'function' ||
        token.symbol === '(')) {
      out.push({ type: 'operator', symbol: '*' });
      out.push(token);
    } else if (lastToken.type === 'function' && token.symbol !== '(') {
      out.push({ type: 'operator', symbol: '(' });
      out.push(token);
      out.push({ type: 'operator', symbol: ')' });
    } else {
      out.push(token);
    }
    lastToken = token;
  };

  this.tokens = out;
}

MathParser.prototype.buildTree = function () {
  this.operatorStack = [];
  this.operandStack = [];
  let lastNode = null;

  for (let tokenindex = 0; tokenindex < this.tokens.length; tokenindex++) {
    var token = this.tokens[tokenindex];

    if (token.symbol === ')') {
      this.handleSubExpression(tokenindex);
    } else if (token.type === 'number' || token.type === 'variable') {
      this.operandStack.push(token);
    } else if (token.type === 'function') {
      this.operatorStack.push(token);
    } else if (token.symbol === '(') {
      this.operatorStack.push(token);
    } else if (token.symbol === '!') {
      var unary = this.isUnary(token, lastNode);
      if (unary) {
        token.symbol = 'not';
        this.operatorStack.push(token);
        if (tokenindex + 1 < this.tokens.length &&
          this.tokens[tokenindex + 1].symbol === '*') {
          this.tokens.splice(tokenindex + 1, 1);
        }
      } else {
        if (this.operandStack.length === 0) {
          throw new Error("Syntax error: ! without something to apply it to");
        }
        var op = this.operandStack.pop();
        this.operandStack.push({
          type: 'function',
          symbol: 'factorial',
          input: op
        });
      }
    } else if (token.type === 'operator') {
      var unary = this.isUnary(token, lastNode);
      if (unary) {
        if (token.symbol === '+') {
          token.type = '';
          token.symbol = '';
        } else if (token.symbol === '-') {
          token.symbol = '~';
        }
      } else {
        while (this.operatorStack.length > 0) {
          var peek = this.operatorStack[this.operatorStack.length - 1];
          var peekinfo = this.operators[peek.symbol];
          var tokeninfo = this.operators[token.symbol];

          if (typeof peekinfo === 'boolean' || typeof tokeninfo === 'boolean') {
            break;
          }

          if (tokeninfo.precedence < peekinfo.precedence ||
            (tokeninfo.precedence === peekinfo.precedence &&
              tokeninfo.assoc === 'left')) {
            var popped = this.operatorStack.pop();
            var handled = this.handleExpression(popped);
            this.operandStack.push(handled);
          } else {
            break;
          }
        }
      }

      if (token.symbol !== '') {
        this.operatorStack.push(token);
      }
    }

    if (token.symbol !== '') {
      lastNode = token;
    }
  }

  while (this.operatorStack.length > 0) {
    var popped = this.operatorStack.pop();
    var handled = this.handleExpression(popped);
    this.operandStack.push(handled);
  }

  if (this.operandStack.length > 1) {
    throw new Error("Syntax error - expression didn't terminate");
  }

  this.AST = this.operandStack.pop();
}

MathParser.prototype.isUnary = function (token, lastNode) {
  if (['-', '+', '!'].indexOf(token.symbol) === -1) {
    return false;
  }

  if ((this.operandStack.length === 0 && this.operatorStack.length === 0) ||
    (lastNode.type === 'operator' && lastNode.symbol !== ')' &&
      lastNode.symbol !== '!')) {
    return true;
  }
  return false;
}

MathParser.prototype.handleExpression = function (node) {
  if (node.type === 'function' || node.symbol === '(') {
    throw new Error("Syntax error - parentheses mismatch");
  }

  if (node.symbol === '~' || node.symbol === 'not') {
    var left = this.operandStack.pop();
    if (left === undefined) {
      throw new Error("Syntax error - unary negative with nothing following");
    }
    node.left = left;
    return node;
  }

  var right = this.operandStack.pop();
  var left = this.operandStack.pop();
  if (left === undefined || right === undefined) {
    throw new Error("Syntax error");
  }
  node.left = left;
  node.right = right;
  return node;
}

MathParser.prototype.handleSubExpression = function (tokenindex) {
  let clean = false;
  let popped;

  while ((popped = this.operatorStack.pop())) {
    if (popped.symbol === '(') {
      clean = true;
      break;
    }
    var node = this.handleExpression(popped);
    this.operandStack.push(node);
  }

  if (!clean) {
    throw new Error("Syntax error - parentheses mismatch");
  }

  if (this.operatorStack.length > 0) {
    var previous = this.operatorStack[this.operatorStack.length - 1];
    if (previous.type === 'function') {
      var node = this.operatorStack.pop();
      var operand = this.operandStack.pop();

      if (node.symbol === 'log_') {
        if (operand === undefined) {
          throw new Error("Syntax error - missing index");
        }
        node.symbol = 'log';
        node.index = operand;
        this.operatorStack.push(node);
        if (tokenindex + 1 < this.tokens.length &&
          this.tokens[tokenindex + 1].symbol === '*') {
          this.tokens.splice(tokenindex + 1, 1);
        }
        return;
      } else if (node.symbol === 'root(') {
        if (operand === undefined) {
          throw new Error("Syntax error - missing index");
        }
        node.symbol = 'nthroot';
        node.index = operand;
        this.operatorStack.push(node);
        if (tokenindex + 1 < this.tokens.length &&
          this.tokens[tokenindex + 1].symbol === '*') {
          this.tokens.splice(tokenindex + 1, 1);
        }
        return;
      } else {
        if (operand === undefined) {
          throw new Error("Syntax error - missing function input");
        }
        node.input = operand;
      }

      if (node.symbol.indexOf('^') !== -1) {
        var sides = node.symbol.split('^');
        var subSymbol = sides[0], power = sides[1];
        if (power === '-1' && (window['a' + subSymbol] || Math['a' + subSymbol])) {
          node.symbol = 'arc' + subSymbol;
        } else {
          node.symbol = subSymbol;
          var powerNode = {
            type: 'operator',
            symbol: '^',
            left: node,
            right: {
              type: 'number',
              symbol: parseInt(power)
            }
          };
          this.operandStack.push(powerNode);
          return;
        }
      }
      this.operandStack.push(node);
    }
  }
}

MathParser.prototype.evalNode = function (node) {
  if (!node) {
    throw new Error("Cannot evaluate an empty expression");
  }

  if (node.type === 'number') {
    if (this.docomplex) {
      if (Array.isArray(node.symbol)) {
        return [parseFloat(node.symbol[0]), parseFloat(node.symbol[1])];
      } else {
        return [parseFloat(node.symbol), 0];
      }
    } else {
      return parseFloat(node.symbol);
    }
  } else if (node.type === 'variable') {
    if (node.symbol in this.variableValues) {
      if (this.docomplex && typeof this.variableValues[node.symbol] === 'number') {
        return [parseFloat(this.variableValues[node.symbol]), 0];
      } else {
        return this.variableValues[node.symbol];
      }
    } else if (node.symbol === 'pi') {
      return this.docomplex ? [Math.PI,0] : Math.PI;
    } else if (node.symbol === 'e') {
      return this.docomplex ? [Math.E,0] : Math.E;
    } else if (this.docomplex && node.symbol === 'i') {
      return [0, 1];
    } else {
      throw new Error("Variable found without a provided value");
    }
  } else if (node.type === 'function') {
    var insideval = this.evalNode(node.input);
    let indexval;
    if (node.index) {
      indexval = this.evalNode(node.index);
    }

    let funcname = node.symbol;

    // Domain checks for real functions
    if (!this.docomplex) {
      switch (funcname) {
        case 'sqrt':
          if (insideval < 0) {
            throw new Error('Invalid input to ' + funcname);
          }
          break;
        case 'log':
          if (insideval <= 0) {
            throw new Error('Invalid input to ' + funcname);
          }
          if (indexval <= 0) {
            throw new Error('Invalid base to ' + funcname);
          }
          break;
        case 'arcsin':
        case 'arccos':
          var rounded = Math.round(insideval * 1e12) / 1e12;
          if (rounded < -1 || rounded > 1) {
            throw new Error('Invalid input to ' + funcname);
          }
          break;
        case 'factorial':
          if (Math.round(insideval) !== Math.floor(insideval) || insideval < 0) {
            throw new Error('invalid factorial input ' + insideval);
          } else if (insideval > 150) {
            throw new Error('too big of factorial input ' + insideval);
          }
          break;
      }
    }

    // Rewrite arctan to atan to match JS function names
    funcname = funcname.replace('arc', 'a');

    if (this.docomplex) {
      funcname = 'cplx_' + funcname;
      if (!Array.isArray(insideval)) {
        insideval = [insideval, 0];
      }
    }

    if (node.index) {
      return this.callFunction(funcname, insideval, indexval);
    }
    return this.callFunction(funcname, insideval);
  } else if (node.symbol === '~') {
    // unary negation
    if (this.docomplex) {
      var ev = this.evalNode(node.left);
      if (Array.isArray(ev)) {
        return [-1 * ev[0], -1 * ev[1]];
      } else {
        return -1 * ev;
      }
    } else {
      return -1 * this.evalNode(node.left);
    }
  } else if (node.symbol === 'not') {
    // unary not
    return !this.evalNode(node.left);
  } else if (this.operators[node.symbol]) {
    // operator
    var opfunc = this.operators[node.symbol].evalfunc;
    return opfunc(
      this.evalNode(node.left),
      this.evalNode(node.right)
    );
  } else {
    throw new Error("Syntax error");
  }
}

MathParser.prototype.callFunction = function (funcname) {
  // Map function names to actual implementations
  var tocall = null;
  if (['sqrt','sin','cos','tan','asin','acos','atan','sinh','cosh','tanh',
      'asinh','acosh','atanh','exp','abs', 'sign'].indexOf(funcname) !== -1
  ) {
    tocall = Math[funcname];
  } else if (
    ['factorial','nthroot','sec','csc','cot','asec','acsc','acot','sech','csch','coth',
      'asech','acsch','acoth','funcvar','cplx_sqrt','cplx_sin','cplx_cos','cplx_tan',
      'cplx_log','cplx_exp','cplx_abs','cplx_asin','cplx_acos','cplx_atan','cplx_sinh',
      'cplx_cosh','cplx_tanh','cplx_asinh','cplx_acosh','cplx_atanh','cplx_sec','cplx_csc',
      'cplx_cot','cplx_sech','cplx_csch','cplx_coth','cplx_asec','cplx_acsc','cplx_acot',
      'cplx_asech','cplx_acsch','cplx_acoth','cplx_nthroot','cplx_factorial','cplx_funcvar'
    ].indexOf(funcname) !== -1
  ) {
    tocall = window[funcname];
  } else if (funcname === 'log') {
    tocall = function (x,base) {
      base = (base === undefined) ? Math.E : base;
      return Math.log(x) / Math.log(base);
    }
  } else if (funcname === 'ln') {
    tocall = Math.log;
  }

  if (tocall !== null) {
    let args = [];
    if (arguments.length > 1) {
      args = Array.prototype.slice.call(arguments, 1);
    }
    return tocall.apply(null, args);
  } else {
    throw new Error('Unknown function: ' + funcname);
  }
}

MathParser.prototype.isMultiple = function (a, b) {
  if (b === 0) return false;
  var v = Math.abs(a) / Math.abs(b);
  return Math.abs(Math.floor(v + 1e-10) - v) < 1e-8;
}


// Math function implementations
function factorial(x) {
  if (x < 0) return false;
  if (x === 0) return 1;
  for (let i = x - 1; i > 0; i--) {
    x *= i;
  }
  return x;
}

function nthroot(x, n) {
  if (x === 0) return 0;
  if (Math.floor(n) !== n) {
    return safepow(x, 1 / n);
  } else if (n % 2 === 0 && x < 0) {
    throw new Error("Can't take even root of negative value");
  } else if (n === 0) {
    throw new Error("Can't take 0th root");
  } else if (x < 0) {
    return -1 * Math.exp((1 / n) * Math.log(Math.abs(x)));
  } else {
    return Math.exp((1 / n) * Math.log(Math.abs(x)));
  }
}

function funcvar(input, v) {
  return v * Math.sin(v + input);
}

function safepow(base, power) {
  if (base === 0) {
    if (power === 0) {
      console.error("0^0 is undefined");
      return NaN;
    } else {
      return 0;
    }
  }
  if (typeof base !== 'number' || typeof power !== 'number') {
    console.error("cannot evaluate powers with nonnumeric values");
    return NaN;
  }
  if (base < 0 && Math.floor(power) !== power) {
    for (let j = 3; j < 50; j += 2) {
      if (Math.abs(Math.round(j * power) - (j * power)) < 0.000001) {
        if (Math.round(j * power) % 2 === 0) {
          return Math.exp(power * Math.log(Math.abs(base)));
        } else {
          return -1 * Math.exp(power * Math.log(Math.abs(base)));
        }
      }
    }
    console.error("invalid power for negative base");
    return NaN;
  }
  return Math.pow(base,power);
}

// polyfill
// Hyperbolic sine
if (!Math.sinh) {
  Math.sinh = function(x) {
    return (Math.exp(x) - Math.exp(-x)) / 2;
  };
}

// Hyperbolic cosine
if (!Math.cosh) {
  Math.cosh = function(x) {
    return (Math.exp(x) + Math.exp(-x)) / 2;
  };
}

// Hyperbolic tangent
if (!Math.tanh) {
  Math.tanh = function(x) {
    if (x === Infinity) return 1;
    if (x === -Infinity) return -1;
    var ePos = Math.exp(x);
    var eNeg = Math.exp(-x);
    return (ePos - eNeg) / (ePos + eNeg);
  };
}
// Inverse hyperbolic sine
if (!Math.asinh) {
  Math.asinh = function(x) {
    return Math.log(x + Math.sqrt(x * x + 1));
  };
}

// Inverse hyperbolic cosine
if (!Math.acosh) {
  Math.acosh = function(x) {
    if (x < 1) return NaN;
    return Math.log(x + Math.sqrt(x * x - 1));
  };
}

// Inverse hyperbolic tangent
if (!Math.atanh) {
  Math.atanh = function(x) {
    if (x <= -1 || x >= 1) return NaN;
    return 0.5 * Math.log((1 + x) / (1 - x));
  };
}

// Trigonometric cofunctions
function sec(x) {
  var val = Math.cos(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function csc(x) {
  var val = Math.sin(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function cot(x) {
  var val = Math.tan(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function sech(x) {
  var val = Math.cosh(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function csch(x) {
  var val = Math.sinh(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function coth(x) {
  var val = Math.tanh(x);
  return Math.abs(val) < 1e-16 ? NaN : 1/val;
}

function asec(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  var inv = Math.round((1 / x) * 1e12) / 1e12;
  return Math.acos(inv);
}

function acsc(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  var inv = Math.round((1 / x) * 1e12) / 1e12;
  return Math.asin(inv);
}

function acot(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  return Math.atan(1 / x);
}

function asech(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  var inv = Math.round((1 / x) * 1e12) / 1e12;
  return Math.acosh(inv);
}

function acsch(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  var inv = Math.round((1 / x) * 1e12) / 1e12;
  return Math.asinh(inv);
}

function acoth(x) {
  if (Math.abs(x) < 1e-16) {
    return NaN;
  }
  var inv = Math.round((1 / x) * 1e12) / 1e12;
  return Math.atanh(inv);
}

// Complex number functions
function cplx_subt(a, b) {
  return [a[0] - b[0], a[1] - b[1]];
}

function cplx_mult(a, b) {
  return [a[0] * b[0] - a[1] * b[1], a[0] * b[1] + a[1] * b[0]];
}

function cplx_div(n, d) {
  var ds = d[0] * d[0] + d[1] * d[1];
  if (ds === 0) {
    throw new Error("Cannot divide by zero in complex division");
  }
  return [(n[0] * d[0] + n[1] * d[1]) / ds, (n[1] * d[0] - n[0] * d[1]) / ds];
}

function cplx_sqrt(z) {
  return cplx_nthroot(z, 2);
}

function cplx_nthroot(z, b) {
  var r = z[0] * z[0] + z[1] * z[1];
  var m = safepow(r, b / (2 * b));
  var t = Math.atan2(z[1], z[0]);
  return [m * Math.cos(t / b), m * Math.sin(t / b)];
}

function cplx_log(z, b = Math.E) {
  var r = Math.sqrt(z[0] * z[0] + z[1] * z[1]);
  var t = Math.atan2(z[1], z[0]);
  return [Math.log(r) / Math.log(b), t / Math.log(b)];
}

function cplx_exp(z) {
  var r = Math.exp(z[0]);
  return [r * Math.cos(z[1]), r * Math.sin(z[1])];
}

function cplx_abs(z) {
  return Math.sqrt(z[0] * z[0] + z[1] * z[1]);
}

function cplx_sin(z) {
  return [Math.sin(z[0]) * Math.cosh(z[1]), Math.cos(z[0]) * Math.sinh(z[1])];
}

function cplx_cos(z) {
  return [Math.cos(z[0]) * Math.cosh(z[1]), -1 * Math.sin(z[0]) * Math.sinh(z[1])];
}

function cplx_tan(z) {
  return cplx_div(cplx_sin(z), cplx_cos(z));
}

function cplx_sec(z) {
  return cplx_div([1, 0], cplx_cos(z));
}

function cplx_csc(z) {
  return cplx_div([1, 0], cplx_sin(z));
}

function cplx_cot(z) {
  return cplx_div(cplx_cos(z), cplx_sin(z));
}

function cplx_sinh(z) {
  return [Math.sinh(z[0]) * Math.cos(z[1]), Math.cosh(z[0]) * Math.sin(z[1])];
}

function cplx_cosh(z) {
  return [Math.cosh(z[0]) * Math.cos(z[1]), Math.sinh(z[0]) * Math.sin(z[1])];
}

function cplx_tanh(z) {
  return cplx_div(cplx_sinh(z), cplx_cosh(z));
}

function cplx_sech(z) {
  return cplx_div([1, 0], cplx_cosh(z));
}

function cplx_csch(z) {
  return cplx_div([1, 0], cplx_sinh(z));
}

function cplx_coth(z) {
  return cplx_div(cplx_cosh(z), cplx_sinh(z));
}

function cplx_asin(z) {
  var zz = [z[0] * z[0] - z[1] * z[1], 2 * z[0] * z[1]];
  var s = cplx_nthroot([1 - zz[0], -1 * zz[1]], 2);
  var inVal = [s[0] - z[1], s[1] + z[0]];
  var ln = cplx_log(inVal);
  return [ln[1], -1 * ln[0]];
}

function cplx_acos(z) {
  var zz = [z[0] * z[0] - z[1] * z[1], 2 * z[0] * z[1]];
  var s = cplx_nthroot([zz[0] - 1, zz[1]], 2);
  var inVal = [s[0] + z[0], s[1] + z[1]];
  var ln = cplx_log(inVal);
  return [ln[1], -1 * ln[0]];
}

function cplx_atan(z) {
  var ln = cplx_log(cplx_div([-1 * z[0], 1 - z[1]], [z[0], 1 + z[1]]));
  return [2 * ln[1], -2 * ln[0]];
}

function cplx_asinh(z) {
  var r = cplx_sqrt([z[0] * z[0] - z[1] * z[1] + 1, 2 * z[0] * z[1]]);
  return cplx_log([z[0] + r[0], z[1] + r[1]]);
}

function cplx_acosh(z) {
  var r = cplx_sqrt([z[0] * z[0] - z[1] * z[1] - 1, 2 * z[0] * z[1]]);
  return cplx_log([z[0] + r[0], z[1] + r[1]]);
}

function cplx_atanh(z) {
  return cplx_mult([0.5, 1], cplx_log(cplx_div([1 + z[0], z[1]], [1 - z[0], -1 * z[1]])));
}

function cplx_asec(z) {
  var zz = [z[0] * z[0] - z[1] * z[1], 2 * z[0] * z[1]];
  var s = cplx_nthroot([1 - zz[0], -1 * zz[1]], 2);
  var inVal = cplx_div([1 + s[0], s[1]], z);
  var ln = cplx_log(inVal);
  return [ln[1], -1 * ln[0]];
}

function cplx_acsc(z) {
  var zz = [z[0] * z[0] - z[1] * z[1], 2 * z[0] * z[1]];
  var s = cplx_nthroot([zz[0] - 1, zz[1]], 2);
  var inVal = cplx_div([s[0], 1 + s[1]], z);
  var ln = cplx_log(inVal);
  return [ln[1], -1 * ln[0]];
}

function cplx_acot(z) {
  var ln = cplx_log(cplx_div([z[0], z[1] + 1], [z[0], z[1] - 1]));
  return [2 * ln[1], -2 * ln[0]];
}

function cplx_asech(z) {
  return cplx_acosh(cplx_div([1, 0], z));
}

function cplx_acsch(z) {
  return cplx_asinh(cplx_div([1, 0], z));
}

function cplx_acoth(z) {
  return cplx_mult([0.5, 1], cplx_log(cplx_div([z[0] + 1, z[1]], [z[0] - 1, z[1]])));
}

function cplx_factorial(z) {
  // Simplified complex factorial - would need gamma function for full implementation
  if (z[1] === 0) {
    return [factorial(z[0]), 0];
  }
  throw new Error("Complex factorial not fully implemented");
}

function cplx_funcvar(input, v) {
  if (!Array.isArray(input)) input = [input, 0];
  if (!Array.isArray(v)) v = [v, 0];
  return cplx_mult(v, cplx_sin([v[0] + input[0], v[1] + input[1]]));
}

// for asciisvg
var pi = Math.PI, ln = Math.log, e = Math.E;
var arcsin = Math.asin, arccos = Math.acos, arctan = Math.atan;

var funcstoindexarr = "sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root|arcsin|arccos|arctan|arcsec|arccsc|arccot|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|argsinh|argcosh|argtanh|argsech|argcsch|argcoth|arsinh|arcosh|artanh|arsech|arcsch|arcoth|pi".split("|");
function functoindex(match) {
	for (var i=0;i<funcstoindexarr.length;i++) {
		if (funcstoindexarr[i]==match) {
			return '@'+i+'@';
		}
	}
    return match;
}
function indextofunc(match, contents) {
	return funcstoindexarr[contents];
}

function matchtolower(match) {
	return match.toLowerCase();
}
function mathjs(st) {
  //translate a math formula to js function notation
  // a^b --> pow(a,b)
  // na --> n*a
  // (...)d --> (...)*d
  // sin^-1 --> arcsin etc.
  //while ^ in string, find term on left and right
  //slice and concat new formula string
  //parenthesizes the function variables
  st = st.replace(/(\+\s*-|-\s*\+)/g,'-').replace(/-\s*-/g,'+');
  st = st.replace(/\[/g,"(").replace(/\]/g,")");
  st = st.replace(/\b00+\./g,'0.');
  st = st.replace(/root\s*(\d+)/,"root($1)");
  st = st.replace(/\|(.*?)\|/g,"abs($1)");
  st = st.replace(/(Sin|Cos|Tan|Sec|Csc|Cot|Arc|Abs|Log|Exp|Ln|Sqrt)/gi, matchtolower);
  st = st.replace(/pi/g, "(pi)");
  //temp store of scientific notation
  st = st.replace(/([0-9])E([\-0-9])/g,"$1(EE)$2");
  st = st.replace(/\*?\s*degrees?/g,"*((pi)/180)");
  st = st.replace(/div/,'/');
  //convert named constants
  st = st.replace(/e/g, "(E)");
  //convert functions
  st = st.replace(/log_([a-zA-Z\d\.]+|\(([a-zA-Z\/\d\.]+)\))\s*\(/g,"nthlog($1,");
  st = st.replace(/log/g,"logten");
  st = st.replace(/(sin|cos|tan|sec|csc|cot|sinh|cosh|tanh|sech|csch|coth)\^(-1|\(-1\))/g,"arc$1");
  st = st.replace(/(sin|cos|tan|sec|csc|cot|ln)\^(\d+|\(\d+\))\s*\(/g,"$1n($2,");
  st = st.replace(/root\s*\(([a-zA-Z\/\d\.]+)\)\s*\(/g,"nthroot(($1),");

  //add implicit mult for "3 4"
  st = st.replace(/([0-9]\.?)\s+([0-9])/g,"$1*$2");

  //clean up
  st = st.replace(/#/g,"");
  st = st.replace(/\s/g,"");

  //add implicit multiplication
  st = st.replace(/([0-9]\.?)([\(a-zA-Z])/g,"$1*$2");
  st = st.replace(/(!)([0-9\(a-zA-Z])/g,"$1*$2");
  st = st.replace(/\)([\(0-9a-zA-Z]|\.\d+)/g,"\)*$1");

  //restore scientific notation
  st= st.replace(/([0-9])\*\(EE\)\*?([\-0-9])/g,"$1e$2");

  //convert powers
  var i,j,k, ch, nch, nested;
  while ((i=st.lastIndexOf("^"))!=-1) {
    //find left argument
    if (i==0) return "Error: missing argument";
    j = i-1;
    ch = st.charAt(j);
    if ((ch>="0" && ch<="9") || ch=='.') {// look for (decimal) number
      j--;
      while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      if (ch==".") {
        j--;
        while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      }
    } else if (ch==")") {// look for matching opening bracket and function name
      nested = 1;
      j--;
      while (j>=0 && nested>0) {
        ch = st.charAt(j);
        if (ch=="(") nested--;
        else if (ch==")") nested++;
        j--;
      }
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      j--;
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else {
      return "Error: incorrect syntax in "+st+" at position "+j;
    }
    //find right argument
    if (i==st.length-1) return "Error: missing argument";
    k = i+1;
    ch = st.charAt(k);
    nch = st.charAt(k+1);
    if (ch>="0" && ch<="9" || (ch=="-" && nch!="(") || ch==".") {// look for signed (decimal) number
      k++;
      while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      if (ch==".") {
        k++;
        while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      }
    } else if (ch=="(" || (ch=="-" && nch=="(")) {// look for matching closing bracket and function name
      if (ch=="-") { k++;}
      nested = 1;
      k++;
      while (k<st.length && nested>0) {
        ch = st.charAt(k);
        if (ch=="(") nested++;
        else if (ch==")") nested--;
        k++;
      }
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      k++;
      while (k<st.length && ((ch=st.charAt(k))>="a" && ch<="z" ||
               ch>="A" && ch<="Z")) k++;
      if (ch=='(' && st.slice(i+1,k).match(/^(sinn|cosn|tann|secn|cscn|cotn|sin|cos|tan|sec|csc|cot|logten|nthlogten|log|ln|exp|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|sqrt|abs|nthroot|factorial|safepow)$/)) {
	      nested = 1;
	      k++;
	      while (k<st.length && nested>0) {
		ch = st.charAt(k);
		if (ch=="(") nested++;
		else if (ch==")") nested--;
		k++;
	      }
      }
    } else {
      return "Error: incorrect syntax in "+st+" at position "+k;
    }
    st = st.slice(0,j+1)+"safepow("+st.slice(j+1,i)+","+st.slice(i+1,k)+")"+
           st.slice(k);
  }
  return st;
}