import './acl';

import './page/index';
import './component/admin-guard-user-list';
import './component/admin-guard-audit-log';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Module.register('admin-guard', {
    type: 'plugin',
    name: 'admin-guard',
    title: 'admin-guard.general.title',
    description: 'admin-guard.general.description',
    version: '1.0.0',
    color: '#ff3d00',
    icon: 'regular-users',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
    },

    routes: {
        index: {
            component: 'admin-guard-index',
            path: 'index',
            children: {
                users: {
                    component: 'admin-guard-user-list',
                    path: 'users',
                    meta: {
                        parentPath: 'sw.settings.index.plugins',
                    },
                },
                auditLog: {
                    component: 'admin-guard-audit-log',
                    path: 'audit-log',
                    meta: {
                        parentPath: 'sw.settings.index.plugins',
                    },
                },
            },
        },
    },

    settingsItem: [
        {
            name: 'admin-guard',
            to: 'admin.guard.index.users',
            label: 'admin-guard.general.title',
            group: 'plugins',
            icon: 'regular-users',
        },
    ],
});
