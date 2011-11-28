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

use vManager,
	 vBuilder,
	 Nette,
	 vBuilder\Orm\Repository;

/**
 * Attachment entity for comments
 *
 * @author Adam Staněk (V3lbloud)
 * @since Jul 4, 2011
 * 
 * @Table(name="pm_attachments")
 * @Column(id, realName="attachmentId", pk, type="integer", generatedValue)
 * @Column(commentId, type="integer")
 * @Column(name, type="string")
 * @Column(type, type="string")
 * @Column(path)
 */
class Attachment extends vBuilder\Orm\ActiveEntity {
	
	public function getUrl() {
		return vManager\Modules\System\FilesPresenter::getLink($this->path);
	}
	
	public function getAbsolutePath() {
		return vManager\FileSaver::getBaseDir() . mb_substr($this->path, 1);
	}
	
	public static function registerAttachmentFileHandler() {
		vManager\Modules\System\FilesPresenter::$handers[] = function ($filename) {
			if(!Nette\Utils\Strings::startsWith($filename, '/attachments/')) return ;
			
			if($attachment = Nette\Environment::getContext()->repository->findAll(__NAMESPACE__ . '\\Attachment')->where('[path] = %s', $filename)->fetch()) {
				if(!file_exists($attachment->getAbsolutePath())) return ;
				
				// TODO: Overeni opravneni
				
				return new vBuilder\Application\Responses\FileResponse($attachment->getAbsolutePath(), null, $attachment->type); 
			}
		};
	}
	
}
