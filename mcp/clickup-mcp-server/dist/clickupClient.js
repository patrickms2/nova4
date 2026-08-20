import axios from 'axios';
export class ClickUpClient {
    client;
    apiKey;
    constructor(apiKey) {
        this.apiKey = apiKey;
        this.client = axios.create({
            baseURL: 'https://api.clickup.com/api/v2',
            headers: {
                'Authorization': this.apiKey,
                'Content-Type': 'application/json',
            },
        });
    }
    async getAuthorizedTeams() {
        const response = await this.client.get('/team');
        return response.data.teams;
    }
    async getSpaces(teamId) {
        const response = await this.client.get(`/team/${teamId}/space`);
        return response.data.spaces;
    }
    async getTask(taskId) {
        const response = await this.client.get(`/task/${taskId}`);
        return response.data;
    }
    async getTasks(listId, options) {
        const params = {};
        if (options?.include_markdown_description) {
            params.include_markdown_description = 'true';
        }
        if (options?.include_closed) {
            params.include_closed = 'true';
        }
        if (options?.order_by) {
            params.order_by = options.order_by;
        }
        if (options?.statuses && options.statuses.length > 0) {
            params['statuses[]'] = options.statuses;
        }
        if (options?.assignees && options.assignees.length > 0) {
            params['assignees[]'] = options.assignees;
        }
        if (options?.tags && options.tags.length > 0) {
            params['tags[]'] = options.tags;
        }
        const response = await this.client.get(`/list/${listId}/task`, { params });
        return response.data;
    }
    async getFilteredTeamTasks(teamId, options) {
        const params = {};
        if (options?.space_ids && options.space_ids.length > 0) {
            params['space_ids[]'] = options.space_ids;
        }
        if (options?.project_ids && options.project_ids.length > 0) {
            params['project_ids[]'] = options.project_ids;
        }
        if (options?.list_ids && options.list_ids.length > 0) {
            params['list_ids[]'] = options.list_ids;
        }
        if (options?.statuses && options.statuses.length > 0) {
            params['statuses[]'] = options.statuses;
        }
        if (options?.assignees && options.assignees.length > 0) {
            params['assignees[]'] = options.assignees;
        }
        if (options?.tags && options.tags.length > 0) {
            params['tags[]'] = options.tags;
        }
        if (options?.include_closed) {
            params.include_closed = 'true';
        }
        if (options?.order_by) {
            params.order_by = options.order_by;
        }
        const response = await this.client.get(`/team/${teamId}/task`, { params });
        return response.data;
    }
    async createTask(listId, taskData) {
        const response = await this.client.post(`/list/${listId}/task`, {
            name: taskData.name,
            description: taskData.description || '',
            assignees: taskData.assignees || [],
            status: taskData.status || 'to do',
            priority: taskData.priority || 0,
            due_date: taskData.due_date,
            start_date: taskData.start_date,
            time_estimate: taskData.time_estimate,
            tags: taskData.tags || [],
        });
        return response.data;
    }
    async updateTask(taskId, taskData) {
        const response = await this.client.put(`/task/${taskId}`, taskData);
        return response.data;
    }
    async deleteTask(taskId) {
        await this.client.delete(`/task/${taskId}`);
    }
}
//# sourceMappingURL=clickupClient.js.map