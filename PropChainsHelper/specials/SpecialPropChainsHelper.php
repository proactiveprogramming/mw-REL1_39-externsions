<?php
/**
 * propChainsHelper SpecialPage for PropChainsHelper extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialPropChainsHelper extends SpecialPage
{
	public function __construct()
	{
		parent::__construct( 'propChainsHelper' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 */
	public function execute( $sub )
	{
		$out = $this->getOutput();

		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'special-propChainsHelper-title' ) );
		$out->addWikiMsg( 'special-propChainsHelper-intro' );
		$this->getOutput()->addModules( 'ext.smw.autocomplete.property' );
		$out->addModules( 'ext.propChainsHelper' );

		$this->doSpecialPropChainsHelper($out);
	}

	/**
	 * Do the actual display and logic of Special:PropChainsHelper.
	 */
	function doSpecialPropChainsHelper($out)
	{
		global $ctqProps;

		$request = $this->getRequest();

		$this->category = $request->getText( 'cat' );
		$this->params = $request->getText( 'params' );

		// check for CSRF
		/*$user = $this->getUser();
		if ( !$user->matchEditToken( $request->getVal( 'token' ) ) ) {
			$out->addWikiMsg( 'sessionfailure' );
			return;
		}*/

		MWDebug::init();

		$this->showForm();

		if ($this->category !== '') {
			$sepF = '%0A';
			$ret = Xml::openElement('p');
			$base_domain = SpecialPage::getTitleFor( 'Ask' );
			$elems = array_map(function ($x) {
				return trim($x);
			}, explode(',', $this->params));
			$criteria = array_filter($elems, function ($x) {
				return (strpos($x, '=') > 0 || strpos($x, '>') > 0 || strpos($x, '<') > 0);
			});
			$printouts = array_filter($elems, function ($x) {
				return (!strpos($x, '=') && !strpos($x, '>') && !strpos($x, '<'));
			});
			$criteria = implode($sepF , $this->completeChains($this->category, $criteria, true));
			$printouts = implode($sepF, $this->completeChains($this->category, $printouts, false));
			// e.g.: http://localhost/wiki/Special:Ask?q=[[Category:Experiments]]%0A[[Start+date::%E2%89%A51950]][[Start+date::%E2%89%A41960]]&po=?ID%20Experimental%20code%0A?Project%0A?Topic%0A?Start%20date%0A?End%20date%0A?Animal%20permit%0A?Project%20Manager&p[mainlabel]=ID&eq=yes
			$url = $base_domain->getLocalURL();
			$sep = strpos($url, '?') !== false ? '&' : '?';
			$url .= $sep . "q=[[Category:" . $this->category . "]]%0A$criteria&po=$printouts";
			$ret .= '<button onclick="window.location.href=\'' . $url . '\'">Run query</button><hr />';
			$ret .= Xml::closeElement('p');
			$out->addHTML($ret);
//			$out->addHTML("Elems: " . print_r($ctqProps, true));
			$out->addHTML("<br/>Criteria: " . str_replace($sepF, ',', $criteria));
			$out->addHTML("<br/>Printouts: " . str_replace($sepF, ',', $printouts));
		}
	}

	/**
	 * Add chains to items
	*/
	function completeChains($category, $items, $crit)
	{
        global $pchPropLevels, $pchCatLevels, $pchLinkProps;

        $res = [];
        foreach ($items as $item) {
            if ($crit) {
                    $elems = preg_split("/([<>~!=]+)/", $item, 2, PREG_SPLIT_DELIM_CAPTURE);
                    $item = trim($elems[0]);
                    $rest = $elems[1] . $elems[2];
                    $rest = str_replace('<=', '≤', $rest);
                    $rest = str_replace('>=', '≥', $rest);
                    $rest = str_replace('<', '<', $rest);
                    $rest = str_replace('>', '>', $rest);
                    $rest = str_replace('<>', '!', $rest);
                    $rest = str_replace('~', '~', $rest);
                    $rest = str_replace('!~', '!~', $rest);
                    $rest = str_replace('=', '', $rest);
            }
            $from = $pchCatLevels[$category];
            $item = str_replace('_', ' ', $item);
            $pl = $pchPropLevels[$item];
            $chainNum = $pl[0];
	    $to = $pl[1];
            if ($from === $to)
                $chains = '';
            else if ($from > $to) {
                $chains = [];
                for ($i = $from; $i > $to; $i--)
                    $chains[] = $pchLinkProps[$chainNum][$i];
                $chains = implode('.', $chains);
            }
            else { // $from < $to
                $chains = [];
                for ($i = $from; $i < $to; $i++)
                    $chains[] = '-' . $pchLinkProps[$chainNum][$i];
                    $chains = implode('.', $chains);
            }
            if ($chains !== '' && strpos($item, $chains) === false)
                $item = str_replace($item, $chains . '.' . $item, $item);
            if ($crit)
                $res[] = "[[$item::$oper$rest]]";
            else
                $res[] =  $item;
        }

        return $res;
	}

	function showForm()
	{
		global $pchDomains;

		$out = $this->getOutput();

		$out->addHTML(
			Xml::openElement(
				'form',
				[
					'id' => 'search',
					'action' => $this->getPageTitle()->getFullURL(),
					'method' => 'post'
				]
			) . "\n" .
			Html::hidden( 'pchDomains', json_encode($pchDomains), ['id' => 'pchDomains'] ) .
			Html::hidden( 'token', $out->getUser()->getEditToken() )
		);

		$out->addHTML(
			Xml::openElement('p') .
			Xml::label( 'Category ', 'cat-label', [ 'for' => 'cat' ] ) .
			//Xml::inputLabel( 'Property', 'property', 'smw-property-input', 20, 'property' ) . ' ' .
			Xml::input( 'cat', 20, $this->category, [ 'id' => 'cat', 'class' => 'awesome' ] ) .
			Xml::closeElement( 'p' ) .
		    Xml::openElement('p') . 
			Xml::label( 'Parameters ', 'params-label', [ 'for' => 'param'] ) .
			'<br/>' .
			Xml::textarea( 'params', $this->params, 10, 5, []) .
			Xml::closeElement( 'p' ) .
			Xml::submitButton( 'Prepare semantic query' ) .
			Xml::closeElement( 'form' )
		);
		$out->addModules( 'ext.propChainsHelper' );
	}

	/**
	 * @inheritDoc
	*/
	protected function getGroupName()
	{
		return 'other';
	}

}

