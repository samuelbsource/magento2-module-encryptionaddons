<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\ResourceModel;

/**
 * Extends the config resource model with new methods
 */
class Config extends \Magento\Config\Model\ResourceModel\Config
{
    /**
     * Get config values by path
     *
     * @param string $path
     * @return array
     */
    public function getValuesByPath($path)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(), ['config_id', 'value']
        )->where(
            'path = ?',
            $path
        );
        return $connection->fetchPairs($select);
    }

    /**
     * Set config values by id
     *
     * @param int $configId
     * @param string $value
     * @return self
     */
    public function setValuesById($configId, $value)
    {
        $whereCondition = [$this->getIdFieldName() . '=?' => $configId];
        $connection = $this->getConnection();
        $connection->update($this->getMainTable(), ['value' => $value], $whereCondition);
        return $this;
    }
}
