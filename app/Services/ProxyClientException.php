<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class ProxyClientException extends RuntimeException implements ClientExceptionInterface
{

}
