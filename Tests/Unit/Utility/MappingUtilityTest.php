<?php
namespace Cobweb\ExternalImport\Tests\Unit;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\ExternalImport\Utility\MappingUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case for the External Import mapping utility.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_externalimport
 */
class MappingUtilityTest extends UnitTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
     */
//    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');

    /**
     * Local instance for testing
     *
     * @var MappingUtility
     */
    protected $mappingUtility;

    public function setUp()
    {
        $this->mappingUtility = GeneralUtility::makeInstance(MappingUtility::class);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStrposProvider()
    {
        $data = array(
                'australia' => array(
                        'inputData' => 'Australia',
                        'mappingTable' => array(
                                'Commonwealth of Australia' => 'AU',
                                'Kingdom of Spain' => 'ES'
                        ),
                        'mappingConfiguration' => array(
                                'matchMethod' => 'strpos',
                                'matchSymmetric' => false
                        ),
                        'result' => 'AU'
                )
        );
        return $data;
    }

    /**
     * Test soft-matching method with strpos
     *
     * @test
     * @dataProvider mappingTestWithStrposProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult)
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStrposSymmetricProvider()
    {
        $data = array(
                'australia' => array(
                        'inputData' => 'Commonwealth of Australia',
                        'mappingTable' => array(
                                'Australia' => 'AU',
                                'Spain' => 'ES'
                        ),
                        'mappingConfiguration' => array(
                                'matchMethod' => 'strpos',
                                'matchSymmetric' => true
                        ),
                        'result' => 'AU'
                )
        );
        return $data;
    }

    /**
     * Test soft-matching method with strpos and symmetric flag
     *
     * @test
     * @dataProvider mappingTestWithStrposSymmetricProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStrposSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult)
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     *
     * @return array
     */
    public function mappingTestWithStrposWithBadMappingTableProvider()
    {
        $data = array(
            // Case doesn't match in this data set
            'wrong case' => array(
                    'inputData' => 'australia',
                    'mappingTable' => array(
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ),
                    'mappingConfiguration' => array(
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    )
            ),
            'no matching data' => array(
                    'inputData' => 'Swaziland',
                    'mappingTable' => array(
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ),
                    'mappingConfiguration' => array(
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    )
            )
        );
        return $data;
    }

    /**
     * @test
     * @dataProvider mappingTestWithStrposWithBadMappingTableProvider
     * @expectedException \UnexpectedValueException
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     */
    public function failMatchWordsWithStrposNotSymmetric($inputData, $mappingTable, $mappingConfiguration)
    {
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }

    /**
     * Provide data that will not match for strpos
     *
     * @return array
     */
    public function mappingTestWithStriposProvider()
    {
        $data = array(
                'australia' => array(
                        'inputData' => 'australia',
                        'mappingTable' => array(
                                'Commonwealth of Australia' => 'AU',
                                'Kingdom of Spain' => 'ES'
                        ),
                        'mappingConfiguration' => array(
                                'matchMethod' => 'stripos',
                                'matchSymmetric' => false
                        ),
                        'result' => 'AU'
                )
        );
        return $data;
    }

    /**
     * Test soft-matching method with stripos
     *
     * @test
     * @dataProvider mappingTestWithStriposProvider
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     * @param string $expectedResult
     */
    public function matchWordsWithStirposNotSymmetric($inputData, $mappingTable, $mappingConfiguration, $expectedResult)
    {
        $actualResult = $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Provide data for soft matching with strpos
     * (currently provides only one data set, but could provide more in the future)
     *
     * @return array
     */
    public function mappingTestWithStriposWithBadMappingTableProvider()
    {
        $data = array(
            // Case doesn't match in this data set
            'no matching data' => array(
                    'inputData' => 'Swaziland',
                    'mappingTable' => array(
                            'Commonwealth of Australia' => 'AU',
                            'Kingdom of Spain' => 'ES'
                    ),
                    'mappingConfiguration' => array(
                            'matchMethod' => 'strpos',
                            'matchSymmetric' => false
                    )
            )
        );
        return $data;
    }

    /**
     * @test
     * @dataProvider mappingTestWithStriposWithBadMappingTableProvider
     * @expectedException \UnexpectedValueException
     * @param string $inputData
     * @param array $mappingTable
     * @param array $mappingConfiguration
     */
    public function failMatchWordsWithStriposNotSymmetric($inputData, $mappingTable, $mappingConfiguration)
    {
        $this->mappingUtility->matchSingleField($inputData, $mappingConfiguration, $mappingTable);
    }
}
