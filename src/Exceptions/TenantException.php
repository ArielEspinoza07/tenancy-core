<?php

declare(strict_types=1);

namespace Tenancy\Exceptions;

use RuntimeException;
use Tenancy\Exceptions\Contracts\TenancyException;

abstract class TenantException extends RuntimeException implements TenancyException {}
