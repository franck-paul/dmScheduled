<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dmPending, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */			"Scheduled Dashboard Module",
	/* Description*/		"Display scheduled posts on dashboard",
	/* Author */			"Franck Paul",
	/* Version */			'0.3',
	array(
		/* Permissions */	'permissions' =>	'admin',
		/* Type */			'type' =>			'plugin',
		/* Settings */		'settings' =>		array('pref'  => '#user-favorites.dmscheduled')
	)
);
