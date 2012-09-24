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

namespace vManager\Modules\Docs;

use vManager, vBuilder, Nette, Nette\Utils\Strings;

/**
 * Markdown document presenter
 *
 * @author Adam Staněk (V3lbloud)
 * @since Aug 27, 2012
 */
class MarkdownPresenter extends vManager\Modules\System\SecuredPresenter {
	
	public function renderDefault() {
		$this->template->html = $this->getHtmlData();
	}
	
	public function actionPdf() {
		$renderer = new vManager\Reporting\PdfRenderer($this->context);
		$renderer->setTemplateFile(__DIR__ . '/../Templates/Markdown/pdf.latte');
				
		$renderer->template->html = $this->getHtmlData(true);
		$renderer->template->date = new \DateTime;
		$renderer->template->versionString = '1.0';
		$renderer->template->title = 'Sarantis: Redesign webu';
		
		$url = 'http://www.v3net.cz';
		
		if($url) {
			$renderer->template->qrCodeUrl = 'http://www.montclair.edu/bldg_m/qr_svg.php?URL=' . urlencode($url) . '&Type=svg&Submit=Generate';
			
			$renderer->onBeforeOutput[] = function ($mPdf) {
				$mPdf->SetHTMLFooterByName("MyFooter2");	
			};
		}
		
		$renderer->render(); 
		exit;
	}
	
	protected function getHtmlData($mpdf = false) {
		$data = file_get_contents(__DIR__ . '/../Samples/nabidka.md');
		$html = $this->convertMarkdown2Html($data);
		
		$html = '<div class="block firstBlock">' . $html . '</div>';

		$headings = array();
		$headingAfterContent = false;
		
		$html = Strings::replace($html, '#\\<(/?[a-z]+|h([1-6]))\\>#', function ($matches) use (&$headings, $mpdf, &$headingAfterContent) {

			// Heading
			if(isset($matches[2])) {			
				$level = (int) intval($matches[2]);
				for($i = $level + 1; $i <= 6; $i++) unset($headings[$i]);
	
				if(!isset($headings[$level])) {
					$headings[$level] = true;
					$class = 'first';
					if($headingAfterContent) $class .= ' headingAfterContent';
					$headingAfterContent = false;
					
					return '<h' . $level . ' class="' . $class . '">';			
				} elseif($level < 3) {
					return '</div>' . ($mpdf ? '<pagebreak margin-top="25mm" />' : '') . '<div class="block">' . $matches[0];
				} else
					return $matches[0];
			}
			
			else {
				$headingAfterContent = true;
			
				// <$matches[1]>
				if($matches[1] == 'ul') {
					return "<div class=\"list\">";
				} elseif($matches[1] == '/ul') {
					return "</div>";
				} elseif($matches[1] == 'li') {
					return "<div class=\"bullet\">";
				} elseif($matches[1] == '/li') {
					return "</div>";
				} else
					return $matches[0];
			}

		});
		
		return $html;		
		
	}
	
	protected function convertMarkdown2Html($markdownText) {
		static $parser;
		if (!isset($parser)) {
			$parser = new \Markdown_Parser;
		}
		
		# Transform text using parser.
		return $parser->transform($markdownText);
	}
	
}
