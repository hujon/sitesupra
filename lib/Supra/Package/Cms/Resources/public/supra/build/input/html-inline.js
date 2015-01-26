/**
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
YUI.add("supra.input-inline-html", function (Y) {
	//Invoke strict mode
	"use strict";
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = false;
	
	Input.NAME = "input-html-inline";
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		'doc': {
			value: null
		},
		'win': {
			value: null
		},
		'toolbar': {
			value: null
		},
		'inline': {
			value: true,
			readOnly: true
		},
		// HTML plugin information
		'plugins': {
			value: null
		},
		'defaultValue': {
			value: ''
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Constants
		 */
		INPUT_TEMPLATE: '',
		LABEL_TEMPLATE: '',
		CONTENT_TEMPLATE: null,
		
		/**
		 * HTMLEditor instance
		 * @type {Object}
		 * @private
		 */
		htmleditor: null,
		
		
		
		
		/**
		 * ----------------------------------- PRIVATE --------------------------------------
		 */
		
		
		
		/**
		 * Attach event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			if (this.htmleditor) {
				this.htmleditor.after('change', function (evt) {
					this.fire('change');
				}, this);
			}
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var doc = this.get('doc'),
				win = this.get('win'),
				src = this.get('srcNode'),
				toolbar = this.get('toolbar');
			
			if (!toolbar) {
				var action = Supra.Manager.EditorToolbar;
				if (action) {
					toolbar = action.getToolbar();
				}
			}
			
			if (doc && win && src) {
				this.htmleditor = new Supra.HTMLEditor({
					'doc': doc,
					'win': win,
					'srcNode': src,
					'iframeNode': this.get('nodeIframe'),
					'toolbar': toolbar,
					'mode': Supra.HTMLEditor.MODE_RICH,
					'parent': this,
					'root': this.get('root') || this,
					'disabled': this.get('disabled'),
					'plugins': this.get('plugins')
				});
				this.htmleditor.render();
			}
		},
		
		/**
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			if (this.htmleditor) {
				this.htmleditor.detach('change');
				this.htmleditor.destroy();
				this.htmleditor = null;
			}
		},
		
		
		
		/**
		 * ----------------------------------- API --------------------------------------
		 */
		
		
		
		/**
		 * Returns HTMLEditor instance
		 * 
		 * @return HTMLEditor instance
		 * @type {Object}
		 */
		getEditor: function () {
			return this.htmleditor;
		},
		
		/**
		 * Returns attribute value
		 * 
		 * @param {String} key Attribute name
		 * @return Attribute value
		 * @type {String}
		 */
		getAttribute: function (key) {
			return this.get('srcNode').getAttribute(key);
		},
		
		
		
		
		/**
		 * ----------------------------------- ATTRIBUTES --------------------------------------
		 */
		
		
		
		/**
		 * Disabled attribute setter
		 * Disable / enable HTMLEditor
		 * 
		 * @param {Boolean} value New state value
		 * @return New state value
		 * @type {Boolean}
		 * @private
		 */
		_setDisabled: function (value) {
			if (value) {
				this.blur();
			}
			
			if (this.htmleditor) {
				this.htmleditor.set('disabled', !!value);
				return !!value;
			}
			
			return false;
		},
		
		/**
		 * Value attribute getter
		 * Returns value, object with 'html' and 'data' keys
		 * 
		 * @param {Object} value Previous value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_getValue: function (value) {
			if (this.htmleditor) {
				//Remove data which is not bound to anything
				this.htmleditor.removeExpiredData();
				
				return {
					'html': this.htmleditor.getHTML(),
					'data': this.htmleditor.getAllData(),
					'fonts': this.htmleditor.getUsedFonts()
				};
			} else {
				return value;
			}
		},
		
		/**
		 * saveValue attribute getter
		 * Returns value for sending to server, object with 'html' and 'data' keys
		 * 
		 * @param {Object} value Previous value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_getSaveValue: function (value) {
			if (this.htmleditor) {
				return {
					'html': this.htmleditor.getProcessedHTML(),
					'data': this.htmleditor.getProcessedData(),
					'fonts': this.htmleditor.getUsedFonts()
				};
			} else {
				return value;
			}
		},
		
		/**
		 * Value attribute setter
		 * Set HTMLEdtior html and data
		 * 
		 * @param {Object} value New value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			if (typeof value === 'string') {
				value = {
					data: {},
					html: value
				};
			}
			
			if (this.htmleditor) {
				this.htmleditor.setAllData(value ? value.data : {});
				this.htmleditor.setHTML(value ? value.html : '');
			}
			
			return value;
		}
		
	});
	
	Input.lipsum = function () {
		return {
			'data': {},
			'html': Supra.Lipsum.html()
		};
	};
	
	Supra.Input.InlineHTML = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-proto", "supra.htmleditor"]});
