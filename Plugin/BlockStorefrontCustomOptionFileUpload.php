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
     * Blocks multipart custom-option file uploads before Magento moves the
     * temporary upload into pub/media/custom_options/quote.
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
            $temporaryPath = $this->getTemporaryUploadPath($upload->getFileInfo($fieldName), $fieldName);
            $removed = $this->removeTemporaryUpload($temporaryPath);

            $this->logger->warning('Blocked multipart custom-option file upload.', [
                'ip' => $this->sanitize($this->remoteAddress->getRemoteAddress()),
                'field' => $this->sanitize($fieldName),
                'option_id' => $option->getId(),
                'product_id' => $option->getProductId(),
                'temporary_file_removed' => $removed,
            ]);

            throw new LocalizedException(
                __('File uploads for product custom options are disabled.')
            );
        }

        /** @var array<mixed> $result */
        $result = $proceed($processingParams, $option);

        return $result;
    }

    /**
     * @param array<mixed> $fileInfo
     */
    private function getTemporaryUploadPath(array $fileInfo, string $fieldName): ?string
    {
        $entry = $fileInfo[$fieldName] ?? null;

        if (!is_array($entry)) {
            return null;
        }

        $temporaryPath = $entry['tmp_name'] ?? null;

        return is_string($temporaryPath) && $temporaryPath !== ''
            ? $temporaryPath
            : null;
    }

    private function removeTemporaryUpload(?string $temporaryPath): bool
    {
        if ($temporaryPath === null || !is_uploaded_file($temporaryPath)) {
            return false;
        }

        if (@unlink($temporaryPath)) {
            return true;
        }

        $this->logger->error('Could not remove blocked multipart upload temporary file.', [
            'temporary_path' => $this->sanitize($temporaryPath),
        ]);

        return false;
    }

    private function sanitize(?string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string) $value) ?? '';
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

        return substr($value, 0, self::MAX_LOG_VALUE_LENGTH);
    }
}
