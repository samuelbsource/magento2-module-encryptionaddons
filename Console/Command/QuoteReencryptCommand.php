<?php
namespace SamuelbSource\EncryptionAddons\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class QuoteReencryptCommand extends Command
{
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('encryption:quote:reencrypt')
            ->setDescription('')
            ->setDefinition([]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new Error('Not implemented');
        return Command::SUCCESS;
    }
}
