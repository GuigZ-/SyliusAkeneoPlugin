<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Command;

use League\Pipeline\Pipeline;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Factory\AssociationTypePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;

final class ImportAssociationTypeCommand extends AbstractImportCommand
{
    use LockableTrait;

    protected static $defaultDescription = 'Import Associations type from Akeneo PIM.';

    /** @var string */
    protected static $defaultName = 'akeneo:import:association-type';

    /** @var AssociationTypePipelineFactory */
    private $associationTypePipelineFactory;

    /** @var ClientFactory */
    private $clientFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AssociationTypePipelineFactory $associationTypePipelineFactory,
        ClientFactory $clientFactory,
        LoggerInterface $akeneoLogger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->associationTypePipelineFactory = $associationTypePipelineFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $akeneoLogger;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        if (!$this->lock()) {
            $output->writeln(Messages::commandAlreadyRunning());

            return 0;
        }

        $context = parent::createContext($input, $output);

        $this->logger->notice(self::$defaultName);
        /** @var Pipeline $associationTypePipeline */
        $associationTypePipeline = $this->associationTypePipelineFactory->create();

        $associationTypePayload = new AssociationTypePayload($this->clientFactory->createFromApiCredentials(), $context);
        $associationTypePipeline->process($associationTypePayload);

        $this->logger->notice(Messages::endOfCommand(self::$defaultName));
        $this->release();

        return 0;
    }
}
