/*
XOWA: the XOWA Offline Wiki Application
Copyright (C) 2012-2017 gnosygnu@gmail.com

XOWA is licensed under the terms of the General Public License (GPL) Version 3,
or alternatively under the terms of the Apache License Version 2.0.

You may use XOWA according to either of these licenses as is most appropriate
for your project on a case-by-case basis.

The terms of each license can be found in the source code repository:

GPLv3 License: https://github.com/gnosygnu/xowa/blob/master/LICENSE-GPLv3.txt
Apache License: https://github.com/gnosygnu/xowa/blob/master/LICENSE-APACHE2.txt
*/
package gplx.xowa.mediawiki.extensions.Wikibase.lib.includes.Store; import gplx.*; import gplx.xowa.*; import gplx.xowa.mediawiki.*; import gplx.xowa.mediawiki.extensions.*; import gplx.xowa.mediawiki.extensions.Wikibase.*; import gplx.xowa.mediawiki.extensions.Wikibase.lib.*; import gplx.xowa.mediawiki.extensions.Wikibase.lib.includes.*;
import gplx.xowa.mediawiki.includes.*;
import gplx.xowa.mediawiki.includes.page.*;
// REF.WBASE:2020-01-19
/**
* Provides a list of ordered Property numbers
*
* @license GPL-2.0-or-later
* @author Lucie-Aim�e Kaffee
*/
class XomwWikiPagePropertyOrderProvider extends XomwWikiTextPropertyOrderProvider implements XomwPropertyOrderProvider {
	/**
	* @var Title
	*/
	private XomwTitleOld pageTitle;

	/**
	* @param Title pageTitle page name the ordered property list is on
	*/
	public XomwWikiPagePropertyOrderProvider(XomwTitleOld pageTitle) {
		this.pageTitle = pageTitle;
	}

	/**
	* Get Content of MediaWiki:Wikibase-SortedProperties
	*
	* @return String|null
	* @throws PropertyOrderProviderException
	*/
	@Override protected String getPropertyOrderWikitext() {
		if (!XophpObject_.is_true(this.pageTitle)) {
			throw new XomwPropertyOrderProviderException("Not able to get a title");
		}

//			XomwWikiPage wikiPage = XomwWikiPage.factory(this.pageTitle);
//
//			$pageContent = $wikiPage->getContent();
//
//			if ($pageContent === null) {
//				return null;
//			}
//
//			if (!($pageContent instanceof TextContent)) {
//				throw new PropertyOrderProviderException("The page content of " + this.pageTitle->getText() + " is not TextContent");
//			}
//
//			return strval($pageContent->getNativeData());
		return null;
	}

}
