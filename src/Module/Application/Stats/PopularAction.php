<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Application\Stats;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PopularAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'popular';

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private VideoRepositoryInterface $videoRepository;

    public function __construct(
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        VideoRepositoryInterface $videoRepository
    ) {
        $this->ui              = $ui;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
        $this->videoRepository = $videoRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        $this->ui->showHeader();

        define('TABLE_RENDERED', 1);

        // Temporary workaround to avoid sorting on custom base requests
        define('NO_BROWSE_SORTING', true);

        $this->ui->showBoxTop(T_('Information'));

        $thresh_value   = $this->configContainer->get(ConfigurationKeyEnum::STATS_THRESHOLD);
        $catalog_filter = $this->configContainer->get(ConfigurationKeyEnum::CATALOG_FILTER);
        $user_id        = ($catalog_filter)
            ? Core::get_global('user')->id
            : null;

        /**
         * We limit threshold for all items otherwise the counter will not be the same that the top_sql query.
         * Example: Item '1234' => 3 counts during period with 'get_top_sql'.
         * Without threshold, 'show_objects' would return the total which could be 24 during all time)
         */

        $browse = $this->modelFactory->createBrowse();
        $browse->set_threshold($thresh_value);
        $browse->set_type(
            'album',
            Stats::get_top_sql('album', $thresh_value, 'stream', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_threshold($thresh_value);
        $browse->set_type(
            'artist',
            Stats::get_top_sql('artist', $thresh_value, 'stream', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_threshold($thresh_value);
        $browse->set_type(
            'song',
            Stats::get_top_sql('song', $thresh_value, 'stream', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) &&
            $this->videoRepository->getItemCount(Video::class)
        ) {
            $browse = $this->modelFactory->createBrowse();
            $browse->set_type(
                'video',
                Stats::get_top_sql('video', $thresh_value, 'stream', $user_id)
            );
            $browse->set_simple_browse(true);
            $browse->show_objects(null);
            $browse->store();
        }

        $browse = $this->modelFactory->createBrowse();
        $browse->set_threshold($thresh_value);
        $browse->set_type(
            'playlist',
            Stats::get_top_sql('playlist', $thresh_value, 'stream', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $this->ui->showBoxBottom();

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
