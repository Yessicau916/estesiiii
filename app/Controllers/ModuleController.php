<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Role.php';

class ModuleController {
    private const MODULES = [
        'dashboard' => [
            'title' => 'Dashboard',
            'description' => 'Resumen general de la plataforma y sus roles.',
            'icon' => 'dashboard',
        ],
        'employees' => [
            'title' => 'Empleados',
            'description' => 'Directorio de personal técnico y administrativo.',
            'icon' => 'users',
        ],
        'categories' => [
            'title' => 'Categorías',
            'description' => 'Clasificación de actividades, especialidades y servicios.',
            'icon' => 'folder',
        ],
        'work-orders' => [
            'title' => 'Orden de trabajo',
            'description' => 'Seguimiento, ejecución y asignación de órdenes de campo.',
            'icon' => 'clipboard',
        ],
        'forms' => [
            'title' => 'Formulario',
            'description' => 'Plantillas de inspección técnica y recolección de datos.',
            'icon' => 'file',
        ],
        'contracts' => [
            'title' => 'Contrato',
            'description' => 'Gestión contractual y asignaciones vinculadas.',
            'icon' => 'signature',
        ],
        'assignments' => [
            'title' => 'Asignación',
            'description' => 'Consulta y administra las asignaciones de contratos.',
            'icon' => 'clipboard',
        ],
        'regions' => [
            'title' => 'Regiones',
            'description' => 'Consulta y administra las regiones operativas.',
            'icon' => 'folder',
        ],
    ];

    public function show(string $slug): void {
        if (!isset(self::MODULES[$slug])) {
            http_response_code(404);
            echo 'Módulo no encontrado';
            return;
        }

        $module = self::MODULES[$slug];
        $activeModule = $slug;
        $roles = $slug === 'dashboard' ? Role::findAll() : [];
        $activeRoles = count(array_filter($roles, static fn (Role $role): bool => $role->getStatus() === '1'));
        $rolesWithPermissions = count(array_filter($roles, static fn (Role $role): bool => $role->getPermissions() !== []));

        require __DIR__ . '/../Views/modules/index.php';
    }
}
