<?php

namespace Codyas\SkeletonBundle\Controller;

use Codyas\SkeletonBundle\Exception\InvalidFormException;
use Codyas\SkeletonBundle\Helper\Constants;
use Codyas\SkeletonBundle\Model\CrudEntity;
use Codyas\SkeletonBundle\Model\CrudEntityInterface;
use Codyas\SkeletonBundle\Model\RowRendererArguments;
use Codyas\SkeletonBundle\Service\CrudService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[Route('/{fqdn}')]
class CrudController extends AbstractController
{

    private readonly CrudEntity $entityConfiguration;

    public function __construct(
        private EntityManagerInterface        $em,
        private PaginatorInterface            $paginator,
        private TranslatorInterface           $translator,
        private RouterInterface               $router,
        private Environment                   $twig,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CrudService                   $crudService
    )
    {
    }

    #[Route('/', name: "csk_crud_list", methods: [Request::METHOD_GET])]
    public function list(Request $request)
    {
        // TODO
    }

    #[Route('/fetch', name: "csk_crud_fetch", methods: [Request::METHOD_GET])]
    public function fetch(Request $request): Response
    {
        $start = $request->query->get('start', 0);
        $length = $request->query->get('length', 10);
        $page = intval($start / $length) + 1;
        $query = $this->buildQuery($request);
        $pagination = $this->paginator->paginate($query, $page, $length);
        $response = $this->buildResponse($pagination);
        return $this->json([
            "data" => $response,
            "recordsFiltered" => $pagination->getTotalItemCount(),
            "recordsTotal" => $pagination->getTotalItemCount()
        ]);
    }

    #[Route('/create', name: "csk_crud_create", methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function create(Request $request): Response
    {
        $instance = new $this->entityConfiguration->fqdn;
        return $this->buildAndHandleForm($request, $instance);
    }

    #[Route('/edit/{id}', name: "csk_crud_edit", methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function edit(mixed $id, Request $request): Response
    {
        $instance = $this->crudService->retrieveInstance($this->entityConfiguration->fqdn, $id);
        return $this->buildAndHandleForm($request, $instance);
    }

    #[Route('/delete/{id}', name: "csk_crud_delete", methods: [Request::METHOD_DELETE])]
    public function delete(mixed $id, Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        if (!array_key_exists("token", $payload) || !$payload["token"]){
            throw new BadRequestHttpException("Delete token not present in request.");
        }
        if (!$this->isCsrfTokenValid($this->entityConfiguration->getEncodedFqdn(), $payload["token"])) {
            throw new BadRequestHttpException();
        }
        $instance = $this->crudService->retrieveInstance($this->entityConfiguration->fqdn, $id);
        $this->crudService->removeInstance($instance);
        $this->createFlashNonBlockingAlert([
            'type' => Constants::TYPE_SUCCESS,
            'title' => $this->translator->trans("Done!", domain: "SkeletonBundle"),
            'msg' => $this->translator->trans("The item was successfully deleted.", domain: "SkeletonBundle"),
        ]);
        return $this->json([]);
    }

    public function setEntityConfiguration(CrudEntity $entityConfiguration): static
    {
        $this->entityConfiguration = $entityConfiguration;
        return $this;
    }

    private function buildQuery(Request $request): Query
    {
        $orderColumn = $request->query->get('order_column', 'id');
        $orderDirection = $request->query->get('order_dir', 'desc');
        $entityRepository = $this->em->getRepository($this->entityConfiguration->fqdn);
        if (!$this->entityConfiguration->customFetch) {
            return $entityRepository->createQueryBuilder('e')
                ->orderBy("e.$orderColumn", $orderDirection)
                ->getQuery();

        }
        $filterFormType = $this->entityConfiguration->filterType;
        $filter = [
            'orderColumn' => $orderColumn,
            'orderDirection' => $orderDirection
        ];
        if ($filterFormType) {
            $filterForm = $this->createForm($filterFormType, []);
            $filterForm->submit($request->get($filterForm->getName()));
            $filter = $filterForm->getData();
        }
        $filterCollection = new ArrayCollection($filter);
        return $entityRepository->fetch($filterCollection);
    }

    public function buildResponse(PaginationInterface $pagination): array
    {
        $response = [];
        /**
         * @var int $key
         * @var CrudEntityInterface $item
         */
        foreach ($pagination as $key => $item) {
            $actionButtons = $this->entityConfiguration->displayActionsButtons === true ? [
                $this->renderView($this->entityConfiguration->actionButtonsTemplate, [
                    'record' => $item,
                    'entity' => $this->entityConfiguration->entityIdentifier,
                ])
            ] : [];
            $response [] = array_merge(
                $this->entityConfiguration->displayRowNumber ? [$key + 1] : [],
                $item->renderDataTableRow(new RowRendererArguments(
                    $this->translator,
                    $this->router,
                    $this->twig,
                    $this->authorizationChecker,
                    $this->getUser()
                )),
                $actionButtons
            );
        }
        return $response;
    }

    private function handleFormResponse(Request $request, CrudEntityInterface $instance): Response
    {
        return match ($this->acceptsJsonResponse($request)) {
            true => $this->json([
                'instanceUrl' => $this->crudService->generateInstanceEditUrl($instance, $this->entityConfiguration)
            ]),
            default => $this->render(...$this->crudService->renderForm($this->entityConfiguration, $instance))
        };
    }

    public function acceptsJsonResponse(Request $request): bool
    {
        return in_array('application/json', $request->getAcceptableContentTypes());
    }

    public function handleInvalidFormException(Request $request, InvalidFormException $invalidFormException): Response|JsonResponse
    {
        $response = new Response(status: Response::HTTP_BAD_REQUEST);
        return match ($this->acceptsJsonResponse($request)) {
            true => $this->json([
                'form' => $this->renderView($this->entityConfiguration->formTemplate, [
                    'entityConfig' => $this->entityConfiguration,
                    'form' => $invalidFormException->getForm()->createView()
                ]),
                'type' => Constants::TYPE_ERROR,
                'title' => $this->translator->trans("Warning", domain: "SkeletonBundle"),
                'msg' => $this->translator->trans("We have detected some errors on your form input. Please check the highlighted fields.", domain: "SkeletonBundle"),
            ], Response::HTTP_BAD_REQUEST),
            false => $this->render($this->entityConfiguration->getCreateTemplate(), [
                'entityConfig' => $this->entityConfiguration,
                'form' => $invalidFormException->getForm()->createView()
            ], $response),
        };
    }

    public function createFlashNonBlockingAlert(array $payload): void
    {
        $this->addFlash(Constants::FLASH_NOT_BLOCKING_ALERTS, json_encode($payload));
    }

    public function buildAndHandleForm(Request $request, mixed $instance): Response
    {
        try {
            if ($request->isMethod(Request::METHOD_POST)) {
                $this->crudService->handleFormSubmission($instance, $this->entityConfiguration, $request);
                $this->createFlashNonBlockingAlert([
                    'type' => Constants::TYPE_SUCCESS,
                    'title' => $this->translator->trans("Done!", domain: "SkeletonBundle"),
                    'msg' => $this->translator->trans("The changes to the record were successfully saved.", domain: "SkeletonBundle"),
                ]);
            }
            return $this->handleFormResponse($request, $instance);
        } catch (InvalidFormException $invalidFormException) {
            return $this->handleInvalidFormException($request, $invalidFormException);
        }
    }
}
