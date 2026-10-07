# AppSig Project

## Overview
AppSig is a role management system built using PHP following the MVC (Model-View-Controller) architecture. This project allows administrators to manage user roles, including creating, editing, viewing, and deleting roles. The application is structured to separate concerns, making it easier to maintain and extend.

## Project Structure
```
appsig
├── app
│   ├── Controllers
│   │   └── RoleController.php
│   ├── Models
│   │   └── Role.php
│   └── Views
│       └── roles
│           ├── index.php
│           └── form.php
├── public
│   ├── index.php
│   ├── css
│   │   └── styles.css
│   └── js
│       └── roles.js
├── routes
│   └── web.php
├── config
│   └── database.php
└── README.md
```

## Installation
1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Set up your database and update the `config/database.php` file with your database credentials.
4. Run the application using a local server (e.g., XAMPP, MAMP, or built-in PHP server).

## Usage
- Access the application through your web browser at `http://localhost/appsig/public/index.php`.
- Use the navigation to manage roles:
  - **List Roles**: View all roles in the system.
  - **Create Role**: Fill out the form to add a new role.
  - **Edit Role**: Modify existing roles.
  - **Delete Role**: Remove roles from the system.

## Features
- Role management with CRUD operations.
- User-friendly interface for managing roles.
- Responsive design with CSS for better user experience.
- JavaScript for client-side validation and interactions.

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>AppSig</strong>
                    <small>Panel</small>
                </div>
            </div>

            <nav class="main-menu">
                <a class="nav-item active" href="#">
                    <span class="icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🛡️</span>
                    <span>Roles</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🧑‍💼</span>
                    <span>Empleados</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📄</span>
                    <span>Formularios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">⚙️</span>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">AD</div>
                    <div>
                        <strong>Admin</strong>
                        <small>Superusuario</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <span class="eyebrow">Gestión</span>
                    <h1>Roles y permisos</h1>
                </div>

                <div class="topbar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar..." />
                    </div>

                    <button class="icon-btn" type="button">🔔</button>
                    <button class="icon-btn" type="button">✉️</button>

                    <div class="user-chip">
                        <div class="avatar small">AD</div>
                        <div>
                            <strong>Admin</strong>
                            <small>admin@appsig.com</small>
                        </div>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span>Total roles</span>
                    <strong id="totalRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Activos</span>
                    <strong id="activeRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Inactivos</span>
                    <strong id="inactiveRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Permisos</span>
                    <strong id="rolesWithPermissions">0</strong>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Listado de roles</h2>
                    <button type="button" id="openCreateBtn" class="primary-btn">+ Nuevo rol</button>
                </div>

                <table class="role-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="roleModal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Crear rol</h3>
                <button type="button" id="closeModalBtn" class="close-btn">✕</button>
            </div>

            <form id="roleForm">
                <input type="hidden" id="roleId" name="id" />

                <div class="form-grid">
                    <label class="field">
                        <span>Nombre</span>
                        <input type="text" id="roleName" name="name" required />
                    </label>

                    <label class="field">
                        <span>Estado</span>
                        <select id="roleStatus" name="status">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Descripción</span>
                        <textarea id="roleDescription" name="description" rows="4"></textarea>
                    </label>
                </div>

                <div class="module-section">
                    <div class="module-header">
                        <h4>Permisos por módulo</h4>
                        <button type="button" id="toggleAllModulesBtn" class="secondary-btn small">Seleccionar todo</button>
                    </div>
                    <div id="moduleList" class="module-list"></div>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="secondary-btn">Cancelar</button>
                    <button type="submit" class="primary-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>AppSig</strong>
                    <small>Panel</small>
                </div>
            </div>

            <nav class="main-menu">
                <a class="nav-item active" href="#">
                    <span class="icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🛡️</span>
                    <span>Roles</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🧑‍💼</span>
                    <span>Empleados</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📄</span>
                    <span>Formularios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">⚙️</span>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">AD</div>
                    <div>
                        <strong>Admin</strong>
                        <small>Superusuario</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <span class="eyebrow">Gestión</span>
                    <h1>Roles y permisos</h1>
                </div>

                <div class="topbar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar..." />
                    </div>

                    <button class="icon-btn" type="button">🔔</button>
                    <button class="icon-btn" type="button">✉️</button>

                    <div class="user-chip">
                        <div class="avatar small">AD</div>
                        <div>
                            <strong>Admin</strong>
                            <small>admin@appsig.com</small>
                        </div>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span>Total roles</span>
                    <strong id="totalRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Activos</span>
                    <strong id="activeRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Inactivos</span>
                    <strong id="inactiveRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Permisos</span>
                    <strong id="rolesWithPermissions">0</strong>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Listado de roles</h2>
                    <button type="button" id="openCreateBtn" class="primary-btn">+ Nuevo rol</button>
                </div>

                <table class="role-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="roleModal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Crear rol</h3>
                <button type="button" id="closeModalBtn" class="close-btn">✕</button>
            </div>

            <form id="roleForm">
                <input type="hidden" id="roleId" name="id" />

                <div class="form-grid">
                    <label class="field">
                        <span>Nombre</span>
                        <input type="text" id="roleName" name="name" required />
                    </label>

                    <label class="field">
                        <span>Estado</span>
                        <select id="roleStatus" name="status">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Descripción</span>
                        <textarea id="roleDescription" name="description" rows="4"></textarea>
                    </label>
                </div>

                <div class="module-section">
                    <div class="module-header">
                        <h4>Permisos por módulo</h4>
                        <button type="button" id="toggleAllModulesBtn" class="secondary-btn small">Seleccionar todo</button>
                    </div>
                    <div id="moduleList" class="module-list"></div>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="secondary-btn">Cancelar</button>
                    <button type="submit" class="primary-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>AppSig</strong>
                    <small>Panel</small>
                </div>
            </div>

            <nav class="main-menu">
                <a class="nav-item active" href="#">
                    <span class="icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🛡️</span>
                    <span>Roles</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🧑‍💼</span>
                    <span>Empleados</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📄</span>
                    <span>Formularios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">⚙️</span>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">AD</div>
                    <div>
                        <strong>Admin</strong>
                        <small>Superusuario</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <span class="eyebrow">Gestión</span>
                    <h1>Roles y permisos</h1>
                </div>

                <div class="topbar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar..." />
                    </div>

                    <button class="icon-btn" type="button">🔔</button>
                    <button class="icon-btn" type="button">✉️</button>

                    <div class="user-chip">
                        <div class="avatar small">AD</div>
                        <div>
                            <strong>Admin</strong>
                            <small>admin@appsig.com</small>
                        </div>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span>Total roles</span>
                    <strong id="totalRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Activos</span>
                    <strong id="activeRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Inactivos</span>
                    <strong id="inactiveRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Permisos</span>
                    <strong id="rolesWithPermissions">0</strong>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Listado de roles</h2>
                    <button type="button" id="openCreateBtn" class="primary-btn">+ Nuevo rol</button>
                </div>

                <table class="role-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="roleModal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Crear rol</h3>
                <button type="button" id="closeModalBtn" class="close-btn">✕</button>
            </div>

            <form id="roleForm">
                <input type="hidden" id="roleId" name="id" />

                <div class="form-grid">
                    <label class="field">
                        <span>Nombre</span>
                        <input type="text" id="roleName" name="name" required />
                    </label>

                    <label class="field">
                        <span>Estado</span>
                        <select id="roleStatus" name="status">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Descripción</span>
                        <textarea id="roleDescription" name="description" rows="4"></textarea>
                    </label>
                </div>

                <div class="module-section">
                    <div class="module-header">
                        <h4>Permisos por módulo</h4>
                        <button type="button" id="toggleAllModulesBtn" class="secondary-btn small">Seleccionar todo</button>
                    </div>
                    <div id="moduleList" class="module-list"></div>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="secondary-btn">Cancelar</button>
                    <button type="submit" class="primary-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>AppSig</strong>
                    <small>Panel</small>
                </div>
            </div>

            <nav class="main-menu">
                <a class="nav-item active" href="#">
                    <span class="icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🛡️</span>
                    <span>Roles</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🧑‍💼</span>
                    <span>Empleados</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📄</span>
                    <span>Formularios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">⚙️</span>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">AD</div>
                    <div>
                        <strong>Admin</strong>
                        <small>Superusuario</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <span class="eyebrow">Gestión</span>
                    <h1>Roles y permisos</h1>
                </div>

                <div class="topbar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar..." />
                    </div>

                    <button class="icon-btn" type="button">🔔</button>
                    <button class="icon-btn" type="button">✉️</button>

                    <div class="user-chip">
                        <div class="avatar small">AD</div>
                        <div>
                            <strong>Admin</strong>
                            <small>admin@appsig.com</small>
                        </div>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span>Total roles</span>
                    <strong id="totalRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Activos</span>
                    <strong id="activeRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Inactivos</span>
                    <strong id="inactiveRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Permisos</span>
                    <strong id="rolesWithPermissions">0</strong>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Listado de roles</h2>
                    <button type="button" id="openCreateBtn" class="primary-btn">+ Nuevo rol</button>
                </div>

                <table class="role-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="roleModal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Crear rol</h3>
                <button type="button" id="closeModalBtn" class="close-btn">✕</button>
            </div>

            <form id="roleForm">
                <input type="hidden" id="roleId" name="id" />

                <div class="form-grid">
                    <label class="field">
                        <span>Nombre</span>
                        <input type="text" id="roleName" name="name" required />
                    </label>

                    <label class="field">
                        <span>Estado</span>
                        <select id="roleStatus" name="status">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Descripción</span>
                        <textarea id="roleDescription" name="description" rows="4"></textarea>
                    </label>
                </div>

                <div class="module-section">
                    <div class="module-header">
                        <h4>Permisos por módulo</h4>
                        <button type="button" id="toggleAllModulesBtn" class="secondary-btn small">Seleccionar todo</button>
                    </div>
                    <div id="moduleList" class="module-list"></div>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="secondary-btn">Cancelar</button>
                    <button type="submit" class="primary-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">A</div>
                <div>
                    <strong>AppSig</strong>
                    <small>Panel</small>
                </div>
            </div>

            <nav class="main-menu">
                <a class="nav-item active" href="#">
                    <span class="icon">⌂</span>
                    <span>Dashboard</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">👥</span>
                    <span>Usuarios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🛡️</span>
                    <span>Roles</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">🧑‍💼</span>
                    <span>Empleados</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📄</span>
                    <span>Formularios</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">📊</span>
                    <span>Reportes</span>
                </a>
                <a class="nav-item" href="#">
                    <span class="icon">⚙️</span>
                    <span>Configuración</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-mini">
                    <div class="avatar">AD</div>
                    <div>
                        <strong>Admin</strong>
                        <small>Superusuario</small>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="page-title">
                    <span class="eyebrow">Gestión</span>
                    <h1>Roles y permisos</h1>
                </div>

                <div class="topbar-actions">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Buscar..." />
                    </div>

                    <button class="icon-btn" type="button">🔔</button>
                    <button class="icon-btn" type="button">✉️</button>

                    <div class="user-chip">
                        <div class="avatar small">AD</div>
                        <div>
                            <strong>Admin</strong>
                            <small>admin@appsig.com</small>
                        </div>
                    </div>
                </div>
            </header>

            <section class="stats-grid">
                <div class="stat-card">
                    <span>Total roles</span>
                    <strong id="totalRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Activos</span>
                    <strong id="activeRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Inactivos</span>
                    <strong id="inactiveRoles">0</strong>
                </div>
                <div class="stat-card">
                    <span>Permisos</span>
                    <strong id="rolesWithPermissions">0</strong>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <h2>Listado de roles</h2>
                    <button type="button" id="openCreateBtn" class="primary-btn">+ Nuevo rol</button>
                </div>

                <table class="role-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable"></tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="roleModal" class="modal hidden">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="modalTitle">Crear rol</h3>
                <button type="button" id="closeModalBtn" class="close-btn">✕</button>
            </div>

            <form id="roleForm">
                <input type="hidden" id="roleId" name="id" />

                <div class="form-grid">
                    <label class="field">
                        <span>Nombre</span>
                        <input type="text" id="roleName" name="name" required />
                    </label>

                    <label class="field">
                        <span>Estado</span>
                        <select id="roleStatus" name="status">
                            <option value="ACTIVO">ACTIVO</option>
                            <option value="INACTIVO">INACTIVO</option>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Descripción</span>
                        <textarea id="roleDescription" name="description" rows="4"></textarea>
                    </label>
                </div>

                <div class="module-section">
                    <div class="module-header">
                        <h4>Permisos por módulo</h4>
                        <button type="button" id="toggleAllModulesBtn" class="secondary-btn small">Seleccionar todo</button>
                    </div>
                    <div id="moduleList" class="module-list"></div>
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelBtn" class="secondary-btn">Cancelar</button>
                    <button type="submit" class="primary-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html>## Contributing
Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.