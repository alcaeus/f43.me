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
    /**
     * Get the base query to fetch items
     *
     * @param  string   $id    Feed id
     * @param  int      $limit Number of items to return
     * @param  int      $skip  Item to skip before applying the limit
     *
     * @return Doctrine\ODM\MongoDB\Query\Query
     */
    private function getItemsByFeedIdQuery($id, $limit = null, $skip = null)
    {
        $q = $this->createQueryBuilder()
            ->field('feed.id')->equals($id)
            ->sort('published_at', 'DESC');

        if (null !== $limit) {
            $q->limit(0);
        }

        if (null !== $skip) {
            $q->skip(0);
        }

        return $q->getQuery();
    }

    /**
     * Find all items for a given Feed id
     *
     * @param  int   $id Feed id
     *
     * @return Doctrine\ODM\MongoDB\LoggableCursor
     */
    public function findByFeedId($id)
    {
        return $this->getItemsByFeedIdQuery($id)
            ->execute();
    }

    /**
     * Retrieve the last item for a given Feed id
     *
     * @param  int   $id Feed id
     *
     * @return j0k3r\FeedBundle\Document\FeedItem
     */
    public function findLastItemByFeedId($id)
    {
        return $this->getItemsByFeedIdQuery($id, 1)
            ->getSingleResult();
    }

    /**
     * Return feeds which HAVE items
     *
     * @return array of MongoId
     */
    public function findAllFeedWithItems()
    {
        $q = $this->createQueryBuilder()
            ->map('function() { emit(this.feed.$id, 1); }')
            ->reduce('function(k, vals) {
                var sum = 0;
                for (var i in vals) {
                    sum += vals[i];
                }
                return sum;
            }')
            ->getQuery();
        $items = $q->execute();

        $res = array();
        foreach ($items as $item) {
            $res[] = (string) $item['_id'];
        }

        return $res;
    }
}