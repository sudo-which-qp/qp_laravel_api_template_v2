<?php

namespace App\Enums;

enum StatusCode: int
{

    case Ok = 200;
    case Continue = 201;
    case BadRequest = 400;
    case Unauthorized = 401;
    case Forbidden = 403;
    case NotFound = 404;
    case MethodNotAllowed = 405;
    case UnprocessableEntity = 422;
    case InternalServerError = 500;
}
