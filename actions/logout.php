<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_destroy();
session_name(SESSION_NAME);
session_start();
set_flash('success', 'You have been logged out.');
redirect(APP_URL . '/index.php');
