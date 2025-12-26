<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\News;

class SyncViewCountsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:sync-view-counts')
            ->setDescription('Sync cached view counts to database (run every hour)')
            ->setHelp('This command synchronizes cached post view counts to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $cache = $container->get('cache.app');
        $em = $container->get('doctrine')->getManager();
        $output->writeln('Starting view count synchronization...');

        try {
            // Get all cache keys for post views
            $output->writeln('Scanning cache for post view data...');
            
            // Since we can't directly iterate cache keys in all adapters,
            // we'll use the database to get all posts and check their cache
            $posts = $em->getRepository(News::class)->findAll();
            $synced = 0;
            $failed = 0;

            foreach ($posts as $post) {
                try {
                    $cacheKey = 'post_views_' . $post->getId();
                    
                    // Get cached view count
                    $item = $cache->getItem($cacheKey);
                    
                    if ($item->isHit()) {
                        $cachedViews = $item->get();
                        
                        if ($cachedViews > 0) {
                            // Add cached views to database
                            $currentViews = $post->getViewCounts();
                            $post->setViewCounts($currentViews + $cachedViews);
                            $em->persist($post);
                            
                            // Delete cache item
                            $cache->deleteItem($cacheKey);
                            
                            $synced++;
                            
                            if ($synced % 10 === 0) {
                                $output->writeln("Synced {$synced} posts...");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $output->writeln("<error>Error syncing post {$post->getId()}: {$e->getMessage()}</error>");
                }
            }

            // Flush all changes at once
            $em->flush();

            $output->writeln("<info>View count synchronization completed!</info>");
            $output->writeln("Synced: {$synced} posts");
            $output->writeln("Failed: {$failed} posts");
            
            return 0;
        } catch (\Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return 1;
        }
    }
}
