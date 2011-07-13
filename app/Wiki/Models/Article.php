<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 V3Net.cz
 * 
 * For more information visit http://www.vmanager.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
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

namespace vManager\Modules\Wiki;

use vManager, vBuilder, Nette;

/**
 * Project entity data class
 *
 * @author Jirka Vebr
 * 
 * @Table(name="wiki_articles")
 * 
 * @Behavior(Versionable, idField = "id", revisionField = "revision")
 * 
 * @Column(id, pk, type="integer")
 * @Column(title, type="string")
 * @Column(text, type="string")
 * @Column(added, type="DateTime")
 * @Column(lastModified, realName="last_modified", type="DateTime")
 * @Column(revision, pk, type="integer")
 * @Column(active, type="integer")
 * @Column(url, type="string")
 * @Column(author, realName="authorId", type="OneToOne", entity="vManager\Security\User", joinOn="author=id")
 */
class Article extends vBuilder\Orm\ActiveEntity {
			
}