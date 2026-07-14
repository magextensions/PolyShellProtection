<?php

declare(strict_types=1);

namespace Magextensionsio\PolyShellProtection\Plugin;

use Magextensionsio\PolyShellProtection\Logger\Logger;
use Magento\Catalog\Model\Webapi\Product\Option\Type\File\Processor;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

final class BlockWebApiCustomOptionFileUpload
{
    private const MAX_LOG_VALUE_LENGTH = 255;

    public function __construct(
        private readonly Logger $logger,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * Prevent every file_info payload from being persisted to
     * pub/media/custom_options/quote through Magento's Web API processor.
     *
     * @throws InputException
     */
    public function beforeProcessFileContent(
        Processor $subject,
        ImageContentInterface $imageContent
    ): void {
        $this->logger->warning('Blocked Web API custom-option file upload.', [
            'ip' => $this->sanitize($this->remoteAddress->getRemoteAddress()),
            'filename' => $this->sanitize((string) $imageContent->getName()),
            'mime_type' => $this->sanitize((string) $imageContent->getType()),
        ]);

        throw new InputException(
            __('File uploads for product custom options are disabled.')
        );
    }

    private function sanitize(?string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return substr($value, 0, self::MAX_LOG_VALUE_LENGTH);
    }
}
