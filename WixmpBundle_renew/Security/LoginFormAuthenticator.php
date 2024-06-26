<?php

namespace Webkul\Modules\Wix\WixmpBundle\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use App\Events\UserEvent;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $router;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $platform;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->dispatcher = $dispatcher;
    }

    public function supports(Request $request)
    {
        /*
         * supports() is called on every request :  check for login url
         */
        $this->platform = $request->get('platform');
        $loginURL = '';
        if (!empty($this->platform)) {
            $loginURL .= $this->platform.'_';
        }
        $loginURL .= 'administrator_login';
        
        return $request->attributes->get('_route') === $loginURL && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
            // 'csrf_token' => $request->request->get('_csrf_token'),
            'platform' => $request->get('platform'),
        ];
        
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        // if (!$this->csrfTokenManager->isTokenValid($token)) {
        //     throw new InvalidCsrfTokenException();
        // }
            
        $user = $this->entityManager->getRepository(User::class)->getUserForLogin(['username' => $credentials['username'], 'platform' => $credentials['platform']]);
        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Invalid Username or Password');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->triggerEvent($token);
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        } else {
            // $dashboardPath = '';
            // if (!empty($request->get('platform'))) {
            //     $dashboardPath .= $request->get('platform').'_';
            // }
            // $dashboardPath .= 'administrator_dashboard';

            return new RedirectResponse('dashboard');
        }

        // For example : return new RedirectResponse($this->router->generate('some_route'));
        //throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl()
    {
        $platform = '';
        if (!empty($this->platform)) {
            $platform = $this->platform.'_';
        }

        return $this->router->generate($platform.'administrator_login', ['platform' => $this->platform]);
    }

    /**
     * dispatch event on success auth.
     */
    protected function triggerEvent($token)
    {
        $event = new UserEvent(null, $token->getUser());
        $this->dispatcher->dispatch(
            $event, UserEvent::WIX_SELLER_LOGIN
        );
    }
}
