<?php

declare(strict_types=1);

namespace Magextensionsio\PolyShellProtection\Plugin;

use Magextensionsio\PolyShellProtection\Logger\Logger;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorFile;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

final class BlockStorefrontCustomOptionFileUpload
{
    private const