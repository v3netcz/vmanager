<?php
/**
 * Test: Test of Project entity.
 * 
 * It'is in fact test also for Tickets and etc.
 * because the behavior is similar to Project.
 *
 * @author Adam Staněk (V3lbloud)
 * @since Apr 14, 2011
 *
 * @subpackage UnitTests
 *
 * This file is part of vBuilder Framework (vBuilder FW).
 * 
 * Copyright (c) 2011 Adam Staněk <adam.stanek@v3net.cz>
 * 
 * For more information visit http://www.vbuilder.cz
 * 
 * vBuilder FW is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * vBuilder FW is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with vBuilder FW. If not, see <http://www.gnu.org/licenses/>.
 */

namespace vManager\Modules\Tickets\UnitTests;

require __DIR__ . '/../../scripts/bootstrap.php';

use vManager,
	 vBuilder,
	 dibi,
	 Nette,
	 vBuilder\Test\Assert;

// Vytvorim si docasne tabulky pro testovani entity
dibi::query("CREATE TEMPORARY TABLE [pm_comments_temp] LIKE [".vManager\Modules\Tickets\Comment::getMetadata()->getTableName()."]");
dibi::query("CREATE TEMPORARY TABLE [pm_projects_temp] LIKE [".vManager\Modules\Tickets\Project::getMetadata()->getTableName()."]");

/**
 * Pritizena entita s pozmenenou tabulkou pro komentare
 * 
 * @Table(name="pm_comments_temp")
 */
class Comment extends vManager\Modules\Tickets\Comment {
	
	protected static function & getMetadataInternal() {
		$reflection = new Nette\Reflection\ClassType('vManager\Modules\Tickets\Comment');
		$metadata = new vBuilder\Orm\AnnotationMetadata($reflection);
		
		$reflection2 = new Nette\Reflection\ClassType(get_called_class());
		$metadata2 = new vBuilder\Orm\AnnotationMetadata($reflection2);		
		
		$metadata3 =  new vBuilder\Orm\MergedMetadata($metadata, $metadata2);
		return $metadata3;
	}
	
}

/**
 * Pritizena entita s pozmenenou tabulkou pro projekty
 * 
 * @Table(name="pm_projects_temp")
 * @Column(comment, realName="commentId", type="OneToOne", entity="vManager\Modules\Tickets\UnitTests\Comment, joinOn="comment=id")
 */
class Project extends vManager\Modules\Tickets\Project {
	
	protected static function & getMetadataInternal() {
		$reflection = new Nette\Reflection\ClassType('vManager\Modules\Tickets\Project');
		$metadata = new vBuilder\Orm\AnnotationMetadata($reflection);
		
		$reflection2 = new Nette\Reflection\ClassType(get_called_class());
		$metadata2 = new vBuilder\Orm\AnnotationMetadata($reflection2);		
		
		$metadata3 =  new vBuilder\Orm\MergedMetadata($metadata, $metadata2);
		
		return $metadata3;
	}
	
}

/******************************************************************************/

// Vytvoreni noveho projektu ***************************************************
$p1 = new Project;
$p1->name = 'New project';

$c1 = new Comment;
$c1->text = 'First comment';
$p1->comment = $c1;

$p1->save();

Assert::arrayEqual(array(array(
	 'projectId' => 1,
	 'revision' => 1,
	 'commentId' => 1,
	 'name' => 'New project',
)), dibi::query('SELECT [projectId], [revision], [commentId], [name] FROM [pm_projects_temp]')->fetchAll());

Assert::arrayEqual(array(array(
	 'commentId' => 1,
	 'comment' => 'First comment'
)), dibi::query('SELECT [commentId], [comment] FROM [pm_comments_temp]')->fetchAll());

// Vytvoreni 2. projektu *******************************************************
$p2 = new Project;
$p2->name = 'Second project';

$c2 = new Comment;
$c2->text = 'First comment on second project';
$p2->comment = $c2;

$p2->save();

Assert::arrayEqual(array(array(
	 'projectId' => 1,
	 'revision' => 1,
	 'commentId' => 1,
	 'name' => 'New project'
), array(
	 'projectId' => 2,
	 'revision' => 1,
	 'commentId' => 2,
	 'name' => 'Second project',
)), dibi::query('SELECT [projectId], [revision], [commentId], [name] FROM [pm_projects_temp]')->fetchAll());

Assert::arrayEqual(array(array(
	 'commentId' => 1,
	 'comment' => 'First comment'
), array(
	 'commentId' => 2,
	 'comment' => 'First comment on second project'
)), dibi::query('SELECT [commentId], [comment] FROM [pm_comments_temp]')->fetchAll());

// Zmena nazvu druheho projektu a pridani komentare ****************************
$p2->name = 'The Greatest Project';

$p2->comment = new Comment;
$p2->comment->text = 'Bla bla';
$p2->comment->public = true;

$p2->save();

Assert::arrayEqual(array(array(
	 'projectId' => 1,
	 'revision' => 1,
	 'commentId' => 1,
	 'name' => 'New project'
), array(
	 'projectId' => 2,
	 'revision' => -1,
	 'commentId' => 2,
	 'name' => 'Second project'
), array(
	 'projectId' => 2,
	 'revision' => 2,
	 'commentId' => 3,
	 'name' => 'The Greatest Project',
)), dibi::query('SELECT [projectId], [revision], [commentId], [name] FROM [pm_projects_temp]')->fetchAll());

Assert::arrayEqual(array(array(
	 'commentId' => 1,
	 'comment' => 'First comment'
), array(
	 'commentId' => 2,
	 'comment' => 'First comment on second project'
), array(
	 'commentId' => 3,
	 'comment' => 'Bla bla'
)), dibi::query('SELECT [commentId], [comment] FROM [pm_comments_temp]')->fetchAll());

