<?php

namespace Webffilm\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Template,
    Symfony\Component\Security\Core\SecurityContextInterface,
    Webffilm\BaseBundle\Interfaces\InitControllerInterface,
    Webffilm\UserBundle\Entity\UserParent,
    Webffilm\UserBundle\Entity\UserSchool,
    Webffilm\UserBundle\Entity\UserEducator,
    Webffilm\UserBundle\Entity\User,
    Webffilm\BaseBundle\Entity\Story as StoryObj,
    Symfony\Component\HttpFoundation\JsonResponse,
    Webffilm\UserBundle\Entity\ChildToStory,
    Webffilm\UserBundle\Entity\UserChild,
    Webffilm\UserBundle\Form\Type\Story as StoryType,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\EntityRepository,
    Doctrine\Common\Collections\ArrayCollection,
    Symfony\Bundle\FrameworkBundle\Translation\Translator,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class StoryController extends Controller implements InitControllerInterface {

    private $return = array();
    private $request;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var User
     */
    private $user;

    /**
     *
     * @var EntityRepository
     */
    private $storyRepository;

    /**
     * @var Translator
     */
    private $translator;

    public function init(Request $request, SecurityContextInterface $security_context) {
        $this->request = $request;
        $this->em = $this->getDoctrine()->getManager();
        $this->user = $this->container->get('security.context')->getToken()->getUser();
        $this->storyRepository = $this->em->getRepository('WebffilmBaseBundle:Story');
        $this->translator = $this->container->get('translator');
    }

    /**
     * Parent
     * @Route("/parent/story/{page}", name="user_parent_my_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * Child
     * @Route("/child/story/{page}", name="user_child_my_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * School
     * @Route("/school/story/{page}", name="user_school_my_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * School
     * @Route("/educator/story/{page}", name="user_educator_my_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Template
     */
    public function indexAction(Request $request, $page = 1) {

        $qb = $this->em->createQueryBuilder();

        if ($request->get('_route') === 'user_child_my_stories') {
            $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                    ->createQueryBuilder('s')
                    ->leftJoin('s.childStories', 'cs')
                    ->where($qb->expr()->orX($qb->expr()->eq('s.creator', $this->user->getId()), $qb->expr()->eq('cs.child', $this->user->getId())))
                    ->andWhere($qb->expr()->eq('s.developStatus', 0))
                    ->addOrderBy('s.updated', 'DESC')
                    ->addOrderBy('s.created', 'DESC');
        } elseif ($request->get('_route') === 'user_school_my_stories') {
            $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                    ->createQueryBuilder('s')
                    ->leftJoin('s.creator', 'child')
                    ->leftJoin('child.schoolInvitations', 'i')
                    ->leftJoin('s.creator', 'educator')
                    ->where('child INSTANCE OF Webffilm\UserBundle\Entity\UserChild OR educator INSTANCE OF Webffilm\UserBundle\Entity\UserEducator')
                    ->andwhere($qb->expr()->orX(
                                    $qb->expr()->eq('i.school', $this->user->getId()), $qb->expr()->eq('educator.school', $this->user->getId())
                    ))
                    ->andWhere($qb->expr()->eq('s.developStatus', 0))
                    ->addOrderBy('s.updated', 'DESC')
                    ->addOrderBy('s.created', 'DESC');
        } else {
            $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                    ->createQueryBuilder('s')
                    ->where($qb->expr()->eq('s.creator', $this->user instanceof UserParent || $this->user instanceof UserSchool || $this->user instanceof UserEducator ? $this->user->getId() : $this->user->getParent()->getId()))
                    ->andWhere($qb->expr()->eq('s.developStatus', 0))
                    ->addOrderBy('s.updated', 'DESC')
                    ->addOrderBy('s.created', 'DESC');
        }
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $page, 10);
        $this->return['seo'] = $this->container->get('base.controller.front')->getSeo($this->container->get('request')->get('_route'));
        $this->return['stories'] = $pagination;
        if ($request->get('_route') === 'user_child_my_stories') {
            return $this->render('WebffilmUserBundle:Story:index_child.html.twig', $this->return);
        } elseif ($request->get('_route') === 'user_school_my_stories') {
            return $this->render('WebffilmUserBundle:Story:index_school.html.twig', $this->return);
        } elseif ($request->get('_route') === 'user_educator_my_stories') {
            return $this->render('WebffilmUserBundle:Story:index_educator.html.twig', $this->return);
        } else {

            return $this->return;
        }
    }

    /**
     * @Route("/parent/story/drafts/{page}", name="user_parent_my_stories_drafts", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Route("/child/story/drafts/{page}", name="user_child_my_stories_drafts", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Route("/school/story/drafts/{page}", name="user_school_my_stories_drafts", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Route("/educator/story/drafts/{page}", name="user_educator_my_stories_drafts", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Template
     */
    public function workerAction(Request $request, $page = 1) {
        $qb = $this->em->createQueryBuilder();
        $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                ->createQueryBuilder('s')
                ->where($qb->expr()->eq('s.creator', $this->user->getId()))
                ->andWhere($qb->expr()->eq('s.publish', 0))
                ->andWhere($qb->expr()->eq('s.developStatus', 1))
                ->addOrderBy('s.updated', 'DESC')
                ->addOrderBy('s.created', 'DESC');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $page, 10);
        $this->return['seo'] = $this->container->get('base.controller.front')->getSeo($this->container->get('request')->get('_route'));
        $this->return['stories'] = $pagination;
        if ($request->get('_route') === 'user_school_my_stories_drafts') {
            return $this->render('WebffilmUserBundle:Story:worker_school.html.twig', $this->return);
        } elseif ($request->get('_route') === 'user_educator_my_stories_drafts') {
            return $this->render('WebffilmUserBundle:Story:worker_educator.html.twig', $this->return);
        } else {
            return $this->render($this->user->hasRole('ROLE_PARENT') ? 'WebffilmUserBundle:Story:worker.html.twig' : 'WebffilmUserBundle:Story:worker_child.html.twig', $this->return);
        }
    }

    /**
     * @Route("/parent/story/favourites/{page}", name="user_parent_my_stories_favourites", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * 
     * @Route("/child/story/favourites/{page}", name="user_child_my_stories_favourites", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * 
     * @Route("/school/story/favourites/{page}", name="user_school_my_stories_favourites", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * 
     * @Route("/educator/story/favourites/{page}", name="user_educator_my_stories_favourites", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * 
     * @Template
     */
    public function favouritesAction(Request $request, $page = 1) {
        $qb = $this->em->createQueryBuilder();
        $query = $this->em->getRepository('WebffilmBaseBundle:StoryFavourite')
                ->createQueryBuilder('f')
                ->where($qb->expr()->eq('f.user', $this->user->getId()))
                ->addOrderBy('f.created', 'DESC');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $page, 10);
        $this->return['favourites'] = $pagination;
        if ($request->get('_route') === 'user_child_my_stories_favourites') {
            return $this->render('WebffilmUserBundle:Story:favourites_child.html.twig', $this->return);
        } elseif ($request->get('_route') === 'user_school_my_stories_favourites') {
            return $this->render('WebffilmUserBundle:Story:favourites_school.html.twig', $this->return);
        } elseif ($request->get('_route') === 'user_educator_my_stories_favourites') {
            return $this->render('WebffilmUserBundle:Story:favourites_educator.html.twig', $this->return);
        } else {
            $this->return['seo'] = $this->container->get('base.controller.front')->getSeo($this->container->get('request')->get('_route'));
            return $this->return;
        }
    }

    /**
     * @Route("/parent/story/add", name="user_parent_my_stories_add")
     * @Template
     */
    public function addAction(Request $request) {
        $story = new StoryObj();
        $form = $this->createForm(new StoryType($this->container), $story);
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                try {
                    $story->setCreator($this->user instanceof UserParent ? $this->user : $this->user->getParent());
                    $story->setLastEditor($this->user instanceof UserParent ? $this->user : $this->user->getParent());
                    $this->em->persist($story);
                    $this->em->flush();
                    $this->get('session')->getFlashBag()->add(
                            'success', $this->get('translator')->trans('successfully.added.new.story')
                    );
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add(
                            'error', $this->get('translator')->trans('failed.added.new.story') . ': ' . $e->getMessage()
                    );
                }
                return $this->redirect(($rf = $request->server->get('HTTP_REFERER')) ? $rf : $this->generateUrl('user_parent_my_stories'));
            }
        }
        $this->return["form"] = $form->createView();
        return $this->return;
    }

    /**
     * @Route("/parent/story/edit/{story}", name="user_parent_my_stories_edit",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/child/story/edit/{story}", name="user_child_my_stories_edit",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/school/story/edit/{story}", name="user_school_my_stories_edit",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/educator/story/edit/{story}", name="user_educator_my_stories_edit",
     *        requirements={"story"="\d+"})
     * @Template
     */
    public function editAction(Request $request, $story) {
        $story = $this->storyRepository->find($story);
        if ($story) {
            if ($this->user instanceof UserParent) {
                $condition = $story->getCreator()->getId() != $this->user->getId();
            } else if ($this->user instanceof UserChild) {
                $condition = $story->getCreator()->getId() != $this->user->getId();
                if ($condition) {
                    $condition = $story->getCreator()->getId() != $this->user->getParent()->getId();
                }
            } else {
                $condition = true;
            }

            if ($condition) {
                throw new \Exception($this->translator->trans('this.is.not.your.story'), 400);
            } else {
                $form = $this->createForm(new StoryType($this->container), $story);
                if ($request->isMethod('POST')) {
                    $form->bind($request);
                    if ($form->isValid()) {
                        try {
                            $this->em->flush();
                            $this->get('session')->getFlashBag()->add(
                                    'success', $this->get('translator')->trans('successfully.update.story')
                            );
                        } catch (\Exception $e) {
                            $this->get('session')->getFlashBag()->add(
                                    'error', $this->get('translator')->trans('failed.update.story') . ': ' . $e->getMessage()
                            );
                        }
                        return $this->redirect(($rf = $request->server->get('HTTP_REFERER')) ? $rf : $this->generateUrl($this->user instanceof UserParent ? 'user_parent_my_stories' : 'user_child_my_stories'));
                    }
                }
                $this->return["form"] = $form->createView();
            }
        } else {
            throw new Exception($this->translator->trans('story.not.found'), 400);
        }
        return $this->return;
    }

    /**
     * @Route("/parent/story/delete/{story}", name="user_parent_my_stories_delete",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/child/story/delete/{story}", name="user_child_my_stories_delete",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/school/story/delete/{story}", name="user_school_my_stories_delete",
     *        requirements={"story"="\d+"})
     * 
     * @Route("/educator/story/delete/{story}", name="user_educator_my_stories_delete",
     *        requirements={"story"="\d+"})
     */
    public function deleteAction(Request $request, $story) {
        $story = $this->storyRepository->find($story);
        if ($story) {
            //if user creator is not owner story, or child parent is not owner this story
            if ($this->user->getId() != $story->getCreator()->getId()) {
                throw new \Exception($this->translator->trans('this.is.not.your.story'), 400);
            } else {
                try {
                    $this->em->remove($story);
                    $this->em->flush();
                    
                    $this->container->get('piwik')->removeStory();
                    
                    $this->get('session')->getFlashBag()->add(
                            'success', $this->get('translator')->trans(
                                    'your.story.was.successfully.removed'));
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add(
                            'error', $this->get('translator')->trans(
                                    'an.error.with.story.remove') . ': ' . $e->getMessage()
                    );
                }
            }
        } else {
            throw new \Exception($this->translator->trans('story.not.found'), 400);
        }
        return $this->redirect(($rf = $request->server->get('HTTP_REFERER')) ? $rf : $this->generateUrl($this->user instanceof UserParent ? 'user_parent_my_stories' : 'user_child_my_stories'));
    }

    /**
     * @Route("/parent/story/status/{type}/{story}", name="user_parent_my_stories_status",     
     *  requirements={"story"="\d+", "type"="(publish|developStatus)" })
     * 
     * @Route("/child/story/status/{type}/{story}", name="user_child_my_stories_status",     
     *  requirements={"story"="\d+", "type"="(publish|developStatus)" })
     * 
     * @Route("/school/story/status/{type}/{story}", name="user_school_my_stories_status",     
     *  requirements={"story"="\d+", "type"="(publish|developStatus)" })
     * 
     * @Route("/educator/story/status/{type}/{story}", name="user_educator_my_stories_status",     
     *  requirements={"story"="\d+", "type"="(publish|developStatus)" })
     */
    public function statusAction(Request $request, $type, $story) {
        $story = $this->storyRepository->find($story);
        if ($story) {
            //if user creator is not owner story, or child parent is not owner this story
            if ($this->user instanceof UserParent ? $story->getCreator()->getId() != $this->user->getId() : $story->getCreator()->getId() != $this->user->getParent()->getId()) {
                throw new \Exception($this->translator->trans('this.is.not.your.story'), 400);
            } else {
                try {
                    $type === 'publish' ? $story->setPublish(!$story->getPublish()) : $story->setDevelopStatus(!$story->getDevelopStatus());
                    $this->em->flush();
                    if ($story->getPublish()) {
                        $this->container->get('base.controller.front')->sendStroyPublishMsgToAdmin($story);
                        if ($this->user instanceof UserParent && $this->user->getConditionSubscription()) {
                            if ($this->user->getConditionSubscription()->getTimestamp() >= time()) {
                                $this->container->get('base.controller.front')->addFreeSubscription($this->user, 7);
                                $this->container->get('session')->set('freeSubscriptionApplying', true);
                            }
                        }
                    }
                    $this->get('session')->getFlashBag()->add(
                            'success', $this->get('translator')->trans(
                                    'your.story.status.your.successfully.changed'));
                } catch (\Exception $e) {
                    $this->get('session')->getFlashBag()->add(
                            'error', $this->get('translator')->trans(
                                    'an.error.with.status.changed') . ': ' . $e->getMessage()
                    );
                }
            }
        } else {
            throw new \Exception($this->translator->trans('story.not.found'), 400);
        }
        return $this->redirect(($rf = $request->server->get('HTTP_REFERER')) ? $rf : $this->generateUrl($this->user instanceof UserParent ? 'user_parent_my_stories' : 'user_child_my_stories'));
    }

    /**
     * @Route("/parent/story/add-to-child", name="user_parent_my_stories_add_to_child")
     * @Route("/school/story/add-to-child", name="user_school_my_stories_add_to_child")
     * @Route("/educator/story/add-to-child", name="user_educator_my_stories_add_to_child")
     */
    public function addChildAction(Request $request) {
        if ($request->isXmlHttpRequest()) {
            /* @var $story Story */
            $story = $this->storyRepository->find($request->get('story'));
            if ($story && $request->get('story') && $request->get('child')) {
                try {
                    /* @var $child UserChild */
                    $child = $this->em->getRepository('WebffilmUserBundle:UserChild')->find($request->get('child'));
                    if ($child) {
                        if ($child->getParent()->getId() == $this->user->getId()) {
                            $issetJustForChild = $child->getChildStories()->filter(function($entry) use ($child) {
                                if ($entry->getChild()->getId() == $child->getId()) {
                                    return true;
                                } else {
                                    return false;
                                }
                            });
                            if ($issetJustForChild instanceof ArrayCollection && $issetJustForChild->count() > 0) {
                                $action = 'remove';
                                foreach ($issetJustForChild as $o) {
                                    $this->em->remove($o);
                                }
                                $this->em->flush();
                                return new JsonResponse(array('success' => true, 'action' => $action, 'msg' => $this->translator->trans('successfully.removed.story.from.your.child.%child%.stories', array('%child%' => $child->getUsername()))));
                            } else {
                                $action = 'add';
                                $childToStory = new ChildToStory();
                                $childToStory->setChild($child);
                                $childToStory->setStory($story);
                                $this->em->persist($childToStory);
                                $this->em->flush();
                                return new JsonResponse(array('success' => true, 'storyUsers' => $story->getChildStories()->count(), 'action' => $action, 'msg' => $this->translator->trans('successfully.added.story.to.your.child.%child%.stories', array('%child%' => $child->getUsername()))));
                            }
                        } else {
                            return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('this.is.not.your.child')));
                        }
                    } else {
                        return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('cant.find.this.child')));
                    }
                } catch (\Exception $e) {
                    return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('an.error.occured') . ': ' . $e->getMessage()));
                }
            } else {
                return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('it.story.is.not.exist!')));
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.method!'));
        }
    }

    /**
     * @Route("/parent/story/share", name="user_parent_my_stories_share")
     * @Route("/school/story/share", name="user_school_my_stories_share")
     * @Route("/educator/story/share", name="user_educator_my_stories_share")
     */
    public function shareAction(Request $request) {
        if ($request->isXmlHttpRequest()) {
            /* @var $story Story */
            $story = $this->storyRepository->find($request->get('story'));
            if ($story && $request->get('story') && $request->get('child')) {
                $html = $this->renderView('WebffilmUserBundle:Story:share.ajax.html.twig', $this->return);
                return new \Symfony\Component\HttpFoundation\JsonResponse(array('success' => true, 'html' => $html));
            } else {
                return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('it.story.is.not.exist!')));
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
        }
    }

    /**
     * @Route("/story/creator", name="user_story_generator")
     * @Route("/story/creator/{scenario}", name="user_story_generator_with_scenario", 
     *  requirements={"scenario"="\d+"})
     * @Route("/story/creator/task/{task}", name="user_story_generator_with_task", 
     *  requirements={"task"="\d+"})
     * @Route("/story/creator/sample/{sample}", name="user_story_generator_with_sample", 
     *  requirements={"sample"="\d*"},
     * defaults={"sample"="0"})
     * @Template
     */
    public function creatorAction(Request $request, $scenario = null) {
        $this->return['seo'] = $this->container->get('base.controller.front')->getSeo($this->container->get('request')->get('_route'));
        if ($request->attributes->get('_route') === 'user_story_generator') { //without scenario
            return $this->return;
        } else { //with scenario
            $this->return['scenarioId'] = $scenario;
        }
        if (($task = $request->get('task', false))) {
            $this->return['task'] = $task;
            $project = $this->em->getRepository('WebffilmUserBundle:SchoolProjects')->find($task);
            $projectGuidelines = array(
                'scenesMin' => $project->getScenesMin(), //min required scenes count
                'scenesMax' => $project->getScenesMax(), //max scenes count
                'minutesMin' => $project->getMinutesMin(), //min story time in minutes
                'minutesMax' => $project->getMinutesMax(), //max story time in minutes
                'sound' => $project->getSound() // if sound in this story ?
            );
            $this->return['projectGuidelines'] = $projectGuidelines;
        }

        if ($request->get('sample', false)) {
            $this->return['sample'] = $request->get('sample');
        }

        $this->return['lang'] = $this->getRequest()->getLocale();

        $this->saveAsProjectUrl();
        return $this->return;
    }

    /**
     * @Route("/story/load/{id}", name="user_story_generator_load_save", requirements={"id"="\d+"})
     * @Template
     */
    public function creatorLoadSaveAction(Request $request, $id) {
        $this->return['toonId'] = $id;
        $this->saveAsProjectUrl();
        return $this->return;
    }

    /**
     * @Route("/story/save", name="user_story_save")
     * @Template
     */
    public function saveAction(Request $request) {
        if ($request->isXmlHttpRequest() && ($request->isMethod('POST'))) {
            // TODO: sprawdzac czy moze edytowac - autor, rodzic, osoba upowazniona?
            $save_data = $request->get("data");
            if ((int) $save_data['id'] == 0) {
                try {
                    // add row
                    $story = new StoryObj();
                    $story->setCreator($this->user);
                    $story->setLastEditor($this->user);


                    if ($save_data['title'] != "") {
                        $story->setName($save_data['title']);
                        $story->setDevelopStatus(0);
                    } else {
                        $dt = new \DateTime();
                        $story->setName($dt->format('Y-m-d H:i'));
                        $story->setDevelopStatus(1);
                    }

                    $story->setStory($save_data["movie"]);
                    $story->setSave($save_data["save"]);
                    $story->setAudio($save_data["audio"]);
                    if (isset($save_data["audio"]) && $save_data["audio"] != '{}') {
                        $story->setIsAudio(true);
                    } else {
                        $story->setIsAudio(false);
                    }
                    $story->setForAge((int) $save_data["age"]);

                    # set school task story if task exist
                    if (isset($save_data['task']) && @$save_data['task']) {
                        $task = $this->em->getRepository('WebffilmUserBundle:SchoolProjects')->find((int) $save_data['task']);
                        if ($task) {
                            if (method_exists($story, 'setProject')) {
                                $story->setProject($task);
                            }
                        }
                    }

                    # set school sample story if sample exist
                    if (isset($save_data['sample']) && @$save_data['sample']) {
                        $task = $this->em->getRepository('WebffilmUserBundle:SchoolProjects')->find((int) $save_data['sample']);
                        if ($task) {
                            if (method_exists($story, 'setSampleProject')) {
                                $story->setSampleProject($task);
                            }
                        }
                    }

                    $this->em->persist($story);
                    $this->em->flush();

                    $dir = "" . $story->getId();
                    $path = __DIR__ . '/../../../../web/assets/thumbs/';
                    if (!file_exists($path . $dir)) {
                        mkdir($path . $dir, 0777, true);
                    }

                    $img = $save_data['thumb'];
                    $img = str_replace('data:image/jpeg;base64,', '', $img);
                    $img = str_replace(' ', '+', $img);
                    $img_data = base64_decode($img);
                    $file = $path . $dir . "/" . $story->getId() . '.jpg';
                    file_put_contents($file, $img_data);
                    //info about free 7 days subscription if created story and story was published
                    if ($this->user instanceof UserParent && $this->user->getConditionSubscription()) {
                        if ($this->user->getConditionSubscription()->getTimestamp() >= time()) {
                            $this->container->get('session')->set('freeConditionSubscription', true);
                        }
                    }
                    
                    # piwik save story stats - success
                    $this->container->get('piwik')->savedStory(true);

                    return new JsonResponse(array('success' => true, 'status' => 'create', 'saveId' => $story->getId()));
                } catch (\Exception $e) {
                    # piwik save story stats - fail
                    $this->container->get('piwik')->savedStory(false);
                    
                    return new JsonResponse(array('success' => false, 'msg' => $e->getMessage()));
                }
            } else {
                //edit row
                $loggedUser = $this->user; //logged user
                /**
                 * Function to check user persmissions to story
                 */
                $checkPermissions = function(StoryObj $story) use ($loggedUser) {
                    if ($story->getCreator()->getId() === $loggedUser->getId()) { //if user has "ROLE_PARENT" and it's his record
                        return true;
                    } else {
                        //if user has ROLE_CHILD we must test all child with permission to this story
                        return $permission = $story->getChildStories()->filter(function(ChildToStory $entry) use ($loggedUser) {
                                    if ($entry->getChild()->getId() === $loggedUser->getId() && $loggedUser->hasRole('ROLE_CHILD_EDIT')) {
                                        return true;
                                    }
                                })->count() > 0;
                    }
                    return false;
                };
                $story = $this->storyRepository->find((int) $save_data['id']);
                if ($story) { //if story exist
                    $story->setLastEditor($this->user);
                    if ($checkPermissions($story)) { //check if user has permissions to update this story
                        $dt = new \DateTime();
                        if ($save_data['title'] != "") {
                            $story->setName($save_data['title']);
                        }

                        if ($save_data['title'] != "") {
                            if (strtotime($save_data['title'])) {
                                $story->setDevelopStatus(1);
                            } else {
                                $story->setDevelopStatus(0);
                            }
                        }

                        $story->setStory($save_data["movie"]);
                        $story->setSave($save_data["save"]);
                        $story->setAudio($save_data["audio"]);
                        if (isset($save_data["audio"]) && $save_data["audio"] != '{}') {
                            $story->setIsAudio(true);
                        } else {
                            $story->setIsAudio(false);
                        }
                        $story->setTechStatusA(0);
                        $story->setTechStatusV(0);
                        $this->em->persist($story);
                        $this->em->flush();

                        $dir = "" . $story->getId();
                        $path = __DIR__ . '/../../../../web/assets/thumbs/';
                        if (!file_exists($path . $dir)) {
                            mkdir($path . $dir, 0777, true);
                        }
                        chmod($path . $dir, 0777);
                        $img = $save_data['thumb'];
                        $img = str_replace('data:image/jpeg;base64,', '', $img);
                        $img = str_replace(' ', '+', $img);
                        $img_data = base64_decode($img);
                        $file = $path . $dir . "/" . $story->getId() . '.jpg';
                        if (file_exists($file)) {
                            chmod($file, 0777);
                            unlink($file);
                        }
                        file_put_contents($file, $img_data);

                        return new JsonResponse(array('success' => true, 'status' => 'edit', 'saveId' => $story->getId()));
                    } else { //if hasn't got permissions return error
                        new JsonResponse(array('success' => false, 'error' => $this->translator->trans('you.havent.got.permissions.to.this.action')));
                    }
                } else { //if no exists return json with error message
                    new JsonResponse(array('success' => false, 'error' => $this->translator->trans('this.story.no.exist')));
                }
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
        }
    }

    /**
     * @Route("/story/play/{id}", name="user_show_story", requirements={"id"="\d+"})
     * @Template
     * 
     */
    public function playStoryAction(Request $request, $id) {
        return array("toonId" => $id);
    }

    /**
     * @Route("/story/play-ajax/{id}", name="user_show_story_ajax", requirements={"id"="\d+"})
     * @Template
     * 
     */
    public function playStoryAjaxAction(Request $request, $id) {

        //if ($request->isXmlHttpRequest()) {
        /* @var $story Webffilm\BaseBundle\Entity\Story */
        $story = $this->storyRepository->find($id);
        if ($story) {
            //if ($story->getPublish() || $story->getCreator()->getId() === $this->user->getId()) { //if story is published we can play this
            return new JsonResponse(array('success' => true, "toon" => array("movieDetails" => $story->getSave(), "movieData" => $story->getStory())));
            //} else { //if cant play story
            //    return new JsonResponse(array('success' => false, 'error' => $this->translator->trans('this.story.is.not.published.and.cant.play.it')));
            //}
        } else {
            return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('it.story.is.not.exist!')));
        }
