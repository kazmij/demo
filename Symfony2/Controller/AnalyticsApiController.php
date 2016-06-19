<?php

namespace Bally\AdminBundle\Controller;

use Bally\AdminBundle\Controller\MainController,
    Symfony\Component\HttpFoundation\Request,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Security,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpFoundation\JsonResponse,
    Bally\EntityBundle\Entity\App,
    Bally\EntityBundle\Entity\Statistics,
    Bally\EntityBundle\Entity\StatisticsAssets,
    Bally\EntityBundle\Entity\StatisticsDownloads,
    Bally\EntityBundle\Entity\StatisticsLocations,
    Bally\EntityBundle\Entity\StatisticsShares,
    Bally\EntityBundle\Entity\StatisticsTime,
    Bally\EntityBundle\Entity\Country,
    Bally\EntityBundle\Entity\City;

/**
 * @author Arcyro <arek@arcyro.pl>
 * @version v1
 */
class AnalyticsApiController extends MainController {

    /**
     * Json data from request as array
     * @var array
     */
    private $data = array();

    /**
     * Current app object
     * @var App
     */
    private $app;

    /**
     * Action type
     * @var integer
     */
    private $type;

    /**
     * Unique session id
     * @var string
     */
    private $sessionId;

    /**
     * Statistics object
     * @var Statistics
     */
    private $statistics;

    /**
     * Api version
     * @var string
     */
    private $version;

    /**
     * Allowed actions array
     * @var array
     */
    private $actions = array(
        1 => 'downloadAppAction',
        2 => 'downloadAssetsAction',
        3 => 'shareAction',
        4 => 'chooseAssetsAction',
        5 => 'startSessionAction',
        6 => 'endSessionAction'
    );

