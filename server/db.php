<?php
/**
 * Portable API database bootstrap.
 *
 * This file intentionally reads its connection settings from the shared
 * application config so the same project can run on localhost and hosted environments.
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
