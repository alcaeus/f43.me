<?php

namespace j0k3r\FeedBundle\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * FeedItemRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FeedItemRepository extends DocumentRepository
{
    public function findByFeedId($id)
    {
        return $this->createQueryBuilder()
            ->field('feed.id')->equals($id)
            ->sort('updated_at', 'DESC')
            ->getQuery()
            ->execute();
    }
}
