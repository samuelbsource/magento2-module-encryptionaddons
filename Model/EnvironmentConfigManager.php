<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model;

use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\Data\ConfigDataFactory;
use Magento\Framework\Config\File\ConfigFilePool;

class EnvironmentConfigManager
{
    private ConfigDataFactory $configDataFactory;
    private Writer $writer;
    private array $changes;

    /**
     * Constructor
     *
     * @param ConfigDataFactory $configDataFactory
     * @param Writer $writer
     */
    public function __construct(
        ConfigDataFactory $configDataFactory,
        Writer $writer
    ) {
        $this->configDataFactory = $configDataFactory;
        $this->writer = $writer;
        $this->changes = [];
    }

    /**
     * Set a configuration value for a specified path
     *
     * @param string $path The configuration path
     * @param mixed $value The value to set at the configuration path
     * @return self
     */
    public function set(string $path, $value)
    {
        $this->changes[$path] = $value;
        return $this;
    }

    /**
     * Save the configuration values to the deployment configuration file
     *
     * @return self
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->writer->checkIfWritable()) {
            throw new \Exception(__('Deployment configuration file is not writable.'));
        }

        // Change the config
        $encryptSegment = $this->configDataFactory->create(ConfigFilePool::APP_ENV);
        foreach ($this->changes as $key => $value) {
            $encryptSegment->set($key, $value);
        }

        // Save the data
        $this->writer->saveConfig([
            $encryptSegment->getFileKey() => $encryptSegment->getData()
        ]);

        $this->changes = [];
        return $this;
    }
}
