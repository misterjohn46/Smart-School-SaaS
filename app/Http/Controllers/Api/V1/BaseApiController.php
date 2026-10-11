<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseHelper;

/**
 * BaseApiController
 *
 * Controller dasar untuk semua endpoint API versi 1.
 * Semua controller API V1 harus extends dari sini agar otomatis
 * memiliki akses ke ApiResponseHelper trait.
 */
class BaseApiController extends Controller
{
    use ApiResponseHelper;
}
