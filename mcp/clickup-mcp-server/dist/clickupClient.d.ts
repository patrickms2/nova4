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
export declare class ClickUpClient {
    private client;
    private apiKey;
    constructor(apiKey: string);
    getAuthorizedTeams(): Promise<ClickUpTeam[]>;
    getSpaces(teamId: string): Promise<ClickUpSpace[]>;
    getTask(taskId: string): Promise<ClickUpTask>;
    getTasks(listId: string, options?: {
        include_markdown_description?: boolean;
        include_closed?: boolean;
        order_by?: string;
        statuses?: string[];
        assignees?: string[];
        tags?: string[];
    }): Promise<{
        tasks: ClickUpTask[];
    }>;
    getFilteredTeamTasks(teamId: string, options?: {
        space_ids?: string[];
        project_ids?: string[];
        list_ids?: string[];
        statuses?: string[];
        assignees?: string[];
        tags?: string[];
        include_closed?: boolean;
        order_by?: string;
    }): Promise<{
        tasks: ClickUpTask[];
    }>;
    createTask(listId: string, taskData: {
        name: string;
        description?: string;
        assignees?: string[];
        status?: string;
        priority?: number;
        due_date?: string;
        start_date?: string;
        time_estimate?: number;
        tags?: string[];
    }): Promise<ClickUpTask>;
    updateTask(taskId: string, taskData: {
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
    }): Promise<ClickUpTask>;
    deleteTask(taskId: string): Promise<void>;
}
//# sourceMappingURL=clickupClient.d.ts.map