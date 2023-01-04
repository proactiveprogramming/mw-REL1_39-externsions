# mediawiki-extensions-Ainut

How to edit applications manually:

```php
$mng = new Ainut\ApplicationManager( wfGetLB() );
$app = $mng->findLatestByUser( User::newFromName( 'Risblo' )->getId() );
$fields = $app->getFields();
var_dump( $fields );
$app->setFields( $fields );
$app->setRevision( $app->getRevision() + 1 );
$app->setTimestamp( 0 );
$mng->saveApplication( $app );
```
