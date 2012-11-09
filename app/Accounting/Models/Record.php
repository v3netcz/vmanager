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

namespace vManager\Modules\Accounting;

use vBuilder;

/**
 * Accounting book record
 *
 * @author Adam Staněk (V3lbloud)
 * @since Feb 5, 2011
 * 
 * @Table(name="accounting_records")
 *
 * @Column(id, pk, type="integer", generatedValue)
 * @Column(evidenceId)
 * @Column(date, type="DateTime")
 * @Column(description, type="string")
 * @Column(value)
 * @Column(md, type="OneToOne", entity="vManager\Modules\Accounting\BillingClass", joinOn="md=id")
 * @Column(d, type="OneToOne", entity="vManager\Modules\Accounting\BillingClass", joinOn="d=id")
 *
 * @Column(subject, realName="subjectId", type="OneToOne", entity="vManager\Modules\Accounting\Subject", joinOn="subject=id")
 * @Column(subjectEvidenceId)
 */
class Record extends vBuilder\Orm\ActiveEntity {

	function setEvidenceId($evidenceId) {
		$this->data->evidenceId = preg_replace('/\\s+/', '', $evidenceId);
	}
	
	function setSubjectEvidenceId($evidenceId) {
		$this->data->subjectEvidenceId = preg_replace('/\\s+/', '', $evidenceId);
	}

}
