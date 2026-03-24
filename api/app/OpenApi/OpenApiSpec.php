<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Clothes Shop API',
    description: 'Swagger documentation for testing Clothes Shop API endpoints.'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Local server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Use JWT token in format: Bearer {token}'
)]
#[OA\Tag(
    name: 'Admin Categories',
    description: 'Admin category management endpoints'
)]
class OpenApiSpec
{
}
