<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

use AppBundle\Entity\News;
use AppBundle\Entity\NewsUpdate;

/**
 * @DI\Service("collegefootball.app.news")
 */
class NewsService
{
    private $em;

    /**
     * @DI\InjectParams({
     *      "em" = @DI\Inject("doctrine.orm.entity_manager")
     *  })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getNews()
    {
        $repository       = $this->em->getRepository('AppBundle:NewsUpdate');
        $latestNewsUpdate = $repository->createQueryBuilder('nu')
            ->orderBy('nu.id', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        $repository = $this->em->getRepository('AppBundle:News');
        $now        = new \DateTime("now");

        if (! $latestNewsUpdate || $latestNewsUpdate->getCreatedAt()->modify('+1 hours')->format('U') < $now->format('U')) {
            /* check for updates */
            $response = \Unirest\Request::get('http://www.espn.com/espn/rss/ncf/news');

            $news = simplexml_load_string($response->body);

            /* insert new items into db */
            foreach ($news->channel->item as $item) {
                $exists = $repository->findOneByEspnGuid($item->guid);

                if (! $exists) {
                    $newsItem = new News();
                    $newsItem->setTitle($item->title);
                    $newsItem->setDescription($item->description);
                    $newsItem->setLink($item->link);
                    $newsItem->setEspnGuid($item->guid);

                    $pubDate = new \DateTime($item->pubDate);
                    $newsItem->setDate($pubDate);

                    $this->em->persist($newsItem);
                }
            }

            /* add to news_update */
            $newsUpdate = new NewsUpdate();
            $newsUpdate->setTitle($news->channel->title);
            $newsUpdate->setDescription($news->channel->description);
            $newsUpdate->setLastBuildDate($news->channel->lastBuildDate);
            $this->em->persist($newsUpdate);

            $this->em->flush();
        }

        /* now read from db */
        $news = $repository->createQueryBuilder('n')
            ->orderBy('n.date', 'desc')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $news;
    }
}
