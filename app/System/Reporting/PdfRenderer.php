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

namespace vManager\Reporting;

use vManager,
	vBuilder,
	Nette,
	mPDF;

// Trailing slashes!
define("_MPDF_TEMP_PATH", TEMP_DIR . '/mpdf/');
define('_MPDF_TTFONTDATAPATH', FILES_DIR . '/mpdf-fontdata/');

// TODO: INCLUDE PATH
require_once(LIBS_DIR . '/mPDF/mpdf.php');

/**
 * Renderer for PDF reports
 *
 * @author Adam Staněk (velbloud)
 * @since Jan 28, 2011
 */
class PdfRenderer extends TemplateReportRenderer {
	
	/** @var mPDF mPDF instance */
	private $mPdf;
	
	/** @var int Height of page header - including bottom margin (in mm) */
	protected $headerHeight = 9;
	
	/** @var int Height of page footer - including top margin (in mm) */
	protected $footerHeight = 9;
	
	/** @var int page margin - top (in mm) */
	protected $pageMarginTop = 16;
	
	/** @var int page margin - right (in mm) */
	protected $pageMarginRight = 15;
	
	/** @var int page margin - bottom (in mm) */
	protected $pageMarginBottom = 16;
	
	/** @var int page margin - left (in mm) */
	protected $pageMarginLeft = 15;
	

	/**
	 * Renders report into output buffer
	 */
	function render() {
		$this->setup();
		$this->mPdf->Output();
	}
	
	/**
	 * Renders report into file
	 */
	function renderToFile($filepath) {
		$this->setup();
		$this->mPdf->Output($filepath, 'F');
	}
	
	/**
	 * Sets up mPDF
	 */
	protected function setup() {
		$this->mPdf = null;
		$tpl = $this->getTemplate();
		//$tpl->someVar = 123;		
		
		// --------
		
		$mPdfData = (String) $tpl;
		$this->mPdf = $this->createMPdf();
		$this->mPdf->WriteHTML($mPdfData);
	}	
	
	/**
	 * Creates instance of mPDF
	 * 
	 * @return mPDF instance
	 */
	protected function createMPdf() {
		if(!is_dir(_MPDF_TEMP_PATH)) {
			if(@mkdir(_MPDF_TEMP_PATH, 0770, true) === false) // @ - is escalated to exception
				throw new Nette\IOException("Cannot create directory '"._MPDF_TEMP_PATH."'");
		}

		if(!is_dir(_MPDF_TTFONTDATAPATH)) {
			if(@mkdir(_MPDF_TTFONTDATAPATH, 0770, true) === false) // @ - is escalated to exception
				throw new Nette\IOException("Cannot create directory '"._MPDF_TTFONTDATAPATH."'");
		}

		// http://mpdf1.com/manual/index.php?tid=184
		$mPdf = new mPDF(
			'utf-8',										// Mode
			'a4',												// Format
			$this->pageMarginTop,				// Margin left (in mm)
			$this->pageMarginRight,			// Margin right (in mm)
			$this->pageMarginBottom,		// Margin top (in mm)
			$this->pageMarginLeft,			// Margin bottom (in mm)
			$this->headerHeight,				// Header margin (in mm)
			$this->footerHeight					// Footer margin (in mm)
		);

		$mPdf->setAutoFont(0);
		return $mPdf;
	}
	
	/**
	 * Sets page margin (in mm)
	 * 
	 * @param int top
	 * @param int right
	 * @param int bottom
	 * @param int left 
	 * 
	 * @throws Nette\InvalidStateException if mPDF was already initialized
	 */
	public function setPageMargins($top, $right, $bottom, $left) {
		if($this->mPdf) throw new Nette\InvalidStateException("mPDF engine has been already initialized.");
		
		$this->pageMarginTop = $top;
		$this->pageMarginRight = $right;
		$this->pageMarginBottom = $bottom;
		$this->pageMarginLeft = $left;
	}
	
	/**
	 * Sets height of header (in mm)
	 * 
	 * @param int height
	 */
	public function setHeaderHeight($height) {
		if($this->mPdf) throw new Nette\InvalidStateException("mPDF engine has been already initialized.");
		
		$this->headerHeight = $height;
	}
	
	/**
	 * Sets height of footer (in mm)
	 * 
	 * @param int height
	 */
	public function setFooterHeight($height) {
		if($this->mPdf) throw new Nette\InvalidStateException("mPDF engine has been already initialized.");
		
		$this->footerHeight = $height;
	}
	
}