//        } else {
//            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
//        }
    }

    /**
     * @Route("/story/load-save", name="user_load_save_ajax")
     * @Template
     */
    public function loadSaveAjaxAction(Request $request) {

        if ($request->isXmlHttpRequest()) {
            // check auth !
            $id = (int) $request->request->get('save');
            $story = $this->storyRepository->find($id);
            if ($story) {
                $loggedUser = $this->user; //logged user
                /**
                 * Function to check user persmissions to story
                 */
                $checkPermissions = function(StoryObj $story) use ($loggedUser) {
                    if ($story->getCreator()->getId() === $loggedUser->getId()) { //if user has "ROLE_PARENT" and it's his record
                        return true;
                    } else {
                        //if user has ROLE_CHILD we must test all child with permission to this story
                        return $permission = $story->getChildStories()->filter(function(ChildToStory $entry) use ($loggedUser) {
                                    if ($entry->getChild()->getId() === $loggedUser->getId() && $loggedUser->hasRole('ROLE_CHILD_EDIT')) {
                                        return true;
                                    }
                                })->count() > 0;
                    }
                    return false;
                };
                if ($checkPermissions($story)) {

                    return new JsonResponse(array('success' => true, "toon" => array("movieDetails" => $story->getSave(), "movieData" => $story->getStory())));
                } else {
                    return new JsonResponse(array('success' => false, 'error' => 'you.havent.got.permissions.to.this.action'));
                }
            } else {
                return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('it.story.is.not.exist!')));
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
        }
    }

    /**
     * @Route("/story/save/audio/wav", name="story_save_audio_wav")
     * 
     */
    public function saveAudioWavAction(Request $request) {
        if ($request->isXmlHttpRequest()) {
            $path = __DIR__ . '/../../../../web/assets/audio/';
            $filename = time() . "_" . substr(md5(uniqid(rand(), true)), 0, 6) . ".wav";
            if (move_uploaded_file($_FILES['recordWAV']['tmp_name'], $path . $filename)) {
                return new JsonResponse(array("error" => false, "name" => $filename));
            } else {
                return new JsonResponse(array("error" => true));
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
        }
    }

    /**
     * @Route("/story/save/audio/flash/wav", name="story_save_audio_flash_wav")
     * 
     */
    public function saveFlashAudioWavAction(Request $request) {
        if (isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
            $path = __DIR__ . '/../../../../web/assets/audio/';
            $filename = time() . "_" . substr(md5(uniqid(rand(), true)), 0, 6) . ".wav";
            $fp = fopen($path . $filename, "wb");
            fwrite($fp, $GLOBALS['HTTP_RAW_POST_DATA']);
            fclose($fp);
            return new Response($filename);
        } else {
            return new Response("No HTTP_RAW_POST_DATA");
        }
    }

    /*     * *******************  RENDER VIDEO ******************** */

    /**
     * @Route("/story/render/{id}", name="render_story", requirements={"id"="\d+"})
     * @Template
     * 
     */
    public function renderStoryAction(Request $request, $id) {
        return array("toonId" => $id);
    }

    /**
     * @Route("/story/render/ajax/{id}", name="render_story_ajax", requirements={"id"="\d+"})
     * @Template
     * 
     */
    public function renderStoryAjaxAction(Request $request, $id) {

        if ($request->isXmlHttpRequest()) {
            /* @var $story Webffilm\BaseBundle\Entity\Story */
            $story = $this->storyRepository->find($id);
            if ($story) {
                return new JsonResponse(array('success' => true, "toon" => array("movieDetails" => $story->getSave(), "movieData" => $story->getStory())));
            } else {
                return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('it.story.is.not.exist!')));
            }
        } else {
            exit($this->translator->trans('it.is.no.POST.AJAX.method!'));
        }
    }

    /**
     * @Route("/parent/start", name="user_parent_my_stories_start")
     * @Route("/child/start", name="user_child_my_stories_start")
     * @Route("/school/start", name="user_school_my_stories_start")
     * @Route("/educator/start", name="user_educator_my_stories_start")
     * @Template
     */
    public function startAction(Request $request) {
        if ($this->user->hasRole('ROLE_SCHOOL')) {
            return $this->redirect($this->generateUrl('user_school_my_users'));
        }
        $this->return['seo'] = $this->get('base.controller.front')->getSeo($this->request->get('_route'));
        return $this->return;
    }

    private function saveAsProjectUrl() {
        # saveAsProjectUrl is url for account type to redirect after "save as project"
        $pre = 'http://' . $_SERVER['HTTP_HOST'];
        if ($this->user->hasRole('ROLE_PARENT')) {
            $this->return['saveAsProjectUrl'] = $pre . $this->container->get('router')->generate('user_parent_my_stories_drafts');
        } elseif ($this->user->hasRole('ROLE_CHILD')) {
            $this->return['saveAsProjectUrl'] = $pre . $this->container->get('router')->generate('user_child_my_stories_drafts');
        } elseif ($this->user->hasRole('ROLE_SCHOOL')) {
            $this->return['saveAsProjectUrl'] = $pre . $this->container->get('router')->generate('user_school_my_stories_drafts');
        } elseif ($this->user->hasRole('ROLE_EDUCATOR')) {
            $this->return['saveAsProjectUrl'] = $pre . $this->container->get('router')->generate('user_educator_my_stories_drafts');
        }
    }

    /**
     * @Route("/educator/sample/stories/{page}", name="user_educator_my_sample_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Route("/school/sample/stories/{page}", name="user_school_my_sample_stories", 
     *  requirements={"page"="\d*"},
     * defaults={"page"="1"})
     * @Template
     */
    public function sampleStoriesAction(Request $request, $page = 1) {

        $qb = $this->em->createQueryBuilder();

        if ($request->get('_route') === 'user_educator_my_sample_stories') {
            $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                    ->createQueryBuilder('s')
                    //->where($qb->expr()->orX($qb->expr()->eq('s.creator', $this->user->getId())))
                    ->andWhere($qb->expr()->eq('s.developStatus', 0))
                    ->addOrderBy('s.updated', 'DESC')
                    ->addOrderBy('s.created', 'DESC');
        } else {
            $inQuery = $this->em->createQuery('SELECT e.id FROM WebffilmUserBundle:UserEducator e LEFT JOIN e.school sch WHERE sch.id = ' . $this->user->getId());
            $query = $this->em->getRepository('WebffilmBaseBundle:Story')
                    ->createQueryBuilder('s')
                    //->where($qb->expr()->in('s.creator', $inQuery))
                    ->andWhere($qb->expr()->eq('s.developStatus', 0))
                    ->addOrderBy('s.updated', 'DESC')
                    ->addOrderBy('s.created', 'DESC');
        }
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $page, 10);
        $this->return['seo'] = $this->container->get('base.controller.front')->getSeo($this->container->get('request')->get('_route'));
        $this->return['stories'] = $pagination;
        return $this->return;
    }

    /**
     * @Route("/story/html/{story}", name="user_story_load_html", 
     *  requirements={"story"="\d+"})
     * @Method({"POST"})
     */
    public function loadStoryHtmlAction(Request $request, $story) {
        if ($request->isXmlHttpRequest()) {
            $story = $this->em->getRepository('WebffilmBaseBundle:Story')->find($story);
            if (!$story) {
                return new JsonResponse(array('success' => false, 'msg' => $this->translator->trans('h1.story.is.not.found')));
            } else {
                $this->return['story'] = $story;
                $html = $this->container->get('templating')->render('WebffilmUserBundle:Story:storyHtml.html.twig', $this->return);
                return new JsonResponse(array('success' => true, 'html' => $html));
            }
        } else {
            exit('Only ajax requests are accepted');
        }
    }

}
