<?php

declare(strict_types=1);

class ActiveDirectory {
    public static function isMockMode(): bool {
        return strtolower(trim((string) getenv('AD_DIRECTORY_MODE'))) === 'mock';
    }

    public static function accountExists(string $account): bool {
        foreach (self::search($account) as $user) {
            if (hash_equals($account, $user['account'])) return true;
        }
        return false;
    }

    public static function search(string $query): array {
        if (self::isMockMode()) {
            $users = [
                [
                    'id' => 'juan.perez', 'account' => 'juan.perez', 'display_name' => 'Juan Pérez',
                    'given_name' => 'Juan', 'surname' => 'Pérez', 'email' => 'juan.perez@ejemplo.com',
                    'employee_id' => '', 'title' => 'PROGRAMADOR', 'department' => '',
                ],
                [
                    'id' => 'maria.gomez', 'account' => 'maria.gomez', 'display_name' => 'Maria Gómez',
                    'given_name' => 'Maria', 'surname' => 'Gómez', 'email' => 'maria.gomez@ejemplo.com',
                    'employee_id' => '', 'title' => 'FINANCIERO', 'department' => '',
                ],
            ];
            $term = mb_strtolower(trim($query));
            return array_values(array_filter($users, static fn (array $user): bool => str_contains(
                mb_strtolower(implode(' ', [$user['display_name'], $user['account'], $user['email']])),
                $term
            )));
        }

        if (!extension_loaded('ldap')) {
            throw new RuntimeException('El servidor PHP no tiene habilitada la extensión LDAP.');
        }

        $host = trim((string) getenv('AD_LDAP_HOST'));
        $baseDn = trim((string) getenv('AD_LDAP_BASE_DN'));
        $bindDn = trim((string) getenv('AD_LDAP_BIND_DN'));
        $bindPassword = (string) getenv('AD_LDAP_BIND_PASSWORD');
        $port = (int) (getenv('AD_LDAP_PORT') ?: 636);
        $startTls = in_array(strtolower((string) getenv('AD_LDAP_STARTTLS')), ['1', 'true', 'yes'], true);

        if ($host === '' || $baseDn === '' || $bindDn === '' || $bindPassword === '') {
            throw new RuntimeException('Falta configurar AD_LDAP_HOST, AD_LDAP_BASE_DN, AD_LDAP_BIND_DN y AD_LDAP_BIND_PASSWORD.');
        }
        if (!str_starts_with($host, 'ldap://') && !str_starts_with($host, 'ldaps://')) {
            $host = ($port === 636 ? 'ldaps://' : 'ldap://') . $host;
        }

        $connection = @ldap_connect($host, $port);
        if ($connection === false) {
            throw new RuntimeException('No fue posible conectar con Active Directory.');
        }

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($connection, LDAP_OPT_NETWORK_TIMEOUT, 5);

        if ($startTls && !@ldap_start_tls($connection)) {
            @ldap_unbind($connection);
            throw new RuntimeException('No fue posible iniciar una conexión segura con Active Directory.');
        }
        if (!@ldap_bind($connection, $bindDn, $bindPassword)) {
            @ldap_unbind($connection);
            throw new RuntimeException('No fue posible autenticar la consulta con Active Directory.');
        }

        $escapedQuery = ldap_escape($query, '', LDAP_ESCAPE_FILTER);
        $filter = '(&(objectCategory=person)(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2))' .
            '(|(displayName=*' . $escapedQuery . '*)(mail=*' . $escapedQuery . '*)(sAMAccountName=*' . $escapedQuery . '*)(userPrincipalName=*' . $escapedQuery . '*)))';
        $attributes = ['sAMAccountName', 'userPrincipalName', 'displayName', 'givenName', 'sn', 'mail', 'employeeID', 'title', 'department'];
        $search = @ldap_search($connection, $baseDn, $filter, $attributes, 0, 20);
        if ($search === false) {
            @ldap_unbind($connection);
            throw new RuntimeException('No fue posible consultar usuarios en Active Directory.');
        }

        $entries = ldap_get_entries($connection, $search);
        @ldap_unbind($connection);

        $users = [];
        for ($index = 0; $index < ($entries['count'] ?? 0); $index++) {
            $entry = $entries[$index];
            $get = static fn (string $attribute): string => (string) ($entry[strtolower($attribute)][0] ?? '');
            $account = $get('sAMAccountName') ?: $get('userPrincipalName');
            if ($account === '') continue;

            $displayName = $get('displayName');
            $givenName = $get('givenName');
            $surname = $get('sn');
            if ($displayName === '') $displayName = trim($givenName . ' ' . $surname);

            $users[] = [
                'id' => $account,
                'account' => $account,
                'display_name' => $displayName !== '' ? $displayName : $account,
                'given_name' => $givenName,
                'surname' => $surname,
                'email' => $get('mail') ?: $get('userPrincipalName'),
                'employee_id' => $get('employeeID'),
                'title' => $get('title'),
                'department' => $get('department'),
            ];
        }

        return $users;
    }
}