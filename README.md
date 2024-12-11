# Blob Object Storage Plugin
This plugin stores uploaded files using Relay Blob Bundle instead of using the local filesystem.

## Relay-Blob Bundle
https://github.com/digital-blueprint/relay-blob-bundle

## DbpRelayBlobLibrary
https://github.com/digital-blueprint/relay-blob-library

## Installation

```bash
# from kanboard root directory
cd plugins
git clone git@github.com:digital-blueprint/plan-blob-storage-plugin.git
mv plan-blob-storage-plugin BlobStorage
cd BlobStorage
composer install --no-dev
```

Note: Plugin folder is case-sensitive.

## Configuration
The plugin can be configured in two ways: through the user interface or using the config file.

### With the user interface
Go to Settings > Integrations >  Blob File Storage

![blob-plugin-config-form](https://github.com/digital-blueprint/plan-blob-storage-plugin/assets/5683951/56cde866-bf10-4208-b63d-d2c0ae21dea5)

### With the config file

Add those config parameters in your `config.php`:

```php
define('BLOB_KEY', '12345678901234567890123456789012345678901234567890123456789012');
define('BLOB_API_HOST', 'https://blog-api-host.com');
define('BLOB_BUCKET_ID', 'your-bucket-id');
define('BLOB_OAUTH_IDP_URL', 'https://your.oauth.server');
define('BLOB_CLIENT_ID', 'your-client-id');
define('BLOB_CLIENT_SECRET', 'your-client-secret');
```

### Requirements
------------
- PHP >= 8.1
- Kanboard >= 1.2.1

```mermaid
---
title: Blob Storage Plugin
---
graph TB
    subgraph BlobApi
        uploadFileByPrefix["uploadFileByPrefix()"]
        getFileDataByPrefix["getFileDataByPrefix()"]
        deleteFileByPrefix["deleteFileByPrefix()"]
    end
    subgraph BlobStorage
        moveFile["moveFile()"] --> uploadFileByPrefix
        put["put()"] --> uploadFileByPrefix
        output["output()"] --> getFileDataByPrefix
        get["get()"] --> getFileDataByPrefix
        remove["remove()"] --> deleteFileByPrefix
    end
    subgraph BlobTaskFileModel
        uploadFiles["uploadFiles()"] --> uploadFile
        uploadFile["uploadFile()"] --> moveFile
        uploadScreenshot["uploadScreenshot()"] --> uploadContent
        uploadContent["uploadContent()"] --> put
    end
    subgraph FileModel
        Fremove["remove()"] --> remove
    end
    subgraph ViewFileController
        download["download()"] --> output
        thumbnail["thumbnail()"] --> output
        renderFileWithCache["renderFileWithCache()"] --> output
        getFileContent["getFileContent()"] --> get
    end
    subgraph BlobTaskFileController
        Tsave["save()"] --> uploadFiles
        Tscreenshot["screenshot()"] --> uploadScreenshot
        Tremove["remove()"] --> Fremove
    end
    subgraph BlobProjectFileController
        Psave["save()"] --> uploadFiles
        Pscreenshot["screenshot()"] --> uploadScreenshot
        Premove["remove()"] --> Fremove
    end
```
