//Invoke strict mode
"use strict";

SU('supra.tabs', 'dd-drag', function (Y) {
	
	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	//Add as left bar child
	Manager.getAction('LayoutLeftContainer').addChildAction('PageInsertBlock');
	
	/**
	 * Sidebar panel action to Insert new block 
	 * Actual block information is taken from Blocks action
	 */
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'PageInsertBlock',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Action doesn't have template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		
		
		/**
		 * Block data
		 * @type {Object}
		 */
		data: null,
		
		
		
		
		/**
		 * Load blocks data
		 * 
		 * @private
		 */
		loadBlocks: function () {
			if (this.data) return;
			this.getPlaceHolder().addClass('loading');
			
			var url = this.getDataPath();
			
			Supra.io(url, {
				'on': {
					'success': function (data) {
						
						this.data = {};
						var block_action = Manager.getAction('Blocks');
						
						//Create tabs
						for(var i in data) {
							var group = data[i];
							var group_blocks = group.blocks;
							
							var content = this.tabs.addTab({"id": group.id, "title": group.title});
								content.append('<div class="block-list"><ul></ul></div>');
								content = content.one('ul');
							
							//Create block items
							for (var k in group_blocks) {
								//Get block data from Blocks action
								var block = block_action.getBlock(group_blocks[k]);
								var node = Y.Node.create('<li data="' + block.id + '"><img src="' + block.icon + '" alt="' + Y.Escape.html(block.description) + '" /><label>' + Y.Escape.html(block.title) + '</label></li>');
								content.append(node);
								
								this.data[block.id] = block;
								this.data[block.id].node = node;
							}
							
							content.append('<li class="clear"><!-- --></li>');
						}
						
						//Remove loading
						this.getPlaceHolder().removeClass('loading');
						
						//Drag&drop
						this.setupDD();
						
						//Fire resize event
						this.fire('resize');
					}
				}
			}, this);
		},
		
		/**
		 * Returns block by Id
		 * 
		 * @param {String} id Block ID
		 * @return Block properties
		 * @type {Object}
		 */
		getBlock: function (id) {
			return (type in this.data ? this.data[type] : null);
		},
		
		/**
		 * Set up Drag & Drop
		 * 
		 * @private
		 */
		setupDD: function () {
			this.drags = this.getPlaceHolder().all('div.block-list li');
			this.drags.each(Y.bind(function (v, k, items) {
				var node = items.item(k);
				
				//List item clearing floats shouldn't be dragable,
				//because it's visual element
				if (node.hasClass('clear')) return;
				
				var id = node.getAttribute('data');
				var data = this.data[id];
				
				//Add to DD list 
				SU.Manager.PageContent.registerDD({
					'type': 'block',
					'data': data,
					'id': id,
					'node': node
				});
				
			}, this));
		},
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			//Hide content until all widgets are rendered
			this.getPlaceHolder().addClass('hidden');
			
			//Check 'tabs' attribute for additional tab configuration
			var tab_config = this.get('tabs') || {};
			this.set('tabs', tab_config);
			
			//Create tabs
			var tabs = this.tabs = new Supra.Tabs();
			
			for(var id in tab_config) {
				if (Y.Lang.isObject(tab_config[id])) {
					tabs.addTab({"id": id, "title": tab_config[id].title, "icon": tab_config[id].icon});
				}
			}
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			this.tabs.render(this.getPlaceHolder());
			
			//Add className to allow custom styles
			this.tabs.get('boundingBox').addClass(Y.ClassNameManager.getClassName('tab', 'blocks'));
			
			//Show content
			this.getPlaceHolder().removeClass('hidden');
			
			//On visibility change show/hide tabs
			this.on('visibleChange', function (evt) {
				if (evt.prevVal != evt.newVal) {
					if (evt.newVal) {
						this.tabs.show();
					} else {
						this.tabs.hide();
					}
				}
			});
			
			//Fire resize event
			this.fire('resize');
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			Action.Base.prototype.hide.apply(this, arguments);
			Manager.getAction('LayoutLeftContainer').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			this.loadBlocks();
			Manager.getAction('LayoutLeftContainer').setActiveAction(this.NAME);
		}
	});
	
});