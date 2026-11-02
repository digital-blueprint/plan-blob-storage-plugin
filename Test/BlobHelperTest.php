<?php

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Test;

use Kanboard\Plugin\BlobStorage\Helper\BlobHelper;
use PHPUnit\Framework\TestCase;

class BlobHelperTest extends TestCase
{
    public function testGenerateBlobKeyFilenameSanitizesOriginalFilename(): void
    {
        $this->assertSame(
            'tasks/1/file-hash' . DIRECTORY_SEPARATOR . 'my_report-final.pdf',
            BlobHelper::generateBlobKeyFilename('my report/final.pdf', 'tasks/1/file-hash')
        );
    }
}
