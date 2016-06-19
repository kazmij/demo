<?php

namespace Bally\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Statistics
 * @ORM\Table(name="statistics")
 * @ORM\Entity(repositoryClass="Bally\EntityBundle\Repository\StatisticsRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"default" = "Statistics", "time" = "StatisticsTime", "downloads" = "StatisticsDownloads", "topAssets" = "StatisticsAssets", "shares" = "StatisticsShares", "locations" = "StatisticsLocations" })
 */
class Statistics {

    use ORMBehaviors\Timestampable\Timestampable;
    //use ORMBehaviors\Loggable\Loggable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * User unique session id
     * @ORM\Column(name="session_id", type="string", length=120, nullable=true, unique=true)
     * @var string
     */
    private $sessionId;
    
    /**
     * User unique session id
     * @ORM\Column(name="my_session_id", type="string", length=120, nullable=true)
     * @var string
     */
    private $mySessionId;    

    /**
     * User ip number
     * @ORM\Column(name="ip", type="string", length=25, nullable=true)
     * @Assert\Ip()
     * @var string
     */
    private $ip;

    /**
     * Action type
     * @ORM\Column(name="action", type="string", length=50, nullable=false)
     * @var string
     */
    private $action;

    /**
     * App version
     * @ORM\ManyToOne(targetEntity="App")
     * @ORM\JoinColumn(name="app_id", referencedColumnName="id")
     */
    private $app;

    /**
     * Times collection for session id
     * @ORM\OneToMany(targetEntity="StatisticsTime", mappedBy="statistics")
     * @ORM\OrderBy({"createdAt" = "DESC", "updatedAt" = "DESC"})
     */
    private $times;

    /**
     * Downloads collection for session id
     * @ORM\OneToMany(targetEntity="StatisticsDownloads", mappedBy="statistics")
     * @ORM\OrderBy({"createdAt" = "DESC", "updatedAt" = "DESC"})
     */
    private $downloads;

    /**
     * Assets views collection for session id
     * @ORM\OneToMany(targetEntity="StatisticsAssets", mappedBy="statistics")
     * @ORM\OrderBy({"createdAt" = "DESC", "updatedAt" = "DESC"})
     */
    private $assets;

    /**
     * Shares collection for session id
     * @ORM\OneToMany(targetEntity="StatisticsShares", mappedBy="statistics")
     * @ORM\OrderBy({"createdAt" = "DESC", "updatedAt" = "DESC"})
     */
    private $shares;

    /**
     * Inheritance discriminator column
     * @var string
     */
    private $type;

    /**
     * Field to change, to update "updatedAt" tim grom KNP Doctrine Behaviors
     * @ORM\Column(name="update_only", type="boolean", nullable=true)
     * @var boolean
     */
    private $updateOnly = false;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="statistics_time_country_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $country;

    public function __construct() {
        $this->times = new \Doctrine\Common\Collections\ArrayCollection();
        $this->downloads = new \Doctrine\Common\Collections\ArrayCollection();
        $this->assets = new \Doctrine\Common\Collections\ArrayCollection();
        $this->shares = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return Statistics
     */
    public function setIp($ip) {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Set sessionId
     *
     * @param string $sessionId
     * @return Statistics
     */
    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return string 
     */
    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return Statistics
     */
    public function setAction($action) {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Add times
     *
     * @param \Bally\EntityBundle\Entity\StatisticsTime $times
     * @return Statistics
     */
    public function addTime(\Bally\EntityBundle\Entity\StatisticsTime $times) {
        $this->times[] = $times;

        return $this;
    }

    /**
     * Remove times
     *
     * @param \Bally\EntityBundle\Entity\StatisticsTime $times
     */
    public function removeTime(\Bally\EntityBundle\Entity\StatisticsTime $times) {
        $this->times->removeElement($times);
    }

    /**
     * Get times
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTimes() {
        return $this->times;
    }

    /**
     * Add downloads
     *
     * @param \Bally\EntityBundle\Entity\StatisticsDownloads $downloads
     * @return Statistics
     */
    public function addDownload(\Bally\EntityBundle\Entity\StatisticsDownloads $downloads) {
        $this->downloads[] = $downloads;

        return $this;
    }

    /**
     * Remove downloads
     *
     * @param \Bally\EntityBundle\Entity\StatisticsDownloads $downloads
     */
    public function removeDownload(\Bally\EntityBundle\Entity\StatisticsDownloads $downloads) {
        $this->downloads->removeElement($downloads);
    }

    /**
     * Get downloads
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDownloads() {
        return $this->downloads;
    }

    /**
     * Add assets
     *
     * @param \Bally\EntityBundle\Entity\StatisticsAssets $assets
     * @return Statistics
     */
    public function addAsset(\Bally\EntityBundle\Entity\StatisticsAssets $assets) {
        $this->assets[] = $assets;

        return $this;
    }

    /**
     * Remove assets
     *
     * @param \Bally\EntityBundle\Entity\StatisticsAssets $assets
     */
    public function removeAsset(\Bally\EntityBundle\Entity\StatisticsAssets $assets) {
        $this->assets->removeElement($assets);
    }

    /**
     * Get assets
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAssets() {
        return $this->assets;
    }

    /**
     * Set app
     *
     * @param \Bally\EntityBundle\Entity\App $app
     * @return Statistics
     */
    public function setApp(\Bally\EntityBundle\Entity\App $app = null) {
        $this->app = $app;

        return $this;
    }

    /**
     * Get app
     *
     * @return \Bally\EntityBundle\Entity\App 
     */
    public function getApp() {
        return $this->app;
    }

    /**
     * Set updateOnly
     *
     * @param boolean $updateOnly
     * @return Statistics
     */
    public function setUpdateOnly($updateOnly) {
        $this->updateOnly = $updateOnly;

        return $this;
    }

    /**
     * Get updateOnly
     *
     * @return boolean 
     */
    public function getUpdateOnly() {
        return $this->updateOnly;
    }

    /**
     * Add shares
     *
     * @param \Bally\EntityBundle\Entity\StatisticsShares $shares
     * @return Statistics
     */
    public function addShare(\Bally\EntityBundle\Entity\StatisticsShares $shares) {
        $this->shares[] = $shares;

        return $this;
    }

    /**
     * Remove shares
     *
     * @param \Bally\EntityBundle\Entity\StatisticsShares $shares
     */
    public function removeShare(\Bally\EntityBundle\Entity\StatisticsShares $shares) {
        $this->shares->removeElement($shares);
    }

    /**
     * Get shares
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getShares() {
        return $this->shares;
    }

    /**
     * Set country
     *
     * @param \Bally\EntityBundle\Entity\Country $country
     * @return StatisticsTime
     */
    public function setCountry(\Bally\EntityBundle\Entity\Country $country = null) {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Bally\EntityBundle\Entity\Country 
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Set mySessionId
     *
     * @param string $mySessionId
     * @return Statistics
     */
    public function setMySessionId($mySessionId)
    {
        $this->mySessionId = $mySessionId;

        return $this;
    }

    /**
     * Get mySessionId
     *
     * @return string 
     */
    public function getMySessionId()
    {
        return $this->mySessionId;
    }
}
