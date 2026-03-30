Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'additional_permissions',
    parent: null,
    key: 'elixdigi_admin_guard',
    roles: {
        read: {
            privileges: ['elixdigi_admin_guard:read'],
            dependencies: [],
        },
        manage: {
            privileges: ['elixdigi_admin_guard:manage', 'elixdigi_admin_guard:read'],
            dependencies: [],
        },
    },
});
