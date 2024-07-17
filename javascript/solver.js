	   $(function() {
        controlEditor.setOption("extraKeys", {
         "Ctrl-M": function(cm) {
          handleSageEvent();
         }
	    });


		$("#solveroperation").on("change",operationChange );
		$("#solveropenbutton").on("click",handleSageEvent );
		$("#solverclosebutton").on("click",hideSolver );
		$("#solverclosebutton").on("touchend",function(e) {
			if (e.preventDefault) {e.preventDefault()}; hideSolver()
			});
		$("#solvercopy").on("click",handleSolverCopy);
		//prevent Enter to submit in popup, rather evaluate sagemath
		$("#imathastosage").on("keydown",function (event) {
			if (event.keyCode === 13) {
				handleSolverGo();
				event.preventDefault();
			}
			return true; //process as usual
		});
		//when the Sage CodeMirror div is edited, increase opacity
		$("#sagecell").on("click", handleSageClick );
		$("#sagecell").on("keyup", handleSageClick );

		$("#imathastosage").on("click",handleImathasClick);
		$("#imathastosage").on("keyup",handleImathasClick);
		$("#imathastosage").on("focus",handleImathasClick);
		$("#imathastosage").on("drop",function(event) {handleDrop(event);});
		//TODO make paste event trigger
		//$("#imathastosage").on("paste",function(event) {handleDrop(event);});
		$("#sagetocontrol").on("dragstart", function(event) {
			handleDragStart(event);
		});
		$("#sagetocontrol").on("dragend", function(event) {
			controlEditor.removeOverlay("answer");
		});
		$("#solverappend").on("click",handleSolverAppend);
		$("#solverappendalone").on("click",handleSolverAppendAlone);
		$("#solverhelpicon").on("click",handleSolverHelp);
		$("#solverinputhelpicon").on("click",handleSolverInputHelp);
		$("#solveroutputhelpicon").on("click",handleSolverOutputHelp);
		$("#solvergobutton").on("click",handleSolverGo);

		//capture debug message to detect when Sagecell evaluate is complete
		(function(){
			var originallog = console.debug;
			console.debug = function(txt) {
			// Do really interesting stuff
			if (txt.search("kernel_idle.Kernel") >=0) {
				handleSolverCopy();
			}
			originallog.apply(console, arguments);
			}
		})();

		//store the location of the solver div
		function initializeSolverPos() {
			var pos = $("#solverpopup").position();
			lastsolverpos = {
				left: pos.left,
				top: pos.top,
				scroll: $(window).scrollTop()
			};
		}

		$("#solvertopbar").mousedown(function(evt) {
				if (lastsolverpos===undefined||lastsolverpos===null) {
					initializeSolverPos();
				}
				if (evt.preventDefault) {evt.preventDefault()};
				$("body").addClass("unselectable");
				solvermousebase = {left:evt.pageX, top: evt.pageY};
				$("body").bind("mousemove",solvermousemove);
				$("body").mouseup(function(event) {
					var p = $("#solverpopup").position();
					lastsolverpos.left = p.left;
					lastsolverpos.top = p.top;
					$("body").unbind("mousemove",solvermousemove);
					$("body").removeClass("unselectable");
					$(this).unbind(event);
					});
				});


		$("#solvertopbar").bind("touchstart", function(evt) {
			if (evt.preventDefault) {evt.preventDefault()};
			var touch = evt.originalEvent.changedTouches[0] || evt.
			originalEvent.touches[0];
			solvermousebase = {left:touch.pageX, top: touch.pageY};
			$("body").addClass("unselectable");
			$("body").bind("touchmove",solvertouchmove);
			$("body").bind("touchend", function(event) {
				var p = $("#solverpopup").offset();
				lastsolverpos.left = p.left;
				lastsolverpos.top = p.top;
				$("body").unbind("touchmove",solvertouchmove);
				$("body").removeClass("unselectable");
				$(this).unbind(event);
			});
		});

		//CodeMirror mode to highlight $answer lines during drag
		CodeMirror.defineMode("answer", function() {
			return {
				token: function(stream) {
					//look for a line containing $answer[...]
					var tw_pos = stream.string.search(/\$answers?[\[\]0-9\s]*=/i);
					stream.skipToEnd();
					if (tw_pos === -1) {
						stream.skipToEnd();
						return null;
					}
					return "searching";
			}
		  };
		});
	});
	   





		function handleSageEvent() {
			//copy some styles to avoid additional colors
			/*if ( $("#navlist").css("color") !==undefined) {
				$("#solvergobutton").css( "color",
						$("#navlist").css("color") );
				$("#solvertopbar").css( "color",
						$("#navlist").css("color") );
			}
			if ( $("#navlist").css("background-color") !==undefined) {
				$("#solvergobutton").css( "background-color",
						$("#navlist").css("background-color") );
				$("#solvertopbar").css( "background-color",
						$("#navlist").css("background-color") );
			}*/
			var sagecommand = '';
			$("#solverpopup").show()
				if (controlEditor.somethingSelected()) {
					var selection=controlEditor.getSelection();
					$("#imathastosage").val(selection);
					var formula_variables = convertMomSageVariables(selection,true);
					sagecommand=guessSageCommand(formula_variables);
					//sendSageCommand(sagecommand);
				}
			if (controlEditor.getValue().search(/\$answer[\[\]\s0-9]*=/i) > 0) {
				//disable Append button
				$("#solverappend").prop("disabled", true);
				$("#solverappend").css("color", "#808080");
			} else {
				//enable Append button
				$("#solverappend").prop("disabled", false);
				$("#solverappend").css("color", "#000000");
			}
			// Make the div with id "sagecell" a Sage cell
			/*this.sm=sagecell.makeSagecell({inputLocation:  "#sagecell",
					outputLocation: "#sagecelloutput",
					//callback: handleSolverCopy,
					autoeval: true,
					codelocation: sagemathcode,
					hide: ["permalink","evalButton"],
					evalButtonText: "Evaluate"});
			$("#sagecell").find(".sagecell_evalButton").click();
			*/
			if ($("#sagecell iframe").length > 0) {
				document.getElementById("solversagecell").contentWindow.setCode(sagecommand);
			} else {
				$("#sagecell").append($("<iframe></iframe>", {
						id: "solversagecell",
						src: imasroot+"/javascript/solversagecell.html?code="+encodeURIComponent(sagecommand)
				}));
			}
		}
                                                   
		//facilitate copying to Common Control
		function handleSolverCopy() {
			//var sageoutput=$("#solverpopup").find(".sagecell_sessionOutput").text();
			var sageoutput=document.getElementById("solversagecell").contentWindow.getOutput();
			if (sageoutput.match(/Traceback|Error/i)) {
				//show error message
				$("#sagecelloutput").css("opacity",1.0);
				$("#sagetocontroldiv").hide();
			} else {
				$("#sagetocontroldiv").show();
				$("#sagetocontrol").val(convertMomSageVariables(sageoutput,false).formula);
				$("#sagetocontrol").text(convertMomSageVariables(sageoutput,false).formula);
				//$("#sagetocontrol").select();
			}
		}

		//Append output as $answer to Common Control
		function handleSolverAppend() {
			handleSolverCopy();
			controlEditor.setValue(controlEditor.getValue()+"\n$answer = \""+$("#sagetocontrol").val()+"\"");
		}

		//Append output to Common Control
		function handleSolverAppendAlone() {
			handleSolverCopy();
			controlEditor.setValue(controlEditor.getValue()+"\n"+$("#sagetocontrol").val());
		}

		//Show Solver Input help
		function handleSolverInputHelp() {
			//load using help.php
			$("#solverinputhelp").load("../help.php?section=solverinput&bare=true");
			$("#solverinputhelp").toggle();
		}

		//Show Sage help
		function handleSolverHelp() {
			//load using help.php
			$("#solverhelpbody").load("../help.php?section=solversage&bare=true");
			$("#solverhelpbody").find(".pagetitle").remove();
			$("#solverhelpbody").toggle();
		}

		//Show Sage Ouput help
		function handleSolverOutputHelp() {
			//load using help.php
			$("#solveroutputhelp").load("../help.php?section=solveroutput&bare=true");
			$("#solveroutputhelp").find(".pagetitle").remove();
			$("#solveroutputhelp").toggle();
		}

		//convert between IMathAS and Sage variables and syntax
		function convertMomSageVariables(formula,toSage) {
			//list of math functions from mathjs.js
			var math_functions = "sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root|arcsin|arccos|arctan|arcsec|arccsc|arccot|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth".split("|");
			formula=formula.replace(/[\"\"\`]/g,"");
			formula=formula.trim();
			if (toSage) {
				formula=formula.replace(/=/g,"==");
				//save this variables as a property for later
				var imathas_variables_with_dups=formula.match(/\$([a-z][a-z0-9_]*)/gi);
				this.imathas_variables=[];
				if (imathas_variables_with_dups) {
					imathas_variables_with_dups.forEach(function(value,index) {
						//add if not already
						value=value.replace("$","");
						if (this.imathas_variables.indexOf(value)===-1) {
							this.imathas_variables.push(value);
						}
					});
				}
				//sage variables include MOM variables (rands) plus any MOM symbolic variables
				var sage_variables_with_dups=formula.match(/([a-z][a-z0-9_]*)/gi);
				if (sage_variables_with_dups) {
					//remove math function names from sage_variables
					var sage_variables=sage_variables_with_dups.filter(function(variable,index,self) {
						return (math_functions.indexOf(variable)==-1 &&
									self.indexOf(variable)==index);
					});
				}
				if (sage_variables_with_dups && imathas_variables) {
					//list a symbolic variable before MOM variables (rands)
					var limit=sage_variables.length;
					while (imathas_variables.indexOf(sage_variables[0])>=0 && limit>0) {
						//rotate
						sage_variables.push(sage_variables.shift());
						limit--;
					}
				}
				//make multiplication explicit
				//don"t put a multiplication after math functions
				formula=formula.replace(/([a-z0-9]+) ?([\($])/gi,"$1*$2");
				formula=formula.replace(/([a-z0-9]+) ([a-z$])/gi,"$1*$2");
				formula=formula.replace(/([0-9]+)([a-z$])/gi,"$1*$2");
				//remove unintended multiplication after math functions
				math_functions.forEach(function (mathfunc) {
						var regex=new RegExp(mathfunc+"\\\*","ig");
						formula=formula.replace(regex,mathfunc);
						});
				formula=formula.replace(/\$/g,"");
				return { formula: formula, variables: sage_variables};
			} else {  //converting to MOM
				//remove initial "x =="
				formula=formula.replace(/\[[a-z]\w* ==\s*/ig,"");
				formula=formula.replace(/,\s?[a-z]\w* ==\s*/ig,",");
				if (this.imathas_variables!==undefined && this.imathas_variables!=null) {
					//prepend IMathAS variables with $
					imathas_variables.forEach(function(value,index) {
							var regex=new RegExp(value+"\\b","g");
							formula=formula.replace(regex,"$"+value)
							});
				}
				formula=formula.replace(/(^\[)|(\]$)/g,"");
				formula=formula.replace(/==/g,"=");
				return { formula: formula };
			}
		}

		//Guess the sage command based on the formula if selected
		function guessSageCommand(formula_variables) {
			var formula=formula_variables.formula;
			var variables=formula_variables.variables;
			if (variables===undefined||variables===null) {
				return formula;
			}

			var variable_list=variables.join(",");
			sagecommand=variable_list+"=var(\""+variable_list+"\")";
			var operation=$("#solveroperation").val();
			if (operation!==undefined && operation != "") {
				sagecommand=sagecommand+"\n"+operation+"( "+formula+" , "+variables[0]+")";
			} else if ( formula.indexOf("=")>=0 ) {
				//if no operation selected and contains =
				//assume solving for the first symbolic variable
				sagecommand=sagecommand+"\nsolve( "+formula+" , "+variables[0]+")";
				$("#solveroperation").val("solve")
										.trigger("chosen:updated");
			} else {
				//start with example of differentiating wrt the first symbolic variable
				sagecommand=sagecommand+"\ndiff( "+formula+" , "+variables[0]+")";
				$("#solveroperation").val("diff")
										.trigger("chosen:updated");
			}
			return sagecommand;
		}

		//Send command to Sage cell
		function sendSageCommand(command) {
			//append a new script element to #sagecell for new sagecell
			/*var script = document.createElement("script");
			script.type="text/x-sage";
			script.innerHTML=sagecommand;
			$("#sagecell").append(script);

			//update existing sagecell with new command
			if ( $("#sagecell").find(".CodeMirror").size() > 0 ) {
				$("#sagecell").find(".CodeMirror").get(0).CodeMirror.setValue(sagecommand);
			}*/
		}

		function hideSolver() {
			$("#solverpopup").hide();
			$("#solverpopup").css("display","none");
		}

		function operationChange() {
			if ($("#imathastosage").val()) {
				//added since the user might modify the imathastosage string then select an operation change 
				var formula_variables = convertMomSageVariables(
						$("#imathastosage").val(), true);
				var formula=formula_variables.formula;
				var variables=formula_variables.variables;
				var sagecommand;
				var operation=$("#solveroperation").val();
				if (variables===undefined||variables===null||operation===undefined || operation == "") {
					sagecommand = formula;
				} else {
					var variable_list=variables.join(",");
					var variablestr=variable_list+"=var(\""+variable_list+"\")";
					if (operation=="plot") {
						sagecommand=variablestr+"\nplot("+formula+", ("+variables[0]+",-10,10))";
					} else if (operation=="simplify") {
						sagecommand="def fullsimp(expr):return expr.simplify_full().simplify_trig()\n"+variablestr+"\nfullsimp("+formula+")";
					} else {
						sagecommand=variablestr+"\n"+operation+"( "+formula+" , "+variables[0]+")";
					}
				}
				
				//$("#sagecell").find(".CodeMirror").get(0).CodeMirror.setValue(sagecommand);;
				document.getElementById("solversagecell").contentWindow.setCode(sagecommand);
			} else {
				if ( $("#sagecell").find(".CodeMirror").size() <= 0 ) {
					return; //no code window found
				}
				var sagecommand=$("#sagecell").find(".CodeMirror").get(0).CodeMirror.getValue();
				//remove fullsimp def if there
				sagecommand = sagecommand.replace(/.*?def\s+fullsimp.*?\n/,"");
				//get variable declaration
				variables=sagecommand.replace(/\n.*/i,"");
				//remove previous operation
				sagecommand=sagecommand.replace(/.*\n\s*(diff|solve|integral|plot|fullsimp)/i,"");
				//remove plot range if present
				sagecommand=sagecommand.replace(/,\s*\(([a-z_0-9]+),[-0-9\s,]*\)\)/i,", $1)");
				//coming from fullsimp, re-add variable
				if (!sagecommand.match(/,/)) {
					var firstvar = variables.match(/^(.*?),/);
					sagecommand=sagecommand.replace(/\)\s*$/,","+firstvar[1]+")");
				}
				if ($("#solveroperation").val()=="diff") {
					sagecommand=variables+"\ndiff"+sagecommand;
				} else if ($("#solveroperation").val()=="solve") {
					sagecommand=variables+"\nsolve"+sagecommand;
				} else if ($("#solveroperation").val()=="integral") {
					sagecommand=variables+"\nintegral"+sagecommand;
				} else if ($("#solveroperation").val()=="plot") {
					var variable=sagecommand.replace(/.*,\s*([\w_]+)\s*\)/im,"$1");
					sagecommand=sagecommand.replace(/(.*),\s*[\w_]+\s*\)/im,"$1");
					sagecommand=variables+"\nplot"+sagecommand+", ("+variable+",-10,10))";
				} else if ($("#solveroperation").val()=="simplify") {
					sagecommand=sagecommand.replace(/,\s*([a-z0-9_]+)/i,"");
					sagecommand="def fullsimp(expr):return expr.simplify_full().simplify_trig()\n"+variables+"\nfullsimp"+sagecommand;
				}
				//$("#sagecell").find(".CodeMirror").get(0).CodeMirror.setValue(sagecommand);
				document.getElementById("solversagecell").contentWindow.setCode(sagecommand);
			}
			//$("#sagecell").find(".sagecell_evalButton").click();
		}

		function handleSolverGo() {
			if ($("#imathastosage").val()) {
				var formula_variables = convertMomSageVariables(
						$("#imathastosage").val(), true);
				sagecommand=guessSageCommand(formula_variables);
				document.getElementById("solversagecell").contentWindow.setCode(sagecommand);
				//sendSageCommand(sagecommand);
			}
			//$("#sagecell").find(".sagecell_evalButton").click();
		}

		function handleImathasClick() {
			//for IE, guess initial position
			var go_offset = $("#solvergobutton").offset();
			go_offset.top = $("#imathastosage").offset().top
			$("#solvergobutton").offset(go_offset);

			$("#solvergobutton").css("top","initial");
		}

		function handleSageClick() {
			$("#imathastosage").val("");
			//TODO save initial location for IE
			var go_offset = $("#solvergobutton").offset();
			go_offset.top = $(".sagecell_input").offset().top
			$("#solvergobutton").offset(go_offset);
			$(".sagecell_input").css("opacity",1.0);
			$("#sagecell").css("opacity",1.0);
			$("#sagecelloutput").css("opacity",1.0);
		}

		function handleDrop(event) {
			//clear the CodeMirror selection
			controlEditor.setCursor(controlEditor.getCursor());
			var imathas_dropped = event.originalEvent.dataTransfer.getData("Text");
			var formula_variables = convertMomSageVariables(imathas_dropped , true);
			sagecommand=guessSageCommand(formula_variables);
			//sendSageCommand(sagecommand);
			//$("#sagecell").find(".sagecell_evalButton").click();
			document.getElementById("solversagecell").contentWindow.setCode(sagecommand);
			return false;
		}

		function handleDragStart(event) {
			event.originalEvent.dataTransfer.setData("text", $("#"+event.target.id).text());
			controlEditor.addOverlay("answer");
		}

		var lastsolverpos, solvermousebase;
		function solvermousemove(evt) {
			$("#solverpopup").css("left", (evt.pageX - solvermousebase.left) + lastsolverpos.left)
				.css("top", (evt.pageY - solvermousebase.top) + lastsolverpos.top);
			if (evt.preventDefault) {evt.preventDefault()};
			if (evt.stopPropagation) {evt.stopPropagation()};
			return false;
		}

		function solvertouchmove(evt) {
			var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];

			$("#solverpopup").css("left", (touch.pageX - solvermousebase.left) + lastsolverpos.left)
				.css("top", (touch.pageY - solvermousebase.top) + lastsolverpos.top);
			if (evt.preventDefault) {evt.preventDefault()};
			if (evt.stopPropagation) {evt.stopPropagation()};

			return false;
		}
