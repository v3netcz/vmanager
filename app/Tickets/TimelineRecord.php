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

namespace vManager\Modules\Tickets;

use vBuilder,
	vManager,
	DateTime;

/**
 * Simple implementation of timeline records
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 13, 2012
 */
class TimelineRecord extends vBuilder\Object implements vManager\Timeline\IRecord {

	public $ticket;
	public $ticketName;
	
	public $author;

	public $hasBeenCreated;
	public $hasBeenSolved;

	private $_timestamp;

	public function __construct(DateTime $timestamp) {
		$this->_timestamp = $timestamp;
	}

	/**
	 * Returns record's timestamp
	 *
	 * @return DateTime
	 */
	public function getTimestamp() {
		return $this->_timestamp;
	}

	/**
	 * Returns class name of record renderer
	 *
	 * @return string
	 */
	public function getRendererClass() {
		return 'vManager\Modules\Tickets\\TimelineRecordRenderer';
	}

}