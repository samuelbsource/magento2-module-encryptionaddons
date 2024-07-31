<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Represents a batch of items that are preloaded.
 */
class BatchLoaded extends Batch
{
    protected array $items = [];

    /**
     * Set items to be used later
     *
     * @param array $items
     * @return self
     */
    public function setItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Fetch items for this batch
     */
    public function getItems()
    {
        return $this->items;
    }
}
