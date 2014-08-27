//Invoke strict mode
"use strict";


/**
 * Main manager action, initiates all other actions
 */
Supra(function (Y) {

	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 */
		NAME: 'UserPermissions',
		
		/**
		 * Load action stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load action template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * Selected application
		 * @type {String}
		 * @private
		 */
		application: null,
		
		/**
		 * All application data
		 * @type {Object}
		 * @private
		 */
		applications: null,
		
		
		/**
		 * Bind Actions together
		 * 
		 * @private
		 */
		render: function () {
			
			//Load application list
			Supra.io(this.getDataPath('applications'), {
				'context': this,
				'on': {
					'success': this.renderApplications
				}
			});
			
			//On app click
			this.one('ul').delegate('click', this.onAppClick, 'li', this);
			
		},
		
		/**
		 * Open application properties
		 */
		onAppClick: function (event) {
			var target = event.target.closest('li'),
				app = target.getAttribute('data-app-id'),
				action = Manager.getAction('PermissionProperties'),
				data = null,
				user = Manager.getAction('User').getData();
			
			target.siblings().removeClass('selected');
			target.addClass('selected');
			
			this.application = app;
			
			for(var i=0,ii=this.applications.length; i<ii; i++) {
				if (this.applications[i].id == app) {
					data = this.applications[i];
					break;
				}
			}
			
			action.setPlaceHolder(this.one());
			action.execute(data, data.permissions, user.permissions[app]);
		},
		
		/**
		 * Render application list
		 * 
		 * @param {Array} data Application list
		 * @private
		 */
		renderApplications: function (data /* Application list */) {
			
			var container = this.one('ul');
			var html = Supra.Template('applicationsListItem', {'data': data});
			
			container.empty();
			container.append(html);
			
			//Update application list
			this.applications = data;
			
			if (Manager.User.isUser()) {
				this.setUserData(Manager.getAction('User').getData());
			} else {
				this.setGroupData(Manager.getAction('User').getData());
			}
			
			//Update user application data list
			this.updateUserAppList();
		},
		
		/**
		 * Set defaults for all apps which are missing from user data
		 * 
		 * @private
		 */
		updateUserAppList: function () {
			if (this.applications) {
				var user = Manager.getAction('User').getData().permissions,
					apps = this.applications,
					permissions = null;
				
				for(var i=0,ii=apps.length; i<ii; i++) {
					if (!(apps[i].id in user)) {
						
						user[apps[i].id] = {};
						
						permissions = apps[i].permissions;
						for(var k=0,kk=permissions.length; k<kk; k++) {
							user[apps[i].id][permissions[k].id] = permissions[k].value;
							if (permissions[k].subproperty) {
								user[apps[i].id].items = [];
							}
						}
					}
				}
				
				//Update user permissions
				var ul = this.one('ul'),
					li = null,
					allow = null;
				
				for(var app in user) {
					allow = user[app].allow;
					li = ul.one('li[data-id="' + app.replace(/[\/\\]/g, '') + '"]');
					
					if (li) {
						li
							.removeClass('allow-0')
							.removeClass('allow-1')
							.removeClass('allow-2')
							.addClass('allow-' + allow);
					}
				}
			}
		},
		
		/**
		 * Update UI
		 * 
		 * @param {Object} data User data
		 * @private
		 */
		setUserData: function (data /* User data */) {
			var img = this.one('div.info img'),
				a	= this.one('div.info a'),
				b	= this.one('div.info b');

			img.setAttribute('src', data.avatar + '?r=' + (+new Date()));
			a.set('text', data.name || Supra.Intl.get(['userdetails', 'default_name']));
			
			if (Manager.User.isUser()) {
				a.removeClass('hidden');
				a.next().removeClass('hidden');
				b.set('text', Supra.Intl.get(['userdetails', 'group_' + data.group]));
			} else {
				a.addClass('hidden');
				a.next().addClass('hidden');
				b.set('text', Supra.Intl.get(['userdetails', 'group_' + data.group_id]));
			}
			
			var ul = this.one('ul'),
				li = null,
				allow = false;
			
			//Fill application list for new user
			this.updateUserAppList();
		},
		
		/**
		 * Update UI
		 * 
		 * @param {Object} data Group data
		 * @private
		 */
		setGroupData: function (data /* Group data */) {
			this.setUserData(data);
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			if (this.get('visible')) {
				this.set('visible', false);
				
				//Unselect
				this.one('ul').all('li').removeClass('selected');
			}
		},
		
		/**
		 * Execute action
		 */
		execute: function () {
			//If new user: validate form
			if (Manager.User.isUser() && !Manager.User.getData().user_id) {
				//
				Manager.UserDetails.createNewUser();
				Manager.UserDetails.execute();
				
				//Unset 'down' state
				Manager.PageToolbar.buttons.permissions.set('down', false)
				
				return;
			}
			
			//Slide
			this.show();
			
			var user = Manager.getAction('User');
			
			if (Manager.User.isUser()) {
				//Update UI with user data
				this.setUserData(Manager.getAction('User').getData());
				user.slideshow.set('slide', this.NAME);
			} else {
				//Update UI with group data
				this.setGroupData(Manager.getAction('User').getData());
				user.slideshow.syncUI();
			}
		}
	});
	
});