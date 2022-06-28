<?php

namespace App\Repository;

use App\Entity\Place;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Place>
 *
 * @method Place|null find($id, $lockMode = null, $lockVersion = null)
 * @method Place|null findOneBy(array $criteria, array $orderBy = null)
 * @method Place[]    findAll()
 * @method Place[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Place::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Place $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Place $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return Place[] Returns an array of Place objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Place
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
/*    //recupera por duración de la película
    public function findAllByduration(int $min, int $max):array{
        //consukta den DQL ( Doctrine Query Language)
        return $this->getEntityManager()->createQuery(
            "select p from App\Entity\Pelicula p
                where p.duracion between :min and :max
                order by p.duracion desc"
            )
            ->setParameter("min",$min)
            ->setParameter("max",$max)
            ->getResult();
    }
*/
    // Ejemplo hecho por mi
    // muestra los últimos 4 lugares , con carátula, que se han añadido
    public function placeShow():array
    {
        $places = $this->getEntityManager()->createQuery(
            'SELECT p
             FROM App\Entity\Place p
            WHERE p.caratula IS NOT NULL
             ORDER BY p.id DESC'
            )->setMaxResults(4)  //limit
            ->setFirstResult(0) //offset
            ->getResult();
            //$respuesta = implode('<br>', $peliculas);
            return  $places;
    }
    
    // Método que hay que llamar desde la portada para que muestre las últimas carátulas
    // Se llama desde DefaultControlller.
    // Ejemplo de clase.
    
    public function findLast(int $quantity=4,bool $cover=TRUE) :array {
        return $this->getEntityManager()->createQuery(
            "SELECT p FROM App\Entity\Place p"
            .($cover? " WHERE p.caratula IS NOT NULL":"")
            ." ORDER BY p.id DESC"
            )
            ->setMaxResults($quantity)->getResult();
    }
}
