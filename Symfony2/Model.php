<?php

namespace Arcyro\BaseBundle\Helper;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Model extends Extension {

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    public $em;

    public function load(array $configs, ContainerBuilder $container) {
        
    }

    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Metoda pobierająca ostnią pozycje porządkową "order"w danej tabeli, może być uwarunkowa 
     * przez id i język
     * @param string $entityName - nazwa Entity
     * @param string $positionField - kolumna/pole w Entity które jest kolumną pozcyji
     * @param array $conditions - opcjonalne, jest to tablica warunków ($key => $val), gdzie "$key" to nazwa pola, zaś $val jego wartość
     * @return integer 
     */
    public function getLastPosition($entityName, $positionField, $conditions = array()) {
        $qb = $this->em->createQueryBuilder();
        try {
            $query = $this->em->createQueryBuilder()
                    ->select($qb->expr()->max('p.' . $positionField))
                    ->from($entityName, 'p');
            $i = 0;
            foreach ($conditions as $field => $value) {
                if ($i == 0) {
                    if ($value === null) {
                        $query->where('p.' . $field . ' is null');
                    } else {
                        $query->where($qb->expr()->eq('p.' . $field, $value));
                    }
                } else {
                    if ($value === null) {
                        $query->andWhere('p.' . $field . ' is null');
                    } else {
                        $query->andWhere($qb->expr()->eq('p.' . $field, $value));
                    }
                }
                $i++;
            }

            $result = $query->getQuery()->execute();
            return !empty($result) ? ($result[0][1] ? $result[0][1] : 0) : 0;
        } catch (Exception $e) {
            print_r($e);
            die;
            return false;
        }
    }

    /**
     * Metoda porządkuje elementy w danej tabeli z bazy danych spełniającyh opcjonalne warunki
     * przydatna po usuwaniu elementu z bazy, funkcja dba o to aby element były "po kolei" tak aby nie było przerw 
     * w kolejności np 1 - 2 - 5 tylko 1 - 2 - 3 skoro są 3 elementy
     * @param string $entityName - nazwa Entity
     * @param string $positionField - nazwa pola służącego do porządkowania rekordów
     * @param array $conditions - warunki porządkowania elementów w postaci tablicy $key => $value, gdzie $key to nazwa pola, zaś $value to jego wartość
     * @return boolean 
     */
    public function orderElementsPosition($entityName, $positionField, $conditions = array()) {
        $qb = $this->em->createQueryBuilder();
        try {
            $this->em->getConnection()->beginTransaction();
            $query = $this->em->createQueryBuilder()
                    ->select('p')
                    ->from($entityName, 'p');

            foreach ($conditions as $field => $value) {
                $query->where($qb->expr()->eq('p.' . $field, $value));
            }

            $query->orderBy('p.' . $positionField, 'ASC');

            $result = $query->getQuery()->execute();

            $i = 1;
            foreach ($result as $r) {
                $position = call_user_func_array(array($r, 'get' . ucfirst($positionField)), array());
                if ($i != $position) {
                    call_user_func_array(array($r, 'set' . ucfirst($positionField)), array($i));
                    $this->em->persist($r);
                    $this->em->flush();
                }
                $i++;
            }
            $this->em->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            return false;
        }
    }

    /**
     * Metoda ustawia pozycję danego elementu w danej tabeli bazy danych, niezbędna przy przenoszeniu elementów np na liście czy w menu
     * metoda jest uniwersalna i można ją zawsze stosować do zmiany pozycji elementu
     * @param string $entityName - nazwa entity
     * @param string $positionField - nazwa pola odpowiadającego za pozycję elementu
     * @param integer $dropedElementId - id elementu, któy został przeniesiony
     * @param integer $dropOnElementId - id elementu, którego pozycję przejmie $dropedElementId
     * @param type $conditions - tablica z warunkami dla ustawienia pozycja array($key => $value), gdzie $key to nazwa pola, zaś $value wartość
     * @return boolean 
     */
    public function setElementPosition($entityName, $positionField, $dropedElementId, $dropOnElementId, $conditions = array()) {
        try {
            $this->em->getConnection()->beginTransaction();
            $qb = $this->em->createQueryBuilder();
            $dropedElement = $this->em->getRepository($entityName)->find($dropedElementId);
            $dropOnElement = $this->em->getRepository($entityName)->find($dropOnElementId);
            $oldPosition = call_user_func_array(array($dropedElement, 'get' . ucfirst($positionField)), array());
            $newPosition = call_user_func_array(array($dropOnElement, 'get' . ucfirst($positionField)), array());
            if ($oldPosition > $newPosition) {
                $from = $newPosition + 1;
                $to = $oldPosition - 1;
            } else {
                $from = $oldPosition + 1;
                $to = $newPosition - 1;
            }
            $query = $this->em->createQueryBuilder()
                    ->select('p')
                    ->from($entityName, 'p')
                    ->where($qb->expr()->between('p.' . $positionField, $from, $to));

            foreach ($conditions as $field => $value) {
                if ($value === null) {
                    $query->andWhere('p.' . $field . ' is null');
                } else {
                    $query->andWhere($qb->expr()->eq('p.' . $field, $value));
                }
            }
            $query->orderBy('p.' . $positionField, 'ASC');
            $result = $query->getQuery()->execute();
            foreach ($result as $r) {
                $actualPosition = call_user_func_array(array($r, 'get' . ucfirst($positionField)), array());
                if ($oldPosition > $newPosition) {
                    call_user_func_array(array($r, 'set' . ucfirst($positionField)), array($actualPosition + 1));
                } else {
                    call_user_func_array(array($r, 'set' . ucfirst($positionField)), array($actualPosition - 1));
                }
                $this->em->persist($r);
                $this->em->flush();
            }
            call_user_func_array(array($dropedElement, 'set' . ucfirst($positionField)), array($newPosition));
            if ($oldPosition > $newPosition) {
                call_user_func_array(array($dropOnElement, 'set' . ucfirst($positionField)), array($newPosition + 1));
            } else {
                call_user_func_array(array($dropOnElement, 'set' . ucfirst($positionField)), array($newPosition - 1));
            }
            $this->em->persist($dropedElement);
            $this->em->flush();
            $this->em->persist($dropOnElement);
            $this->em->flush();
            $this->em->getConnection()->commit();
            return true;
        } catch (Doctrine\ORM\Query\QueryException $e) {
            $this->em->getConnection()->rollback();
            return false;
        }
    }

}

