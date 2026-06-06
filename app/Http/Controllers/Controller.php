<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "E-Waste Collection API",
    description: "API Documentation for E-Waste & Scrap Collection Platform",
    contact: new OA\Contact(email: "admin@example.com")
)]
#[OA\SecurityScheme(
    securityScheme: "apiAuth",
    type: "http",
    name: "Token based Based",
    in: "header",
    bearerFormat: "JWT",
    scheme: "bearer",
    description: "Login with email and password to get the authentication token"
)]
abstract class Controller
{
//
}
