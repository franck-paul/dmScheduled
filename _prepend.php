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

if (!defined('DC_RC_PATH')) { return; }

// Public and Admin mode

if (!defined('DC_CONTEXT_ADMIN')) { return false; }

// Admin mode

$__autoload['dmScheduledRest'] = dirname(__FILE__).'/_services.php';

// Register REST methods
$core->rest->addFunction('dmScheduledCheck',array('dmScheduledRest','checkScheduled'));
$core->rest->addFunction('dmLastScheduledRows',array('dmScheduledRest','getLastScheduledRows'));
