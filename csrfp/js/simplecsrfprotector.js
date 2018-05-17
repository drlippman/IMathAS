/**
 * =================================================================
 * Javascript code for CSRF Protector
 * Adapted from OWASP Foundation's CSRFP Protector 
  * Licensed under the Apache License, Version 2.0

 * Task it does: Fetch csrftoken from head, and attach it to every
 *			-- XHR
 *			-- dynamic forms
 * =================================================================
 */

var CSRFP = {
	CSRFP_TOKEN: 'csrfp_token',
	CSRFP_TOKEN_VALUE: 0,
	/**
	 * function to set CSRFP token from head
	 *
	 * @param: string, token value
	 *
	 * @return: void
	 */
	setToken: function(token) {
		CSRFP.CSRFP_TOKEN_VALUE = token;
	},
	/**
	 * function to get Auth key and return it to requesting function
	 *
	 * @param: void
	 *
	 * @return: string, csrftoken
	 */
	_getAuthKey: function() {
		return CSRFP.CSRFP_TOKEN_VALUE;
	},
	/**
	 * Function to create and return a hidden input element
	 * For storing the CSRFP_TOKEN
	 *
	 * @param void
	 *
	 * @return input element
	 */
	_getInputElt: function() {
		var hiddenObj = document.createElement("input");
		hiddenObj.setAttribute('name', CSRFP.CSRFP_TOKEN);
		hiddenObj.setAttribute('class', CSRFP.CSRFP_TOKEN);
		hiddenObj.type = 'hidden';
		hiddenObj.value = CSRFP._getAuthKey();
		return hiddenObj;
	},
	/**
	 * Returns absolute path for relative path
	 *
	 * @param base, base url
	 * @param relative, relative url
	 *
	 * @return absolute path (string)
	 */
	_getAbsolutePath: function(base, relative) {
		var stack = base.split("/");
		var parts = relative.split("/");
		// remove current file name (or empty string)
		// (omit if "base" is the current folder without trailing slash)
		stack.pop();

		for (var i = 0; i < parts.length; i++) {
			if (parts[i] == ".")
				continue;
			if (parts[i] == "..")
				stack.pop();
			else
				stack.push(parts[i]);
		}
		return stack.join("/");
	},
	/**
	 * Remove jcsrfp-token run fun and then put them back
	 *
	 * @param function
	 * @param reference form obj
	 *
	 * @retrun function
	 */
	_csrfpWrap: function(fun, obj) {
		return function(event) {
			// Remove CSRf token if exists
			if (typeof obj[CSRFP.CSRFP_TOKEN] !== 'undefined') {
				var target = obj[CSRFP.CSRFP_TOKEN];
				target.parentNode.removeChild(target);
			}

			// Trigger the functions
			var result = fun.apply(this, [event]);

			// Now append the csrfp_token back
			obj.appendChild(CSRFP._getInputElt());

			return result;
		};
	}
};

//==========================================================
// Adding tokens, wrappers on window onload
//==========================================================

function csrfprotector_init() {

	// definition of basic FORM submit event handler to intercept the form request
	// and attach a CSRFP TOKEN if it's not already available
	var BasicSubmitInterceptor = function(event) {
		if (typeof event.target[CSRFP.CSRFP_TOKEN] === 'undefined') {
			event.target.appendChild(CSRFP._getInputElt());
		} else {
			//modify token to latest value
			event.target[CSRFP.CSRFP_TOKEN].value = CSRFP._getAuthKey();
		}
	}

	//==================================================================
	// Adding csrftoken to request resulting from <form> submissions
	// Add for each POST, while for mentioned GET request
	// TODO - check for method
	//==================================================================
	// run time binding
	//document.querySelector('body').addEventListener('submit', function(event) {
	jQuery("body").on('submit', function(event) {
		if (event.target.tagName.toLowerCase() === 'form') {
			BasicSubmitInterceptor(event);
		};
	});

	//==================================================================
	// Adding csrftoken to request resulting from direct form.submit() call
	// Add for each POST
	// TODO - check for form method
	//==================================================================
	HTMLFormElement.prototype.submit_ = HTMLFormElement.prototype.submit;
	HTMLFormElement.prototype.submit = function() {
		// check if the FORM already contains the token element
		if (!jQuery(this).find("."+CSRFP.CSRFP_TOKEN).length)
			this.appendChild(CSRFP._getInputElt());
		this.submit_();
	}


	/**
	 * Add wrapper for HTMLFormElements addEventListener so that any further
	 * addEventListens won't have trouble with CSRF token
	 */
	 if (typeof HTMLFormElement.prototype.addEventListener !== 'undefined') {
		HTMLFormElement.prototype.addEventListener_ = HTMLFormElement.prototype.addEventListener;
		HTMLFormElement.prototype.addEventListener = function(eventType, fun, bubble) {
			if (eventType === 'submit') {
				var wrapped = CSRFP._csrfpWrap(fun, this);
				this.addEventListener_(eventType, wrapped, bubble);
			} else {
				this.addEventListener_(eventType, fun, bubble);
			}
		}
	 }
	/**
	 * Add wrapper for IE's attachEvent
	 * todo - check for method
	 * todo - typeof is now obselete for IE 11, use some other method.
	 */
	if (typeof HTMLFormElement.prototype.attachEvent !== 'undefined') {
		HTMLFormElement.prototype.attachEvent_ = HTMLFormElement.prototype.attachEvent;
		HTMLFormElement.prototype.attachEvent = function(eventType, fun) {
			if (eventType === 'onsubmit') {
				var wrapped = CSRFP._csrfpWrap(fun, this);
				this.attachEvent_(eventType, wrapped);
			} else {
				this.attachEvent_(eventType, fun);
			}
		}
	}


	//==================================================================
	// Wrapper for XMLHttpRequest & ActiveXObject (for IE 6 & below)
	// Set X-No-CSRF to true before sending if request method is
	//==================================================================

	/**
	 * Wrapper to XHR open method
	 * Add a property method to XMLHttpRequst class
	 * @param: all parameters to XHR open method
	 * @return: object returned by default, XHR open method
	 */
	function new_open(method, url, async, username, password) {
		this.method = method;
		var isAbsolute = (url.indexOf("./") === -1) ? true : false;
		if (!isAbsolute) {
			var base = location.protocol +'//' +location.host
							+ location.pathname;
			url = CSRFP._getAbsolutePath(base, url);
		}

		return this.old_open(method, url, async, username, password);
	}

	/**
	 * Wrapper to XHR send method
	 * Add query paramter to XHR object
	 *
	 * @param: all parameters to XHR send method
	 *
	 * @return: object returned by default, XHR send method
	 */
	function new_send(data) {
		if (this.method.toLowerCase() === 'post') {
			// attach the token in request header
			this.setRequestHeader(CSRFP.CSRFP_TOKEN, CSRFP._getAuthKey());
		}
		return this.old_send(data);
	}

	if (window.XMLHttpRequest) {
		// Wrapping
		XMLHttpRequest.prototype.old_send = XMLHttpRequest.prototype.send;
		XMLHttpRequest.prototype.old_open = XMLHttpRequest.prototype.open;
		XMLHttpRequest.prototype.open = new_open;
		XMLHttpRequest.prototype.send = new_send;
	}
	if (typeof ActiveXObject !== 'undefined') {
		ActiveXObject.prototype.old_send = ActiveXObject.prototype.send;
		ActiveXObject.prototype.old_open = ActiveXObject.prototype.open;
		ActiveXObject.prototype.open = new_open;
		ActiveXObject.prototype.send = new_send;
	}
}

jQuery(function() {
	csrfprotector_init();
});