    /**
     * Method is used before any other actions in this controller
     */
    public function _init() {
        parent::_init();
        # Set access to mobile app for this
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400'); // cache for 1 day
        }
        try {
            if ($this->request->isMethod('POST')) {
                $this->version = $this->request->get('version', 1);
                $this->sessionId = $this->request->get('unique', null);
                $this->type = $this->request->get('type', null);
                $appId = $this->request->get('app_id', null);
                $data = $this->request->get('data', null);
                # If required arg no exist return json with error
                if (!($this->sessionId && $this->type && $appId)) {
                    $response = new JsonResponse(array('success' => false, 'msg' => 'Once of required arguments is null, valid structure is (String unique, String type, String app_id, Object data = null)'));
                    $response->send();
                    exit;
                }
                # if data json is not null then create array from this
                if ($data) {
                    $this->data = json_decode($data, true);
                }

                # App object
                $this->app = $this->em->getRepository('BallyEntityBundle:App')->find($appId);

                # If can't find app by id, we must break because it's required value
                if (!$this->app) {
                    $response = new JsonResponse(array('success' => false, 'msg' => 'App with code: ' . $appId . ' no exist!!!'));
                    $response->send();
                    exit;
                } 
                $this->statistics = $this->em->getRepository('BallyEntityBundle:Statistics')->findOneBy(array('sessionId' => $this->sessionId, 'app' => $this->app->getId()));
                # if all ok, can save statistics if no exist
            } else { # return error if it's no post method
                $response = new JsonResponse(array('success' => false, 'msg' => 'Only post method allowed for api !!'));
                $response->send();
                exit;
            }
        } catch (\Exception $e) {
            $response = new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
            $response->send();
            exit;
        }
    }

    /**
     * Main action wchich redirect to other actions idetified by type id
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $type
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function indexAction(Request $request) {
        if (method_exists($this, $this->actions[$this->type])) {
            return call_user_func_array(array($this, $this->actions[$this->type]), array($this->data));
        } else {
            return new JsonResponse(array('success' => false, 'msg' => 'Action with id"' . $this->type . '" no exist, maybe wrong name is it ?'));
        }
    }

    /**
     * Action used if app was downloaded, requried params in data array is: language, country, city and ios_version
     * @param array $data
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function downloadAppAction($data) {
        try {
            if ($this->statistics) {
                $language = @$data['language'];
                $country = @$data['country'];
                $city = @$data['city'];
                $ios_version = @$data['ios_version'];
                if ($city && $language && $country) {
                    # Get language object by id
                    $language = $this->em->getRepository('BallyEntityBundle:Language')->find($language);
                    $qb = $this->em->createQueryBuilder();
                    # Set country object if no exist or user existing
                    if ($country) {
                        if (is_numeric($country)) {
                            $countryObj = $this->em->getRepository('BallyEntityBundle:Country')->find($country);
                        } else {
                            $country = trim($country);
                            $countryObj = $this->em
                                    ->getRepository('BallyEntityBundle:Country')
                                    ->createQueryBuilder('c')
                                    ->where($qb->expr()->like($qb->expr()->upper('c.name'), $qb->expr()->literal(strtoupper($country))))
                                    ->getQuery()
                                    ->getOneOrNullResult();
                            if ($countryObj) {
                                $country = $countryObj;
                            } else {
                                $countryObj = new Country();
                                $countryObj->setName(strtoupper($country));
                                $countryObj->setLanguage($language);
                                $this->em->persist($countryObj);
                                $this->em->flush();
                            }
                        }
                    }
                    # Set city object if no exist or user existing
                    if ($city) {
                        if (is_numeric($city)) {
                            $cityObj = $this->em->getRepository('BallyEntityBundle:City')->find($city);
                        } else {
                            $city = trim($city);
                            $cityObj = $this->em
                                    ->getRepository('BallyEntityBundle:City')
                                    ->createQueryBuilder('c')
                                    ->where($qb->expr()->like($qb->expr()->upper('c.name'), $qb->expr()->literal(strtoupper($city))))
                                    ->getQuery()
                                    ->getOneOrNullResult();
                            if ($cityObj) {
                                $city = $cityObj;
                            } else {
                                $cityObj = new City();
                                $cityObj->setName(strtoupper($city));
                                if ($countryObj) {
                                    $cityObj->setCountry($countryObj);
                                }
                                $this->em->persist($cityObj);
                                $this->em->flush();
                            }
                        }
                    }
                    # Create new statistics of download
                    $this->statistics->setCountry($countryObj);
                    $statisticsDownloads = new StatisticsDownloads();
                    $statisticsDownloads
                            ->setStatistics($this->statistics)
                            ->setAction($this->type)
                            ->setVersion($ios_version)
                            ->setMySessionId($this->sessionId)
                            ->setLanguage($language)
                            ->setCountry($countryObj)
                            ->setCity($cityObj)
                            ->setAppDownload(1)
                            ->setIp(@$_SERVER['REMOTE_ADDR']);
                    $this->em->persist($statisticsDownloads);
                    $this->em->flush();
                    return new JsonResponse(array('success' => true, 'msg' => 'App download stats added successufly'));
                } else {
                    throw new \Exception('Once or more of parameters from data json(country, city, language, ios_version) is empty!');
                }
            } else {
                throw new \Exception('Cant find statistics for this session id and app id!');
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

    /**
     * If any asset was downloaded, it must be saved to statistics
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function downloadAssetsAction() {
        try {
            if ($this->statistics) {
                $statisticsAssets = new StatisticsDownloads();
                $statisticsAssets
                        ->setStatistics($this->statistics)
                        ->setAction($this->type)
                        ->setMySessionId($this->sessionId)
                        ->setIp(@$_SERVER['REMOTE_ADDR'])
                        ->setAssetDownloads(1);
                $this->em->persist($statisticsAssets);
                $this->em->flush();
                return new JsonResponse(array('success' => true, 'msg' => 'Assets download stats successufly added'));
            } else {
                throw new \Exception('Cant find statistics for this session id and app id!');
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

    /**
     * Share facebook, twitter statistics, in $data array required "social" param 
     * @param type $data
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function shareAction($data) {
        try {
            $social = @$data['social'];
            if (!$social) {
                throw new \Exception('Social param no exist in data json!');
            }
            if ($this->statistics) {
                $statisticsShare = new StatisticsShares();
                $statisticsShare
                        ->setStatistics($this->statistics)
                        ->setAction($this->type)
                        ->setShareType($social)
                        ->setMySessionId($this->sessionId)
                        ->setIp(@$_SERVER['REMOTE_ADDR'])
                        ->setShareCount(1);
                $this->em->persist($statisticsShare);
                $this->em->flush();
                return new JsonResponse(array('success' => true, 'msg' => 'Share actions successufly added'));
            } else {
                throw new \Exception('Cant find statistics for this session id and app id!');
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

    /**
     * Save count of specific asset view, required asset_id as asset variable in data array
     * @param type $data
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function chooseAssetsAction($data) {
        try {
            $styleId = @$data['styleId'];
            $materialId = @$data['materialId'];
            $colorId = @$data['colorId'];
            $type = @$data['type'];
            if (!($styleId && $materialId && $colorId && $type)) { 
                throw new \Exception('Error. One of the parameters (styleId, materialId, colorId, type) is empty, it must be numeric values and type as string( shoes, belts)!');
            }
            
            $asset = $this->em
                    ->getRepository('BallyEntityBundle:Asset')
                    ->createQueryBuilder('a')
                    ->leftJoin('a.product_type', 't')
                    ->where('a.product_style = :style')
                    ->andWhere('a.leather = :material')
                    ->andWhere('a.color = :color')
                    ->andWhere('t.app = :app')
                    ->andWhere('t.name = :type')
                    ->setParameters(array(
                        'style' => (int) $styleId,
                        'material' => (int) $materialId,
                        'color' => (int) $colorId,
                        'app' => $this->app->getId(),
                        'type' => $type
                    ))
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            if (!$asset) { 
                throw new \Exception('Asset with this parameters no exist!');
            }
             
            if ($this->statistics) {
                $statisticsShare = new StatisticsAssets();
                $statisticsShare
                        ->setStatistics($this->statistics)
                        ->setAction($this->type)
                        ->setMySessionId($this->sessionId)
                        ->setIp(@$_SERVER['REMOTE_ADDR'])
                        ->setViews(1)
                        ->setAsset($asset);
                $this->em->persist($statisticsShare);
                $this->em->flush();
                return new JsonResponse(array('success' => true, 'msg' => 'Choose assets actions successufly added'));
            } else {
                throw new \Exception('Cant find statistics for this session id and app id!');
            }
        } catch (\PDOException $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

    /**
     * Start session action, start was saved early in _init function, bu in this point is returned status
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function startSessionAction() {
        try {
            $isset = false;
            if ($this->statistics) {
                $isset = true;
                $this->statistics->setUpdateOnly(!$this->statistics->getUpdateOnly());
            } else {
                $this->statistics = new Statistics();
                $this->statistics
                        ->setSessionId($this->sessionId)
                        ->setMySessionId($this->sessionId)
                        ->setAction($this->type)
                        ->setApp($this->app)
                        ->setIp(@$_SERVER['REMOTE_ADDR']);
                $this->em->persist($this->statistics);
            }
            $statisticsTime = new StatisticsTime();
            $statisticsTime
                    ->setStatistics($this->statistics)
                    ->setAction($this->type)
                    ->setTimeType('start')
                    ->setMySessionId($this->sessionId)
                    ->setIp(@$_SERVER['REMOTE_ADDR']);
            $this->statistics->addTime($statisticsTime);
            $this->em->persist($statisticsTime);
            $this->em->flush();
            return new JsonResponse(array('success' => true, 'msg' => $isset ? 'Restart session action successuflly did' : 'Start session action successuflly did'));
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

    /**
     * Action save to time of session in stats if request was sent
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function endSessionAction() {
        try {
            if ($this->statistics) {
                $statisticsTime = new StatisticsTime();
                $statisticsTime
                        ->setStatistics($this->statistics)
                        ->setAction($this->type)
                        ->setMySessionId($this->sessionId)
                        ->setIp(@$_SERVER['REMOTE_ADDR'])
                        ->setTimeType('end')
                        ->setTimeLength(time() - $this->statistics->getCreatedAt()->getTimestamp());
                $this->em->persist($statisticsTime);
                $this->em->flush();
                return new JsonResponse(array('success' => true, 'msg' => 'End session action successuflly did'));
            } else {
                throw new \Exception('Cant find statistics for this session id and app id!');
            }
        } catch (\Exception $e) {
            return new JsonResponse(array('success' => false, 'msg' => 'An error occured', 'error' => $e->getMessage()));
        }
    }

}
