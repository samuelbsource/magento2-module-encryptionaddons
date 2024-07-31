<?php
namespace SamuelbSource\EncryptionAddons\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Magento\Framework\Encryption\EncryptorInterface;
use SamuelbSource\EncryptionAddons\Model\ResourceModel\OrderPayment;
use SamuelbSource\EncryptionAddons\Model\ResourceModel\Batch\BatchCollection;

/**
 * Class OrderReencryptCommand
 *
 * This command handles the re-encryption of order payments. It can re-encrypt
 * specific orders by their IDs, or all orders with a non-null cc_number_enc value.
 */
class OrderReencryptCommand extends Command
{
    private const BATCH_SIZE = 50000; // higher value = faster but use more memory, lower value = slower but use less memory
    private OrderPayment $orderPayment;
    private EncryptorInterface $encryptor;

    /**
     * Constructor
     *
     * @param OrderPayment $orderPayment
     * @param EncryptorInterface $encryptor
     */
    public function __construct(OrderPayment $orderPayment, EncryptorInterface $encryptor) {
        $this->orderPayment = $orderPayment;
        $this->encryptor = $encryptor;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('encryption:order:reencrypt')
            ->setDescription('Re-encrypt order payments. Optionally specify order IDs to re-encrypt specific orders.')
            ->addArgument(
                'ids',
                InputArgument::IS_ARRAY,
                'Optional list of order IDs to re-encrypt. When not specified, all orders with non-null cc_number_enc values will be re-encrypted.'
            )
            ->addOption(
                'batch-size',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of orders to process in a single batch.',
                50000 // default value
            );
        parent::configure();
    }

    /**
     * Executes the command to re-encrypt order payments.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids');
        $batchSize = $input->getOption('batch-size');

        if (!empty($ids)) {
            return $this->reencryptOrdersById($output, $ids);
        }

        return $this->reencryptAllOrders($output, $batchSize);
    }

    /**
     * Re-encrypts all existing orders by specified order ids
     *
     * @param OutputInterface $output
     * @param array $orderIds
     * @return int Command exit code
     */
    private function reencryptOrdersById(OutputInterface $output, array $orderIds)
    {
        // Re-encrypt specific orders
        $collection = $this->orderPayment->getPaymentsByIds($orderIds);
        if ($collection->isEmpty()) {
            $output->writeln('<error>No orders with specified ids found.</error>');
            return Command::FAILURE;
        }
        $foundIds = [];
        foreach ($collection->getItems() as $payment) {
            $foundIds[] = $payment['parent_id'];
        }
        $output->writeln('<info>Reencrypting orders with ids: ' . implode(',', $foundIds) . '.</info>');
        return $this->reencryptPayments($output, $collection);
    }

    /**
     * Re-encrypts all orders with non-null encryption values.
     *
     * @param OutputInterface $output
     * @param int $batchSize
     * @return int Command exit code
     */
    private function reencryptAllOrders(OutputInterface $output, int $batchSize)
    {
        $output->writeln("Re-encrypting all orders with non-null encryption value.");
        $collection = $this->orderPayment->getPaymentsWithNonNullEncryption($batchSize);
        if ($collection->isEmpty()) {
            $output->writeln('<error>No orders with non-null cc_number_enc found.</error>');
            return Command::FAILURE;
        }
        return $this->reencryptPayments($output, $collection);
    }

    /**
     * Re-encrypts all order payments in the specified BatchCollection
     *
     * @param OutputInterface $output
     * @param BatchCollection $collection
     * @return int Command exit code
     */
    private function reencryptPayments(OutputInterface $output, BatchCollection $collection)
    {
        // Initialize progress bar
        $progressBar = new ProgressBar($output, $collection->getTotal());
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $output->writeln('<info>Reencryption was started.</info>');
        $progressBar->start();
        $progressBar->display();

        // Iterate over collection
        foreach ($collection->getItems() as $payment) {
            $progressBar->setMessage(sprintf("Order id: %d", $payment['parent_id']));

            try {
                $decrypted = $this->encryptor->decrypt($payment['cc_number_enc']);

                // Magento might return an empty string if decryption failed
                if (empty($decrypted) && !empty($payment['cc_number_enc'])) {
                    $output->writeln(
                        sprintf('<error>Decryption failed for order ID %d. Value might be corrupted.</error>', $payment['parent_id'])
                    );
                    continue;
                }

                $this->orderPayment->saveAssoc($payment['entity_id'], ['cc_number_enc' => $this->encryptor->encrypt($decrypted)]);
            } catch (\Exception $e) {
                // Handle any exception that occurs during decryption/encryption
                $output->writeln(
                    sprintf('<error>Error processing order ID %d: %s</error>', $payment['parent_id'], $e->getMessage())
                );
            }

            // Advance the progress bar
            $progressBar->advance();
        }

        // Done
        $progressBar->finish();
        $output->writeln('');
        $output->writeln('<info>Re-encryption process completed successfully.</info>');

        return Command::SUCCESS;
    }
}
