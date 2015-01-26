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
 * Manager Action plugin to automatically set container as main
 * content and resize if left, right or top (Header) containers changes
 */
YUI.add('supra.manager-action-plugin-maincontent', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginMainContent () {
		PluginMainContent.superclass.constructor.apply(this, arguments);
		this.children = {};
	};
	
	PluginMainContent.NAME = 'PluginMainContent';
	
	Y.extend(PluginMainContent, Action.PluginBase, {
		
		initialize: function () {
			var layoutTopContainer = Supra.Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = Supra.Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = Supra.Manager.getAction('LayoutRightContainer');
			
			//Attribute
			if (this.host.addAttr) {
				this.host.addAttr('layoutDisabled', {
					'value': false
				});
			}
			
			//Position
			this.host.one().addClass('center-container');
			
			//Container position sync with other actions
			this.host.plug(Supra.PluginLayout, {
				'offset': [0, 0, 0, 0],		// Default offset from page viewport
				'supressInteractions': 375	// How long suppress interaction with UI after UI sync
			});
			
			//Offsets from other containers 
			this.host.layout.addOffset(layoutTopContainer, layoutTopContainer.one(), 'top', 0);
			this.host.layout.addOffset(layoutLeftContainer, layoutLeftContainer.one(), 'left', 0);
			this.host.layout.addOffset(layoutRightContainer, layoutRightContainer.one(), 'right', 0);
			
			//On visible change and execute reposition container
			this.host.after('execute', function (e) {
				//Update position
				this.host.layout.syncUI();
			}, this);
			
			this.host.on('visibleChange', function (e) {
				if (e.newVal) {
					//Update position
					this.host.layout.syncUI();
				}
			}, this);
			
			this.host.layout.syncUI();
		}
		
	});
	
	Action.PluginMainContent = PluginMainContent;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base']});