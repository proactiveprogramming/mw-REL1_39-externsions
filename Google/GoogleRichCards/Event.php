<?php
/**
 * GoogleRichCards
 * Google Rich Cards metadata generator for Events
 *
 * PHP version 5.4
 *
 * @category Extension
 * @package  GoogleRichCards
 * @author   Igor Shishkin <me@teran.ru>
 * @license  GPL http://www.gnu.org/licenses/gpl.html
 * @link     https://github.com/teran/mediawiki-GoogleRichCards
 */

namespace MediaWiki\Extension\GoogleRichCards;

use OutputPage;
use Parser;
use PPFrame;

if (!defined('MEDIAWIKI')) {
  echo("This is a Mediawiki extension and doesn't provide standalone functionality\n");
  die(1);
}

class Event {
  /**
   * @var static Event instance for Signleton pattern
   */
  public static $instance;

  /**
   * Singleon pattern getter
   *
   * @return Event
   */
  public static function getInstance() {
    if(!isset(self::$instance)) {
      self::$instance = new Event();
    }

    return self::$instance;
  }

  public function parse($text, $params = [], Parser $parser, PPFrame $frame) {
    $o = array();
    foreach($params as $k => $v) {
      $o[$k] = $parser->recursiveTagParse($v, $frame);
    }
    return '<!-- EventData:'.json_encode($o).' -->';
  }

  public function render(OutputPage &$out) {
    if($out->isArticle()) {
      foreach($this->getData($out->mBodytext) as $event) {
        $e = json_decode($event);

        $event = array(
           '@context'    => 'http://schema.org',
           '@type'       => 'Event',
           'name'        => $this->getMetadataField($e, 'name', 'name'),
           'startDate'   => $this->getMetadataField($e, 'startDate', 'startdate'),
           'endDate'     => $this->getMetadataField($e, 'endDate', 'enddate'),
           'description' => $this->getMetadataField($e, 'description', 'description'),
           'image'       => $this->getRawURL($e->{'image'}),
        );

        if($e->{'place'}) {
          $event['location'] = array(
            '@type' => 'Place',
            'name'  => $e->{'place'},
            'address' => array(
              'streetAddress'   => $this->getMetadataField($e, 'streetAddress', 'streetaddress'),
              'addressLocality' => $this->getMetadataField($e, 'addressLocality', 'locality'),
              'postalCode'      => $this->getMetadataField($e, 'postalCode', 'postalcode'),
              'addressRegion'   => $this->getMetadataField($e, 'addressRegion', 'region'),
              'addressCountry'  => $this->getMetadataField($e, 'addressCountry', 'country'),
            ),
          );
        }

        if($e->{'performer'}) {
          $event['performer'] = array(
            '@type'   => 'PerformingGroup',
            'name'    => $this->getMetadataField($e, 'performer', 'performer'),
          );

          if($e->{'offer'}) {
            $event['offers'] = array(
              '@type'         => 'Offer',
              'url'           => $this->getRawURL($e->{'offerurl'}),
              'price'         => $this->getMetadataField($e, 'offerPrice', 'offerprice'),
              'priceCurrency' => $this->getMetadataField($e, 'offerCurrency', 'offercurrency'),
              'availability'  => $this->getItemAvailability($e->{'offeravailability'}),
              'validFrom'     => $this->getMetadataField($e, 'validFrom', 'validfrom'),
            );
          }
        }

        $out->addHeadItem(
          'GoogleRichCardsEvent_'.$e->{'name'},
          '<script type="application/ld+json">'.json_encode($event).'</script>'
        );
      }
    }
  }

  private function getData($pageText) {
		$matches = preg_match_all('/<!-- EventData:(\{(.*)\}) -->/m', $pageText, $extracted);

		return $extracted[1];
  }
  
  private function getRawURL($htmlLink) {
    $matches = preg_match_all('/((https?:\\/\\/)([a-z0-9\.\/_-]+))/i', $htmlLink, $extracted);

    return $extracted[0][0];
  }

  private function getItemAvailability($value) {
    switch ($value) {
      case "Discontinued":
        return "http://schema.org/Discontinued";
      case "InStock":
        return "http://schema.org/InStock";
      case "InStoreOnly":
        return "http://schema.org/InStoreOnly";
      case "LimitedAvailability":
        return "http://schema.org/LimitedAvailability";
      case "OnlineOnly":
        return "http://schema.org/OnlineOnly";
      case "OutOfStock":
        return "http://schema.org/OutOfStock";
      case "PreOrder":
        return "http://schema.org/PreOrder";
      case "PreSale":
        return "http://schema.org/PreSale";
      case "SoldOut":
        return "http://schema.org/SoldOut";
    }
    return "";
  }

  private function getMetadataField($obj, $field, $name) {
    $value = $obj->{$name};
    if ($value == '{{{'.$field.'}}}') {
      return "";
    }
    return $value;
  }
}

?>
