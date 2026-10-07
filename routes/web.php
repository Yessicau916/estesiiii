<?php

use App\Controllers\RoleController;

$router->get('/roles', [RoleController::class, 'index']);
$router->get('/roles/create', [RoleController::class, 'create']);
$router->post('/roles', [RoleController::class, 'store']);
$router->get('/roles/edit/{id}', [RoleController::class, 'edit']);
$router->post('/roles/update/{id}', [RoleController::class, 'update']);
$router->post('/roles/delete/{id}', [RoleController::class, 'destroy']);