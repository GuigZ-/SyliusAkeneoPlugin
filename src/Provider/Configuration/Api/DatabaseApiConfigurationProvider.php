<?php

namespace Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Model\Configuration\ApiConnectionInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\Configuration\DatabaseApiConfigurationToApiConnectionTransformer;

/**
 * @deprecated
 */
class DatabaseApiConfigurationProvider implements ApiConnectionProviderInterface
{
    //TODO: add DefaultPriority at 0

    private ?ApiConnectionInterface $apiConnection = null;
    private RepositoryInterface $apiConfigurationRepository;
    private DatabaseApiConfigurationToApiConnectionTransformer $databaseApiConfigurationToApiConnectionTransformer;

    public function __construct(
        RepositoryInterface $apiConfigurationRepository,
        DatabaseApiConfigurationToApiConnectionTransformer $databaseApiConfigurationToApiConnectionTransformer
    ) {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->databaseApiConfigurationToApiConnectionTransformer = $databaseApiConfigurationToApiConnectionTransformer;
    }

    public function get(): ApiConnectionInterface
    {
        if (null !== $this->apiConnection) {
            return $this->apiConnection;
        }

        $configuration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$configuration instanceof ApiConfiguration) {
            throw new ApiNotConfiguredException();
        }

        return $this->apiConnection = $this->databaseApiConfigurationToApiConnectionTransformer->transform($configuration);
    }
}
