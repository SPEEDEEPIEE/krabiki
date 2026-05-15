<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Workshops API",
 *     version="2.0.0",
 *     description="REST API для управления мастер-классами, площадками и регистрациями. Вариант 15."
 * )
 * @OA\Server(url="http://localhost:8000/api/v1")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum"
 * )
 * @OA\Tag(name="Auth")
 * @OA\Tag(name="Venues")
 * @OA\Tag(name="Workshops")
 * @OA\Tag(name="Registrations")
 */
class Swagger
{
}