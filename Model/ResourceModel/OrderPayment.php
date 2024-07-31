<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Model\ResourceModel;

use SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\Batch;
use SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchCollection;

/**
 * Extends the Order\Payment resource model with new methods
 */
class OrderPayment extends \Magento\Sales\Model\ResourceModel\Order\Payment
{
    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite
     * @param \Magento\Sales\Model\ResourceModel\Attribute $attribute
     * @param \Magento\SalesSequence\Model\Manager $sequenceManager
     * @param \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchFactory $batchFactory
     * @param \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchLoadedFactory $batchLoadedFactory
     * @param \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchCollectionFactory $batchCollectionFactory
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite $entityRelationComposite,
        \Magento\Sales\Model\ResourceModel\Attribute $attribute,
        \Magento\SalesSequence\Model\Manager $sequenceManager,
        \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchFactory $batchFactory,
        \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchLoadedFactory $batchLoadedFactory,
        \SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchCollectionFactory $batchCollectionFactory,
        $connectionName = null
    ) {
        $this->batchFactory = $batchFactory;
        $this->batchLoadedFactory = $batchLoadedFactory;
        $this->batchCollectionFactory = $batchCollectionFactory;
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $attribute,
            $sequenceManager,
            $connectionName
        );
    }

    /**
     * Fetch order payments by order id
     *
     * @param array $ids
     * @return BatchCollection
     */
    public function getPaymentsByIds($ids)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'parent_id IN(?)',
            $ids
        )->where(
            'cc_number_enc IS NOT NULL'
        );
        $items = $connection->fetchAssoc($select);

        return $this->batchCollectionFactory
            ->create()
            ->setTotal(count($items))
            ->add(
                $this->batchLoadedFactory->create()->setItems($items)
            );
    }

    /**
     * Fetch all order payments with non null cc_number_enc
     *
     * @param int $batchSize
     */
    public function getPaymentsWithNonNullEncryption(int $batchSize)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(), ["count" => "COUNT(*)"]
        )->where(
            'cc_number_enc IS NOT NULL'
        );
        $count = intval($connection->fetchOne($select), 10);
        $numberOfBatches = ceil($count / $batchSize);
        $collection = $this->batchCollectionFactory
            ->create()
            ->setTotal($count);

        // Prepare batches
        for ($i=0; $i < $numberOfBatches; $i++) {
            $batchStartOffset = $i * $batchSize;
            $batchEndOffset = (($i + 1) * $batchSize) - 1;

            $select = clone $connection->select();
            $select->from(
                $this->getMainTable()
            )->where(
                'cc_number_enc IS NOT NULL'
            )->limit($batchSize, $batchStartOffset);

            $collection->add(
                $this->batchFactory->create()
                    ->setResourceModel($this)
                    ->setStart($batchStartOffset)
                    ->setEnd($batchEndOffset)
                    ->setSelect($select)
            );
        }

        return $collection;
    }

    /**
     * Save order payment as assoc array
     *
     * @param int|string $paymentId
     * @param array $data
     */
    public function saveAssoc($paymentId, array $data)
    {
        $whereCondition = [$this->getIdFieldName() . '=?' => $paymentId];
        $connection = $this->getConnection();
        $connection->update($this->getMainTable(), $data, $whereCondition);
        return $this;
    }
}
