<?php

namespace CollegeFootball\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use CollegeFootball\AppBundle\Entity\Person;
use CollegeFootball\AppBundle\Form\Type\PersonType;

/**
 * Security Controller
 */
class SecurityController extends Controller
{
    /**
     * @Route("/login", name="collegefootball_security_login")
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('CollegeFootballAppBundle:Security:login.html.twig', [
            'username' => $lastUsername,
            'error'    => $error,
        ]);
    }

    /**
     * @Route("/create-account", name="collegefootball_security_create")
     */
    public function createAction(Request $request)
    {
        $person = new Person();

        $username = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10/strlen($x)) )),1,10);
        $person->setUsername($username);

        $form = $this->createForm(PersonType::class, $person, [
            'create' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $passwordEncoder = $this->get('security.password_encoder');
            $password        = $passwordEncoder->encodePassword($person, $person->getPassword());
            $person->setPassword($password);

            $username = $person->getEmail();
            $username = explode('@', $username);
            $person->setUsername($username[0]);

            $em = $this->getDoctrine()->getManager();
            $em->persist($person);
            $em->flush();

            $token = new UsernamePasswordToken($person, null, "secured_area", $person->getRoles());
            $this->get("security.token_storage")->setToken($token); //now the user is logged in

            //now dispatch the login event
            $event   = new InteractiveLoginEvent($request, $token);
            $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

            return $this->redirectToRoute('collegefootball_app_index');
        }

        return $this->render('CollegeFootballAppBundle:Security:create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
