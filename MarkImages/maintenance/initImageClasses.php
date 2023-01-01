<?php
$IP = getenv('MW_INSTALL_PATH');
if ($IP === false) {
    $IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * @license MIT
 * @author Ostrzyciel
 */
class InitImageClasses extends Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Initializes MarkImages CSS class cache');
        $this->addOption(
            'namespaces',
            'Comma-separated list of namespace(s) to refresh',
            false,
            true
        );
        $this->addOption(
            'earlier-than',
            'Run only on pages touched earlier than this timestamp',
            false,
            true
        );
        $this->addOption(
            'later-than',
            'Run only on pages touched later than this timestamp',
            false,
            true
        );
        $this->addOption('start', 'Starting page ID', false, true);
        $this->setBatchSize(100);
        $this->requireExtension('MarkImages');
    }

    public function execute()
    {
        $lastId = $this->getOption('start', 0);

        do {
            $tables = ['page'];
            $conds = [
                'page_id > ' . (int)$lastId,
                'page_namespace' => 6,
                'page_is_redirect' => 0,
            ];
            $fields = ['page_id'];
            $dbr = wfGetDB(DB_REPLICA);
            if ($this->hasOption('earlier-than')) {
                $conds[] = 'page_touched < '
                    . $dbr->addQuotes($this->getOption('earlier-than'));
            }
            if ($this->hasOption('later-than')) {
                $conds[] = 'page_touched > '
                    . $dbr->addQuotes($this->getOption('later-than'));
            }
            $res = $dbr->select(
                $tables,
                $fields,
                $conds,
                __METHOD__,
                ['LIMIT' => $this->mBatchSize, 'ORDER_BY' => 'page_id', 'GROUP BY' => 'page_id']
            );
            foreach ($res as $row) {
                $lastId = $row->page_id;
                $title = Title::newFromID($row->page_id);
                $classes = MarkImages::getClasses($title);
                MarkImagesDB::updateClasses($title, implode(' ', $classes));
            }

            $this->output("$lastId\n");
        } while ($res->numRows());
        $this->output("done\n");
    }
}
$maintClass = InitImageClasses::class;
require_once RUN_MAINTENANCE_IF_MAIN;
