#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
  Tool,
} from '@modelcontextprotocol/sdk/types.js';
import { ClickUpClient } from './clickupClient.js';

const CLICKUP_API_KEY = process.env.CLICKUP_API_KEY;

if (!CLICKUP_API_KEY) {
  console.error('Error: CLICKUP_API_KEY environment variable is required');
  process.exit(1);
}

const clickUpClient = new ClickUpClient(CLICKUP_API_KEY);

const server = new Server(
  {
    name: 'clickup-mcp-server',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

const TOOLS: Tool[] = [
  {
    name: 'get_authorized_teams',
    description: 'Get all workspaces (teams) authorized for the authenticated user',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'get_spaces',
    description: 'Get all spaces in a specific team/workspace',
    inputSchema: {
      type: 'object',
      properties: {
        team_id: {
          type: 'string',
          description: 'The ID of the team/workspace',
        },
      },
      required: ['team_id'],
    },
  },
  {
    name: 'get_task',
    description: 'Get detailed information about a specific task',
    inputSchema: {
      type: 'object',
      properties: {
        task_id: {
          type: 'string',
          description: 'The ID of the task',
        },
      },
      required: ['task_id'],
    },
  },
  {
    name: 'get_tasks',
    description: 'Get all tasks in a specific list with optional filters',
    inputSchema: {
      type: 'object',
      properties: {
        list_id: {
          type: 'string',
          description: 'The ID of the list',
        },
        include_markdown_description: {
          type: 'boolean',
          description: 'Return task descriptions in Markdown format',
        },
        include_closed: {
          type: 'boolean',
          description: 'Include closed tasks in the response',
        },
        order_by: {
          type: 'string',
          description: 'Order by field (id, created, updated, due_date)',
          enum: ['id', 'created', 'updated', 'due_date'],
        },
        statuses: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by statuses (e.g., ["to do", "in progress"])',
        },
        assignees: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by assignee IDs',
        },
        tags: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by tags',
        },
      },
      required: ['list_id'],
    },
  },
  {
    name: 'get_filtered_team_tasks',
    description: 'Get filtered tasks across an entire team/workspace',
    inputSchema: {
      type: 'object',
      properties: {
        team_id: {
          type: 'string',
          description: 'The ID of the team/workspace',
        },
        space_ids: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by space IDs',
        },
        project_ids: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by folder/project IDs',
        },
        list_ids: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by list IDs',
        },
        statuses: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by statuses',
        },
        assignees: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by assignee IDs',
        },
        tags: {
          type: 'array',
          items: { type: 'string' },
          description: 'Filter by tags',
        },
        include_closed: {
          type: 'boolean',
          description: 'Include closed tasks',
        },
        order_by: {
          type: 'string',
          description: 'Order by field (id, created, updated, due_date)',
          enum: ['id', 'created', 'updated', 'due_date'],
        },
      },
      required: ['team_id'],
    },
  },
  {
    name: 'create_task',
    description: 'Create a new task in a specific list',
    inputSchema: {
      type: 'object',
      properties: {
        list_id: {
          type: 'string',
          description: 'The ID of the list to create the task in',
        },
        name: {
          type: 'string',
          description: 'The name of the task',
        },
        description: {
          type: 'string',
          description: 'The description of the task',
        },
        assignees: {
          type: 'array',
          items: { type: 'string' },
          description: 'Array of assignee IDs',
        },
        status: {
          type: 'string',
          description: 'The status of the task',
        },
        priority: {
          type: 'number',
          description: 'Priority level (0-4)',
          minimum: 0,
          maximum: 4,
        },
        due_date: {
          type: 'string',
          description: 'Due date in milliseconds since epoch',
        },
        start_date: {
          type: 'string',
          description: 'Start date in milliseconds since epoch',
        },
        time_estimate: {
          type: 'number',
          description: 'Time estimate in milliseconds',
        },
        tags: {
          type: 'array',
          items: { type: 'string' },
          description: 'Array of tags',
        },
      },
      required: ['list_id', 'name'],
    },
  },
  {
    name: 'update_task',
    description: 'Update an existing task',
    inputSchema: {
      type: 'object',
      properties: {
        task_id: {
          type: 'string',
          description: 'The ID of the task to update',
        },
        name: {
          type: 'string',
          description: 'The new name of the task',
        },
        description: {
          type: 'string',
          description: 'The new description of the task',
        },
        status: {
          type: 'string',
          description: 'The new status of the task',
        },
        priority: {
          type: 'number',
          description: 'New priority level (0-4)',
          minimum: 0,
          maximum: 4,
        },
        due_date: {
          type: 'string',
          description: 'New due date in milliseconds since epoch',
        },
        start_date: {
          type: 'string',
          description: 'New start date in milliseconds since epoch',
        },
        time_estimate: {
          type: 'number',
          description: 'New time estimate in milliseconds',
        },
        assignees_add: {
          type: 'array',
          items: { type: 'string' },
          description: 'Assignee IDs to add',
        },
        assignees_remove: {
          type: 'array',
          items: { type: 'string' },
          description: 'Assignee IDs to remove',
        },
        tags_add: {
          type: 'array',
          items: { type: 'string' },
          description: 'Tags to add',
        },
        tags_remove: {
          type: 'array',
          items: { type: 'string' },
          description: 'Tags to remove',
        },
      },
      required: ['task_id'],
    },
  },
  {
    name: 'delete_task',
    description: 'Delete a task',
    inputSchema: {
      type: 'object',
      properties: {
        task_id: {
          type: 'string',
          description: 'The ID of the task to delete',
        },
      },
      required: ['task_id'],
    },
  },
];

