<?php

namespace App\Mcp;

use App\Models\Resource as ResourceModel;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Support\UriTemplate;

class DynamicResourceTemplate extends DynamicResource implements HasUriTemplate
{
    public function __construct(protected ResourceModel $templateResourceModel)
    {
        parent::__construct($templateResourceModel);
    }

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate($this->templateResourceModel->uri_template);
    }
}
