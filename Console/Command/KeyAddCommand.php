<?php
declare(strict_types=1);

namespace SamuelbSource\EncryptionAddons\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Math\Random;
use Magento\Framework\Config\ConfigOptionsListConstants;
use SamuelbSource\EncryptionAddons\Model\EnvironmentConfigManager;

class KeyAddCommand extends Command
{
    private EnvironmentConfigManager $environmentConfigManager;
    private EncryptorInterface $encryptor;
    private Random $random;

    /**
     * Constructor
     *
     * @param EnvironmentConfigManager $environmentConfigManager
     * @param EncryptorInterface $encryptor
     * @param Random $random
     */
    public function __construct(
        EnvironmentConfigManager $environmentConfigManager,
        EncryptorInterface $encryptor,
        Random $random
    ) {
        $this->environmentConfigManager = $environmentConfigManager;
        $this->encryptor = $encryptor;
        $this->random = $random;
        parent::__construct();
    }

    /**
     * Configure the command options and description
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('encryption:key:add')
            ->setDescription('Add a new encryption key to the environment configuration')
            ->addArgument(
                'key',
                InputArgument::OPTIONAL,
                'Optional encryption key to use instead of generating a random key'
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
        $key = $input->getArgument('key');

        // If no key is provided, generate a random one
        if ($key === null) {
            $key = $this->random->getRandomString(ConfigOptionsListConstants::STORE_KEY_RANDOM_STRING_SIZE);
            $output->writeln('<info>No key provided. Generated a random key: ' . $key . '</info>');
        } else {
            $output->writeln('<info>Using the provided key.</info>');
        }

        // Add key to the list of keys
        $this->encryptor->setNewKey($key);

        // Write the encrypted key to the deployment configuration
        $this->environmentConfigManager
            ->set(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY, $this->encryptor->exportKeys())
            ->save();

        // Output success message
        $output->writeln('<info>Encryption key successfully added to the environment configuration.</info>');

        // Return success status code
        return Command::SUCCESS;
    }
}
