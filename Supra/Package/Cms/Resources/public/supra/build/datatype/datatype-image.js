/*
 * Add color parsing and formatting
 */
YUI.add('supra.datatype-image', function(Y) {
	//Invoke strict mode
	"use strict";
	
	var Image = Y.namespace("DataType.Image");
	
	Image.parse = function (value) {
		// Parse image information
		if (value && value.sizes) {
			// Add crop information
			value = {
				'image': value,
				'crop_left': 0,
				'crop_top': 0,
				'crop_width': value.sizes.original.width,
				'crop_height': value.sizes.original.height,
				'size_width': value.sizes.original.width,
				'size_height': value.sizes.original.height
			};
		} else if (!value) {
			value = {
				'image': null,
				'crop_left': 0,
				'crop_top': 0,
				'crop_width': 0,
				'crop_height': 0,
				'size_width': 0,
				'size_height': 0
			};
		}
		
		return value;
	};
	
	/**
	 * Format image value by stripping out unnecessary data
	 * Full image data is replaced with id
	 */
	Image.format = function (value) {
		if (value && typeof value === 'object') {
			// Strip image information and replace with id
			if (value.image && value.image.sizes) {
				// InlineMedia, HTML image data, etc.
				value.image = value.image.id;
			} else if (value.image && value.image.image && value.image.image.sizes) {
				// InlineImage, BlockBackground
				value.image.image = value.image.image.id;
			} else if (value.id && value.sizes) {
				// Image
				value = value.id;
			}
			
			if (value && typeof value === 'object') {
				// Make sure crop values are numbers
				if ('crop_left' in value || 'crop_top' in value || 'crop_width' in value || 'crop_height' in value) {
					if (!value.crop_left) value.crop_left = 0;
					if (!value.crop_top) value.crop_top = 0;
					if (!value.crop_width) value.crop_width = value.size_width || 0;
					if (!value.crop_height) value.crop_height = value.size_height || 0;
				}
			}
		}
		return value;
	};
	
	/**
	 * Reset all filenames to prevent cache
	 */
	Image.resetCache = function (data) {
		if (typeof data === 'string') {
			// Return string with random parameter
			var random_str = +new Date() + '' + Math.floor(Math.random() * 1000);
			return data.replace(/\?.*/, '') + '?_=' + random_str;
		} else if (data && typeof data === 'object') {
			// Replace filenames
			if (data.sizes.original) {
				data.sizes.original.external_path = Image.resetCache(data.sizes.original.external_path);
			}
			if (data.file_web_path) {
				data.file_web_path = Image.resetCache(data.file_web_path);
			}
			return data; 
		}
	};
	
	/**
	 * Returns size name which matches criteria
	 * 
	 * @param {Object} data Image data
	 * @param {Object} options Criteria
	 * @returns {String|Null} Size name or null if none of the sizes matches
	 */
	Image.getSizeName = function (data, options) {
		options = Supra.mix({
			'minWidth': null,
			'maxWidth': null,
			'minHeight': null,
			'maxHeight': null,
			'width': null,
			'height': null
		}, options);
		
		var sizes     = data.sizes,		// All sizes
			name      = null,			// Name of the current size
			match     = "",				// Last match name
			diff      = 99999,			// Difference between criteria and image for last match
			tmp_diff  = 0,				// Difference between criteria and image for given size
			satisfied = false,			// All criteria are satisfied for given image
			criteria  = null,			// Criteria name which is beeing checked
			value     = null;			// Criteria value
		
		for (name in sizes) {
			satisfied = true;
			tmp_diff = 0;
			
			for (criteria in options) {
				value = options[criteria];
				
				if (value !== null) {
					if (criteria === 'width') {
						tmp_diff += Math.abs(sizes[name].width - value);
					}
					if (criteria === 'minWidth') {
						if (sizes[name].width >= value) {
							tmp_diff += sizes[name].width - value;
						} else {
							satisfied = false;
							break;
						}
					}
					if (criteria === 'maxWidth') {
						if (sizes[name].width < value) {
							tmp_diff += value - sizes[name].width;
						} else {
							satisfied = false;
							break;
						}
					}
					if (criteria === 'height') {
						tmp_diff += Math.abs(sizes[name].height - value);
					}
					if (criteria === 'minHeight') {
						if (sizes[name].height >= value) {
							tmp_diff += sizes[name].height - value;
						} else {
							satisfied = false;
							break;
						}
					}
					if (criteria === 'maxHeight') {
						if (sizes[name].height < value) {
							tmp_diff += value - sizes[name].height;
						} else {
							satisfied = false;
							break;
						}
					}
				}
			}
			
			if (satisfied && tmp_diff < diff) {
				diff = tmp_diff;
				match = name;
			}
		}
		
		return match;
	};
	
	/**
	 * Returns image crop_left, crop_top, crop_width, crop_height and left and top style positions for 
	 * image at given 'position'
	 */
	Image.position = function (data, options) {
		options = Supra.mix({
			
			// Node which to use for calculations
			'node': null,
			// If node matches filter value then traverse up the tree to find correct node
			'nodeFilter': '.supra-image, .supra-icon, .supra-image-inner, img',
			
			// If node is not set then this will be used
			'maxCropWidth': 0,
			// If node is not set then this will be used
			'maxCropHeight': 0,
			
			// Background position for which to do calculations
			'position': '0% 0%',
			
			// Background attachment for which to do calculations
			'attachment': 'scroll'
			
		}, options);
		
		// Find closest node fulfilling filter
		var node = options.node,
			doc,
			node_width,
			node_height;
		
		if (node && options.nodeFilter) {
			// Inline nodes take children width, not full
			while (node.test(options.nodeFilter) || node.getStyle('display') == 'inline') {
				node = node.ancestor();
			}
		}
		
		if (options.attachment === 'fixed' && node) {
			doc = Y.Node(node.getDOMNode().ownerDocument) || node.getDOMNode();
			node_width  = doc.get('winWidth');
			node_height = doc.get('winHeight');
		} else {
			node_width  = (node ? node.getAttribute('width')  || node.get('offsetWidth') : 0)  || options.maxCropWidth || 0;
			node_height = (node ? node.getAttribute('height') || node.get('offsetHeight') : 0) || options.maxCropHeight || 0;	
		}
		
		var position = String(options.position).match(/(\d+\%)\s(\d+%)/) || ['', '0%', '0%'],
			
			crop_left = data.crop_left,
			crop_top = data.crop_top,
			
			crop_width = data.crop_width,
			crop_height = data.crop_height,
			
			style_left = '0px',
			style_top  = '0px';
		
		if (position[1] == '0%') {
			if (crop_left == 0 && crop_width != data.size_width) {
				// Was 100% before, because it doesn't match
				crop_left = data.size_width - crop_width;
			}
			crop_left = Math.min(Math.max(0, crop_left), data.size_width);
			crop_width = data.size_width - crop_left;
			style_left = -crop_left + 'px';
		} else if (position[1] == '50%') {
			// Full size
			crop_left = 0;
			crop_width = data.size_width;
			style_left = '50%';
		} else if (position[1] == '100%') {
			if (crop_left) {
				// Was 0% before
				crop_width = data.size_width - crop_left;
				crop_left = 0;
			}
			style_left = node_width - data.crop_width + 'px';
		}
		
		if (position[2] == '0%') {
			if (crop_top == 0 && crop_height != data.size_height) {
				// Was 100% before, because it doesn't match
				crop_top = data.size_height - crop_height;
			}
			crop_top = Math.min(Math.max(0, crop_top), data.size_height);
			crop_height = data.size_height - crop_top;
			style_top = -crop_top + 'px';
		} else if (position[2] == '50%') {
			// Full size
			crop_top = 0;
			crop_height = data.size_height;
			style_top = '50%';
		} else if (position[2] == '100%') {
			if (crop_top) {
				// Was 0% before
				crop_height = data.size_height - crop_top;
				crop_top = 0;
			}
			style_top = node_height - data.crop_height + 'px';
		}
		
		return [crop_left, crop_top, crop_width, crop_height, style_left, style_top];
	};
	
	/**
	 * Recalculate crop and image size
	 * 
	 * @param {Object} data Image data
	 * @param {Object} options Resize options
	 */
	Image.resize = function (data, options) {
		options = Supra.mix({
			// Try to fill container, valid values are "horizontal", "vertical", "both" and false
			'fill': 'horizontal',
			
			// Node which to use for calculations
			'node': null,
			// If node matches filter value then traverse up the tree to find correct node
			'nodeFilter': '.supra-image, .supra-icon, .supra-image-inner, img',
			
			// If node is not set then this will be used
			'maxCropWidth': 0,
			// If node is not set then this will be used
			'maxCropHeight': 0,
			
			// If crop size changed then also change image size proportionally
			'scale': false
		}, options);
		
		// Find closest node fulfilling filter
		var node = options.node;
		if (node && options.nodeFilter) {
			// Inline nodes take children width, not full
			while (node.test(options.nodeFilter) || node.getStyle('display') == 'inline') {
				node = node.ancestor();
			}
		}
		
		var size = data.image.sizes.original,
			ratio = size.width / size.height,
			coef = 0,
			
			size_width = data.size_width || size.width,
			size_height = data.size_height || size.height,
			crop_left = data.crop_left || 0,
			crop_top = data.crop_top || 0,
			crop_width = data.crop_width || size.width,
			crop_height = data.crop_height || size.height,
			
			size_max_width = size.width,
			size_max_height = size.height,
			crop_min_width = 28,
			crop_min_height = 28,
			crop_max_width = size_max_width,
			crop_max_height = size_max_height,
			
			node_width  = (node ? node.getAttribute('width')  || node.getInnerWidth() : 0)  || options.maxCropWidth || 0,
			node_height = (node ? node.getAttribute('height') || node.getInnerHeight() : 0) || options.maxCropHeight || 0;
		
		// Calculate maximal and miminal widths and heights base on node size
		if (node_width && options.fill === 'horizontal') {
			crop_width = crop_max_width = Math.min(size.width, node_width);
			
			// If crop changed then scale, etc.
			if (crop_width != data.crop_width) {
				if (options.scale) {
					// Change also crop height and size height
					coef = crop_width / data.crop_width;
					
					crop_left   = Math.floor(data.crop_left * coef);
					crop_top    = Math.floor(data.crop_top * coef);
					crop_height = Math.floor(data.crop_height * coef);
					size_width  = Math.floor(data.size_width * coef);
					size_height = Math.floor(data.size_height * coef);
				}
				
				// Validate size
				if (size_width > size_max_width) {
					size_width = size_max_width;
				}
				if (size_height > size_max_height) {
					size_height = size_max_height;
				}
				
				// Validate crop positions
				if (crop_height + crop_top > size_height) {
					crop_top = size_height - crop_height;
					
					if (crop_top < 0) {
						size_height -= crop_top; // increases size_height
						size_width = Math.floor(size_height * ratio);
						crop_top = 0;
						
						if (size_height > size_max_height) {
							// Image not large enough, reduce crop height
							crop_height -= (size_height - size_max_height);
							size_height = size_max_height;
							size_width = size_max_width;
						}
					}
				}
				if (crop_width + crop_left > size_width) {
					crop_left = size_width - crop_width;
					
					if (crop_left < 0) {
						size_width -= crop_left; // increases size_width
						size_height = Math.floor(size_width / ratio);
						crop_left = 0;
						
						if (size_width > size_max_width) {
							// Image not large enough, reduce crop width
							crop_width -= (size_width - size_max_width);
							size_width = size_max_width;
							size_height = size_max_height;
						}
					}
				}
			}
		}
		
		return Supra.mix({}, data, {
			'crop_left': crop_left,
			'crop_top': crop_top,
			'crop_width': crop_width,
			'crop_height': crop_height,
			'size_width': size_width,
			'size_height': size_height
		});
	};
	
}, YUI.version);