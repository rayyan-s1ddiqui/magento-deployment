<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Sales\Test\Unit\Model\Order\Pdf\Config;

use Magento\Framework\Config\Dom;
use Magento\Framework\Config\Dom\UrnResolver;
use Magento\Framework\Config\ValidationStateInterface;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * Test for validation rules implemented by XSD schema for sales PDF rendering configuration
 */
class XsdTest extends TestCase
{
    /**
     * @var string
     */
    protected static $_schemaPath;

    /**
     * @var string
     */
    protected static $_schemaFilePath;

    public static function setUpBeforeClass(): void
    {
        $urnResolver = new UrnResolver();
        self::$_schemaPath = $urnResolver->getRealPath('urn:magento:module:Magento_Sales:etc/pdf.xsd');
        self::$_schemaFilePath = $urnResolver->getRealPath('urn:magento:module:Magento_Sales:etc/pdf_file.xsd');
    }

    protected function setUp(): void
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
    }

    /**
     * @param string $fixtureXml
     * @param array $expectedErrors
     * @dataProvider schemaByExemplarDataProvider
     */
    public function testSchemaByExemplar($fixtureXml, array $expectedErrors)
    {
        $this->_testSchema(self::$_schemaPath, $fixtureXml, $expectedErrors);
    }

    /**
     * @param string $fixtureXml
     * @param array $expectedErrors
     * @dataProvider fileSchemaByExemplarDataProvider
     */
    public function testFileSchemaByExemplar($fixtureXml, array $expectedErrors)
    {
        $this->_testSchema(self::$_schemaFilePath, $fixtureXml, $expectedErrors);
    }

    /**
     * Test schema against exemplar data
     *
     * @param string $schema
     * @param string $fixtureXml
     * @param array $expectedErrors
     */
    protected function _testSchema($schema, $fixtureXml, array $expectedErrors)
    {
        $validationStateMock = $this->getMockForAbstractClass(ValidationStateInterface::class);
        $validationStateMock->method('isValidationRequired')
            ->willReturn(true);
        $dom = new Dom($fixtureXml, $validationStateMock, [], null, null, '%message%');
        $actualResult = $dom->validate($schema, $actualErrors);
        $this->assertEquals(empty($expectedErrors), $actualResult);
        $this->assertEquals(empty($expectedErrors), empty($actualErrors));
        foreach ($expectedErrors as [$error, $isRegex]) {
            if ($isRegex) {
                $matched = false;
                foreach ($actualErrors as $actualError) {
                    try {
                        $this->assertMatchesRegularExpression($error, $actualError);
                        $matched = true;
                        break;
                    } catch (AssertionFailedError) {
                    }
                }
                $this->assertTrue($matched, "None of the errors matched: $error");
            } else {
                $this->assertContains($error, $actualErrors);
            }
        }
    }

    /**
     * @return array
     */
    public static function schemaByExemplarDataProvider()
    {
        $result = self::_getExemplarTestData();

        $result['non-valid totals missing title'] = [
            '<config><totals><total name="i1"><source_field>foo</source_field></total></totals></config>',
            [
                [
                    "Element 'total': Missing child element(s). Expected is one of ( title, title_source_field, " .
                    "font_size, display_zero, sort_order, model, amount_prefix ).The xml was: \n" .
                    "0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\"><source_field>foo" .
                    "</source_field></total></totals></config>\n2:\n",
                    false,
                ],
            ],
        ];
        $result['non-valid totals missing source_field'] = [
            '<config><totals><total name="i1"><title>Title</title></total></totals></config>',
            [
                [
                    "Element 'total': Missing child element(s). Expected is one of ( source_field, " .
                    "title_source_field, font_size, display_zero, sort_order, model, amount_prefix ).The xml was: " .
                    "\n0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\"><title>Title</title></total>" .
                    "</totals></config>\n2:\n",
                    false,
                ],
            ],
        ];

        return $result;
    }

    /**
     * @return array
     */
    public static function fileSchemaByExemplarDataProvider()
    {
        $result = self::_getExemplarTestData();

        $result['valid totals missing title'] = [
            '<config><totals><total name="i1"><source_field>foo</source_field></total></totals></config>',
            [],
        ];
        $result['valid totals missing source_field'] = [
            '<config><totals><total name="i1"><title>Title</title></total></totals></config>',
            [],
        ];

        return $result;
    }

    /**
     * Return use cases, common for both merged configuration and individual files.
     * Reused by appropriate data providers.
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected static function _getExemplarTestData()
    {
        return [
            'valid empty' => ['<config/>', []],
            'valid empty renderers' => ['<config><renderers/></config>', []],
            'valid empty totals' => ['<config><totals/></config>', []],
            'valid empty renderers and totals' => ['<config><renderers/><totals/></config>', []],
            'non-valid unknown node in <config>' => [
                '<config><unknown/></config>',
                [
                    [
                        "Element 'unknown': This element is not expected.The xml was: \n" .
                        "0:<?xml version=\"1.0\"?>\n1:<config><unknown/></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'valid pages' => [
                '<config><renderers><page type="p1"/><page type="p2"/></renderers></config>',
                [],
            ],
            'non-valid non-unique pages' => [
                '<config><renderers><page type="p1"/><page type="p1"/></renderers></config>',
                [
                    [
                        "Element 'page': Duplicate key-sequence ['p1'] in unique identity-constraint " .
                        "'uniquePageRenderer'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><renderers><page " .
                        "type=\"p1\"/><page type=\"p1\"/></renderers></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid unknown node in renderers' => [
                '<config><renderers><unknown/></renderers></config>',
                [
                    [
                        "Element 'unknown': This element is not expected. Expected is ( page ).The xml was: \n" .
                        "0:<?xml version=\"1.0\"?>\n1:<config><renderers><unknown/></renderers></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'valid page renderers' => [
                '<config><renderers><page type="p1"><renderer product_type="prt1">Class\A</renderer>' .
                '<renderer product_type="prt2">Class\B</renderer></page></renderers></config>',
                [],
            ],
            'non-valid non-unique page renderers' => [
                '<config><renderers><page type="p1"><renderer product_type="prt1">Class\A</renderer>' .
                '<renderer product_type="prt1">Class\B</renderer></page></renderers></config>',
                [
                    [
                        "Element 'renderer': Duplicate key-sequence ['prt1'] in unique identity-constraint " .
                        "'uniqueProductTypeRenderer'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><renderers>" .
                        "<page type=\"p1\"><renderer product_type=\"prt1\">Class\A</renderer><renderer " .
                        "product_type=\"prt1\">Class\B</renderer></page></renderers></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid empty renderer class name' => [
                '<config><renderers><page type="p1"><renderer product_type="prt1"/></page></renderers></config>',
                [
                    [
                        "/Element \'renderer\': .*\'\' is not (a valid value|accepted).*/",
                        true,
                    ],
                ],
            ],
            'non-valid unknown node in page' => [
                '<config><renderers><page type="p1"><unknown/></page></renderers></config>',
                [
                    [
                        "Element 'unknown': This element is not expected. Expected is ( renderer ).The xml was: \n" .
                        "0:<?xml version=\"1.0\"?>\n1:<config><renderers><page type=\"p1\"><unknown/></page>" .
                        "</renderers></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'valid totals' => [
                '<config><totals><total name="i1"><title>Title1</title><source_field>src_fld1</source_field></total>' .
                '<total name="i2"><title>Title2</title><source_field>src_fld2</source_field></total>' .
                '</totals></config>',
                [],
            ],
            'non-valid non-unique total items' => [
                '<config><totals><total name="i1"><title>Title1</title><source_field>src_fld1</source_field></total>' .
                '<total name="i1"><title>Title2</title><source_field>src_fld2</source_field></total>' .
                '</totals></config>',
                [
                    [
                        "Element 'total': Duplicate key-sequence ['i1'] in unique identity-constraint " .
                        "'uniqueTotalItem'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total " .
                        "name=\"i1\"><title>Title1</title><source_field>src_fld1</source_field></total><total " .
                        "name=\"i1\"><title>Title2</title><source_field>src_fld2</source_field></total></totals>" .
                        "</config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid unknown node in total items' => [
                '<config><totals><unknown/></totals></config>',
                [
                    [
                        "Element 'unknown': This element is not expected. Expected is ( total ).The xml was: \n" .
                        "0:<?xml version=\"1.0\"?>\n1:<config><totals><unknown/></totals></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid totals empty title' => [
                '<config><totals><total name="i1"><title/><source_field>foo</source_field></total></totals></config>',
                [
                    [
                        "Element 'title': [facet 'minLength'] The value has a length of '0'; this underruns the " .
                        "allowed minimum length of '1'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals>" .
                        "<total name=\"i1\"><title/><source_field>foo</source_field></total></totals></config>\n2:\n",
                        false,
                    ]
                ],
            ],
            'non-valid totals empty source_field' => [
                '<config><totals><total name="i1"><title>Title</title><source_field/></total></totals></config>',
                [
                    [
                        "Element 'source_field': [facet 'pattern'] The value '' is not accepted by the " .
                        "pattern '[a-z0-9_]+'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total " .
                        "name=\"i1\"><title>Title</title><source_field/></total></totals></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid totals empty title_source_field' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<title_source_field/></total></totals></config>',
                [
                    [
                        "Element 'title_source_field': [facet 'pattern'] The value '' is not accepted by the " .
                        "pattern '[a-z0-9_]+'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total " .
                        "name=\"i1\"><title>Title</title><source_field>foo</source_field><title_source_field/>" .
                        "</total></totals></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'non-valid totals bad model' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<model>a model</model></total></totals></config>',
                [
                    [
                        "/Element \'model\': .*\'a model\' is not (a valid value|accepted).*/",
                        true,
                    ]
                ],
            ],
            'valid totals title_source_field' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<title_source_field>bar</title_source_field></total></totals></config>',
                [],
            ],
            'valid totals model' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<model>Class\A</model></total></totals></config>',
                [],
            ],
            'valid totals font_size' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<font_size>9</font_size></total></totals></config>',
                [],
            ],
            'non-valid totals font_size 0' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<font_size>0</font_size></total></totals></config>',
                [
                    [
                        "Element 'font_size': '0' is not a valid value of the atomic type 'xs:positiveInteger'.The " .
                        "xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\"><title>Title" .
                        "</title><source_field>foo</source_field><font_size>0</font_size></total></totals>" .
                        "</config>\n2:\n",
                        false,
                    ]
                ],
            ],
            'non-valid totals font_size' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<font_size>A</font_size></total></totals></config>',
                [
                    [
                        "Element 'font_size': 'A' is not a valid value of the atomic type 'xs:positiveInteger'.The " .
                        "xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\"><title>Title" .
                        "</title><source_field>foo</source_field><font_size>A</font_size></total></totals></config>" .
                        "\n2:\n",
                        false,
                    ]
                ],
            ],
            'valid totals display_zero' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<display_zero>1</display_zero></total></totals></config>',
                [],
            ],
            'valid totals display_zero true' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<display_zero>true</display_zero></total></totals></config>',
                [],
            ],
            'non-valid totals display_zero' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<display_zero>A</display_zero></total></totals></config>',
                [
                    [
                        "Element 'display_zero': 'A' is not a valid value of the atomic type 'xs:boolean'." .
                        "The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\">" .
                        "<title>Title</title><source_field>foo</source_field><display_zero>A</display_zero>" .
                        "</total></totals></config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'valid totals sort_order' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<sort_order>100</sort_order></total></totals></config>',
                [],
            ],
            'valid totals sort_order 0' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<sort_order>0</sort_order></total></totals></config>',
                [],
            ],
            'non-valid totals sort_order' => [
                '<config><totals><total name="i1"><title>Title</title><source_field>foo</source_field>' .
                '<sort_order>A</sort_order></total></totals></config>',
                [
                    [
                        "Element 'sort_order': 'A' is not a valid value of the atomic type 'xs:nonNegativeInteger'." .
                        "The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total name=\"i1\"><title>Title" .
                        "</title><source_field>foo</source_field><sort_order>A</sort_order></total></totals>" .
                        "</config>\n2:\n",
                        false,
                    ],
                ],
            ],
            'valid totals title with translate attribute' => [
                '<config><totals><total name="i1"><title translate="true">Title</title>' .
                '<source_field>foo</source_field></total></totals></config>',
                [],
            ],
            'non-valid totals title with bad translate attribute' => [
                '<config><totals><total name="i1"><title translate="unknown">Title</title>' .
                '<source_field>foo</source_field></total></totals></config>',
                [
                    [
                        "Element 'title', attribute 'translate': 'unknown' is not a valid value of the atomic type " .
                        "'xs:boolean'.The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><totals><total " .
                        "name=\"i1\"><title translate=\"unknown\">Title</title><source_field>foo</source_field>" .
                        "</total></totals></config>\n2:\n",
                        false,
                    ],
                ],
            ]
        ];
    }
}
