<?php

namespace PierreMiniggio\YoutubeVideoSpinner;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\YoutubeVideoSpinner\Connection\DatabaseConnectionFactory;
use PierreMiniggio\YoutubeVideoSpinner\Repository\LinkedChannelRepository;
use PierreMiniggio\YoutubeVideoSpinner\Repository\NonSpinnedVideoRepository;
use PierreMiniggio\YoutubeVideoSpinner\Repository\VideoToSpinRepository;

class App
{

    public function run(): int
    {

        $code = 0;

        $config = require(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php');

        if (empty($config['db'])) {
            echo 'No DB config';

            return $code;
        }

        $databaseFetcher = new DatabaseFetcher((new DatabaseConnectionFactory())->makeFromConfig($config['db']));
        $channelRepository = new LinkedChannelRepository($databaseFetcher);
        $nonSpinnedVideoRepository = new NonSpinnedVideoRepository($databaseFetcher);
        $VideoToSpinRepository = new VideoToSpinRepository($databaseFetcher);

        $linkedChannels = $channelRepository->findAll();

        if (! $linkedChannels) {
            echo 'No linked channels';

            return $code;
        }

        foreach ($linkedChannels as $linkedChannel) {
            echo PHP_EOL . PHP_EOL . 'Checking account ' . $linkedChannel['s_id'] . '...';

            $videosToSpin = $nonSpinnedVideoRepository->findBySpinnedAccountAndYoutubeChannelIds(
                $linkedChannel['s_id'],
                $linkedChannel['y_id']
            );
            echo PHP_EOL . count($videosToSpin) . ' videos to spin :' . PHP_EOL;

            foreach ($videosToSpin as $videoToSpin) {
                echo PHP_EOL . 'Spinning ' . $videoToSpin['title'] . ' ...';

                $VideoToSpinRepository->insertSpinnedIfNeeded(
                    $videoToSpin['id'],
                    $linkedChannel['s_id'],
                    $videoToSpin['id']
                );
                echo PHP_EOL . $videoToSpin['title'] . ' spinned !';
            }

            echo PHP_EOL . PHP_EOL . 'Done for account ' . $linkedChannel['s_id'] . ' !';
        }

        return $code;
    }
}
