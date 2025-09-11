<?php
require_once '../includes/config.php';
requireRole('customer');

header('Location: products.php');
exit();