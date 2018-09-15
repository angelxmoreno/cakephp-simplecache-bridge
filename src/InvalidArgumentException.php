<?php

namespace Axm\CakePHPSimpleCacheBridge;

use InvalidArgumentException as BaseInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;

/**
 * Class InvalidArgumentException
 */
class InvalidArgumentException extends BaseInvalidArgumentException implements SimpleCacheInvalidArgumentException
{

}
