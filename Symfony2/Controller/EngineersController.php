<?php

namespace Chartwell\Modules\WorksCalendarBundle\Controller;

use Chartwell\Modules\WorksCalendarBundle\Controller\Core\ModuleController;
use Chartwell\Modules\WorksCalendarBundle\Entity\Engineer;
use Chartwell\Modules\WorksCalendarBundle\Form\Type\EngineerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Chartwell\UsersBundle\Entity\User;

class EngineersController extends ModuleController
{

    use \Chartwell\CoreBundle\Controller\traits\SoftDeleteableTrait;

    public function indexAction($page)
    {

        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_VIEW', $this->module)) {
            throw new AccessDeniedException();
        }

        $engineers = $this->getDoctrine()
            ->getRepository('ChartwellUsersBundle:User')
            ->getEngineers(true)
            ->addOrderBy('u.orderBy', 'ASC')
            ->getQuery()
            ->execute();

        $engineersTemporarily = $this->getDoctrine()
            ->getRepository('ChartwellUsersBundle:User')
            ->getEngineers(true, false)
            ->addOrderBy('u.orderBy', 'ASC')
            ->getQuery()
            ->execute();

        $divisionsRepository = $this->getDoctrine()->getRepository('ChartwellUsersBundle:Division');

        $divisions = $divisionsRepository->findBy(array(), array('id' => 'ASC'));

        /* Breadcrumbs */
        $this->breadcrumbs->addItem('Engineers');

        return $this->render('ChartwellModulesWorksCalendarBundle:Engineers:index.html.twig', array(
            'engineers' => $engineers,
            'engineersTemporarily' => $engineersTemporarily,
            'divisions' => $divisions
        ));

    }


    public function viewAction($id)
    {


        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_VIEW', $this->module)) {
            throw new AccessDeniedException();
        }

        $engineersRepository = $this->getDoctrine()
            ->getRepository('ChartwellUsersBundle:User');

        $engineer = $engineersRepository->findOneBy(array('id' => $id, 'isDeleted' => 0));
        if (!$engineer) {
            throw $this->createNotFoundException();
        }

        if (!$engineer->getToken()) {
            $engineer->generateRandomToken();
            $this->getDoctrine()->getManager()->flush();
        }

        $this->breadcrumbs->addItem(
            'Engineers', $this->generateUrl('chartwell_modules_works_calendar_engineers', array(
            'divisionSlug' => $this->division->getSlug()
        ))
        );
        $this->breadcrumbs->addItem('Engineer #' . $engineer->getId());

        return $this->render('ChartwellModulesWorksCalendarBundle:Engineers:view.html.twig', array(
            'engineer' => $engineer
        ));
    }

    public function newTokenAction($id)
    {

        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_VIEW', $this->module) || false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_MANAGE_ENGINEERS', $this->module)) {
            throw new AccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $engineersRepository = $em->getRepository('ChartwellUsersBundle:User');

        $engineer = $engineersRepository->findOneBy(array('id' => $id, 'isDeleted' => 0));
        if (!$engineer) {
            throw $this->createNotFoundException();
        }

        $engineer->generateRandomToken();
        $em->flush();

        $session = $this->getRequest()->getSession();
        $session->getFlashBag()->add('message', 'New token has been generated.');
        return $this->redirectToRoute('chartwell_modules_works_calendar_engineers', array('divisionSlug' => $this->division->getSlug()));
    }

    public function reorderAction()
    {

        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_VIEW', $this->module) || false === $this->get('security.authorization_checker')->isGranted('ROLE_WORKS_CALENDAR_CAN_MANAGE_ENGINEERS', $this->module)) {
            throw new AccessDeniedException();
        }

        $items = $this->getRequest()->get('data');

        $div = $this->getRequest()->get('division');

        $em = $this->getDoctrine()->getManager();
        $engineersRepository = $em->getRepository('ChartwellUsersBundle:User');
        $divisionsRepository = $em->getRepository('ChartwellUsersBundle:Division');

        $division = $divisionsRepository->findOneById($div);
        if (!$division) {
            throw $this->createNotFoundException();
        }

        foreach ($items as $key => $item) {
            $engineer = $engineersRepository->findOneBy(array('id' => $item['id'], 'isDeleted' => 0));
            if (!$engineer) {
                throw $this->createNotFoundException();
            }
            $engineer->setOrderBy($key);
            $engineer->setWorkCalendarDivision($division);
            $engineer->setIsActiveInCalendar((boolean)$item['active']);
            $em->flush();
        }
        return new Response();
    }

    public function sendPersonalInfoAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $engineersRepository = $em->getRepository('ChartwellUsersBundle:User');

        $engineer = $engineersRepository->findOneById($id);
        if (!$engineer) {
            throw $this->createNotFoundException();
        }

        $session = $this->getRequest()->getSession();
        if (!$engineer->getEmail()) {
            $session->getFlashBag()->add('error', 'This engineer doesn\'t have an email address. Cannot send personal info.');
        } else {
            $this->sendPersonalInfo($engineer);
        }
        return $this->redirect($this->generateUrl('chartwell_modules_works_calendar_engineers_view', array('id' => $id, 'divisionSlug' => $this->division->getSlug())));

    }

    private function sendPersonalInfo(User $engineer)
    {
        $session = $this->getRequest()->getSession();
        $mailer = $this->get('chartwell_modules_works_calendar.email_manager');
        $mailer->sendPersonalInfoToEngineer($engineer);
        $session->getFlashBag()->add('message', 'Engineer ' . ($engineer->getFirstName() . ' ' . $engineer->getLastName()) . ' personal info has been successfully sent!');
    }

}
