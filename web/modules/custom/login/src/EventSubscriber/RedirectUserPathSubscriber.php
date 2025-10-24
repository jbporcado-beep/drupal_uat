<?php

namespace Drupal\login\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Session\AccountProxyInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class RedirectUserPathSubscriber implements EventSubscriberInterface {
    private UrlGeneratorInterface $urlGenerator;
    private AccountProxyInterface $currentUser;

    public function __construct(UrlGeneratorInterface $urlGenerator, AccountProxyInterface $currentUser) {
        $this->urlGenerator = $urlGenerator;
        $this->currentUser = $currentUser;
    }

    public function onKernelRequest(RequestEvent $event) {
        // Only process master requests so that redirect happens only once per request
        $isAdmin = $this->currentUser->hasRole('administrator');
        if (!$event->isMainRequest()) {
            return;
        }
        if ($isAdmin) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $is_logout_path = preg_match('/^\/user\/logout/', $path);

        if (preg_match('/^\/user\/[^\/]+/', $path) && !$isAdmin && !$is_logout_path) {
            if ($this->currentUser->isAuthenticated()) {
                $routeName = 'cooperative.dashboard';

                if ($this->currentUser->hasRole('mass_specc_admin')) {
                    $routeName = 'massspecc.home';
                }

                $url = $this->urlGenerator->generate($routeName);
                $response = new RedirectResponse($url, 302);
                $event->setResponse($response);
                return;
            }
        }
        return;
    }

    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = ['onKernelRequest', 100];
        return $events;
    }
}