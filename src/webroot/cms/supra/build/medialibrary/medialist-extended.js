//Invoke strict mode
"use strict";

YUI.add('supra.medialibrary-list-extended', function (Y) {
	
	/*
	 * Shortcuts
	 */
	var Data = Supra.MediaLibraryData,
		List = Supra.MediaLibraryList;
	
	/**
	 * Extended media list
	 * Handles data loading, scrolling, selection
	 */
	function Extended (config) {
		Extended.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Extended.NAME = 'medialist';
	Extended.CLASS_NAME = Y.ClassNameManager.getClassName(Extended.NAME);
	
	
	/**
	 * Constant, folder item template for folder
	 * @type {String}
	 */
	Extended.TEMPLATE_FOLDER_ITEM_FOLDER = '\
		<li class="type-folder">\
			<a><img src="/cms/supra/img/medialibrary/icon-folder.png" alt="" /></a>\
			<span>{title}</span>\
		</li>';
	
	
	Extended.ATTRS = {
		/**
		 * Slideshow class
		 * @type {Function}
		 */
		'slideshowClass': {
			'value': Supra.MediaLibrarySlideshow
		},
		/**
		 * Templates
		 * @type {String}
		 */
		'templateFolderItemFolder': {
			value: Extended.TEMPLATE_FOLDER_ITEM_FOLDER
		}
	};
	
	
	Y.extend(Extended, List, {
		
		/**
		 * Add folder to the parent
		 * 
		 * @param {Number} parent Parent ID
		 */
		addFolder: function (parent, label) {
			var parent_id = null,
				parent_data = null,
				data_object = this.get('dataObject');
			
			if (parent) {
				parent_id = parent;
				parent_data = data_object.getData(parent_id);
				
				if (!parent_data || parent_data.type != Data.TYPE_FOLDER) {
					return false;
				}
			} else {
				parent_data = this.getSelectedFolder();
				if (parent_data) {
					parent_id = parent_data.id;
				} else {
					parent_id = this.get('rootFolderId');
				}
			}
			
			if (parent_data) {
				
			}
		},
		
		/**
		 * Returns currently selected folder
		 * 
		 * @return Selected folder data
		 * @type {Object}
		 */
		getSelectedFolder: function () {
			var history = this.slideshow.getHistory(),
				data_object = this.get('dataObject'),
				item_id = String(history[history.length - 1]).replace('slide_', ''),
				folder_data = data_object.getData(item_id);
			
			while(folder_data) {
				if (folder_data.type == Data.TYPE_FOLDER) return folder_data;
				folder_data = data_object.getData(folder_data.parent);
			}
			
			return null;
		},
		
		/**
		 * Render widget
		 * 
		 * @private
		 */
		renderUI: function () {
			Extended.superclass.renderUI.apply(this, arguments);
		},
		
		/**
		 * Bind event listeners
		 * 
		 * @private
		 */
		bindUI: function () {
			var content = this.get('contentBox');
			
			//On folder click start rename
			content.delegate('click', this.handleRenameClick, 'ul.folder > li.type-folder', this);
			
			//On list click close folder
			content.delegate('click', this.handleCloseFolderClick, 'div.yui3-ml-slideshow-slide', this);
			
			//On item render set up form
			this.on('itemRender', this.handleItemRender, this);
			
			Extended.superclass.bindUI.apply(this, arguments);
		},
		
		/**
		 * Handle click on folder, show rename controls
		 * 
		 * @param {Object} event
		 * @private
		 */
		handleRenameClick: function (event) {
			var target = event.target.closest('li.type-folder');
			
			if (!target || !target.hasClass('selected') || target.hasClass('renaming')) return;
			var id = target.getData('itemId'),
				data = this.get('dataObject').getData(id);
			
			//Create input
			var input = Y.Node.create('<input type="text" value="" />');
			input.setAttribute('value', data.title);
			
			target.one('span').insert(input, 'after');
			target.addClass('renaming');
			
			var string = new Supra.Input.String({
				'srcNode': input,
				'value': data.title
			});
			string.render();
			Y.Node.getDOMNode(input).focus();
			
			//On blur confirm changes
			input.on('blur', this.handleRenameComplete, this, {
				'data': data,
				'node': target,
				'object': string,
				'id': id
			});
			
			event.halt();
		},
		
		/**
		 * Handle renaming confirm/cancel
		 * 
		 * @param {Object} event
		 */
		handleRenameComplete: function (event, obj) {
			var value = obj.object.get('value');
			
			if (obj.data.title != value) {
				obj.data.title = value;
				obj.node.one('span').set('innerHTML', Y.Lang.escapeHTML(value));
				
				this.get('dataObject').saveData(obj.id, {
					'title': value
				});
			}
			
			obj.node.removeClass('renaming');
			obj.object.destroy();
		},
		
		/**
		 * Handle click outside folder, close sub-folders
		 * 
		 * @param {Object} event
		 */
		handleCloseFolderClick: function (event) {
			var target = event.target;
			
			// Click on folder item is already handled 
			if (target.closest('ul.folder')) return;
			
			// Get slide
			target = target.closest('div.yui3-ml-slideshow-slide');
			if (!target) return;
			
			var id = target.getData('itemId');
			if (!id) return;
			
			//Style element
			target.all('li').removeClass('selected');
			
			//Scroll to slide
			this.open(id);
		},
		
		handleItemRender: function (event) {
			
		},
		
		/**
		 * Update widget
		 * 
		 * @private
		 */
		syncUI: function () {
			Extended.superclass.syncUI.apply(this, arguments);
		}
	}, {});
	
	
	Supra.MediaLibraryExtendedList = Extended;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.form', 'supra.medialibrary-list', 'supra.medialibrary-slideshow']});