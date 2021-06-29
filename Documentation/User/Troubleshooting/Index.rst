.. include:: ../../Includes.txt


.. _user-troubleshooting:

Troubleshooting
^^^^^^^^^^^^^^^


.. _user-backend-troubleshooting-not-executed:

The automatic synchronization is not being executed
"""""""""""""""""""""""""""""""""""""""""""""""""""

You may observe that the scheduled synchronization is not taking place
at all. Even if the debug mode is activated and you look at the
logs, you will see no call to external\_import. This may happen when
you set a too high frequency for synchronizations (like 1 minute for
example). If the previous synchronization has not finished, the
Scheduler will prevent the new one from taking place. The symptom is a
message like "[scheduler]: Event is already running and multiple
executions are not allowed, skipping! CRID: xyz, UID: nn" in the
system log (**SYSTEM > Log**). In this case you should stop the current
execution in the Scheduler backend module.


.. _user-backend-troubleshooting-neverending:

The manual synchronization never ends
"""""""""""""""""""""""""""""""""""""

It may be that no results are reported during a manual synchronization
and that the looping arrows continue spinning endlessly. This happens
when something failed completely during the synchronization and the BE
module received no response. See the advice in :ref:`Debugging <user-debugging>`.


.. _user-backend-troubleshooting-all-deleted:

All the existing data was deleted
"""""""""""""""""""""""""""""""""

The most likely cause is that the external data could not be fetched,
resulting in zero items to import. If the delete operation is not
disabled, External import will take that as a sign that all existing
data should be deleted, since the external source didn't provide
anything.

There are various ways to protect yourself against that. Obviously you
can disable the delete operation, so that no record ever gets deleted.
If this is not desirable, you can use the "minimumRecords" option (see
:ref:`General TCA configuration <administration-general-tca>`) below.
For example, if you always expect at least 100 items to be imported,
set this option to 100. If fewer items than this are present in the
external data, the import process will be aborted and nothing will get deleted.


.. _user-backend-troubleshooting-empty-fields:

Can I leave out records with "empty" fields?
""""""""""""""""""""""""""""""""""""""""""""

A likely scenario is wanting to leave out records where one field is empty.
There's no configuration property for that as it is a difficult topic.
First of all what constitutes an "empty field" will vary depending on
the incoming data and what handling is applied to it. What is more
one may want to filter the data at different points in the process
(e.g. after the data is read or after the data is transformed).

This is why there is no configuration property for "requiring" a field.
Such a need is better addressed by creating a :ref:`custom step <developer-steps>`,
that can applied specific criteria and at a precise point in the
import process.

.. _category-troubleshooting-more_resourcces:

How can I update categories from two or more resources?
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""
Categories are often used as a reservoir for different aspects. It may
happen that you want update your categories imported from two or more different sources.
Or you want to update your datat and save your manually integrated categories.
The TYPO3 DataHandler used by external_import will delete all old
relations of categories, before it build the relations oto the newly imported categories.
The 'delete'-option of the 'external'-configuration won't help you.

The solution is a hook that records the old relations to the categories
directly before the update and creates a complete list of old and new
relations for the data handler. You find below an example for the hook-class.
Don't forget the registration of the hook in ``ext_localconf.php``. 

.. code-block:: php

 // hook registered in ext_localconf.php
     $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['external_import']['updatePreProcess'][] =
          \MyVendor\MyExtension\Hooks\SaveRelationsBeforeUpdateHook::class;

.. code-block:: php


    /**
     *
     * Class SaveRelationsBeforeUpdateHook
     * 
     * use Cobweb\ExternalImport\Importer;
     * use MyVendor\MyExtension\Constants\ImportData;
     * use PDO;
     * use TYPO3\CMS\Core\Database\ConnectionPool;
     * use TYPO3\CMS\Core\Database\Query\QueryBuilder;
     * use TYPO3\CMS\Core\Utility\GeneralUtility;
     * 
     */
    class SaveRelationsBeforeUpdateHook
    {
        /**
         * The hook will called with each external_import
         */
        protected const TEXT_LIST = [
            ImportoldData::IMPORT_PROCESS_NEWS_REL, // list with string of allowed-import-indexes
            ImportoldData::IMPORT_PROCESS_NEWS_MAIN,
        ];

        /**
         * @param $theRecord
         * @param Importer $importer
         * @return mixed
         */
        public function processBeforeUpdate($theRecord, $importer)
        {
            $index = $importer->getExternalConfiguration() !== null ? $importer->getExternalConfiguration()->getIndex() : 0;
            if ((in_array($index, self::TEXT_LIST)) &&
                (!empty($theRecord['tx_import_import_reference_id']))
            ) {

                $additionalList = $this->findAllForeignRelationsInMMForNews($theRecord['tx_import_import_reference_id']);
                if (!empty($additionalList)) {
                    $additional = array_column($additionalList, 'refId');
                    if (!empty($theRecord['categories'])) {
                        $list = array_filter(
                            array_map(
                                'intval',
                                explode(',', $theRecord['categories'])
                            )
                        );
                    } else {
                        $list = [];
                    }
                    $theRecord['categories'] = implode(
                        ',',
                        array_filter(
                            array_unique(
                                array_merge($additional, $list)
                            )
                        )
                    );
                }
            }
            return $theRecord;
        }

        /**
          *  Find the old relations to categoeries
          */
        protected function findAllForeignRelationsInMMForNews($referencUidValue)
        {
            $mmTable = 'sys_category_record_mm';
            $table = 'tx_news_domain_model_news'; // your destination-table for the import may be something else 
            $tableOppositeField = 'categories';
            $refUid = 'uid_foreign';
            $referencUid = 'tx_import_import_reference_id'; 
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder
                ->select('mm.uid_local as refId')
                ->from($table, 'main')
                ->join(
                    'main',
                    $mmTable,
                    'mm',
                    $queryBuilder->expr()->eq(
                        'mm.' . $refUid,
                        '`main`.`uid`'
                    )
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'main.' . $referencUid,
                        $queryBuilder->createNamedParameter($referencUidValue, PDO::PARAM_STR)),
                    $queryBuilder->expr()->eq(
                        'mm.tablenames',
                        $queryBuilder->createNamedParameter($table, PDO::PARAM_STR)),
                    $queryBuilder->expr()->eq(
                        'mm.fieldname',
                        $queryBuilder->createNamedParameter($tableOppositeField, PDO::PARAM_STR))
                );
            return $queryBuilder->execute()->fetchAllAssociative();
        }
    }
 
 

