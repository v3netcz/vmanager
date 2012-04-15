<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vManager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vManager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vManager. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vManager\Modules\GitHub;

use vManager, vBuilder;

/**
 * GitHub repository representation
 *
 * @author Adam Staněk (velbloud)
 * @since Apr 14, 2012
 * 
 * @Table(name="github_commits")
 * @Column(id, pk, type="string")
 * @Column(repo, realName="repository", type="OneToOne", entity="vManager\Modules\GitHub\Repository", joinOn="repo=id")
 * @Column(author, type="OneToOne", entity="vManager\Modules\GitHub\User", joinOn="author=id")
 * @Column(timestamp, type="DateTime")
 * @Column(message, type="string")
 * @Column(url, type="string")
 */
class Commit extends vBuilder\Orm\ActiveEntity {

}