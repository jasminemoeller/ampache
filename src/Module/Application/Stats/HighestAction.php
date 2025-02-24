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
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Video;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\VideoRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HighestAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'highest';

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
        $user_id = ($this->configContainer->get(ConfigurationKeyEnum::CATALOG_FILTER))
            ? Core::get_global('user')->id
            : null;

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'song',
            Rating::get_highest_sql('song', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'album',
            Rating::get_highest_sql('album', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        $browse = $this->modelFactory->createBrowse();
        $browse->set_type(
            'artist',
            Rating::get_highest_sql('artist', $user_id)
        );
        $browse->set_simple_browse(true);
        $browse->show_objects();
        $browse->store();

        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_VIDEO) &&
            $this->videoRepository->getItemCount(Video::class)
        ) {
            $sql    = Rating::get_highest_sql('video', $user_id);
            $browse = $this->modelFactory->createBrowse();
            $browse->set_type('video', $sql);
            $browse->set_simple_browse(true);
            $browse->show_objects();
            $browse->store();
        }

        $this->ui->showBoxBottom();

        show_table_render(false, true);

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
