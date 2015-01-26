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
YUI.add('supra.input-gallery', function (Y) {
	//Invoke strict mode
	'use strict';
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-gallery';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		// Image node which is edited
		'targetNode': {
			value: null
		},
		// Default value
		'defaultValue': {
			value: ''
		}
		
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		
		/**
		 * We don't need input, so we unset template
		 */
		INPUT_TEMPLATE: null,
		
		/**
		 * Label template, but since we don't want to render label we unset it
		 */
		LABEL_TEMPLATE: null,
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			var renderTarget = this.get('contentBox'),
				buttonManage;
			
			// Button 'Manage ...'
			buttonManage = new Supra.Button({
				'label': Supra.Intl.get(['form', 'block', 'gallery_manager']) + this.get('label'),
				'style': 'mid-blue'
			});
			buttonManage.on('click', this.openGalleryManager, this);
			buttonManage.render(renderTarget);
			buttonManage.addClass('su-button-fill');
			
			this.widgets = {
				'buttonManage': buttonManage
			};
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			var targetNode = this.get('targetNode');
			if (targetNode) {
				targetNode.on('click', this.openGalleryManager, this);
			}
		},
		
		/**
		 * Clean up
		 * 
		 * @private
		 */
		destructor: function () {
			var widgets = this.widgets,
				key;
			
			for (key in widgets) widgets[key].destroy(true);
			
			this.widgets = null;
		},
		
		
		/* ------------------------- Gallery manager ------------------------ */
		
		
		/**
		 * Open gallery manager
		 */
		openGalleryManager: function () {
			Supra.Manager.executeAction('ItemManager', {
				'host': this,
				'contentElement': this.get('targetNode'),
				
				'itemTemplate': this.getGalleryItemTemplate(),
				'wrapperTemplate': this.getGalleryWrapperTemplate(),
				'properties': this.getGalleryItemProperties(),
				
				'callback': Y.bind(this.onGalleryManagerClose, this),
				
				'data': this.get('value')
			});
		},
		
		/**
		 * Returns template for gallery items while rendered in Gallery manager
		 *
		 * @returns {String} Gallery item template
		 */
		getGalleryItemTemplate: function () {
			var targetNode = this.get('targetNode');
			return targetNode.getAttribute('data-item-template');
		},
		
		/**
		 * Returns template for gallery wrapper while rendered in Gallery manager
		 *
		 * @returns {String} Gallery wrapper template
		 */
		getGalleryWrapperTemplate: function () {
			var targetNode = this.get('targetNode');
			return targetNode.getAttribute('data-wrapper-template');
		},
		
		/**
		 * Returns list of properties for gallery items
		 *
		 * @returns {Array} Gallery item properties
		 */
		getGalleryItemProperties: function () {
			// Properties are hardcoded for now
			return [
				{
					'id': 'image',
					'name': 'image',
					'type': 'InlineImage',
					'label': 'Image'
				}, {
					'id': 'title',
					'name': 'title',
					'type': 'InlineString',
					'label': 'Title'
				}, {
					'id': 'description',
					'name': 'description',
					'type': 'InlineText',
					'label': 'Description'
				}
			];
		},
		
		/**
		 * Gallery managaer closed
		 * 
		 * @param {Array} data
		 * @protected
		 */
		onGalleryManagerClose: function (data) {
			this.set('value', data);
		},
		
		
		/* -------------------------- Image edit ---------------------------- */
		
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			// @TODO Do we need to do anything here?
			return true;
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (value) {
			return value;
		},
		
		/**
		 * Value attribute getter
		 * Returns input value
		 * 
		 * @return {Object}
		 * @private
		 */
		_getValue: function (value) {
			return value;
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			var value = Supra.mix([], this.get('value'), true), // deep clone
				i = 0,
				ii = value.length;
			
			// Extract images
			for (; i < ii; i++) {
				if (value[i].image) {
					value[i].image = Y.DataType.Image.format(value[i].image);
				}
			}
			
			return value;
		}
		
	});
	
	Supra.Input.Gallery = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
