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
YUI().add('supra.htmleditor-plugin-fullscreen', function (Y) {
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	Supra.HTMLEditor.addPlugin('fullscreen', defaultConfiguration, {
		/**
		 * Fullscreen state
		 * @type {Boolean}
		 * @private
		 */
		fullscreen: false,
		
		/**
		 * DOM Node reference point
		 */
		reference_point: null,
		
		/**
		 * Enable fullscreen mode
		 * 
		 * @private
		 */
		enableFullScreenMode: function () {
			if (this.fullscreen) return;
			this.fullscreen = true;
			
			var node = this.htmleditor.get('srcNode').closest('.su-content'),
				body = node.closest('body');
			
			this.reference_point = Y.DOM.removeFromDOM(node);
			
			node.addClass('su-fullscreen');
			body.addClass('su-fullscreen');
			body.append(node);
		},
		
		/**
		 * Disable fullscreen mode
		 * 
		 * @private
		 */
		disableFullScreenMode: function () {
			if (!this.fullscreen) return;
			this.fullscreen = false;
			
			var htmleditor = this.htmleditor,
				body = htmleditor.get('srcNode').closest('body'),
				toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('fullscreen') : null;
			
			button.set('down', false);
			
			this.reference_point.node.removeClass('su-fullscreen');
			body.removeClass('su-fullscreen');
			
			Y.DOM.restoreInDOM(this.reference_point);
		},
		
		/**
		 * Toggle fullscreen mode
		 */
		toggleFullScreenMode: function () {
			var toolbar = this.htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('fullscreen') : null;
			
			if (button.get('down')) {
				this.enableFullScreenMode();
			} else {
				this.disableFullScreenMode();
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			var toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('fullscreen') : null;
			
			// Add command
			htmleditor.addCommand('fullscreen', Y.bind(this.toggleFullScreenMode, this));
			
			if (button) {
				//When un-editable node is selected disable fullscreen mode
				htmleditor.on('editingAllowedChange', function (event) {
					if (!event.allowed) this.disableFullScreenMode();
					button.set('disabled', !event.allowed);
				}, this);
			}
			
			//On editor disable hide source editor
			htmleditor.on('disable', this.disableFullScreenMode, this);
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {
			this.disableFullScreenMode();
		}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.manager', 'supra.htmleditor-base']});