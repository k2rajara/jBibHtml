<?php 
/** displays the summary information of all bib entries.
 * To be displayed in a Joomla article.
 usage:
 <pre>
 $db = zetDB('metrics.bib');
 $d = new ArticleDisplay();
 $d->setDB($db);
 $d->display();
 </pre>
 */
class ArticleDisplay  {

	var $headerCSS = 'sheader';

	var $options = array();

	var $headingLevel = BIBTEXBROWSER_HTMLHEADINGLEVEL;
	function incHeadingLevel ($by=1) {
		$this->headingLevel += $by;
	}
	function decHeadingLevel ($by=1) {
		$this->headingLevel -= $by;
	}

	function setDB(&$bibdatabase) {
		$this->setEntries($bibdatabase->bibdb);
	}

	function metadata() {
		if (BIBTEXBROWSER_ROBOTS_NOINDEX) {
			return array(array('robots','noindex'));
		} else {
			return array();
		}
	}

	/** sets the entries to be shown */
	function setEntries(&$entries) {
		$this->entries = $entries;
	}

	function indexUp() {
		$index=1;
		foreach ($this->entries as $bib) {
			$bib->setAbbrv((string)$index++);
		} // end foreach
		return $this->entries;
	}

	function newest(&$entries) {
		return array_slice($entries,0,BIBTEXBROWSER_NEWEST);
	}

	function indexDown() {
		$index=count($this->entries);
		foreach ($this->entries as $bib) {
			$bib->setAbbrv((string)$index--);
		} // end foreach
		return $this->entries;
	}

	function setQuery($query) {
		$this->query = $query;
	}
	function getTitle() {
		return _DefaultBibliographyTitle($this->query);
	}

	/** Displays a set of bibtex entries in an HTML table */
	function display() {

		uasort($this->entries, 'compare_bib_entries');

		if ($this->options) {
			foreach($this->options as $fname=>$opt) {
				$this->$fname($opt,$entries);
			}
		}

		if (BIBTEXBROWSER_DEBUG) {
			echo 'Style: '.bibtexbrowser_configuration('BIBLIOGRAPHYSTYLE').'<br/>';
			echo 'Order: '.ORDER_FUNCTION.'<br/>';
			echo 'Abbrv: '.ABBRV_TYPE.'<br/>';
			echo 'Options: '.@implode(',',$this->options).'<br/>';
		}

		if ($this->headingLevel == BIBTEXBROWSER_HTMLHEADINGLEVEL) {
			echo "\n".'<span class="count">';
			if (count($this->entries) == 1) {
				echo count ($this->entries).' '.__('result');
			} else if (count($this->entries) != 0) {
				echo count ($this->entries).' '.__('results');
			}
			echo "</span>\n";
		}
		print_header_layout();

		$count = count($this->entries);
		$i=0;
		$pred = NULL;
		foreach ($this->entries as $bib) {
			// by default, index are in decreasing order
			// so that when you add a publicaton recent , the indices of preceding publications don't change
			$bib->setIndex($count-($i++));
			echo $bib->toHTML(true);

			$pred = $bib;
		} // end foreach

		print_footer_layout();

	} // end function

} // end class
?>