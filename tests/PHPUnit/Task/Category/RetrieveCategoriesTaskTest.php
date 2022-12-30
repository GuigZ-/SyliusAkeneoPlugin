<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Category;

use Akeneo\Pim\ApiClient\Api\CategoryApi;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\ResponseStack;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\Category\RetrieveCategoriesTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class RetrieveCategoriesTaskTest extends AbstractTaskTest
{
    private const CATEGORY_COUNT = 67;

    private const CATEGORY_COUNT_AFTER_EXCLUSIONS = 55;

    private const CLOTHES_ROOT_CATEGORY_COUNT = 12;

    private const CLOTHES_ROOT_CATEGORY_COUNT_WITH_EXCLUSIONS = 7;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server->setResponseOfPath(
            '/' . sprintf(CategoryApi::CATEGORIES_URI),
            new ResponseStack(
                new Response($this->getCategories(), [], HttpResponse::HTTP_OK),
            ),
        );

        $this->categoryConfiguration = $this->buildBasicConfiguration();
        $this->manager->flush();
    }

    public function testGetCategories(): void
    {
        $configuration = self::getContainer()->get(CategoryConfigurationProviderInterface::class);
        $configuration->get()->setCategoryCodesToImport(['master']);
        $configuration->get()->setCategoryCodesToExclude([]);

        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        /** @var array $categoriesTree */
        $categoriesTree = $payload->getResources();

        $this->assertCount(self::CATEGORY_COUNT, $categoriesTree);
    }

    public function testGetCategoriesWithExclusions(): void
    {
        $configuration = self::getContainer()->get(CategoryConfigurationProviderInterface::class);
        $configuration->get()->setCategoryCodesToImport(['master']);
        $configuration->get()->setCategoryCodesToExclude(['sales', 'clothes']);

        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        $categoriesTree = $payload->getResources();

        $expectedExcludedCodes = [
            'sales',
            'clothes',
            'pants',
            'jeans',
            'shoes',
            'clothes_accessories',
            'ties',
            'caps',
            'gloves',
            'belts',
        ];

        foreach ($expectedExcludedCodes as $expectedExcludedCode) {
            $this->assertNotContains(
                $expectedExcludedCode,
                array_map(static fn ($val) => $val['code'], $categoriesTree),
            );
        }

        $this->assertCount(self::CATEGORY_COUNT_AFTER_EXCLUSIONS, $categoriesTree);
    }

    public function testGetCategoriesWithRootCategory(): void
    {
        $configuration = self::getContainer()->get(CategoryConfigurationProviderInterface::class);
        $configuration->get()->setCategoryCodesToImport(['clothes']);
        $configuration->get()->setCategoryCodesToExclude([]);

        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        $categoriesTree = $payload->getResources();

        $expectedExcludedCodes = [
            'master',
            'accessories',
            'office',
            'blazers',
            'digital_cameras',
        ];

        foreach ($expectedExcludedCodes as $expectedExcludedCode) {
            $this->assertNotContains(
                $expectedExcludedCode,
                array_map(static fn ($val) => $val['code'], $categoriesTree),
            );
        }

        $this->assertCount(self::CLOTHES_ROOT_CATEGORY_COUNT, $categoriesTree);
    }

    public function testGetCategoriesWithRootCategoryAndExistingExclusion(): void
    {
        $configuration = self::getContainer()->get(CategoryConfigurationProviderInterface::class);
        $configuration->get()->setCategoryCodesToImport(['clothes']);
        $configuration->get()->setCategoryCodesToExclude(['clothes_accessories']);

        $retrieveCategoryPayload = new CategoryPayload($this->createClient());

        /** @var RetrieveCategoriesTask $task */
        $task = $this->taskProvider->get(RetrieveCategoriesTask::class);
        $payload = $task->__invoke($retrieveCategoryPayload);

        $categoriesTree = $payload->getResources();

        $expectedExcludedCodes = [
            'master',
            'accessories',
            'office',
            'blazers',
            'digital_cameras',
        ];

        foreach ($expectedExcludedCodes as $expectedExcludedCode) {
            $this->assertNotContains(
                $expectedExcludedCode,
                array_map(static fn ($val) => $val['code'], $categoriesTree),
            );
        }

        $this->assertCount(self::CLOTHES_ROOT_CATEGORY_COUNT_WITH_EXCLUSIONS, $categoriesTree);
    }
}
