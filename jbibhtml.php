<?php
/**
 * @package    jbibhtml
 * @subpackage com_jbibhtml
 *
 * @license  GNU General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Jbibhtml Content Plugin
 */
class plgContentJbibhtml extends JPlugin
{

	/**
	 * Method to parse $article->text string and insert bibliography html.
	 *
	 * Method is called by the view
	 *
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The content object.  Note $article->text is also available
	 * @param	object	The content params
	 * @param	int		The 'page' number
	 * 
	 * @return  boolean	True on success.
	 */
	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		//Do some quick checks to determine if we should process further.
		//Possible contexts: com_content.featured, com_content.category, com_content.article
		$parts = explode(".", $context);
		
		if ($parts[0] != 'com_content')
		{
			return true;
		}
		// Also add a quick check to see if we should process further.
		//Note this is in complete in contexts like com_content.category where $article->text is the introtext.
		if(JString::strpos($article->text, '{jbibhtml') === false) {
			return true;
		}
		
		$bibFile = $this->params->get('bibtex_file', '');
		if(!preg_match("/{jbibhtml\s*(bibFile=\"([^\"]*)\")?\s*}/i", $article->text, $jbibhtml)) {
			return true;
		} elseif(count($jbibhtml) == 3) {
			//Custom bibFile was given.
			$bibFile = $jbibhtml[2];
		}
		
		// Return if we don't have a bibtex_file.
		if (empty($bibFile))
		{
			JError::raiseWarning( 100, "JBibHtml Error: Bibtex file not given." );
			return true;
		}
		
		//Load bibtexbrowser as a library
		$_GET['library']=1;
		define('BIBTEXBROWSER_BIBTEX_LINKS',false);
		define('BIBTEXBROWSER_PDF_LINKS',false);
		define('ABBRV_TYPE', $this->params->get('ABBRV_TYPE', 'index'));
		require_once JPATH_SITE . '/plugins/content/jbibhtml/bibtexbrowser.php';
		
		global $db;
		$db = new BibDataBase();
		$db->load(JPATH_SITE . $bibFile);
		
		//Find all occurences like \cite{Smith2003} or \cite[theorem 5]{Smith2003} in $article->text.
		$result_count = preg_match_all("/\\\cite(\[([^\]]*)\])?\{([^\}]*)\}/", $article->text, $pat_array);
		
		$resKeys = array();
		//First loop through matches and fill in $resKeys.
		for ($j = 0; $j < count($pat_array[0]); $j++) {
			$match = $pat_array[3][$j];
			$bibKeys = explode(",", $match);
			for ($i = 0; $i < count($bibKeys); $i++) {
				if ($db->contains($bibKeys[$i])) {
					$resKeys[] = $bibKeys[$i];
				}
			}
		}
		
		//We use our own custom method, ArticleDisplay. Could also use SimpleDisplay or AcademicDisplay.
		//TODO: how are the entries being sorted?
		$d = new ArticleDisplay();
		//Heading level is decreased so that the ArticleDisplay->display function doesn't output total count.
		$d->decHeadingLevel();
		//Filter by bibtex keys, $resKeys$, cited in the article.
		$entries = $db->multisearch(array('keys'=> $resKeys));
		$d->setEntries($entries);
		
		//Load all HTML output into a string.
		ob_start();
		
		$d->display();
		$content = ob_get_contents();
		ob_end_clean();
		
		$article->text = str_replace($jbibhtml[0], $content, $article->text);
		
		//In this loop, we create links such as [Smith2003] which replaces \cite{Smith2003} in $article->text.
		//It also handles the case we have something like \cite[theorem 5]{Smith2003}.
		for ($j = 0; $j < count($pat_array[0]); $j++) {
			$match = $pat_array[3][$j];
			$bibKeys = explode(",", $match);
			$link = "[";
			for ($i = 0; $i < count($bibKeys); $i++) {
				if (!$db->contains($bibKeys[$i])) {
					JError::raiseWarning( 100, "JBibHtml Error: Bibtex key '$bibKeys[$i]' not found in file '$bibFile'." );
				} else {
					$entry = $db->getEntryByKey($bibKeys[$i]);
					if($i > 0) $link .= "; ";
					$link .= '<a href="' . JURI::current(). '#' . $entry->getRawAbbrv() . '"><span class="bibkey">' . $entry->getRawAbbrv() . '</span></a>';
				}
			}
			if($pat_array[2][$j] != "") {
				$link .= ", " . $pat_array[2][$j];
			}
			$link .= "]";
			//Link to the bibtex entry.
			$article->text = str_replace($pat_array[0][$j], $link, $article->text);
		}
		
		return true;
	}
}
