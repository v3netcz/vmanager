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

namespace vManager\Application;

/**
 * Interface for implementing modules showable in system timeline
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 13, 2012
 */
interface ITimelineEnabledModule {

	/**
	 * Returns array of timeline records. Records can be in arbitrary order.
	 *
	 * @param DateTime since
 	 * @param DateTime until
 	 * @param array of user ids which should be used for query (defined by getTimelineUsers)
	 *
	 * @return array of vManager/Timeline/IRecord
	 */
	public function getTimelineRecords(\DateTime $since, \DateTime $until, array $forUids);
	
	/**
	 * Returns array of available user ids for timeline presentation.
	 *
	 * This function is important, because modules define their own ACL roles,
	 * so parent presenter doesn't know if current user is priviledged for module's records
	 * or not.
	 *
	 * @warning Function should always return at least current user id or getTimelineRecords
	 * 		won't be queried at all.
	 *
	 * @return array of user ids
	 */
	public function getTimelineUsers();

}