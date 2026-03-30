import template from './template.twig';

const { Component } = Shopware;

Component.register('admin-guard-audit-log', {
    template,

    inject: ['adminGuardService'],

    data() {
        return {
            isLoading: false,
            entries: [],
            total: 0,
            page: 1,
            limit: 25,
        };
    },

    computed: {
        columns() {
            return [
                { property: 'createdAt', label: this.$tc('admin-guard.auditLog.columnDate'), primary: true },
                { property: 'userName', label: this.$tc('admin-guard.auditLog.columnUserName') },
                { property: 'userEmail', label: this.$tc('admin-guard.auditLog.columnUserEmail') },
                { property: 'action', label: this.$tc('admin-guard.auditLog.columnAction') },
                { property: 'details', label: this.$tc('admin-guard.auditLog.columnDetails') },
            ];
        },

        gridData() {
            return this.entries.map((entry) => ({
                id: entry.id,
                createdAt: entry.createdAt ? new Date(entry.createdAt).toLocaleString() : '',
                userName: entry.userName,
                userEmail: entry.userEmail,
                action: entry.action,
                actionLabel: this.getActionLabel(entry.action),
                details: entry.details ? JSON.stringify(entry.details) : '',
            }));
        },
    },

    created() {
        this.loadAuditLog();
    },

    methods: {
        async loadAuditLog() {
            this.isLoading = true;
            try {
                const response = await this.adminGuardService.getAuditLog(this.page, this.limit);
                this.entries = response.data;
                this.total = response.total;
            } catch (error) {
                this.createNotificationError({
                    message: error.message || 'Failed to load audit log',
                });
            } finally {
                this.isLoading = false;
            }
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.loadAuditLog();
        },

        async onExportCsv() {
            try {
                await this.adminGuardService.exportAuditLogCsv();
            } catch (error) {
                this.createNotificationError({
                    message: error.message || 'Failed to export CSV',
                });
            }
        },

        getActionLabel(action) {
            const map = {
                login: this.$tc('admin-guard.auditLog.actionLogin'),
                flagged_warning: this.$tc('admin-guard.auditLog.actionFlaggedWarning'),
                flagged_danger: this.$tc('admin-guard.auditLog.actionFlaggedDanger'),
                auto_disabled: this.$tc('admin-guard.auditLog.actionAutoDisabled'),
                manually_disabled: this.$tc('admin-guard.auditLog.actionManuallyDisabled'),
                manually_enabled: this.$tc('admin-guard.auditLog.actionManuallyEnabled'),
                reminder_sent: this.$tc('admin-guard.auditLog.actionReminderSent'),
            };
            return map[action] || action;
        },

        getActionVariant(action) {
            const map = {
                login: 'info',
                flagged_warning: 'warning',
                flagged_danger: 'danger',
                auto_disabled: 'danger',
                manually_disabled: 'neutral',
                manually_enabled: 'success',
                reminder_sent: 'info',
            };
            return map[action] || 'neutral';
        },
    },
});
