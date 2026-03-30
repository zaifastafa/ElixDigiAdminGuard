import template from './template.twig';

const { Component } = Shopware;

Component.register('admin-guard-user-list', {
    template,

    inject: ['adminGuardService'],

    data() {
        return {
            isLoading: false,
            users: [],
            showConfirmDisable: false,
            userToDisable: null,
        };
    },

    computed: {
        columns() {
            return [
                { property: 'name', label: this.$tc('admin-guard.userList.columnName'), primary: true },
                { property: 'email', label: this.$tc('admin-guard.userList.columnEmail') },
                { property: 'roles', label: this.$tc('admin-guard.userList.columnRoles') },
                { property: 'createdAt', label: this.$tc('admin-guard.userList.columnCreatedAt') },
                { property: 'lastLoginAt', label: this.$tc('admin-guard.userList.columnLastLogin') },
                { property: 'status', label: this.$tc('admin-guard.userList.columnStatus') },
                { property: 'daysInactive', label: this.$tc('admin-guard.userList.columnDaysInactive') },
            ];
        },

        gridData() {
            return this.users.map((user) => ({
                id: user.id,
                name: `${user.firstName} ${user.lastName}`,
                email: user.email,
                roles: user.roles,
                createdAt: user.createdAt ? new Date(user.createdAt).toLocaleDateString() : '',
                lastLoginAt: user.lastLoginAt ? new Date(user.lastLoginAt).toLocaleDateString() : this.$tc('admin-guard.userList.neverLoggedIn'),
                status: user.status,
                daysInactive: user.daysInactive,
                active: user.active,
                admin: user.admin,
            }));
        },
    },

    created() {
        this.loadUsers();
    },

    methods: {
        async loadUsers() {
            this.isLoading = true;
            try {
                const response = await this.adminGuardService.getUsers();
                this.users = response.data;
            } catch (error) {
                this.createNotificationError({
                    message: error.message || 'Failed to load users',
                });
            } finally {
                this.isLoading = false;
            }
        },

        onDisableUser(user) {
            if (user.admin) {
                this.createNotificationError({
                    message: this.$tc('admin-guard.userList.errorSuperAdmin'),
                });
                return;
            }
            this.userToDisable = user;
            this.showConfirmDisable = true;
        },

        async confirmDisable() {
            this.showConfirmDisable = false;
            if (!this.userToDisable) return;

            try {
                await this.adminGuardService.disableUser(this.userToDisable.id);
                this.createNotificationSuccess({
                    message: this.$tc('admin-guard.userList.successDisabled'),
                });
                this.loadUsers();
            } catch (error) {
                const msg = error?.response?.data?.error || 'Failed to disable user';
                this.createNotificationError({ message: msg });
            }
            this.userToDisable = null;
        },

        cancelDisable() {
            this.showConfirmDisable = false;
            this.userToDisable = null;
        },

        async onEnableUser(user) {
            try {
                await this.adminGuardService.enableUser(user.id);
                this.createNotificationSuccess({
                    message: this.$tc('admin-guard.userList.successEnabled'),
                });
                this.loadUsers();
            } catch (error) {
                this.createNotificationError({
                    message: error.message || 'Failed to enable user',
                });
            }
        },

        getStatusVariant(status) {
            const map = {
                active: 'success',
                warning: 'warning',
                danger: 'danger',
                disabled: 'neutral',
                never_logged_in: 'info',
            };
            return map[status] || 'neutral';
        },

        getStatusLabel(status) {
            const map = {
                active: this.$tc('admin-guard.userList.statusActive'),
                warning: this.$tc('admin-guard.userList.statusWarning'),
                danger: this.$tc('admin-guard.userList.statusDanger'),
                disabled: this.$tc('admin-guard.userList.statusDisabled'),
                never_logged_in: this.$tc('admin-guard.userList.statusNeverLoggedIn'),
            };
            return map[status] || status;
        },
    },
});
