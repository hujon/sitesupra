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
YUI.add('supra.ua-normalize', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//UA for IE11
	var tmp;
	if (tmp = Y.UA.userAgent.match(/Trident\/.*rv:(\d+\.\d+)/)) {
		Y.UA.ie = parseFloat(tmp[1]);
	}
	
	//Set browser UA classname
	var html = Y.one('html');
	
	for(var browser in Y.UA) {
		if (Y.UA[browser] && browser != 'os') {
			html.addClass(browser);
			
			if (browser == 'ie') {
				html.addClass(browser + '-' + Y.UA[browser]);
			}
		}
	}
	
	//Touch?
	Y.UA.touch = !!('ontouchstart' in document.documentElement);
	
	//Prevent content scrolling on iPad
	if (Y.UA.touch) {
		// Touch device
		Y.Node(document).on('touchmove', function (e) {
			e.preventDefault();
		});
	}
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: []});