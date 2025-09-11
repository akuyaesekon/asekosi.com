<?php
require_once '../includes/config.php';
requireRole('admin');

header('Location: dashboard.php');
exit();