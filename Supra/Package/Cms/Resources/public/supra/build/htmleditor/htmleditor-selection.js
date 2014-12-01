YUI().add('supra.htmleditor-selection', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Current selection
		 * @type {Object}
		 */
		selection: null,
		
		selectedElement: null,
		
		/**
		 * Path to currently selected element
		 * @type {Array}
		 */
		path: null,
		
		/**
		 * Returns path to currently selected element
		 * 
		 * @return Path to element
		 * @type {Array}
		 */
		getSelectionPath: function () {
			if (this.path) return this.path;
			if (!this.selection) return null;
			
			var path = [], node = this.selection.start, container = Y.Node.getDOMNode(this.get('srcNode'));
			
			while(node && node !== container) {
				if (node.nodeType == 1) path.push(node);
				node = node.parentNode;
			}
			
			this.path = path;
			return path;
		},
		
		/**
		 * Restore selection
		 * 
		 * @param {Object} selection
		 */
		setSelection: function (selection, force) {
			if (this.get('disabled') || force) return;
			
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
				//Standard compatible browsers
				var sel = win.getSelection();
				var range = sel && sel.rangeCount ? sel.getRangeAt(0) : doc.createRange();
				
				try {
					//Preventing error when DOM node doesn't exist
					range.setStart(selection.start, selection.start_offset);
					range.setEnd(selection.end, selection.end_offset);
					
					sel.removeAllRanges();
					sel.addRange(range);
				} catch (err) {
				}
				
				this.resetSelectionCache(selection);
			}
		},
		
		/**
		 * Move cursor to the end of the content
		 */
		deselect: function () {
			var content = this.get('srcNode').getDOMNode();
			if (!content) {
				// Was called after editor is destroyed
				return;
			}
			
			var children = content.childNodes,
				child = children.length ? children[children.length - 1] : null;
			
			while (child && child.nodeType != 3) {
				while (child && child.nodeType != 3 && (child.nodeType != 1 || !child.childNodes.length)) {
					child = child.previousSibling;
				}
				
				if (child && child.nodeType == 1) {
					child = child.childNodes[child.childNodes.length - 1];
				}
			}
			
			if (child && child.nodeType == 3) {
				this.setSelection({
					'start': child,
					'start_offset': child.textContent.length,
					'end': child,
					'end_offset': child.textContent.length
				}, true);
			}
		},
		
		/**
		 * Returns true if selection is collapsed
		 * 
		 * @return True if collapsed, otherwise false
		 * @type {Boolean}
		 */
		selectionIsCollapsed: function () {
			return !this.selection || this.selection.collapsed;
		},
		
		/**
		 * Returns element in which cursor is positioned
		 * Optionally searching for closest (parent) element matching selector or 
		 * if function is provided then uses it for testing element
		 * 
		 * @param {String} selector Optional. Will return first element matching selector
		 * @return HTMLElement or null
		 * @type {HTMLElement}
		 */
		getSelectedElement: function (selector) {
			if (this.selectedElement) {
				if (selector) {
					if (typeof selector === 'string') {
						if (selector == ':block') {
							selector = Supra.HTMLEditor.ELEMENTS_BLOCK_ARR.join(',');
						} else if (selector == ':inline') {
							selector = Supra.HTMLEditor.ELEMENTS_INLINE_ARR.join(',');
						}
						
						//Find closest element matching selector
						var node = new Y.Node(this.selectedElement),
							container = Y.Node.getDOMNode(this.get('srcNode'));
						
						//Don't traverse up more than container	
						while (node && !node.compareTo(container)) {
							if (node.test(selector)) return Y.Node.getDOMNode(node);
							node = node.get('parentNode');
						}
						
						return null;
					} else {
						//Find closest element which returns true for element
						var node = new Y.Node(this.selectedElement),
							container = Y.Node.getDOMNode(this.get('srcNode'));
						
						//Don't traverse up more than container	
						while (node && !node.compareTo(container)) {
							if (selector(node)) return Y.Node.getDOMNode(node);
							node = node.get('parentNode');
						}
						
						return null;
					}
				}
				
				return this.selectedElement;
			}
			
			var selection = this.selection,
				container = Y.Node.getDOMNode(this.get('srcNode'));
			
			if (!selection) return null;
			
			var node = selection.end || selection.start;
			if (selection.end_offset === 0 && (!selection.collapsed)) {
				node = selection.start || selection.end;
			}
			
			//Find HTMLElement
			while(node && node !== container) {
				if (node.nodeType != 1) {
					node = node.parentNode;
				} else {
					this.selectedElement = node;
					if (selector) {
						return this.getSelectedElement(selector);
					} else {
						return node;
					}
				}
			}
			
			this.selectedElement = (node === container ? container : null);
			if (selector && this.selectedElement) {
				return this.getSelectedElement(selector);
			} else {
				return this.selectedElement;
			}
		},
		
		/**
		 * Returns all nodes between selection start and end
		 * 
		 * @returns {Array} List of nodes
		 */
		getSelectedNodes: function () {
			/*
			var selection = this.selection,
				container = Y.Node.getDOMNode(this.get('srcNode')),
				common_ancestor = null,
				contains_start = [],
				contains_end = [],
				start_ancestor = null,
				end_ancestor = null,
				include = true;
			
			if (!selection) {
				return [];
			} else if (selection.collapsed || selection.start === selection.end) {
				return [selection.start];
			} else {
				return this.getNodesInBetween(selection.start, selection.end);
			}*/
			
			var nodes = new Supra.HTMLEditor.Nodes(null, this);
			nodes.fromSelection(this.selection);
			
			return nodes;
		},
		
		/**
		 * Returns all selected elements
		 */
		getSelectedElements: function (options) {
			var selection = this.selection,
				container = Y.Node.getDOMNode(this.get('srcNode')),
				node,
				common_ancestor = null,
				contains_start = [],
				contains_end = [],
				start_ancestor = null,
				end_ancestor = null,
				include = true,
				
				i,
				ii,
				children,
				
				include_inline = options && options.inline,
				inline_elements = Supra.HTMLEditor.ELEMENTS_INLINE,
				
				filter_fn = options && typeof options.filter === 'function' ? options.filter : null,
				filter_str = options && typeof options.filter === 'string' ? options.filter : null;
			
			// Filter using CSS selector
			if (filter_str) {
				if (filter_str === ':inline') {
					filter_str = Supra.HTMLEditor.ELEMENTS_INLINE_ARR.join(',');
				} else if (filter_str === ':block') {
					//filter_str = Supra.HTMLEditor.ELEMENTS_BLOCK_ARR.join(',');
					include_inline = false;
					filter_str = '';
				}
				
				// CSS selector
				if (filter_str) {
					filter_fn = function (node) {
						 return Y.Selector.test(node, filter_str);
					};
				}
			}
			
			if (!selection) {
				return [];
			}
			if (selection.collapsed || selection.start === selection.end) {
				// There is only one element selected
				node = selection.start;
				
				while (node && node !== container && node.nodeType !== 1) {
					node = node.parentNode;
				}
				
				if (include_inline || !(node.tagName.toLowerCase() in inline_elements)) {
					// Filter if we need to
					if (!filter_fn || filter_fn(node)) {
						return [node];
					} else {
						return [];
					}
				} else {
					// We don't want inline elements, but element is inline, so search
					// for block level element
					while (node && node !== container) {
						if (!(node.tagName.toLowerCase() in inline_elements)) {
							if (!filter_fn || filter_fn(node)) {
								return [node];
							}
						}
						node = node.parentNode;
					}
					
					if (!filter_fn || filter_fn(container)) {
						return [container];
					} else {
						return [];
					}
				}
				
				//while (node && node !== )
				return [this.getSelectedElement()];
			}
			
			// Find common ancestor
			common_ancestor = this.getCommonAncestor(selection.start, selection.end);
			
			// Find all start ancestors
			node = selection.start;
			while (node && node !== common_ancestor) {
				if (node.nodeType === 1) {
					if (node.parentNode === common_ancestor) {
						// we don't want to go to nextSibling in this case, because
						// we don't know where to stop (don't know which is selection.end ancestor)
						if (include || !contains_start.length) {
							
							// If either inline elements needs to be included or we have a block element
							if (include_inline || !(node.tagName.toLowerCase() in inline_elements)) {
								// Filter
								if (!filter_fn || filter_fn(node)) { 
									contains_start.push(node);
								}
							}
							
						}
						start_ancestor = node;
						break;
					}
					
					if (include || !contains_start.length) {
						
						// If either inline elements needs to be included or we have a block element
						if (include_inline || !(node.tagName.toLowerCase() in inline_elements)) {
							if (!filter_fn || filter_fn(node)) {
								contains_start.push(node);
							}
						}
						
					}
					start_ancestor = node;
				}
				if (node.nextSibling) {
					node = node.nextSibling;
					
					// If we have a filter function, then we need to traverse all elements, including children one
					if (filter_fn) {
						children = node.getElementsByTagName ? node.getElementsByTagName('*') : [];
						
						for (i=0,ii=children.length; i<ii; i++) {
							if (include_inline || !(children[i].tagName.toLowerCase() in inline_elements)) {
								if (filter_fn(children[i])) {
									contains_start.push(children[i]);
								}
							}
						}
					}
					
					include = true;
				} else {
					node = node.parentNode;
					include = false; // we don't want to include ancestor, only next siblings
				}
			}
			
			// Find all end ancestors
			node = selection.end;
			while (node && node !== common_ancestor && node !== start_ancestor) {
				if (node.nodeType === 1) {
					if (include || !contains_end.length) {
						
						// If either inline elements needs to be included or we have a block element
						if (include_inline || !(node.tagName.toLowerCase() in inline_elements)) {
							if (!filter_fn || filter_fn(node)) {
								contains_end.push(node);
							}
						}
						
					}
				}
				if (node.previousSibling) {
					node = node.previousSibling;
					
					// If we have a filter function, then we need to traverse all elements, including children one
					if (filter_fn) {
						children = node.getElementsByTagName ? node.getElementsByTagName('*') : [];
						
						for (i=0,ii=children.length; i<ii; i++) {
							if (include_inline || !(children[i].tagName.toLowerCase() in inline_elements)) {
								if (filter_fn(children[i])) {
									contains_end.push(children[i]);
								}
							}
						}
					}
					
					include = true;
				} else {
					node = node.parentNode;
					include = false; // we don't want to include ancestor, only next siblings
				}
			}
			
			// If nothing was found, then return common element
			if (!contains_start.length && !contains_end.length) {
				node = common_ancestor;
				if (include_inline || !(node.tagName.toLowerCase() in inline_elements)) {
					// Filter if we need to
					if (!filter_fn || filter_fn(node)) {
						return [node];
					} else {
						return [];
					}
				} else {
					// We don't want inline elements, but common ancestor is inline, so search
					// for block level element
					while (node && node !== container) {
						if (!(node.tagName.toLowerCase() in inline_elements)) {
							if (!filter_fn || filter_fn(node)) {
								return [node];
							}
						}
						node = node.parentNode;
					}
					
					// Didn't found anything
					if (!node || node === container) {
						return [];
					}
					
					// 
					if (!filter_fn || filter_fn(container)) {
						return [container];
					} else {
						return [];
					}
				}
			}
			
			return Y.Array.unique(contains_start.concat(contains_end.reverse()));
		},
		
		/**
		 * Returns selection
		 * 
		 * @return Selection
		 * @type {Object}
		 */
		getSelection: function () {
			var doc = this.get('doc'),
				win = this.get('win');
			
			if (win.getSelection) {
				var sel = win.getSelection(),
					srcNode = Y.Node.getDOMNode(this.get('srcNode')),
					tmp = srcNode,
					offset = 0;
				
				//If there is no selection, then report last node
				if (!sel || !sel.rangeCount) {
					// Find last node
					while (tmp) {
						srcNode = tmp;
						tmp = this.getLastChild(srcNode);
					}
					
					// Find node size
					offset = srcNode.nodeType == 1 ? srcNode.childNodes.length : (srcNode.nodeType == 3 ? srcNode.textContent.length : 0);
					// IE insert icon bug fix
					if (!offset && srcNode.nodeName == 'path') {
						srcNode = srcNode.parentNode.parentNode.parentNode.parentNode;
						offset = srcNode.childNodes.length;
					}
					
					sel = {
						start: srcNode,
						start_offset: offset,
						end: srcNode,
						end_offset: offset,
						collapsed: true
					};
					
					//Update also actual selection
					this.setSelection(sel);
					return sel;
				}
				
				var range = sel.getRangeAt(0);
				var start_container = range.startContainer, start_offset = range.startOffset,
					end_container = range.endContainer, end_offset = range.endOffset,
					start_el = start_container,
					end_el = end_container;
				
				//Check if selection is under root node
				while(start_el && start_el !== srcNode) start_el = start_el.parentNode;
				while(end_el && end_el !== srcNode) end_el = end_el.parentNode;
				
				//Selection is not under root node
				if (!start_el || !end_el) {
					// Find last node
					while (tmp) {
						srcNode = tmp;
						tmp = this.getLastChild(srcNode);
					}
					
					// Find node size
					offset = srcNode.nodeType == 1 ? srcNode.childNodes.length : (srcNode.nodeType == 3 ? srcNode.textContent.length : 0);
					
					sel = {
						start: srcNode,
						start_offset: offset,
						end: srcNode,
						end_offset: offset,
						collapsed: true
					};
					
					//Update also actual selection
					this.setSelection(sel);
					return sel;
				}
				
				//If only one node is selected and there is no actual selection,
				//then change start_container and end_container to that node
				if (start_container == end_container && end_offset - start_offset == 1) {
					var node = start_container.childNodes[start_offset];
					if (node && node.nodeType == 1) {
						start_container = end_container = node;
						start_offset = end_offset = 0;
					}
				}
				
			}
			
			return this._normalizeSelection({
				start: start_container,
				start_offset: start_offset,
				end: end_container,
				end_offset: end_offset,
				collapsed: (start_container == end_container && start_offset == end_offset)
			});
		},
		
		/**
		 * Normalize selection value
		 * 
		 * @param {Object} selection Selection object
		 * @return Normalized selection value
		 * @private
		 */
		_normalizeSelection: function (selection) {
			var tmp_start = null,
				tmp_start_offset = null,
				tmp_end = null,
				tmp_end_offset = null;
			
			if (selection.start !== selection.end) {
				if (selection.start.nodeType == 3) {
					//Text node
					if (selection.start_offset == 0) {
						tmp_start = selection.start.parentNode;
						tmp_start_offset = this.getChildNodeIndex(selection.start);
					} else if (selection.start_offset == selection.start.length) {
						tmp_start = selection.start.nextSibling;
						tmp_start_offset = 0;
						if (!tmp_start) {
							tmp_start = selection.start.parentNode;
							tmp_start_offset = tmp_start.childNodes.length;
						}
					}
				}
				if (selection.end.nodeType == 3) {
					//Text node
					if (selection.end_offset == 0) {
						tmp_end = selection.end.previousSibling;
						tmp_end_offset = tmp_end ? this.getNodeLength(tmp_end) : 0;
						if (!tmp_end) {
							tmp_end = selection.end.parentNode;
							tmp_end_offset = tmp_end ? this.getNodeLength(tmp_end) : 0;
						}
					} else if (selection.end_offset == selection.end.length) {
						tmp_end = selection.end.parentNode;
						tmp_end_offset = this.getChildNodeIndex(selection.end) + 1;
					}
				}
				
				if (tmp_end && tmp_end === tmp_start) {
					selection.end = tmp_end;
					selection.end_offset = tmp_end_offset;
					selection.start = tmp_start;
					selection.start_offset = tmp_start_offset;
				} else if (tmp_end && tmp_end == selection.start) {
					selection.end = tmp_end;
					selection.end_offset = tmp_end_offset;
				} else if (tmp_start && tmp_start == selection.end) {
					selection.start = tmp_start;
					selection.start_offset = tmp_start_offset;
				}
			}
			
			return selection;
		},
		
		/**
		 * Select node
		 * 
		 * @param {HTMLElement} node
		 */
		selectNode: function (node) {
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
				var sel = win.getSelection();
				
				//WebKit may report empty selection
				var range = (sel && sel.rangeCount ? sel.getRangeAt(0) : doc.createRange());
				
				if (node) {
					try {
						//Preventing error when DOM node doesn't exist
						range.selectNode(node);
					} catch (err) {
						return;
					}
				} else {
					var srcNode = Y.Node.getDOMNode(this.get('srcNode'));
					
					if(srcNode) {
						var c = srcNode.lastChild;
						if (c) {
							try {
								//Preventing error when DOM node doesn't exist
								range.setStartAfter(c);
							} catch (err) {
								return;
							}
						}
					}
				}
				
				sel.removeAllRanges();
				sel.addRange(range);
			} else if (doc.selection) {
				//IE < 9
				var range = doc.body.createTextRange();
				range.moveToElementText(node);
				range.select();
			}
			
			this.resetSelectionCache({
				"collapsed": !!(node.nodeType == 1 ? node.childNodes.length : node.length),
				"start": node,
				"start_offset": 0,
				"end": node,
				"end_offset": (node.nodeType == 1 ? node.childNodes.length : node.length)
			});
			
			if (node.nodeType == 1) {
				this.selectedElement = node;
			} else {
				this.selectedElement = null;
			}
		},
		
		/**
		 * Reset selection to nothing
		 */
		resetSelection: function () {
			var node = this.get('srcNode').getDOMNode();
			
			this.selectNode(node);
			this.refresh(true);
		},
		
		/**
		 * Replace selection or wrap selection in tag
		 * 
		 * @param {String} tagName
		 * @param {String} str
		 */
		replaceSelection: function (str, wrapTagName) {
			if (this.get('disabled')) return;
			
			var doc = this.get('doc');
			var win = this.get('win');
			
			if (win.getSelection) {
				//Standard compatible browsers
				var str = (str ? str : wrapTagName ? win.getSelection().toString() : '');
				var node, nodelist;
				
				if (wrapTagName) {
					node = doc.createElement(wrapTagName);
					node.innerHTML = str;
				} else {
					//Create TextNode with &nbsp; (non-breaking space) as content
					//Can't use createTextNode, because &amp; is automatically escaped
					var nodelist = doc.createElement('I');
						nodelist.innerHTML = str;
				}
				
				var sel = win.getSelection();
				var range = sel.getRangeAt(0);
				
				// Remove old content which was selected
				range.deleteContents();
				
				if (str || node) {
					// If we replaced with something, then select it
					if (node) {
						range.insertNode(node);
						range.setStartAfter(node);
					} else if (nodelist) {
						var first = null,
							block_elements = Supra.HTMLEditor.ELEMENTS_BLOCK,
							selected = this.getSelectedElement(Supra.HTMLEditor.ELEMENTS_BLOCK_ARR.join(',')),
							tag = selected ? selected.tagName : null;
						
						// @TODO FIX H1, H2, P, etc. being inserted into other H1, H2, P, etc. elements
						while(nodelist.lastChild) {
							first = nodelist.lastChild;
							if (first.nodeType) range.insertNode(first);
						}
						if (first) range.setStartAfter(first);
					}
					
					sel.removeAllRanges();
					sel.addRange(range);
				}
				
				this.resetSelectionCache();
				
				return node;
			}
		},
		
		/**
		 * Find nodes matching selector in selection or node in which cursor
		 * is positioned if it matches selector
		 * 
		 * @param {Object} selection Selection object
		 * @param {String} selector CSS selector
		 * @return Found nodes
		 * @type {Object}
		 */
		findNodesInSelection: function (selection, selector) {
			if (!this.selection) return new Y.NodeList(node || []);
			
			//@TODO Use correction selection
			
			var node = new Y.Node(this.getSelectedElement());
			
			while(node && !node.test(selector)) {
				node = node.get('parentNode');
			}
			
			var nodelist = new Y.NodeList(node || []);
			return nodelist;
		},
		
		/**
		 * Returns selected text
		 * 
		 * @return Selected text
		 * @type {String}
		 */
		getSelectionText: function () {
			var doc = this.get('doc'),
				win = this.get('win');
			
			if (win.getSelection) {
				return win.getSelection().toString();
			} else if (doc.selection) {
				//IE < 9
				return doc.selection.createRange().htmlText;
			}
		},
		
		/**
		 * Returns true if cursor is at the begining of given node
		 * 
		 * @param {HTMLElement} node Node to check
		 * @returns {Boolean} True if cursor is at the begining of node
		 */
		isCursorAtTheBeginingOf: function (node) {
			var selection = this.selection,
				start     = selection.start,
				length    = this.getNodeLength(start),
				tagname   = null,
				srcNode   = null,
				match     = null;
			
			if (selection.start_offset == 0) {
				return true;
			}
			
			if (start.nodeType == 3) {
				// Text node
				if (start.textContent.match(/^[\r\n\s]*/)[0].length < selection.start_offset) {
					// There is something before selection
					return false;
				}
			} else {
				// Element
				var children = start.childNodes,
					i = 0,
					ii = selection.start_offset;
				
				for (; i<ii; i++) {
					if (this.getNodeLength(children[i]) != 0) {
						// There is non-empty node before selection
						return false;
					}
				}
			}
			
			if (node) {
				// Traverse up the tree till we find the node
				srcNode = this.get('srcNode').getDOMNode();
				
				while (start && start !== srcNode) {
					if (start === node) return true;
					if (this.getFirstChild(start.parentNode) !== start) return false;
					start = start.parentNode;
				}
			} else {
				return true;
			}
			
			return false;
		},
		
		/**
		 * Returns true if cursor is at the end of given node
		 * 
		 * @param {HTMLElement} node Node to check
		 * @returns {Boolean} True if cursor is at the end of node
		 */
		isCursorAtTheEndOf: function (node) {
			var selection = this.selection,
				end       = selection.end,
				length    = this.getNodeLength(end),
				tagname   = null,
				srcNode   = null;
			
			if (selection.end_offset < length) {
				return false;
			}
				// Is at the end, but of what?
				
			if (node) {
				if (node.nodeType == 3) {
					// Text node
					return node === end;
				} else {
					// Element
					srcNode = this.get('srcNode').getDOMNode();
					
					if (node === srcNode) {
						// Selection always will be inside source node
						return true;
					}
					
					while (end && end !== srcNode) {
						if (end === node) return true;
						if (this.getLastChild(end.parentNode) !== end) return false;
						end = end.parentNode;
					}
				}
			} else {
				return true;
			}
			
			return false;
		},
		
		/**
		 * Returns true if all node content is selected, otherwise false
		 * 
		 * @param {HTMLElement} node Node to check
		 * @returns {Boolean} True if all node content is selected
		 */
		isAllNodeSelected: function (node) {
			return this.isCursorAtTheBeginingOf(node) &&
				   this.isCursorAtTheEndOf(node);
		},
		
		/**
		 * Reset selection variable cache
		 * 
		 * @param {Object} selection Optional. Selection object
		 */
		resetSelectionCache: function (selection) {
			this.selectedElement = null;
			this.selection = selection || this.getSelection();
			this.path = null;
		},
		
		/**
		 * Returns next text node, which can be selected
		 * 
		 * @param {Object} node
		 */
		getNextSelectableNode: function (node) {
			var self = this,
				srcNode = this.get('srcNode').getDOMNode(),
				
				nextSibling = function (node) {
					if (!node) return null;
					if (node.nextSibling) return node.nextSibling;
					node = node.parentNode;
					
					while (node && node !== srcNode) {
						if (node.nextSibling) return node.nextSibling;
						node = node.parentNode;
					}
					
					if (node === srcNode) {
						return null;
					} else {
						return node;
					}
				},
				deepSearch = function (node) {
					while (node) {
						if (self.isEditable(node)) {
							if (node.nodeType == 1) {
								var tmp = null;
								if (node.tagName == 'OL' || node.tagName == 'UL') {
									tmp = Y.Node(node).one('LI');
									tmp = tmp ? tmp.getDOMNode() : null;
								} else if (node.tagName == 'TABLE') {
									tmp = Y.Node(node).one('TH, TD');
									tmp = tmp ? tmp.getDOMNode() : null;
								} else {
									tmp = node.firstChild;
								}
								 
								tmp = deepSearch(tmp);
								if (tmp) return tmp;
							} else if (node.nodeType == 3 && self.getNodeLength(node)) {
								return node;
							}
						}
						
						node = nextSibling(node);
					}
				};
			
			node = (node ? node : nextSibling(this.getSelection().end));
			return deepSearch(node);
		}
		
	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});