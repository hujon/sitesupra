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

/**
 * Font sidebar
 */
YUI().add("supra.htmleditor-plugin-fonts", function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	/*
	 * Font plugin handles font selection
	 */
	Supra.HTMLEditor.addPlugin("fonts", defaultConfiguration, {
		
		// Font input
		fontInput: null,
		
		// Font button
		fontFamilyInput: null,
		
		// Fore color button
		foreColorInput: null,
		
		// Back color button
		backColorInput: null,
		
		// Font size button
		fontSizeInput: null,
		
		// Updating input to reflect selected element styles
		silentUpdating: false,
		
		// Font list
		fonts: null,
		
		// Select color type, "fore" or "back"
		colorType: null,
		
		// Font size input change listener
		toolbarFontSizeChangeListener: null,
		
		googleFonts: null,
		
		
		/**
		 * Update selected element font
		 * 
		 * @private
		 */
		updateFont: function () {
			var htmleditor = this.htmleditor;
			
			if (!this.silentUpdating && !htmleditor.get("disabled")) {
				var value = this.fontInput.get("value"),
					data  = value ? this.fontInput.getValueData(value) : null,
					fonts = this.googleFonts;
				
				if (data) {
					if (!fonts) {
						fonts = new Supra.GoogleFonts({
							"doc": htmleditor.get("doc")
						});
					}
					
					fonts.addFonts([data]);
				}
				
				// Trim quotes, since attribute value shouldn't have quotes
				value = value.replace(/\"/g, '');
				
				this.exec(value, "fontname");
				htmleditor._changed();
			}
		},
		
		/**
		 * Update selected element text or background color
		 * 
		 * @private
		 */
		updateColor: function () {
			var htmleditor = this.htmleditor;
			
			if (!this.silentUpdating && !htmleditor.get('disabled')) {
				var value = this.colorInput.get("value");
				this.exec(value, this.colorType + "color");
				htmleditor._changed();
			}
		},
		
		/**
		 * Update selected element font size
		 * 
		 * @private
		 */
		updateFontSize: function () {
			var htmleditor = this.htmleditor;
			
			if (!this.silentUpdating && !htmleditor.get('disabled')) {
				var value = this.fontSizeInput.get("value");
				this.exec(value, "fontsize");
				htmleditor._changed();
			}
		},
		
		/**
		 * When node changes update font, font size and color input values
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleNodeChange: function (event) {
			var htmleditor = this.htmleditor,
				allowEditing = this.htmleditor.editingAllowed,
				element = null;
			
			this.silentUpdating = true;
			
			if (htmleditor.getSelectedElement('img, svg')) {
				// Image is selected, don't allow any text/font manipulation
				allowEditing = false;
			} else {
				element = htmleditor.getSelectedElement();
				
				if (this.color_settings_form && this.color_settings_form.get("visible")) {
					// Color settings form is opened
					var color = "";
					if (element) {
						
						//Traverse up the tree
						var tmpElement = element,
							srcElement = htmleditor.get("srcNode").getDOMNode();
						
						while (tmpElement && tmpElement.style) {
							
							if (this.colorType == "fore") {
								//Text color
								color = tmpElement.tagName === "FONT" ? tmpElement.getAttribute("color") : "";
							} else {
								//Background color
								color = tmpElement.style.backgroundColor || "";
							}
							
							if (color) {
								//Color found, stop traverse
								tmpElement = null;
							} else {
								tmpElement = tmpElement.parentNode;
								if (tmpElement === srcElement) tmpElement = null;
							}
						}
					}
					
					this.colorInput.set("value", color);
					
				} else if (this.font_settings_form && this.font_settings_form.get("visible")) {
					// Font face settings form is opened
					var face = null;
					if (element && element.tagName === "FONT") {
						face = element.getAttribute("face");
					} else {
						//Try finding font from the list, which matches selected font
						face = Y.Node(element).getStyle("fontFamily") || "";
					}
					this.fontInput.set("value", face);
				}
				
				if (element) {
					var size = parseInt(Y.Node(element).getStyle("fontSize"), 10);
					size = (size === 0 ? "0" : (size || " "));
					
					this.fontSizeInput.set("value", size);
				}
			}
			
			this.fontSizeInput.set("disabled", !allowEditing);
			this.fontFamilyInput.set("disabled", !allowEditing);
			this.foreColorInput.set("disabled", !allowEditing);
			this.backColorInput.set("disabled", !allowEditing);
			
			this.fontSizeInput.set("opened", false);
			this.silentUpdating = false;
		},
		
		/**
		 * When editing allowed changes update sidebar visibility
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleEditingAllowChange: function (event) {
			if (!event.allowed) {
				this.hideSidebar();
			}
			
			this.fontSizeInput.set("disabled", !event.allowed);
			this.fontFamilyInput.set("disabled", !event.allowed);
			this.foreColorInput.set("disabled", !event.allowed);
			this.backColorInput.set("disabled", !event.allowed);
		},
		
		/**
		 * Disabled attribute change
		 * 
		 * @param {Object} event Attribute change event facade object
		 * @private
		 */
		handleDisabledChange: function (event) {
			var listener = this.toolbarFontSizeChangeListener;
			
			if (event.newVal && listener) {
				//Disable
				listener.detach();
				this.toolbarFontSizeChangeListener = null;
			} else if (!event.newVal && !listener) {
				//Enable
				this.toolbarFontSizeChangeListener = this.fontSizeInput.after("valueChange", this.updateFontSize, this);
			}
		},
		
		
		/* -------------------------------------- API ---------------------------------------- */
		
		
		/**
		 * Execute command
		 * 
		 * @param {Object} data
		 * @param {String} command
		 * @return True on success, false on failure
		 * @type {Boolean}
		 */
		exec: function (data, command) {
			var htmleditor = this.htmleditor,
				history = htmleditor.getPlugin('history'),
				nodes, node,
				testNode,
				fontname,
				realSize,
				
				nodes,
				i, ii,
				
				found,
				changed = false;
			
			if (htmleditor.selectionIsCollapsed()) {
				// Increase selection to all element if there isn't any
				node = htmleditor.getSelectedElement();
				if (!node) return;
				
				htmleditor.selectNode(node);
				htmleditor.resetSelectionCache();
			} else {
				// IE looses selection
				htmleditor.setSelection(htmleditor.selection);
			}
			
			//If there were text changes then create separate state for it before
			//applying these changes
			history.pushTextState();
			
			if (command == "fontname") {
				nodes = htmleditor.getSelectedNodes().find('font', true);
				found = false;
				changed = false;
				
				for (i=0,ii=nodes.length; i<ii; i++) {
					node = nodes[i];
					testNode = node.parentNode;
				
					//If node font family is the same as new font, then don't set "face"
					fontname = Y.DOM.getStyle(testNode, 'fontFamily');
					if (fontname && fontname.indexOf(data.replace(/,.*/, '')) !== -1) {
						
						node.removeAttribute('face');
						
						if (this.cleanUpNode(node)) {
							changed = true;
						}
						
						found = true;
					}
				}
				
				if (found) {
					if (changed) {
						htmleditor._changed();
						htmleditor.refresh(true);
						history.pushState();
					}
					return;
				}
				
			} else if (command == "forecolor") {
				// Find FONT nodes, which have color set
				nodes = htmleditor.getSelectedNodes().find(function (node) {
					return node.nodeType === 1 && node.tagName === 'FONT' && (node.getAttribute('color') || node.style.color);
				}, true);
				
				found = false;
				changed = false;
				
				for (i=0, ii=nodes.length; i<ii; i++) {
					node = nodes[i];
					
					if (!data) {
						node.style.color = '';
						node.removeAttribute('color');
						
						if (this.cleanUpNode(node)) {
							changed = true;
						}
						
						found = true;
					} else {
						node.setAttribute("color", data);
						found = true;
					}
				}
				
				if (found) {
					if (changed) {
						htmleditor._changed();
						htmleditor.refresh(true);
						history.pushState();
					}
					return;
				}
			} else if (command == "backcolor") {
				
				if (!data) {
					// Remove background color
					// Find closest elements with background color
					nodes = htmleditor.getSelectedNodes().find(function (node) {
						return node.nodeType === 1 && node.style.backgroundColor;
					}, true);
					
					found = false;
					changed = false;
					
					for (i=0, ii=nodes.length; i<ii; i++) {
						node = nodes[i];
						
						if (node.tagName === 'SPAN') {
							htmleditor.unwrapNode(node);
							found = true;
							changed = true;
						} else {
							node.style.backgroundColor = '';
							found = true;
							
							if (this.cleanUpNode(node)) {
								changed = true;
							}
						}
					}
					
					if (found) {
						if (changed) {
							htmleditor._changed();
							htmleditor.refresh(true);
							history.pushState();
						}
						return;
					}
				} else {
					// Set background color
					nodes = htmleditor.getSelectedNodes().find('font', true);
					found = false;
					
					for (i=0, ii=nodes.length; i<ii; i++) {
						nodes[i].style.backgroundColor = data;
						found = true;
					}
					
					if (found) {
						history.pushState();
						return;
					}
				}
				
			}
			
			//Inserts <font> for color, fontsize, fontname andbackground color
			var targetNode = null;
			
			if (command == "backcolor") {
				var selection = htmleditor.selection;
				if (selection.start === selection.end && selection.start_offset !== selection.end_offset) {
					node = htmleditor.replaceSelection(null, "FONT");
					if (node) {
						targetNode = node;
						node.style.backgroundColor = data;
					}
				} else {
					htmleditor.get("doc").execCommand(command, null, data);
				}
			} else {
				htmleditor.get("doc").execCommand(command, null, data);
			}
			
			if (targetNode) {
				htmleditor.selectNode(targetNode);
				htmleditor.refresh(true);
			} else {
				// If all text inside DIV, P, ... was selected, then selection didn't changed
				// (according to text), but new wrapper element was added, so need to reset
				htmleditor.resetSelectionCache();
			}
			
			if (command == "fontsize") {
				
				var nodes = htmleditor.getSelectedNodes().find({'filter': {'font': true}}, true),
					node,
					i = 0,
					ii = nodes.length,
					realSize;
				
				for (; i<ii; i++) {
					node = nodes[i];
					
					if (node.getAttribute('size') || node.className.indexOf('font-') !== -1) {
						node.removeAttribute('size');
						node.style.fontSize = '';
						node.className = '';
						
						//We want to make sure classname is actually needed by
						//checking if node default font size is different than which
						//we are trying to set
						realSize = parseInt(Y.DOM.getStyle(node, 'fontSize'), 10);
						
						if (data && data != realSize) {
							//Fontsize set as classname
							if (!this.isFontSizeCustom(data)) {
								node.className = 'font-' + data;
							} else {
								node.className = 'font-custom';
								node.style.fontSize = data + 'px';
							}
						} else {
							node.className = '';
						}
					}
				}
				
			} else if (command == 'backcolor' || command == 'forecolor') {
				// 'execCommand' may create SPAN instead of FONT, so replace SPAN elements with FONT
				var nodes = htmleditor.getSelectedNodes().find({'filter': {'span': true}}, true),
					node,
					i = 0,
					ii = nodes.length,
					tempNode;
				
				for (; i<ii; i++) {
					node = nodes[i];
					
					if (node.style.backgroundColor || node.style.color || node.getAttribute('color')) {
						tempNode = htmleditor.get('doc').createElement('FONT');
						node.parentNode.insertBefore(tempNode, node);
						
						while(node.firstChild) {
							tempNode.appendChild(node.firstChild);
						}
						
						if (command == 'backcolor') {
							tempNode.style.backgroundColor = node.style.backgroundColor;
						} else if (command == 'forecolor') {
							if (node.style.color) {
								tempNode.setAttribute('color', node.style.color); // Gecko
							} else if (node.getAttribute('color')) {
								tempNode.setAttribute('color', node.getAttribute('color')); // WebKit
							}
						}
						
						node.parentNode.removeChild(node);
					}
				}
			}
			
			//Remove <font> which don't have font size and font family and color 
			this.cleanUp();
			
			htmleditor._changed();
			htmleditor.refresh(true);
			
			history.pushState();
		},
		
		/**
		 * Returns true if given font size is custom size
		 * 
		 * @param {Number} size Font size
		 * @returns {Boolean} True if size is custom font size, otherwise false
		 */
		isFontSizeCustom: function (size) {
			var input = this.fontSizeInput;
			
			if (input && input.hasValue(size)) {
				return false;
			} else {
				return true;
			}
		},
		
		/**
		 * Remove node if doesn't have any styles
		 * 
		 * @param {Object} node Node
		 * @return True if node was removed, otherwise false
		 */
		cleanUpNode: function (node) {
			node = node.getDOMNode ? node.getDOMNode() : node;
			node.removeAttribute("size");
			
			if (!node.getAttribute("face") && !node.className && !node.getAttribute("color") && !node.style.backgroundColor) {
				this.htmleditor.unwrapNode(node);
				return true;
			}
			return false;
		},
		
		/**
		 * Remove all <font> nodes which don't have any style
		 */
		cleanUp: function () {
			var nodes = this.htmleditor.get("srcNode").all("font");
			nodes.each(Y.bind(this.cleanUpNode, this));
		},
		
		/**
		 * Returns list of used fonts
		 * 
		 * @return List of font API ids
		 */
		getUsedFonts: function () {
			var nodes = this.htmleditor.get("srcNode").all("font"),
				used = [];
			
			nodes.each(function (node) {
				var face  = node.getAttribute("face"),
					fonts = null,
					i     = 0,
					ii    = 0,
					safe  = Supra.GoogleFonts.SAFE_FONTS;
				
				if (face) {
					fonts = face.split(/\s*,\s*/g);
					for (ii=fonts.length; i<ii; i++) {
						if (Y.Array.indexOf(safe, fonts[i]) !== -1) {
							// Font is in the safe list, don't send it to server
							return;
						}
					}
					used.push(face);
				}
			});
			
			return Y.Array.unique(used);
		},
		
		/**
		 * Returns list of all fonts
		 * 
		 * @return List of all fonts from configuration
		 */
		getAllFonts: function () {
			return Supra.data.get(["supra.htmleditor", "fonts"]) || [];
		},
		
		
		/* -------------------------------------- Sidebar ---------------------------------------- */
		
		
		/**
		 * Create font sidebar
		 */
		createFontSidebar: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			var form_config = {
					"inputs": [{
						"id": "font",
						"type": "Fonts",
						"label": "",
						"values": []
					}],
					"style": "vertical"
				};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//When user selects a value, update content
			this.fontInput = form.getInput("font");
			this.fontInput.after("valueChange", this.updateFont, this);
			
			this.font_settings_form = form;
			return form;
		},
		
		/**
		 * Show fonts sidebar
		 */
		showFontSidebar: function () {
			//Make sure PageContentSettings is rendered
			var form = this.font_settings_form || this.createFontSidebar(),
				action = Manager.getAction("PageContentSettings"),
				toolbarName = "htmleditor-plugin";
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showFontSidebar(target);
					}
				} else {
					action.once("loaded", function () {
						this.showFontSidebar(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons(toolbarName)) {
				Manager.getAction('PageToolbar').addActionButtons(toolbarName, []);
				Manager.getAction('PageButtons').addActionButtons(toolbarName, []);
			}
			
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": Supra.Intl.get(["htmleditor", "fonts"]),
				"scrollable": false,
				"toolbarActionName": toolbarName
			});
			
			//Fonts toolbar button
			this.htmleditor.get("toolbar").getButton("fonts").set("down", true);
			
			//Hide text ::selection color, otherwise it's hard to see changes
			Y.Node(this.htmleditor.get('doc').body).addClass('su-hide-selection-color');
			
			Y.later(150, this, function () {
				this.htmleditor.focus();
				this.handleNodeChange();
			});
		},
		
		/**
		 * Create color sidebar
		 */
		createColorSidebar: function () {
			//Get form placeholder
			var content = Manager.getAction("PageContentSettings").get("contentInnerNode");
			if (!content) return;
			
			//Properties form
			var form_config = {
				"inputs": [{
					"id": "color",
					"type": "Color",
					"label": "",
					"allowUnset": true
				}],
				"style": "vertical"
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.hide();
			
			//When user selects a value, update content
			this.colorInput = form.getInput("color");
			this.colorInput.after("valueChange", this.updateColor, this);
			
			this.color_settings_form = form;
			return form;
		},
		
		/**
		 * Show color sidebar
		 */
		showColorSidebar: function () {
			//Make sure PageContentSettings is rendered
			var htmleditor = this.htmleditor,
				form = this.color_settings_form || this.createColorSidebar(),
				action = Manager.getAction("PageContentSettings"),
				toolbarName = "htmleditor-plugin",
				label = Supra.Intl.get(["htmleditor", this.colorType + "color"]);
			
			if (!form) {
				if (action.get("loaded")) {
					if (!action.get("created")) {
						action.renderAction();
						this.showColorSidebar(target);
					}
				} else {
					action.once("loaded", function () {
						this.showColorSidebar(target);
					}, this);
					action.load();
				}
				return false;
			}
			
			if (!Manager.getAction('PageToolbar').hasActionButtons(toolbarName)) {
				Manager.getAction('PageToolbar').addActionButtons(toolbarName, []);
				Manager.getAction('PageButtons').addActionButtons(toolbarName, []);
			}
			
			//Change color input label
			form.getInput('color').set('label', label) 
			
			//Show form
			action.execute(form, {
				"doneCallback": Y.bind(this.hideSidebar, this),
				"hideCallback": Y.bind(this.onSidebarHide, this),
				
				"title": label,
				"scrollable": true,
				"toolbarActionName": toolbarName
			});
			
			//Color toolbar button
			htmleditor.get("toolbar").getButton(this.colorType + "color").set("down", true);
			htmleditor.get("toolbar").getButton((this.colorType == "fore" ? "back" : "fore") + "color").set("down", false);
			
			//Update selected text/back color, because color picker could be showing for wrong one
			this.handleNodeChange({});
			
			//Hide text ::selection color, otherwise color change may not be visible untill
			//user deselects text
			Y.Node(htmleditor.get('doc').body).addClass('su-hide-selection-color');
			
			Y.later(150, this, function () {
				htmleditor.focus();
			});
		},
		
		/**
		 * Show or hide sidebar depending on button state
		 */
		toggleBackColorSidebar: function () {
			var button = this.htmleditor.get("toolbar").getButton("backcolor");
			if (button.get('down')) {
				this.showBackColorSidebar();
			} else {
				this.hideSidebar();
			}
		},
		
		/**
		 * Show text color sidebar
		 */
		showBackColorSidebar: function (e) {
			this.colorType = "back";
			this.showColorSidebar();
		},
		
		/**
		 * Show or hide sidebar depending on button state
		 */
		toggleTextColorSidebar: function () {
			var button = this.htmleditor.get("toolbar").getButton("forecolor");
			if (button.get('down')) {
				this.showTextColorSidebar();
			} else {
				this.hideSidebar();
			}
		},
		
		/**
		 * Show text color sidebar
		 * Handle text color button click
		 */
		showTextColorSidebar: function (e) {
			this.colorType = "fore";
			this.showColorSidebar();
		},
		
		/**
		 * Hide fonts sidebar
		 */
		hideSidebar: function () {
			if (this.font_settings_form && this.font_settings_form.get("visible")) {
				Manager.PageContentSettings.hide();
			} else if (this.color_settings_form && this.color_settings_form.get("visible")) {
				Manager.PageContentSettings.hide();
			}
		},
		
		/**
		 * When fonts sidebar is hidden update toolbar button to reflect that
		 * 
		 * @private
		 */
		onSidebarHide: function () {
			var htmleditor = this.htmleditor;
			
			//Show text ::selection color
			Y.Node(htmleditor.get('doc').body).removeClass('su-hide-selection-color');
			
			//Unstyle toolbar button
			htmleditor.get("toolbar").getButton("fonts").set("down", false);
			htmleditor.get("toolbar").getButton("forecolor").set("down", false);
			htmleditor.get("toolbar").getButton("backcolor").set("down", false);
		},
		
		
		/* -------------------------------------- Plugin ---------------------------------------- */
		
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor) {
			var toolbar = htmleditor.get("toolbar");
			
			this.silentUpdating = true;
			this.listeners = [];
			
			htmleditor.addCommand("fonts", Y.bind(this.showFontSidebar, this));
			htmleditor.addCommand("forecolor", Y.bind(this.toggleTextColorSidebar, this));
			htmleditor.addCommand("backcolor", Y.bind(this.toggleBackColorSidebar, this));
			
			// Show inputs / buttons
			var inputs = ["fonts", "fontsize", "forecolor", "backcolor"],
				i = 0,
				ii = inputs.length;
			
			for (;i<ii; i++) {
				toolbar.getButton(inputs[i]).set("visible", true);
			}
			
			// Inputs
			this.fontFamilyInput = toolbar.getButton("fonts");
			this.foreColorInput  = toolbar.getButton("forecolor");
			this.backColorInput  = toolbar.getButton("backcolor");
			
			var input = this.fontSizeInput = toolbar.getButton("fontsize"),
				values = input.get('values');
			
			//Special style
			input.addClass("align-center");
			
			//On enable/disable add or remove listener 
			this.listeners.push(
				htmleditor.on("disabledChange", this.handleDisabledChange, this)
			);
			
			//When un-editable node is selected disable toolbar button
			this.listeners.push(
				htmleditor.on("editingAllowedChange", this.handleEditingAllowChange, this)
			);
			this.listeners.push(
				htmleditor.on("nodeChange", this.handleNodeChange, this)
			);
			
			this.silentUpdating = false;
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			for(var i=0,ii=this.listeners.length; i<ii; i++) {
				this.listeners[i].detach();
			}
			
			if (this.toolbarFontSizeChangeListener) {
				this.toolbarFontSizeChangeListener.detach();
			}
			
			this.toolbarFontSizeChangeListener = null;
			this.listeners = null;
			this.fontInput = null;
			
			if (this.font_settings_form) {
				this.font_settings_form.destroy();
			}
			if (this.color_settings_form) {
				this.color_settings_form.destroy();
			}
		}
		
	});
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {"requires": ["supra.htmleditor-base", "supra.template", "supra.google-fonts"]});