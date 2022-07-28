<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfigurationInterface;
use Synolia\SyliusAkeneoPlugin\Form\Type\ApiConfigurationType;

final class ApiConfigurationController extends AbstractController
{
    private const PAGING_SIZE = 1;

    private EntityManagerInterface $entityManager;

    private EntityRepository $apiConfigurationRepository;

    private FactoryInterface $apiConfigurationFactory;

    private TranslatorInterface $translator;

    private FlashBagInterface $flashBag;

    private ClientFactoryInterface $clientFactory;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $apiConfigurationRepository,
        FactoryInterface $apiConfigurationFactory,
        FlashBagInterface $flashBag,
        ClientFactoryInterface $clientFactory,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->apiConfigurationFactory = $apiConfigurationFactory;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->clientFactory = $clientFactory;
    }

    public function __invoke(Request $request): Response
    {
        /** @var ApiConfigurationInterface|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([], ['id' => 'DESC']);

        if (!$apiConfiguration instanceof ApiConfigurationInterface) {
            /** @var ApiConfigurationInterface $apiConfiguration */
            $apiConfiguration = $this->apiConfigurationFactory->createNew();
        }

        $form = $this->createForm(ApiConfigurationType::class, $apiConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\Form\SubmitButton $testCredentialsButton */
            $testCredentialsButton = $form->get('testCredentials');

            try {
                $client = $this->clientFactory->authenticateByPassword($apiConfiguration);
                $client->getCategoryApi()->all(self::PAGING_SIZE);

                $this->entityManager->persist($apiConfiguration);

                if (!$testCredentialsButton->isClicked()) {
                    $this->entityManager->flush();
                }

                $this->flashBag->add('success', $this->translator->trans('akeneo.ui.admin.authentication_successfully_succeeded'));
            } catch (\Throwable $throwable) {
                $this->flashBag->add('error', $throwable->getMessage());
            }
        }

        return $this->render('@SynoliaSyliusAkeneoPlugin/Admin/AkeneoConnector/api_configuration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
