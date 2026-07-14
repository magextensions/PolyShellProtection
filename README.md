# Magextensionsio PolyShell Protection

A deliberately small Magento 2 mitigation that disables **new product custom-option file uploads**.
It blocks the upload path abused by the PolyShell unrestricted file-upload vulnerability associated
with Adobe bulletin APSB25-94.

The Composer package is named:

```text
magextensions/module-polyshell-protection
```

The Magento module is registered as:

```text
Magextensionsio_PolyShellProtection
```

## What is blocked

1. REST/Web API `file_info` payloads handled by
   `Magento\Catalog\Model\Webapi\Product\Option\Type\File\Processor`.
2. Actual multipart custom-option file uploads handled by
   `Magento\Catalog\Model\Product\Option\Type\File\ValidatorFile`.

An optional file option with no uploaded file continues through Magento's normal logic. Existing
order attachments are not deleted by this module.

Blocked attempts are written to:

```text
var/log/magextensionsio_polyshell.log
```

## Composer installation from GitHub

Add the repository once in the Magento project:

```bash
composer config repositories.magextensions-polyshell-protection vcs \
  https://github.com/magextensions/PolyShellProtection.git
```

Install the current main branch:

```bash
composer require magextensions/module-polyshell-protection:dev-main
```

After a release tag such as `1.0.0` exists, install the stable release with:

```bash
composer require magextensions/module-polyshell-protection:^1.0
```

Then apply Magento setup changes:

```bash
bin/magento module:enable Magextensionsio_PolyShellProtection
bin/magento setup:upgrade
bin/magento cache:flush
```

For production mode deployments, also run the project's normal DI compilation and static-content
deployment steps.

## Verification

```bash
bin/magento module:status Magextensionsio_PolyShellProtection
bin/magento dev:di:info Magento\\Catalog\\Model\\Webapi\\Product\\Option\\Type\\File\\Processor
bin/magento dev:di:info Magento\\Catalog\\Model\\Product\\Option\\Type\\File\\ValidatorFile
tail -f var/log/magextensionsio_polyshell.log
```

Verify that:

- ordinary products can still be added to the cart;
- products with optional file options can be added without uploading a file;
- an actual custom-option file upload is rejected;
- a REST cart-item payload containing `file_info` is rejected;
- normal product-image uploads still work.

## Important security limitations

This module prevents new custom-option uploads. It does **not**:

- remove existing malware;
- scan existing files;
- block PHP execution under `pub/media` at Nginx/PHP-FPM level;
- rotate credentials or repair an already compromised installation.

Keep a separate web-server rule that prevents PHP-like files from executing under `pub/media`, and
clean all existing malicious files before returning an affected store to service.
