<?php

namespace Codyas\SkeletonBundle\Controller;

use App\Entity\User;
use Codyas\SkeletonBundle\Event\UserPasswordChangedEvent;
use Codyas\SkeletonBundle\Exception\ConfigurationException;
use Codyas\SkeletonBundle\Form\UserChangePasswordType;
use Codyas\SkeletonBundle\Service\CrudService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserManagerController extends AbstractController
{

    public function __construct(
        private CrudService                       $crudService,
        private Security                          $security,
        private TranslatorInterface               $translator,
        private EntityManagerInterface            $em,
        private UserPasswordHasherInterface       $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    #[Route('/', name: "csk_user_manager")]
    public function userManager(Request $request): Response
    {
        return $this->render(...$this->crudService->renderListFromFqdn($this->getUserProvider()));
    }

    #[Route('/new', name: "csk_user_create", methods: [Request::METHOD_GET])]
    public function create(Request $request): Response
    {
        $fqdn = $this->getUserProvider();
        return $this->render(...$this->crudService->renderForm(instance: new $fqdn));
    }

    #[Route('/edit/{id}', name: "csk_user_edit", methods: [Request::METHOD_GET])]
    public function edit(User $user, Request $request): Response
    {
        return $this->render(...$this->crudService->renderForm(instance: $user));
    }

    #[Route('/edit/{id}/change-password', name: "csk_user_change_password", methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function changePassword(User $user, Request $request): Response
    {
        $form = $this->createForm(UserChangePasswordType::class, $user, [
            'action' => $this->generateUrl('csk_user_change_password', ['id' => $user->getId()]),
            'method' => Request::METHOD_POST
        ]);
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                $this->em->persist($user);
                $this->em->flush();
                $this->eventDispatcher->dispatch(new UserPasswordChangedEvent($user));
                return $this->json([
                    "title" => $this->translator->trans("Password changed"),
                    "msg" => $this->translator->trans("The user password was successfully changed."),
                ]);
            }
        }
        return $this->json([
            'view' => $this->renderView('@Skeleton/crud/users/form_change_password.html.twig', [
                'form' => $form->createView()
            ]),
            'title' => $this->translator->trans("Change %identifier%'s password", ["%identifier%" => $user->__toString()])
        ], $request->isMethod(Request::METHOD_POST) ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
    }

    public function getUserProvider(): string
    {
        $config = $this->getParameter('skeleton');
        if (!isset($config['security']['user_provider'])) {
            throw new ConfigurationException("Configuration parameter \"skeleton.security.user_provider\" is not set.");
        }
        return $config['security']['user_provider'];
    }
}
