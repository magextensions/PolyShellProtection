<?php

declare(strict_types=1);

namespace Magextensionsio\PolyShellProtection\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

final class Handler extends Base
{
    /**
     * Only warning-and-higher security events are written to this handler.
     *
     * @var int
     */
    protected $loggerType = Logger::WARNING;

    /**
     * @var string
     */
    protected $fileName = '/var/log/magextensionsio_polyshell.log';
}
