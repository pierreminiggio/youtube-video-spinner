<?php

namespace PierreMiniggio\YoutubeVideoSpinner\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class NonSpinnedVideoRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findBySpinnedAccountAndYoutubeChannelIds(int $spinnedAccountId, int $youtubeChannelId): array
    {
        $spinnedContentIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('spinned_content_youtube_video as scyv')
                ->join('spinned_content as g', 'g.id = scyv.spinned_id')
                ->select('g.id')
                ->where('g.account_id = :account_id')
            ,
            ['account_id' => $spinnedAccountId]
        );
        $spinnedContentIds = array_map(fn ($entry) => (int) $entry['id'], $spinnedContentIds);

        $isElonChannel = $youtubeChannelId === 'UCCh4AtUCAhIbmOZbAbB-AhA';
        if ($isElonChannel) {
            // Elon Musk Addict' Shorts are posted from TikTok to Youtube.
            // So we don't need to spin them again to TikTok
            // So yeah, that's why I exclude them
            $shortsIds = $this->fetcher->query(
                $this->fetcher->createQuery(
                    'youtube_video'
                )->select(
                    'id'
                )->where(
                    'channel_id = :channel_id
                    AND description like \'%Shorts%\''
                ),
                ['channel_id' => $youtubeChannelId]
            );

            $shortsIds = array_map(fn ($entry) => (int) $entry['id'], $shortsIds);
        }

        $query = $this->fetcher
            ->createQuery('youtube_video as y')
            ->select('y.id, y.title, y.url')
            ->where('y.channel_id = :channel_id' . (
                $spinnedContentIds ? ' AND scyv.id IS NULL' : ''
            ) . (
                $isElonChannel && $shortsIds
                    ? (' AND y.id NOT IN (' . implode(', ', $shortsIds) . ')')
                    : ''
            ))
        ;

        if ($spinnedContentIds) {
            $query->join(
                'spinned_content_youtube_video as scyv',
                'y.id = scyv.youtube_id AND scyv.spinned_id IN (' . implode(', ', $spinnedContentIds) . ')'
            );
        }
        $postsToPost = $this->fetcher->query($query, ['channel_id' => $youtubeChannelId]);
        
        return $postsToPost;
    }
}
