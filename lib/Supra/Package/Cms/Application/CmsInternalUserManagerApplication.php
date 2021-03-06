<?php

/*
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
 *
 */

namespace Supra\Package\Cms\Application;

use Supra\Core\Application\AbstractApplication;

class CmsInternalUserManagerApplication extends AbstractApplication
{
	protected $id = 'internal-user-manager';

	protected $url = 'internal-user-manager';

	protected $title = 'Backoffice Users';

	protected $icon = '/public/cms/supra/img/apps/backoffice_users';

	protected $route = 'backoffice_users';

	protected $access = self::APPLICATION_ACCESS_PUBLIC;
}