<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dmScheduled, a plugin for Dotclear 2.
#
# Copyright (c) Franck Paul and contributors
# carnet.franck.paul@gmail.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_CONTEXT_ADMIN')) { return; }

class dmScheduledRest
{
	/**
	 * Serve method to check if some entries need to be published.
	 *
	 * @param	core	<b>dcCore</b>	dcCore instance
	 * @param	get		<b>array</b>	cleaned $_GET
	 *
	 * @return	<b>xmlTag</b>	XML representation of response
	 */
	public static function checkScheduled($core,$get)
	{
		global $core;

		$core->blog->publishScheduledEntries();

		$rsp = new xmlTag('check');
		$rsp->ret = true;

		return $rsp;
	}
}
