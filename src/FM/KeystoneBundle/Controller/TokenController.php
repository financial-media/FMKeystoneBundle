<?php

namespace FM\KeystoneBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;

use Symfony\Component\HttpFoundation\Response;

use FM\KeystoneBundle\Entity\Token;
use FM\KeystoneBundle\Entity\User;

class TokenController extends Controller
{
    public function tokensAction()
    {
        $security = $this->get('security.context');

        $token = null;


        if (!$security->getToken() || !(($user = $security->getToken()->getUser()) && $security->isGranted('ROLE_USER'))) {
        	return new Response('Unauthorized', 401);
        }

        $manager = $this->getTokenManager();
        $token = $manager->createToken($user, 3600);

        if (!$token) {
            return new Response('Error obtaining token', 500);
        }

        $data = array(
            'access' => array(
                'token' => $this->getTokenData($token),
                'serviceCatalog' => $this->getServiceCatalogs($token),
                'user' => $this->getUserData($user)
            )
        );

        $response = new Response();
        $response->setContent(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Vary', 'X-Auth-Token');

        return $response;
    }

    /**
     * @return FM\KeystoneBundle\Entity\TokenManager
     */
    protected function getTokenManager()
    {
        return $this->get('fm_keystone.token_manager');
    }

    protected function getTokenData(Token $token)
    {
        return array(
            'expires' => gmdate('Y-m-d\TH:i:s\Z', $token->getExpiresAt()->getTimestamp()),
            'id' => $token->getId()
        );
    }

    protected function getServiceCatalogs(Token $token)
    {
        return array(
            array(
                'endpoints' => $this->getEndpoints($token),
                'type' => 'object-store',
                'name' => 'norris'
            )
        );
    }

    protected function getEndpoints(Token $token)
    {
        return array(
            array(
                'adminURL' => $this->getContainer()->getParameter('admin_url'),
                'publicURL' => $this->getContainer()->getParameter('public_url'),
            )
        );
    }

    protected function getUserData(User $user)
    {
        return array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'name' => (string) $user,
            'roles' => array()
        );
    }

    protected function getContainer()
    {
        return $this->container;
    }
}