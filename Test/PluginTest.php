<?php

declare(strict_types=1);

namespace Kanboard\Plugin\BlobStorage\Test;

use Kanboard\Plugin\BlobStorage\Plugin;
use Kanboard\Plugin\BlobStorage\Model\BlobProjectFileModel;
use Kanboard\Plugin\BlobStorage\Model\BlobTaskFileModel;
use KanboardTests\units\Base;

class PluginTest extends Base
{
    public function testInitializeRegistersFileModelsAndCleanupCommand(): void
    {
        $plugin = new Plugin($this->container);
        $plugin->initialize();

        $this->assertInstanceOf(BlobTaskFileModel::class, $this->container['taskFileModel']);
        $this->assertInstanceOf(BlobProjectFileModel::class, $this->container['projectFileModel']);
        $this->assertTrue($this->container['cli']->has('blob:cleanup-avatars'));
    }

    public function testPluginMetadata(): void
    {
        $plugin = new Plugin($this->container);
        $this->assertNotEmpty($plugin->getPluginName());
        $this->assertNotEmpty($plugin->getPluginDescription());
        $this->assertNotEmpty($plugin->getPluginAuthor());
        $this->assertNotEmpty($plugin->getPluginVersion());
        $this->assertNotEmpty($plugin->getPluginHomepage());
    }
}
