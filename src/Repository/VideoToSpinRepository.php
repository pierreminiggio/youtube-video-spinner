<?php

namespace PierreMiniggio\YoutubeVideoSpinner\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class VideoToSpinRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function insertSpinnedIfNeeded(
        string $spinnedId,
        int $spinnedAccountId,
        int $youtubeVideoId
    ): void
    {
        $postQueryParams = [
            'account_id' => $spinnedAccountId,
            'spinned_id' => $spinnedId
        ];
        $findPostIdQuery = [
            $this->fetcher
                ->createQuery('spinned_content')
                ->select('id')
                ->where('account_id = :account_id AND spinned_id = :spinned_id')
            ,
            $postQueryParams
        ];
        $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        
        if (! $queriedIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('spinned_content')
                    ->insertInto(
                        'account_id, spinned_id',
                        ':account_id, :spinned_id'
                    )
                ,
                $postQueryParams
            );
            $queriedIds = $this->fetcher->query(...$findPostIdQuery);
        }

        $postId = (int) $queriedIds[0]['id'];
        
        $pivotQueryParams = [
            'spinned_id' => $postId,
            'youtube_id' => $youtubeVideoId
        ];

        $queriedPivotIds = $this->fetcher->query(
            $this->fetcher
                ->createQuery('spinned_content_youtube_video')
                ->select('id')
                ->where('spinned_id = :spinned_id AND youtube_id = :youtube_id')
            ,
            $pivotQueryParams
        );
        
        if (! $queriedPivotIds) {
            $this->fetcher->exec(
                $this->fetcher
                    ->createQuery('spinned_content_youtube_video')
                    ->insertInto('spinned_id, youtube_id', ':spinned_id, :youtube_id')
                ,
                $pivotQueryParams
            );
        }
    }
}
