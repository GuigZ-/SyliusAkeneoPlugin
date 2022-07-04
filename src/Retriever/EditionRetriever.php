<?php

namespace Synolia\SyliusAkeneoPlugin\Retriever;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;

class EditionRetriever implements EditionRetrieverInterface
{
    private RepositoryInterface $apiConfigurationRepository;

    private ?ApiConfigurationInterface $configuration = null;

    public function __construct(RepositoryInterface $apiConfigurationRepository)
    {
        $this->apiConfigurationRepository = $apiConfigurationRepository;
    }

    /**
     * @throws ApiNotConfiguredException
     */
    public function getEdition(): string
    {
        if (null === $this->configuration) {
            $this->configuration = $this->apiConfigurationRepository->findOneBy([]);

            if (!$this->configuration instanceof ApiConfiguration) {
                throw new ApiNotConfiguredException();
            }
        }

        return $this->configuration->getEdition();
    }
}
