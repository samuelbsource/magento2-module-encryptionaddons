<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Represents a collection of batch items.
 */
class BatchCollection
{
    protected int $totalCount;
    protected array $data;

    /**
     * Set total count
     *
     * @param int $totalCount
     * @return self
     */
    public function setTotal(int $totalCount)
    {
        $this->totalCount = $totalCount;
        return $this;
    }

    /**
     * Add batch to the collection
     *
     * @param Batch $batch
     * @return self
     */
    public function add(Batch $batch)
    {
        $this->data[] = $batch;
        return $this;
    }

    /**
     * Return total number of items
     *
     * @return int
     */
    public function getTotal()
    {
        return $this->totalCount;
    }

    /**
     * Check if this collection is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->totalCount === 0;
    }

    /**
     * Iterate over batches
     */
    public function getItems()
    {
        foreach ($this->data as $batchIndex => $batch) {
            foreach ($batch->getItems() as $item) {
                yield $item;
            }
        }
    }
}
