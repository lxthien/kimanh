<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Redirect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RedirectSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            // Listen early in the request cycle, before routing
            KernelEvents::REQUEST => ['onKernelRequest', 32],
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo(); // e.g. "/old-url"

        // Ignore admin area requests to prevent lockouts
        if (strpos($path, '/admin') === 0) {
            return;
        }

        $pathClean = '/' . ltrim($path, '/');
        $pathNoSlash = ltrim($path, '/');

        // Look for matching redirect in the database
        $redirect = $this->em->getRepository(Redirect::class)
            ->createQueryBuilder('r')
            ->where('r.enable = :enable')
            ->andWhere('r.oldUrl = :path OR r.oldUrl = :pathClean OR r.oldUrl = :pathNoSlash')
            ->setParameter('enable', true)
            ->setParameter('path', $path)
            ->setParameter('pathClean', $pathClean)
            ->setParameter('pathNoSlash', $pathNoSlash)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($redirect) {
            $newUrl = $redirect->getNewUrl();
            $statusCode = $redirect->getStatusCode() ?: 301;

            // Handle external links (http://, https://, or starting with www.)
            if (preg_match('/^(https?:\/\/|www\.)/i', $newUrl)) {
                if (stripos($newUrl, 'www.') === 0) {
                    $newUrl = 'http://' . $newUrl;
                }
            } else {
                // Ensure local relative URLs have a leading slash
                $newUrl = '/' . ltrim($newUrl, '/');
                
                // Preserve query parameters if any
                $queryString = $request->getQueryString();
                if ($queryString) {
                    $newUrl .= '?' . $queryString;
                }
            }

            // Increment hits and last accessed time asynchronously/efficiently in database
            try {
                $this->em->createQuery('UPDATE AppBundle\Entity\Redirect r SET r.hits = r.hits + 1, r.lastAccessedAt = :now WHERE r.id = :id')
                    ->setParameter('now', new \DateTime())
                    ->setParameter('id', $redirect->getId())
                    ->execute();
            } catch (\Exception $e) {
                // Fail silently to ensure redirection still happens even if DB update fails
            }

            // Perform Redirect with dynamic status code
            $response = new RedirectResponse($newUrl, $statusCode);
            $event->setResponse($response);
        }
    }
}
