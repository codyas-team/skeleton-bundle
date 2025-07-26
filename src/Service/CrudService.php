<?php

namespace Codyas\SkeletonBundle\Service;

use Codyas\SkeletonBundle\Event\CrudEntityCreatedEvent;
use Codyas\SkeletonBundle\Event\CrudEntityDeletedEvent;
use Codyas\SkeletonBundle\Event\CrudEntityModifiedEvent;
use Codyas\SkeletonBundle\Event\CrudEntityPreDeleteEvent;
use Codyas\SkeletonBundle\Event\CrudEntityPrePersistEvent;
use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Exception\InvalidFormException;
use Codyas\SkeletonBundle\Helper\Constants;
use Codyas\SkeletonBundle\Model\CrudEntity;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Codyas\SkeletonBundle\Model\RowRendererArguments;
use Codyas\SkeletonBundle\Security\VoterArgument;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CrudService
{
    public function __construct(
        private EntityManagerInterface            $em,
        private PaginatorInterface                $paginator,
        private TranslatorInterface               $translator,
        private FormFactoryInterface              $formFactory,
        private RequestStack                      $requestStack,
        private RouterInterface                   $router,
        private AuthorizationCheckerInterface     $authorizationChecker,
        private Security                          $security,
        private Environment                       $twig,
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    public function renderListFromFqdn(string $fqdn, ?array $filterData = []): array
    {
        $entityConfig = $this->getEntityConfiguration($fqdn);
        if ($entityConfig->crudMode === CrudEntityInterface::CRUD_MODE_SPA) {
            return $this->renderJsonListResponseFromEntityConfig($entityConfig, $filterData);
        }
        return $this->renderListFromEntityConfig($entityConfig, $filterData);
    }

    public function renderPartialFromFqdn(string $fqdn, ?array $filterData = []): array
    {
        $entityConfig = $this->getEntityConfiguration($fqdn);
        return $this->renderPartialFromEntityConfig($entityConfig, $filterData);
    }

    public function renderListFromEntityConfig(CrudEntity $entityConfig, ?array $filterData = []): array
    {
        if (!$this->authorizationChecker->isGranted(CrudEntityInterface::LIST, new VoterArgument($entityConfig))) {
            throw new AccessDeniedException();
        }
        $filterForm = $this->getFilterFormInstance($entityConfig, $filterData);
        $pagination = $this->buildPagination($entityConfig, $filterForm);
        return [$entityConfig->getListTemplate(), [
            'entityConfig' => $entityConfig,
            'filterForm' => $filterForm?->createView(),
            'pagination' => $pagination,
            'items' => $this->buildResponse($pagination, $entityConfig)
        ]];
    }

    public function renderJsonListResponseFromEntityConfig(CrudEntity $entityConfig, ?array $filterData = []): array
    {
        if (!$this->authorizationChecker->isGranted(CrudEntityInterface::LIST, new VoterArgument($entityConfig))) {
            throw new AccessDeniedException();
        }
        $filterForm = $this->getFilterFormInstance($entityConfig, $filterData);
        $pagination = $this->buildPagination($entityConfig, $filterForm);
        return [
            'pagination' => $this->twig->render('@Skeleton/crud/layout/tabler/_data_table_pagination.html.twig', [
                'lineItems' => $pagination
            ]),
            'content' => $this->twig->render('@Skeleton/crud/layout/tabler/_data_table.html.twig', [
                'entityConfig' => $entityConfig,
                'items' => $this->buildResponse($pagination, $entityConfig)
            ])
        ];
    }

    public function renderPartialFromEntityConfig(CrudEntity $entityConfig, ?array $filterData = []): array
    {
        if (!$this->authorizationChecker->isGranted(CrudEntityInterface::LIST, new VoterArgument($entityConfig))) {
            throw new AccessDeniedException();
        }
        $filterForm = $this->getFilterFormInstance($entityConfig, $filterData);
        $pagination = $this->buildPagination($entityConfig, $filterForm);
        return [$entityConfig->getListTemplate(), [
            'entityConfig' => $entityConfig,
            'filterForm' => $filterForm?->createView(),
            'pagination' => $pagination,
            'items' => $this->buildResponse($pagination, $entityConfig)
        ]];
    }

    public function renderDetails(CrudEntityInterface $instance): array
    {
        $entityConfig = $this->getEntityConfiguration(get_class($instance));
        $this->denyAccessUnlessGranted(CrudEntityInterface::DETAILS, $entityConfig, $instance);
        return [$entityConfig->getDetailsTemplate(), [
            'entityConfig' => $entityConfig,
            'instance' => $instance
        ]];
    }

    public function renderForm(?CrudEntity $entityConfig = null, ?CrudEntityInterface $instance = null): array
    {
        if (!$entityConfig) {
            $entityConfig = $this->getEntityConfiguration(get_class($instance));
        }
        $voterAttribute = $instance?->getId() === null ? CrudEntityInterface::CREATE : CrudEntityInterface::EDIT;
        $this->denyAccessUnlessGranted($voterAttribute, $entityConfig, $instance);

        $formType = $entityConfig->formType;
        $actionUrl = $this->generateInstanceActionUrl($instance, $entityConfig);
        $scope = $this->requestStack->getCurrentRequest()->get('scope');
        $form = $this->formFactory->create($formType, $instance, [
            'action' => $this->buildActionUrlWithScope($actionUrl, $scope),
        ]);
        $template = $instance->getId() ? $entityConfig->getEditTemplate() : $entityConfig->getCreateTemplate();
        return [$template, [
            'entityConfig' => $entityConfig,
            'form' => $form->createView()
        ]];
    }

    public function getEntityConfiguration(string $fqdn): CrudEntity
    {
        $reflectionClass = new \ReflectionClass($fqdn);
        $attributes = $reflectionClass->getAttributes(CrudEntity::class);
        if (empty($attributes)) {
            throw new ConfigurationException("Entity {$fqdn} does not uses the attribute \Codyas\SkeletonBundle\Model\CrudEntity.");
        }
        if (!$reflectionClass->implementsInterface(CrudEntityInterface::class)) {
            throw new ConfigurationException("Entity {$fqdn} must implements interface \Codyas\SkeletonBundle\Model\CrudEntityInterface in order to be managed by the CRUD.");
        }
        $attribute = $attributes[0];
        /** @var CrudEntity $instance */
        return $attribute->newInstance();
    }

    public function buildPagination(CrudEntity $entityConfig, ?FormInterface $filterForm): PaginationInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $formData = $filterForm->getData();
        $query = $this->buildQuery($entityConfig, $request, $filterForm);
        return $this->paginator->paginate($query, $formData['page'], $entityConfig->listPageSize);
    }

    public function buildQuery(CrudEntity $entityConfig, Request $request, ?FormInterface $filterForm): Query
    {
        $orderColumn = $request->query->get('orderColumn', 'id');
        $orderDirection = $request->query->get('orderDirection', 'desc');
        $entityRepository = $this->em->getRepository($entityConfig->fqdn);
        if (!$entityConfig->customFetch) {
            return $entityRepository->createQueryBuilder('e')
                ->orderBy("e.$orderColumn", $orderDirection)
                ->getQuery();

        }
        $filter = [
            'orderColumn' => $orderColumn,
            'orderDirection' => $orderDirection
        ];
        if ($filterForm) {
            $filterForm->submit($request->get($filterForm->getName()));
            $filter = array_merge($filter, $filterForm->getData());
        }
        $filterCollection = new ArrayCollection($filter);
        return $entityRepository->fetch($filterCollection);
    }

    /**
     * @param PaginationInterface $pagination
     * @param CrudEntity $entityConfig
     * @return array
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function buildResponse(PaginationInterface $pagination, CrudEntity $entityConfig): array
    {
        $response = [];
        /**
         * @var int $key
         * @var CrudEntityInterface $item
         */
        foreach ($pagination as $key => $item) {
            $actionButtons = $entityConfig->displayActionsButtons === true ? [
                $this->twig->render($entityConfig->actionButtonsTemplate, [
                    'instance' => $item,
                    'entityConfig' => $entityConfig,
                    'entity' => $entityConfig->getEncodedFqdn(),
                ])
            ] : null;
            $instanceRows = $item->renderDataTableRow(new RowRendererArguments(
                $this->translator,
                $this->router,
                $this->twig,
                $this->authorizationChecker,
                $this->security->getUser()
            ));
            if (count($instanceRows) !== count($entityConfig->dataTableColumns)) {
                throw new ConfigurationException(sprintf("The entity %s::renderDataTableRow should return an array with %s elements, as specified in the column definition; however, it returned an array with %s elements.",
                    $entityConfig->fqdn,
                    count($entityConfig->dataTableColumns),
                    count($instanceRows)
                ));
            }
            $response [] = array_merge(
                $entityConfig->displayRowNumber ? [$key + 1] : [],
                $entityConfig->displayNomenclatorStatusColumn ? [$this->twig->render($entityConfig->nomenclatorStatusColumnTemplate, [
                    'instance' => $item
                ])] : [],
                $instanceRows,
                $actionButtons,
                ['instance' => $item]
            );
        }
        return $response;
    }

    public function retrieveInstance(string $fqdn, mixed $id): CrudEntityInterface
    {
        $instance = $this->em->getRepository($fqdn)->find($id);
        if (!$instance) {
            throw new NotFoundHttpException();
        }
        if (!$instance instanceof CrudEntityInterface) {
            throw new ConfigurationException("Entity \"$fqdn\" does not implements the \"CrudEntityInterface\", therefore it cannot be retrieved.");
        }
        return $instance;
    }

    public function handleFormSubmission(CrudEntityInterface $instance, CrudEntity $entityConfig, Request $request): CrudEntityInterface|array
    {
        $isModification = $instance->getId() !== null;
        $formType = $entityConfig->formType;
        $form = $this->formFactory->create($formType, $instance, [
            'action' => $this->generateInstanceActionUrl($instance, $entityConfig)
        ]);
        $form->handleRequest($request);
        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }
        $this->eventDispatcher->dispatch(new CrudEntityPrePersistEvent($instance, $form));
        $this->em->persist($instance);
        $this->em->flush();
        $event = $isModification ? new CrudEntityModifiedEvent($instance) : new CrudEntityCreatedEvent($instance);
        $this->eventDispatcher->dispatch($event);

        return $instance;
    }

    public function generateInstanceActionUrl(CrudEntityInterface $instance, CrudEntity $entityConfig): string
    {
        if ($instance->getId()) {
            $actionUrl = $this->router->generate('csk_crud_edit', [
                'id' => $instance->getId(),
                'fqdn' => $entityConfig->getEncodedFqdn()]);
        } else {
            $actionUrl = $this->router->generate('csk_crud_create', [
                'fqdn' => $entityConfig->getEncodedFqdn()
            ]);
        }
        return $actionUrl;
    }

    public function generateInstanceEditUrl(CrudEntityInterface $instance, CrudEntity $entityConfiguration): string
    {
        $route = $entityConfiguration->getEditRoute();
        return $this->router->generate($route, [
            'id' => $instance->getId(),
            'fqdn' => $entityConfiguration->getFqdnRouteArgument(Constants::ACTION_EDIT)]);
    }

    public function removeInstance(CrudEntityInterface $instance): void
    {
        $this->eventDispatcher->dispatch(new CrudEntityPreDeleteEvent($instance));
        $this->em->remove($instance);
        $this->em->flush();
        $this->eventDispatcher->dispatch(new CrudEntityDeletedEvent($instance));
    }

    public function isAuthorized(string $attribute, CrudEntity $entityConfig, ?CrudEntityInterface $instance): bool
    {
        return $this->authorizationChecker->isGranted($attribute, new VoterArgument($entityConfig, $instance));
    }

    public function denyAccessUnlessGranted(string $attribute, CrudEntity $entityConfig, ?CrudEntityInterface $instance): void
    {
        if (!$this->isAuthorized($attribute, $entityConfig, $instance)) {
            throw new AccessDeniedHttpException();
        }
    }

    public function getFilterFormInstance(CrudEntity $entityConfig, ?array $filterData): ?FormInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryParams = $request->query->all();
        $filterData['pageSize'] = $request->query->get('pageSize', 10);
        $filterData['page'] = $request->query->get('page', 1);
        $encodedFqdn = $entityConfig->getEncodedFqdn();
        if (array_key_exists($encodedFqdn, $queryParams) && $queryParams[$encodedFqdn]) {
            $filterData = array_merge($queryParams[$encodedFqdn], $filterData);
        }
        $filterForm = null;
        if ($entityConfig->isFilterable()) {
            $filterForm = $this->formFactory->createNamed(
                $entityConfig->getFlattenedFqdn(),
                $entityConfig->filterType,
                $filterData,
                array_merge(
                    $entityConfig->filterTypeOptions, [
                    'method' => Request::METHOD_GET,
                    'csrf_protection' => false
                ]));
        }
        return $filterForm;
    }

    private function buildActionUrlWithScope(string $actionUrl, ?string $scope): string
    {
        $queryParams = $scope ? ['scope' => $scope] : [];
        return $actionUrl . ($queryParams ? '?' . http_build_query($queryParams) : '');
    }

}