server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: TOOLS,
  };
});

server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'get_authorized_teams': {
        const teams = await clickUpClient.getAuthorizedTeams();
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(teams, null, 2),
            },
          ],
        };
      }

      case 'get_spaces': {
        const { team_id } = args as { team_id: string };
        const spaces = await clickUpClient.getSpaces(team_id);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(spaces, null, 2),
            },
          ],
        };
      }

      case 'get_task': {
        const { task_id } = args as { task_id: string };
        const task = await clickUpClient.getTask(task_id);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(task, null, 2),
            },
          ],
        };
      }

      case 'get_tasks': {
        const { list_id, ...options } = args as any;
        const tasks = await clickUpClient.getTasks(list_id, options);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(tasks, null, 2),
            },
          ],
        };
      }

      case 'get_filtered_team_tasks': {
        const { team_id, ...options } = args as any;
        const tasks = await clickUpClient.getFilteredTeamTasks(team_id, options);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(tasks, null, 2),
            },
          ],
        };
      }

      case 'create_task': {
        const { list_id, ...taskData } = args as any;
        const task = await clickUpClient.createTask(list_id, taskData);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(task, null, 2),
            },
          ],
        };
      }

      case 'update_task': {
        const { task_id, assignees_add, assignees_remove, tags_add, tags_remove, ...taskData } = args as any;
        
        const updateData: any = { ...taskData };
        
        if (assignees_add || assignees_remove) {
          updateData.assignees = {
            ...(assignees_add && { add: assignees_add }),
            ...(assignees_remove && { rem: assignees_remove }),
          };
        }
        
        if (tags_add || tags_remove) {
          updateData.tags = {
            ...(tags_add && { add: tags_add }),
            ...(tags_remove && { rem: tags_remove }),
          };
        }
        
        const task = await clickUpClient.updateTask(task_id, updateData);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(task, null, 2),
            },
          ],
        };
      }

      case 'delete_task': {
        const { task_id } = args as { task_id: string };
        await clickUpClient.deleteTask(task_id);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify({ success: true, message: `Task ${task_id} deleted` }, null, 2),
            },
          ],
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    const errorMessage = error instanceof Error ? error.message : String(error);
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({ error: errorMessage }, null, 2),
        },
      ],
      isError: true,
    };
  }
});

async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('ClickUp MCP server running on stdio');
}

main().catch((error) => {
  console.error('Fatal error in main():', error);
  process.exit(1);
});
