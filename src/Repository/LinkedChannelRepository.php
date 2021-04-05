<?php

namespace PierreMiniggio\YoutubeVideoSpinner\Repository;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class LinkedChannelRepository
{
    public function __construct(private DatabaseFetcher $fetcher)
    {}

    public function findAll(): array
    {
        return $this->fetcher->query(
            $this->fetcher
                ->createQuery('spinned_youtube_account_youtube_channel as syayc')
                ->join(
                    'spinned_youtube_account as s',
                    's.id = syayc.spinned_id'
                )
                ->select('
                    syayc.youtube_id as y_id,
                    s.id as s_id
                ')
        );
    }
}
