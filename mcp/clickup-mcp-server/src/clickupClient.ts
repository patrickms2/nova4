import axios, { AxiosInstance } from 'axios';

export interface ClickUpTask {
  id: string;
  name: string;
  description: string;
  status: string;
  assignees: any[];
  priority: number;
  due_date: string | null;
  start_date: string | null;
  time_estimate: number | null;
  time_spent: number | null;
  custom_fields: any[];
  tags: string[];
  parent: string | null;
  list: {
    id: string;
    name: string;
  };
  folder: {
    id: string;
    name: string;
  } | null;
  space: {
    id: string;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface ClickUpSpace {
  id: string;
  name: string;
  access: boolean;
  type: string;
  features: {
    due_dates: {
      enabled: boolean;
      start_date: boolean;
      remap_due_dates: boolean;
      remap_closed_due_date: boolean;
    };
    time_tracking: {
      enabled: boolean;
    };
    points: {
      enabled: boolean;
    };
  };
}

export interface ClickUpTeam {
  id: string;
  name: string;
  members: number[];
}

export class ClickUpClient {
  private client: AxiosInstance;
  private apiKey: string;

  constructor(apiKey: string) {
    this.apiKey = apiKey;
    this.client = axios.create({
      baseURL: 'https://api.clickup.com/api/v2',
      headers: {
        'Authorization': this.apiKey,
        'Content-Type': 'application/json',
      },
    });
  }

  async getAuthorizedTeams(): Promise<ClickUpTeam[]> {
    const response = await this.client.get('/team');
    return response.data.teams;
  }

  async getSpaces(teamId: string): Promise<ClickUpSpace[]> {
    const response = await this.client.get(`/team/${teamId}/space`);
    return response.data.spaces;
  }

  async getTask(taskId: string): Promise<ClickUpTask> {
    const response = await this.client.get(`/task/${taskId}`);
    return response.data;
  }

  async getTasks(listId: string, options?: {
    include_markdown_description?: boolean;
    include_closed?: boolean;
    order_by?: string;
    statuses?: string[];
    assignees?: string[];
    tags?: string[];
  }): Promise<{ tasks: ClickUpTask[] }> {
    const params: any = {};
    
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

  async getFilteredTeamTasks(teamId: string, options?: {
    space_ids?: string[];
    project_ids?: string[];
    list_ids?: string[];
    statuses?: string[];
    assignees?: string[];
    tags?: string[];
    include_closed?: boolean;
    order_by?: string;
  }): Promise<{ tasks: ClickUpTask[] }> {
    const params: any = {};
    
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

  async createTask(listId: string, taskData: {
    name: string;
    description?: string;
    assignees?: string[];
    status?: string;
    priority?: number;
    due_date?: string;
    start_date?: string;
    time_estimate?: number;
    tags?: string[];
  }): Promise<ClickUpTask> {
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

  async updateTask(taskId: string, taskData: {
    name?: string;
    description?: string;
    status?: string;
    priority?: number;
    due_date?: string;
    start_date?: string;
    time_estimate?: number;
    assignees?: {
      add?: string[];
      rem?: string[];
    };
    tags?: {
      add?: string[];
      rem?: string[];
    };
  }): Promise<ClickUpTask> {
    const response = await this.client.put(`/task/${taskId}`, taskData);
    return response.data;
  }

  async deleteTask(taskId: string): Promise<void> {
    await this.client.delete(`/task/${taskId}`);
  }
}
