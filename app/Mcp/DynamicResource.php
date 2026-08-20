<?php

namespace App\Mcp;

use App\Models\Resource as ResourceModel;
use App\Services\ToolExecutor;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class DynamicResource extends Resource
{
    protected ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
        $this->name = $resourceModel->name;
        $this->title = $resourceModel->title;
        $this->description = $resourceModel->description;
        $this->uri = $resourceModel->uri;
        $this->mimeType = $resourceModel->mime_type;
    }

    public function handle(Request $request): Response
    {
        // Static content
        if (! empty($this->resourceModel->content) && empty($this->resourceModel->handler_code)) {
            return Response::text($this->resourceModel->content);
        }

        // Dynamic content via handler code
        if (! empty($this->resourceModel->handler_code)) {
            try {
                $executor = app(ToolExecutor::class);
                $result = $executor->executeResourceHandler(
                    $this->resourceModel,
                    $request->all()
                );

                return Response::text((string) $result);
            } catch (\Throwable $e) {
                return Response::error($e->getMessage());
            }
        }

        return Response::error('No content available');
    }
}
