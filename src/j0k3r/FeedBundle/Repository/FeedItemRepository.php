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
     * @param string $feedId  Feed id
     * @param array  $options limit, sort_by, skip
     *
     * @return \Doctrine\ODM\MongoDB\Query\Query
     */
    private function getItemsByFeedIdQuery($feedId, $options = array())
    {
        $q = $this->createQueryBuilder()
            ->field('feed.id')->equals($feedId);

        if (isset($options['sort_by']) && $options['sort_by']) {
            $q->sort($options['sort_by'], 'DESC');
        } else {
            $q->sort('published_at', 'DESC');
        }

        if (isset($options['limit']) && $options['limit']) {
            $q->limit($options['limit']);
        }

        if (isset($options['skip']) && $options['skip']) {
            $q->skip($options['skip']);
        }

        return $q->getQuery();
    }

    /**
     * Find all items for a given Feed id
     *
     * @param int    $feedId Feed id
     * @param string $sortBy Feed sort by
     *
     * @return \Doctrine\ODM\MongoDB\LoggableCursor
     */
    public function findByFeed($feedId, $sortBy)
    {
        return $this->getItemsByFeedIdQuery($feedId, array('sort_by' => $sortBy))
            ->execute();
    }

    /**
     * Retrieve the last item for a given Feed id
     *
     * @param int $feedId Feed id
     *
     * @return \j0k3r\FeedBundle\Document\FeedItem
     */
    public function findLastItemByFeedId($feedId)
    {
        return $this->getItemsByFeedIdQuery($feedId, array('limit' => 1))
            ->getSingleResult();
    }

    /**
     * Return the raw results of feeds which HAVE items.
     *
     * @return \MongoCursor
     */
    private function resultsForAllFeedsWithNbItems()
    {
        $q = $this->createQueryBuilder()
            ->map('function () { emit(this.feed.$id, 1); }')
            ->reduce('function (k, vals) {
                var sum = 0;
                for (var i in vals) {
                    sum += vals[i];
                }

                return sum;
            }')
            ->getQuery();

        return $q->execute();
    }

    /**
     * Return feeds which HAVE items
     *
     * @return array of MongoId
     */
    public function findAllFeedWithItems()
    {
        $items = $this->resultsForAllFeedsWithNbItems();

        $res = array();
        foreach ($items as $item) {
            $res[] = (string) $item['_id'];
        }

        return $res;
    }

    /**
     * Retrieve all links from cached item for a given id.
     * Link are used as a "unique" key for item.
     *
     * @param int $feedId Feed id
     *
     * @return array
     */
    public function getAllLinks($feedId)
    {
        $res = $this->createQueryBuilder()
            ->select('permalink')
            ->hydrate(false)
            ->field('feed.id')->equals($feedId)
            ->sort('published_at', 'DESC')
            ->getQuery()
            ->execute();

        // store as key to avoid duplicate (even if it doesn't have to happen)
        // and also because it's faster to isset than in_array to match a value
        $results = array();
        foreach ($res as $item) {
            $results[$item['permalink']] = true;
        }

        return $results;
    }

    /**
     * Find all items starting at $skip.
     * Used to remove all old items.
     * I can't find a way to perform the remove in one query (remove & skip doesn't want to work *well* together)
     *
     * @param int $feedId Feed id
     * @param int $skip   Items to keep
     *
     * @return \Doctrine\ODM\MongoDB\EagerCursor
     */
    public function findOldItemsByFeedId($feedId, $skip = 100)
    {
        return $this->createQueryBuilder()
            ->find()
            ->eagerCursor(true)
            ->field('feed.id')->equals($feedId)
            ->sort('published_at', 'desc')
            ->skip((int) $skip)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove all items associated to the given Feed id
     *
     * @param int $feedId Feed id
     *
     * @return array (with key 'n' as number of row affected)
     */
    public function deleteAllByFeedId($feedId)
    {
        return $this->createQueryBuilder()
            ->remove()
            ->field('feed.id')->equals($feedId)
            ->getQuery()
            ->execute();
    }

    /**
     * Count items for a given feed id
     *
     * @param int $feedId Feed id
     *
     * @return integer
     */
    public function countByFeedId($feedId)
    {
        return $this->createQueryBuilder()
            ->count()
            ->field('feed.id')->equals($feedId)
            ->getQuery()
            ->execute();
    }
}
