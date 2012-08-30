//Invoke strict mode
"use strict";

Supra('transition', 'supra.htmleditor', function (Y) {

	//Shortcut
	var Manager = Supra.Manager,
		Action = Manager.Action;
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'EditorToolbar',
		
		/**
		 * No template for toolbar
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: false,
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Dependancy list
		 * @type {Array}
		 */
		DEPENDANCIES: ['LayoutContainers'],
		
		
		
		
		/**
		 * List of buttons
		 * @type {Object}
		 * @private
		 */
		buttons: {},
		
		/**
		 * Tab instance
		 * @type {Object}
		 * @see Supra.Tabs
		 * @private
		 */
		tabs: {},
		
		/**
		 * Toolbar instance
		 * @type {Object}
		 * @see Supra.HTMLEditorToolbar
		 * @private
		 */
		toolbar: null,
		
		/**
		 * Timer used for hiding
		 * @type {Object}
		 * @private
		 */
		hide_timer: null,
		
		
		
		
		
		/**
		 * On action create it's possible to change place holder
		 * @private
		 */
		create: function () {
			//Add action as top bar child
			Manager.getAction('LayoutTopContainer').addChildAction('EditorToolbar');
		},
		
		/**
		 * Set configuration/properties, bind listeners, etc.
		 * @private
		 */
		initialize: function () {
			this.toolbar = new Supra.HTMLEditorToolbar({
				visible: false // initially hidden, because toolbar is created before any input is focused
			});
		},
		
		/**
		 * Returns Supra.HTMLEditorToolbar instance
		 * 
		 * @return Toolbar instance
		 * @type {Object}
		 */
		getToolbar: function () {
			return this.toolbar;
		},
		
		/**
		 * Render widgets
		 * 
		 * @private
		 */
		render: function () {
			this.toolbar.render(this.getPlaceHolder());
			this.toolbar.get('boundingBox').addClass('yui3-editor-toolbar-html');
			this.toolbar.hide();
			
			this.on('visibleChange', function (evt) {
				if (evt.prevVal != evt.newVal) {
					this.toolbar.set('visible', evt.newVal);
				}
			}, this);
			
			this.on('disabledChange', function (evt) {
				if (this.toolbar.get('disabled') != evt.newVal) {
					this.toolbar.set('disabled', evt.newVal);
				}
			}, this);
			
			//Add "Apply", "Close" buttons
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': Y.bind(function () {
					if (Manager.PageContent) {
						var active_content = Manager.PageContent.getContent().get('activeChild');
						if (active_content) {
							active_content.fire('block:save');
							return;
						}
					} else {
						//There is no page content, CRUD?
						this.hide();
					}
					
					Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
				}, this)
			}]);
		},
		
		/**
		 * Hide
		 */
		hide: function () {
			if (!this.get('created') || !this.toolbar.get('visible')) return;
			
			if (this.hide_timer) {
				this.hide_timer.cancel();
			}
			
			this.hide_timer = Y.later(515, this, this.afterHide);
			
			//Removed toolbar buttons
			Manager.getAction('PageToolbar').unsetActiveAction(this.NAME);
			
			//Hide toolbar
			this.toolbar.hide();
			
			//Hide "Done", "Close" buttons
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * After small delay actually hide toolbar
		 */
		afterHide: function () {
			//Unset timer
			this.hide_timer = null;
			
			//When animation ends hide toolbar
			Action.Base.prototype.hide.call(this);
		},
		
		/**
		 * Execute action
		 */
		execute: function (dontShow) {
			if (dontShow) return;
			
			//Cancel timer
			if (this.hide_timer) {
				this.hide_timer.cancel();
				this.hide_timer = null;
			}
			
			//Show toolbar
			this.toolbar.show();
			
			//Add empty button set to PageToolbar to hide buttons
			var pagetoolbar = Manager.getAction('PageToolbar');
			if (!pagetoolbar.hasActionButtons(this.NAME)) {
				pagetoolbar.addActionButtons(this.NAME, []);
			}
			pagetoolbar.setActiveAction(this.NAME);
			
			//Show "Done", "Close" buttons
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			this.show();
		}
	});
	
});