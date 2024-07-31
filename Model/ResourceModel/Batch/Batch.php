<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\DB\Select;

/**
 * Represents a batch of items, no information will be loaded from the database until items are requested.
 */
class Batch
{
    protected AbstractDb $resourceModel;
    protected Select $select;
    protected int $batchStart;
    protected int $batchEnd;

    /**
     * Set resource model for this batch to use
     *
     * @param AbstractDb $resourceModel
     * @return self
     */
    public function setResourceModel(AbstractDb $resourceModel)
    {
        $this->resourceModel = $resourceModel;
        return $this;
    }

    /**
     * Set Select for this batch to use
     *
     * @param Select $select
     * @return self
     */
    public function setSelect(Select $select)
    {
        $this->select = $select;
        return $this;
    }

    /**
     * Set batch start offset
     *
     * @param int $batchStart
     * @return self
     */
    public function setStart(int $batchStart)
    {
        $this->batchStart = $batchStart;
        return $this;
    }

    /**
     * Set batch end offset
     *
     * @param int $batchEnd
     * @return self
     */
    public function setEnd(int $batchEnd)
    {
        $this->batchEnd = $batchEnd;
        return $this;
    }

    /**
     * Fetch items for this batch
     */
    public function getItems()
    {
        $connection = $this->resourceModel->getConnection();
        $select = clone $this->select;
        return $connection->fetchAssoc($select);
    }
}
