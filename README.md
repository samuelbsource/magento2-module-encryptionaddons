# SamuelbSource_EncryptionAddons

[WIP] Provides new ways of managing Magento 2 encryption keys

## Table of contents
- [Summary](#summary)
- [Installation](#installation)
- [Usage](#usage)
- [Credits](#credits)
- [License](#license)

## Summary

Magento only provided way of key rotation is through the Admin panel, this is problematic for multiple reasons
  - Can easily hit php execute limits
  - Entire process must be complete in one go
  - Can't automate key rotation

## Installation
```
composer require samuelbsource/magento2-module-encryptionaddons
bin/magento module:enable SamuelbSource_EncryptionAddons
bin/magento setup:upgrade
```

## Usage
> WARNING: While this module was tested with various modified and vanilla Magento installations, there can be race condition where it might fail to correct reencrypt all data.
> As such, it is recommended that you always take a backup of your env.php and database before rotating encryption keys.

This module adds the following commands to manage encryption keys:

> Adds the specified encryption key to the env.php, if key is not specified it will generate a new key.
> This command does not reencrypt any data, for which other commands can be used.
> Adding a new encryption key to magento will cause Magento to use it when encrypting new values.
> Old values will be decrypted using the previous key, until they are reencrypted with the new key.
```
bin/magento encryption:key:add <optional:key>
```

> Reencrypts core_config_data values to use the latest key and modern cipher.
```
bin/magento encryption:config:reencrypt
```

> Works like the above command but allows you to reencrypt specific path only, this is useful if the module that defined the config path
> was disabled or removed, but you want to key configuration in case you want to bring back the module.
```
bin/magento encryption:config:reencrypt:path <path>
```

> Reencrypts quote_payment to use the latest key and cipher.
> Unlike the core magento command, it does not attempt to run everything in one go, which
> should help with large databases with many orders.
```
bin/magento encryption:quote:reencrypt <optional:ids>
```

> Reencrypts sales_order_payment to use the latest key and cipher.
> Unlike the core magento command, it does not attempt to run everything in one go, which
> should help with large databases with many orders.
```
bin/magento encryption:order:reencrypt <optional:ids>
```

This module also adds a new cron job that checks every day the age of your encryption key,
if the key is older than the configured value (default 90 days, you will get admin notification to rotate keys)

## Planned features

 - Selectively and efficiently find orders/configs using only old encryption key/cipher. This should be doable with a good SQL query.
 - Refactor some code

## Authors

 - Samuel Boczek <samuelboczek@gmail.com>

## License

[MIT](https://opensource.org/licenses/MIT)
