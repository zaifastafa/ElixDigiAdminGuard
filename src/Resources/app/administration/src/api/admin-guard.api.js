const { ApiService } = Shopware.Classes;
const { Application } = Shopware;

class AdminGuardApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = '_action/admin-guard') {
        super(httpClient, loginService, apiEndpoint);
    }

    getUsers() {
        const apiRoute = `${this.getApiBasePath()}/users`;
        return this.httpClient
            .get(apiRoute, { headers: this.getBasicHeaders() })
            .then((response) => ApiService.handleResponse(response));
    }

    disableUser(userId) {
        const apiRoute = `${this.getApiBasePath()}/users/${userId}/disable`;
        return this.httpClient
            .post(apiRoute, {}, { headers: this.getBasicHeaders() })
            .then((response) => ApiService.handleResponse(response));
    }

    enableUser(userId) {
        const apiRoute = `${this.getApiBasePath()}/users/${userId}/enable`;
        return this.httpClient
            .post(apiRoute, {}, { headers: this.getBasicHeaders() })
            .then((response) => ApiService.handleResponse(response));
    }

    getAuditLog(page = 1, limit = 25) {
        const apiRoute = `${this.getApiBasePath()}/audit-log`;
        return this.httpClient
            .get(apiRoute, {
                headers: this.getBasicHeaders(),
                params: { page, limit },
            })
            .then((response) => ApiService.handleResponse(response));
    }

    exportAuditLogCsv() {
        const apiRoute = `${this.getApiBasePath()}/audit-log/export`;
        return this.httpClient
            .get(apiRoute, {
                headers: this.getBasicHeaders(),
                responseType: 'blob',
            })
            .then((response) => {
                const blob = new Blob([response.data], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'admin-guard-audit-log.csv';
                link.click();
                window.URL.revokeObjectURL(url);
            });
    }
}

Application.addServiceProvider('adminGuardService', (container) => {
    const initContainer = Application.getContainer('init');
    return new AdminGuardApiService(initContainer.httpClient, container.loginService);
});
