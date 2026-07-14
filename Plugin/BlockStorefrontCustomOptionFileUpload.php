<?php

declare(strict_types=1);

namespace Magextensionsio\PolyShellProtection\Plugin;

use Magextensionsio\PolyShellProtection\Logger\Logger;
use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\File\ValidatorFile;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\FileTransferFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

final class BlockStorefrontCustomOptionFileUpload
{
    private const MAX_LOG_VALUE_LENGTH = 255;

    public function __construct(
        private readonly FileTransferFactory $fileTransferFactory,
        private readonly Logger $logger,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * Block actual multipart custom-option uploads. An optional file option with
     * no uploaded file continues through Magento's original validation logic.
     *
     * @param callable $proceed
     * @return array<mixed>
     * @throws LocalizedException
     */
    public function aroundValidate(
        ValidatorFile $subject,
        callable $proceed,
        DataObject $processingParams,
        Option $option
    ): array {
        $fieldName = (string) $processingParams->getFilesPrefix()
            . 'options_'
            . (string) $option->getId()
            . '_file';

        $upload = $this->fileTransferFactory->create();

        if ($upload->isUploaded($fieldName)) {
            $this->logger->warning('Blocked multipart custom-option file upload.', [
                'ip' => $this->sanitize($this->remoteAddress->getRemoteAddress()),
                'field' => $fieldName,
                'option_id' => $option->getId(),
                'product_id' => $option->getProductId(),
            ]);

            throw new LocalizedException(
                __('File uploads for product custom options are disabled.')
            );
        }

        /** @var array<mixed> $result */
        $result = $proceed($processingParams, $option);

        return $result;
    }

    private function sanitize(?string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return substr($value, 0, self::MAX_LOG_VALUE_LENGTH);
    }
}
