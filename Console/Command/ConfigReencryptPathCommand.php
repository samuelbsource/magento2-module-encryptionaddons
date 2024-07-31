<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Magento\Framework\Encryption\EncryptorInterface;
use SamuelbSource\EncryptionAddons\Model\ResourceModel\Config;

class ConfigReencryptPathCommand extends Command
{
    private EncryptorInterface $encryptor;
    private Config $coreConfig;

    /**
     * Constructor
     *
     * @param EncryptorInterface $encryptor
     * @param Config $coreConfig
     */
    public function __construct(
        EncryptorInterface $encryptor,
        Config $coreConfig
    ) {
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
        $this->setName('encryption:config:reencrypt:path')
            ->setDescription('Re-encrypts all configuration values for a specified path using the current encryption key.')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'The configuration path whose values need to be re-encrypted. Example: "my/deletedmodule/enabled"'
            );
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
        $path = $input->getArgument('path');

        $output->writeln('<info>Starting re-encryption of configuration values for path: ' . $path . '...</info>');

        // Fetch all values for the given path
        $values = $this->coreConfig->getValuesByPath($path);
        if (empty($values)) {
            $output->writeln('<error>No configuration values found for the specified path: ' . $path . '</error>');
            return Command::FAILURE;
        }

        // Update all values
        foreach ($values as $configId => $value) {
            $output->writeln('<info>Processing config ID: ' . $configId . '</info>');

            try {
                $decrypted = $this->encryptor->decrypt($value);

                // Magento might return an empty string if decryption failed
                if (empty($decrypted) && !empty($value)) {
                    $output->writeln(
                        sprintf('<error>Decryption failed for config ID %d. Value might be corrupted.</error>', $configId)
                    );
                    continue;
                }

                $this->coreConfig->setValuesById($configId, $this->encryptor->encrypt($decrypted));
            } catch (\Exception $e) {
                // Handle any exception that occurs during decryption/encryption
                $output->writeln(
                    sprintf('<error>Error processing config ID %d: %s</error>', $configId, $e->getMessage())
                );
            }
        }

        $output->writeln('<info>Re-encryption process completed successfully.</info>');

        return Command::SUCCESS;
    }
}
