<?php

require_once 'config/auth.php';

// Realizar o logout
fazerLogout();

// Redirecionar para a página de login
header('Location: login.php');
exit();
