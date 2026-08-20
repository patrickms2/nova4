<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Tourism Management API",
 *     version="1.0.0",
 *     description="API documentation for the Tourism Management System",
 *
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="https://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */
class SwaggerController extends Controller
{
    // This file is used only for Swagger annotations
}
