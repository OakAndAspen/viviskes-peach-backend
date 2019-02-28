<?php

namespace App\EventSubscriber;

use App\Controller\TokenAuthenticatedController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class TokenSubscriber implements EventSubscriberInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) return null;

        if ($controller[0] instanceof TokenAuthenticatedController) {
            $token = null;
            $user = null;

            $authHeader = $event->getRequest()->headers->get('authorization');
            if ($authHeader) {
                list($jwt) = sscanf($authHeader, 'Bearer %s');
                if ($jwt) {
                    try {
                        $secretKey = $_ENV['JWT_SECRET'];
                        $token = JWT::decode($jwt, $secretKey, array('HS512'));

                    } catch (\Exception $e) {
                        $token = null;
                    }
                }
            }

            if ($token) {
                $user = $this->em->getRepository(User::class)->find(
                    $token->data->userId
                );
                if ($user) $event->getRequest()->attributes->set('user', $user);
            }

            if (!$token) throw new AccessDeniedHttpException('This action needs a valid token!');
            if (!$user) throw new AccessDeniedHttpException('User was not found.');
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}