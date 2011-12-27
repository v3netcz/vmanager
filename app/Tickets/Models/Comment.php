<?php

/**
 * This file is part of vManager.
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
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

namespace vManager\Modules\Tickets;

use vManager, vBuilder, Nette;

/**
 * Comment entity data class for Projects, Tickets and other versionable entities
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2011
 * 
 * @Table(name="pm_comments")
 * @Column(id, realName="commentId", pk, type="integer", generatedValue)
 * @Column(text, realName="comment", type="string")
 * @Column(private, type="boolean")
 * @Column(attachments, type="OneToMany", entity="vManager\Modules\Tickets\Attachment", joinOn="id=commentId")
 * @Column(deadlineThen, type="DateTime")
 */
class Comment extends vBuilder\Orm\ActiveEntity {
	
	
	
}
