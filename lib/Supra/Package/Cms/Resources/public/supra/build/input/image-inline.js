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
YUI.add('supra.input-image-inline', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	
	function Input (config) {
		this.widgets = {};
		this.image = null;
		
		Input.superclass.constructor.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.NAME = 'input-image-inline';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		// Image node which is edited
		"targetNode": {
			value: null
		},
		//Blank image URI or data URI
		"blankImageUrl": {
			value: "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="
		},
		// Resize image crop to smaller size on zoom if needed
		"allowZoomResize": {
			value: false
		},
		// Change zoom on crop resize if needed
		"allowCropZooming": {
			value: false
		},
		// Stop editing when clicked outside image
		"autoClose": {
			value: true
		},
		// Max crop width is fixed and container can't increase in size
		"fixedMaxCropWidth": {
			value: true
		},
		// Max crop height is fixed and container can't increase in size
		"fixedMaxCropHeight": {
			value: false
		},
		// Crop width is fixed and container can't change in size
		"fixedCropWidth": {
			value: true
		},
		// Crop height is fixed and container can't change in size
		"fixedCropHeight": {
			value: false
		},
		// Default value
		"defaultValue": {
			value: ""
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.BlockBackground, {
		
		/**
		 * Render needed widgets
		 */
		renderUI: function () {
			Supra.Input.BlockBackground.superclass.renderUI.apply(this, arguments);
			
			var inputNode = this.get("inputNode"),
				renderTarget = inputNode.get("parentNode"),
				value = this.get("value");
			
			// Button "Custom image"
			if (this.get('separateSlide')) {
				var buttonCustom = new Supra.Button({
					"label": this.getSectionButtonLabel(),
					"style": "small-gray"
				});
				buttonCustom.addClass("button-section");
				buttonCustom.on("click", this.openSlide, this);
				buttonCustom.render(renderTarget);
				inputNode.insert(buttonCustom.get("boundingBox"), "before");
				
				this.widgets.buttonCustom = buttonCustom;
			} else if (!this.get('slideshowSlideId')) {
				// Since there is a custom slide created by something else
				// then we expect that something else to call edit start
				this.openSlide();
			}
			
			if (value) {
				this._uiSync(value);
			}
		},
		
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			this.after('focusedChange', this._attrFocusedChange, this);
		},
		
		/**
		 * Advanced controls are needed only for block-background input
		 * 
		 * @protected
		 */
		renderAdvancedControlsUI: function () {
			// No need for advanced controls
		},
		
		/**
		 * Update inline editable style
		 */
		syncUI: function () {
			this._applyStyle(this.get('value'));
		},
		
		
		/* ----------------------------- Image edit ------------------------------- */
		
		
		/**
		 * Returns inline target node
		 *
		 * @returns {Object} Target node
		 */
		getTargetNode: function () {
			var node = this.get("targetNode"),
				img;
			
			if (node && !node.test('img')) {
				img = node.one('img');
				
				if (!img) {
					img = Y.Node.create('<img alt="" />');
					node.append(img);
					return img;
				}
				
				return img;
			}
			
			return node;
		},
		
		/**
		 * Start image editing
		 */
		startEditing: function () {
			if (!this.image || !this.image.image) {
				// No data for image to edit
				return false;
			}
			
			var imageResizer = this.widgets.imageResizer,
				node = this.getTargetNode(),
				image = this.get("value"),
				size = image.image.sizes.original;
			
			if (!node) {
				return false;
			}
			
			if (!imageResizer) {
				imageResizer = this.widgets.imageResizer = new Supra.ImageResizer({
					"mode": Supra.ImageResizer.MODE_IMAGE,
					"allowZoomResize": this.get("allowZoomResize"),
					"allowCropZooming": this.get("allowCropZooming"),
					"autoClose": this.get("autoClose")
				});
				imageResizer.on("resize", function (event) {
					var value = this.get("value");
					
					//Update crop, etc.
					if (value) {
						value.crop_top = event.cropTop;
						value.crop_left = event.cropLeft;
						value.crop_width = event.cropWidth;
						value.crop_height = event.cropHeight;
						value.size_width = event.imageWidth;
						value.size_height = event.imageHeight;
						
						this.set("value", value);
					}
						
					if (!event.silent) {
						this.blur();
					}
				}, this);
			}
			
			var maxCropWidth  = Math.min(size.width,  this._getContainerWidth()),
				maxCropHeight = Math.min(size.height, this._getContainerHeight());
			
			imageResizer.set("maxCropWidth", this.get('fixedCropWidth') || this.get('fixedMaxCropWidth') ? maxCropWidth : 0);
			imageResizer.set("maxCropHeight", this.get('fixedCropHeight') || this.get('fixedMaxCropHeight') ? maxCropHeight : 0);
			imageResizer.set("minCropWidth", this.get('fixedCropWidth') ? maxCropWidth : 0);
			imageResizer.set("minCropHeight", this.get('fixedCropHeight') ? maxCropHeight : 0);
			
			imageResizer.set("maxImageHeight", size.height);
			imageResizer.set("maxImageWidth", size.width);
			imageResizer.set("minImageHeight", 32);
			imageResizer.set("minImageWidth", 32);
			imageResizer.set("image", node);
			
			if (image) {
				imageResizer.cropTop = image.crop_top;
				imageResizer.cropLeft = image.crop_left;
				imageResizer.cropWidth = image.crop_width;
				imageResizer.cropHeight = image.crop_height;
				imageResizer.imageWidth = image.size_width;
				imageResizer.imageHeight = image.size_height;
				imageResizer.sync();
			}
			
			this.focus();
			this.openSlide();
			
			return true;
		},
		
		/**
		 * Remove selected image
		 */
		removeImage: function () {
			this.set("value", null);
			this.closeSlide();
		},
		
		
		/* ---------------------------- Media sidebar ------------------------------ */
		
		
		/**
		 * On image insert change input value
		 * 
		 * @private
		 */
		insertImage: function (data, preventEditing) {
			var container_width = this._getContainerWidth(),
				container_height = this._getContainerHeight(),
				width  = data.image.sizes.original.width,
				height = data.image.sizes.original.height,
				crop_width = width,
				crop_height = height,
				ratio  = 0;
			
			// Ignore 1px size, because that may be used to make sure
			// other property has size
			if (container_width <= 1) container_width = 0;
			if (container_height <= 1) container_height = 0;
			
			if (!(this.get('fixedMaxCropWidth') || this.get('fixedCropWidth')) && container_width && container_width < 100) {
				container_width = 100;
			}
			if (!(this.get('fixedMaxCropHeight') || this.get('fixedCropHeight')) && container_height && container_height < 100) {
				container_height = 100;
			}
			
			if (container_width) {
				if (this.get('fixedCropWidth') || width > container_width) {
					ratio = width / height;
					width = crop_width = container_width;
					height = crop_height = Math.round(width / ratio);
				}
			}
			
			if (container_height) {
				if (this.get('fixedCropHeight') && this.get('fixedCropWidth')) {
					ratio = width / height;
					crop_height = container_height;
					height = Math.max(height, crop_height);
					width = Math.round(height * ratio);
				} else if (this.get('fixedCropHeight') || (!this.get('fixedCropWidth') && height > container_height)) {
					ratio = width / height;
					height = crop_height = container_height;
					width = crop_width = Math.round(height * ratio);
				}
			}
			
			this.set("value", {
				"image": data.image,
				"crop_left": 0,
				"crop_top": 0,
				"crop_width": crop_width,
				"crop_height": crop_height,
				"size_width": width,
				"size_height": height
			});
			
			//Start editing image
			if (this.get("editImageAutomatically") && !preventEditing) {
				//Small delay to allow icon sidebar to close before doing anything (eg. opening settings sidebar)
				Y.later(100, this, function () {
					if (this._hasImage()) {
						this.startEditing();
					}
				});
			}
		},
		
		
		/* ------------------------------ Attributes -------------------------------- */
		
		
		/**
		 * Returns true if image is selected, otherwise false
		 * 
		 * @return True if image is selected
		 * @type {Boolean}
		 * @private
		 */
		_hasImage: function () {
			var value = this.get("value");
			return value;
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value Value
		 * @return New value
		 * @type {Object}
		 * @private
		 */
		_setValue: function (_value) {
			var value = (_value === undefined || _value === null || typeof _value !== "object" ? "" : _value);
			
			if (value) {
				value = Supra.Y.DataType.Image.parse(value);
				if (!value.image) value = "";
			}
			
			this.image = value ? value : "";
			
			if (this.widgets) {
				//Update UI
				if (this.widgets.buttonSet) {
					this.widgets.buttonSet.set("label", this.getSectionButtonLabel(_value));
				}
				if (this.widgets.buttonCustom) {
					this.widgets.buttonCustom.set("label", this.getSectionButtonLabel(_value));
				}
				if (this.widgets.buttonRemove) {
					if (this.image) {
						this.widgets.buttonRemove.set("disabled", false);
					} else {
						this.widgets.buttonRemove.set("disabled", true);
					}
				}
				if (this.widgets.buttonEdit) {
					if (this.image) {
						this.widgets.buttonEdit.set("disabled", false);
					} else {
						this.widgets.buttonEdit.set("disabled", true);
					}
				}
			}
			
			this._applyStyle(value);
			
			/*
			 * value == {
			 * 	   "" // or image
			 * }
			 * 
			 */
			this._original_value = value;
			return value;
		},
		
		/**
		 * Value attribute getter
		 * Returns input value
		 * 
		 * @return {Object}
		 * @private
		 */
		_getValue: function () {
			return this.image ? this.image : "";
			
			/*
			 * value == {
			 * 	   "image": { ... image data ... },
			 *     "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *     "size_width": Number, "size_height": Number
			 * }
			 */
		},
		
		/**
		 * Returns value for saving
		 * 
		 * @return {Object}
		 * @private
		 */
		_getSaveValue: function () {
			return Y.DataType.Image.format(this.get("value"));
			
			/*
			 * {
			 * 	   "id": "...id...",
			 *     "crop_height": Number, "crop_width": Number, "crop_left": Number, "crop_top": Number,
			 *     "size_width": Number, "size_height": Number
			 * }
			 */
		},
		
		/**
		 * Extracts image data from value
		 *
		 * @param {Object|Null} value Value
		 * @returns {Object|Null} Image data or null
		 * @protected
		 */ 
		_getImageFromValue: function (value) {
			return value && value.image ? value.image : null;
		},
		
		/**
		 * Apply style
		 * 
		 * @private
		 */
		_applyStyle: function (value) {
			var node = this.getTargetNode(),
				container = null,
				imageResizer;
			
			if (!node || !node.getDOMNode()) return;
			
			if (value) {
				if (this.get('fixedCropWidth')) {
					value.crop_width = this._getContainerWidth();
				} else if (this.get('fixedMaxCropWidth')) {
					value.crop_width = Math.min(value.crop_width, this._getContainerWidth());
				}

				if (this.get('fixedCropHeight')) {
					value.crop_height = this._getContainerHeight();
				} else if (this.get('fixedMaxCropHeight') || this.get('fixedCropHeight')) {
					value.crop_height = Math.min(value.crop_height, this._getContainerHeight());
				}
				
				container = node.ancestor();
				if (!container.hasClass("supra-image")) {
					var doc = node.getDOMNode().ownerDocument;
					container = Y.Node(doc.createElement("span"));
					
					node.insert(container, "after");
					container.addClass("supra-image");
					container.append(node);
				}
				
				node.setStyles({
					"margin": -value.crop_top + "px 0 0 -" + value.crop_left + "px",
					"width": value.size_width + "px",
					"height": value.size_height + "px"
				});
				node.setAttribute("width", value.size_width);
				node.setAttribute("height", value.size_height);
				node.setAttribute("src", Supra.getObjectValue(value, ['image', 'sizes', 'original', 'external_path']) || this.get('blankImageUrl'));
				
				container.setStyles({
					"width": value.crop_width + "px",
					"height": value.crop_height + "px"
				});
			} else {
				// Stop image resizer editing
				imageResizer = this.widgets.imageResizer;
				if (imageResizer) {
					imageResizer.set("image", null);
				}
				
				node.setStyles({
					"margin": "0",
					"width": "",
					"height": ""
				});
				
				node.setAttribute("src", this.get("blankImageUrl"));
				node.removeAttribute("width");
				node.removeAttribute("height");
				
				container = node.closest('.supra-image');
				
				if (container) {
					container.setStyles({
						"width": "auto",
						"height": "auto"
					});
				}
			}
		},
		
		/**
		 * Returns container node width / max crop width
		 * 
		 * @private
		 */
		_getContainerWidth: function () {
			var node = this.getTargetNode(),
				container = null,
				width = 0,
				invalidDisplayValues = {'none': true, 'inline': true};
			
			if (!node) return 0;
			
			container = node.ancestor();
			if (!container) return 0;
			
			// Find container width to calculate max possible width
			while (container && container.test('.supra-image, .supra-image-inner') || container.getStyle('display') in invalidDisplayValues) {
				container = container.ancestor();
			}
			
			return container ? container.get("offsetWidth") : 0	;
		},
		
		/**
		 * Returns container node height / max crop height
		 * 
		 * @private
		 */
		_getContainerHeight: function () {
			var node = this.getTargetNode(),
				container = null,
				height = 0;
			
			if (!node) return 0;
			
			container = node.ancestor();
			if (!container) return 0;
			
			// Find container height to calculate max possible height
			while (container.test('.supra-image, .supra-image-inner')) {
				container = container.ancestor();
			}
			
			return container.get("offsetHeight");
		},
		
		/**
		 * Handle focused attribute change
		 *
		 * @param {Object} e Event facade object
		 * @protected
		 */
		_attrFocusedChange: function (e) {
			var node = this.getTargetNode();
			if (e.prevVal !== e.newVal && node) {
				node.toggleClass(this.getClassName('focused'), e.newVal);
			}
		}
		
	});
	
	Supra.Input.InlineImage = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-block-background"]});
