<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Config\Model\Config\Backend\Encrypted;
use SamuelbSource\EncryptionAddons\Model\ResourceModel\Config;
use SamuelbSource\EncryptionAddons\Model\Config\StructureFactory;

class ConfigReencryptAllCommand extends Command
{
    private StructureFactory $configStructureFactory;
    private EncryptorInterface $encryptor;
    private Config $coreConfig;

    /**
     * Constructor
     *
     * @param StructureFactory $configStructureFactory
     * @param EncryptorInterface $encryptor
     * @param Config $coreConfig
     */
    public function __construct(
        StructureFactory $configStructureFactory,
        EncryptorInterface $encryptor,
        Config $coreConfig
    ) {
        $this->configStructureFactory = $configStructureFactory;
        $this->encryptor = $encryptor;
        $this->coreConfig = $coreConfig;
        parent::__construct();
    }

    /**
     * Configure the command options and description
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('encryption:config:reencrypt')
            ->setDescription('Re-encrypt all configuration values using the current encryption key');
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Command::SUCCESS if the execution is successful
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting re-encryption of all encrypted configuration settings...</info>');

        // Load the configuration structure
        $configStructure = $this->configStructureFactory->create()->reloadData('adminhtml');
        $output->writeln('<info>Configuration structure loaded.</info>');

        // Get all encrypted configuration fields
        $encryptedPaths = $configStructure->getFieldPathsByAttribute('backend_model', Encrypted::class);
        if (!$encryptedPaths) {
            $output->writeln('<error>Could not retrieve a list of encrypted configuration fields.</error>');
            return Command::FAILURE;
        }
        $output->writeln('<info>Retrieved list of encrypted configuration fields.</info>');

        // Initialize progress bar
        $progressBar = new ProgressBar($output, count($encryptedPaths));
        $progressBar->setFormat(
            '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
        );
        $output->writeln('<info>Reencryption was started.</info>');
        $progressBar->start();
        $progressBar->display();

        foreach ($encryptedPaths as $path) {
            $progressBar->setMessage($path);

            // Get all saved values for this path and reencrypt them
            foreach ($this->coreConfig->getValuesByPath($path) as $configId => $value) {
                try {
                    $decrypted = $this->encryptor->decrypt($value);

                    // Magento might return an empty string if decryption failed, report it but continue.
                    if (empty($decrypted) && !empty($value)) {
                        $output->writeln([
                            '',
                            sprintf('<error>Decryption failed for config ID %d. Value might be corrupted.</error>', $configId)
                        ]);
                        continue;
                    }

                    $this->coreConfig->setValuesById($configId, $this->encryptor->encrypt($decrypted));
                } catch (\Exception $e) {
                    $output->writeln([
                        '',
                        sprintf('<error>Error processing config ID %d: %s</error>', $configId, $e->getMessage())
                    ]);
                }
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
