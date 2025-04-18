<?php
/**
 * Copyright 2013 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

return [
    'tab_id_not_unique' => [
        '<?xml version="1.0"?><config><system><tab id="tab1"><label>Label One</label>' .
        '</tab><tab id="tab1"><label>Label Two</label></tab></system></config>',
        [
            [
                "Element 'tab': Duplicate key-sequence ['tab1'] in unique identity-constraint 'uniqueTabId'.\n" .
                "Line: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><tab id=\"tab1\"><label>" .
                "Label One</label></tab><tab id=\"tab1\"><label>Label Two</label></tab></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'section_id_not_unique' => [
        '<?xml version="1.0"?><config><system><section id="section1"><label>Label</label><tab>Tab</tab></section>' .
        '<section id="section1"><label>Label_One</label><tab>Tab_One</tab></section></system></config>',
        [
            [
                "Element 'section': Duplicate key-sequence ['section1'] in unique identity-constraint " .
                "'uniqueSectionId'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><section " .
                "id=\"section1\"><label>Label</label><tab>Tab</tab></section><section id=\"section1\"><label>" .
                "Label_One</label><tab>Tab_One</tab></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'field_id_not_unique' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label><field id="field_id" /><field id="field_id" /></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'field': Duplicate key-sequence ['field_id'] in unique identity-constraint 'uniqueFieldId'." .
                "\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><section id=\"section1\">" .
                "<group id=\"group1\"><label>Label</label><field id=\"field_id\"/><field id=\"field_id\"/></group>" .
                "<group id=\"group2\"><label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'field_element_id_not_expected' => [
        '<?xml version="1.0"?><config><system><section id="section1"><label>Label</label><field id="field_id">' .
        '</field><field id="new_field_id"/></section></system></config>',
        [
            [
                "Element 'field': This element is not expected.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"section1\"><label>Label</label><field id=\"field_id\"/><field " .
                "id=\"new_field_id\"/></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'group_id_not_unique' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label></group>' .
        '<group id="group1"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'group': Duplicate key-sequence ['group1'] in unique identity-constraint 'uniqueGroupId'.\n" .
                "Line: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><section id=\"section1\">" .
                "<group id=\"group1\"><label>Label</label></group><group id=\"group1\"><label>Label_One</label>" .
                "</group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'group_is_not_expected' => [
        '<?xml version="1.0"?><config><system><group id="group1"><label>Label</label><tab>Tab</tab></group>' .
        '<group id="group1"><label>Label_One</label><tab>Tab_One</tab></group></system></config>',
        [
            [
                "Element 'group': This element is not expected. Expected is one of ( tab, section ).\nLine: 1\n" .
                "The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><group id=\"group1\"><label>Label" .
                "</label><tab>Tab</tab></group><group id=\"group1\"><label>Label_One</label><tab>Tab_One</tab>" .
                "</group></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'upload_dir_is_not_expected' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label><field id="field_id" /><upload_dir config="node_one/node_two/node_three" scope_info="1">' .
        'node_one/node_two/node_three</upload_dir></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'upload_dir': This element is not expected.\nLine: 1\nThe xml was: \n0:" .
                "<?xml version=\"1.0\"?>\n1:<config><system><section id=\"section1\"><group id=\"group1\">" .
                "<label>Label</label><field id=\"field_id\"/><upload_dir config=\"node_one/node_two/node_three\" " .
                "scope_info=\"1\">node_one/node_two/node_three</upload_dir></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'upload_dir_with_invalid_type' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label><field id="field_id"><config_path>co</config_path>' .
        '</field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'config_path': [facet 'minLength'] The value has a length of '2'; this underruns the " .
                "allowed minimum length of '5'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"section1\"><group id=\"group1\"><label>Label</label><field " .
                "id=\"field_id\"><config_path>co</config_path></field></group><group id=\"group2\"><label>" .
                "Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
            [
                "/Element \'config_path\': .*\'co\' is not (a valid value|accepted).*/",
                true,
            ],
        ],
    ],
    'if_module_enabled_with_invalid_type' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label><field id="field_id"><if_module_enabled>Som</if_module_enabled>' .
        '</field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'if_module_enabled': [facet 'minLength'] The value has a length of '3'; this underruns the " .
                "allowed minimum length of '5'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"section1\"><group id=\"group1\"><label>Label</label><field " .
                "id=\"field_id\"><if_module_enabled>Som</if_module_enabled></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
            [
                "/Element \'if_module_enabled\': .*\'Som\' is not (a valid value|accepted).*/",
                true,
            ],
        ],
    ],
    'id_minimum length' => [
        '<?xml version="1.0"?><config><system><section id="s"><group id="gr">' .
        '<label>Label</label><field id="f"></field></group><group id="group1"><label>Label</label></group></section>' .
        '<tab id="h"><label>Label_One</label></tab></system></config>',
        [
            [
                "Element 'section', attribute 'id': [facet 'minLength'] The value 's' has a length of '1'; this " .
                "underruns the allowed minimum length of '2'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"s\"><group id=\"gr\"><label>Label</label><field id=\"f\"/></group>" .
                "<group id=\"group1\"><label>Label</label></group></section><tab id=\"h\"><label>Label_One</label>" .
                "</tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'field', attribute 'id': [facet 'minLength'] The value 'f' has a length of '1'; this " .
                "underruns the allowed minimum length of '2'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"s\"><group id=\"gr\"><label>Label</label><field id=\"f\"/></group>" .
                "<group id=\"group1\"><label>Label</label></group></section><tab id=\"h\"><label>Label_One</label>" .
                "</tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'tab', attribute 'id': [facet 'minLength'] The value 'h' has a length of '1'; this " .
                "underruns the allowed minimum length of '2'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"s\"><group id=\"gr\"><label>Label</label><field id=\"f\"/></group>" .
                "<group id=\"group1\"><label>Label</label></group></section><tab id=\"h\"><label>Label_One</label>" .
                "</tab></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'source_model_with_invalid_type' => [
        '<?xml version="1.0"?><config><system><section id="section1"><group id="group1">' .
        '<label>Label</label><field id="field_id"><source_model>Sour</source_model>' .
        '</field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'source_model': [facet 'minLength'] The value has a length of '4'; this underruns the " .
                "allowed minimum length of '5'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"section1\"><group id=\"group1\"><label>Label</label><field " .
                "id=\"field_id\"><source_model>Sour</source_model></field></group><group id=\"group2\"><label>" .
                "Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'base_url_with_invalid_type' => [
        '<?xml version="1.0"?><config><system><section id="section1"><resource>One:</resource>' .
        '<group id="group1"><label>Label</label><field id="field_id"></field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'resource': [facet 'minLength'] The value has a length of '4'; this underruns the allowed " .
                "minimum length of '8'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n" .
                "1:<config><system><section id=\"section1\"><resource>One:</resource><group id=\"group1\">" .
                "<label>Label</label><field id=\"field_id\"/></group><group id=\"group2\"><label>Label_One</label>" .
                "</group></section></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'resource': [facet 'pattern'] The value 'One:' is not accepted by the pattern " .
                "'([A-Z]+[a-zA-Z0-9]{1,}){1,}_[A-Z]+[A-Z0-9a-z]{1,}::[A-Za-z_0-9]{1,}'.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section id=\"section1\"><resource>One:</resource>" .
                "<group id=\"group1\"><label>Label</label><field id=\"field_id\"/></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'advanced_with_invalid_type' => [
        '<?xml version="1.0"?><config><system><section id="section1" advanced="string">' .
        '<group id="group1"><label>Label</label><field id="field_id"></field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'section', attribute 'advanced': 'string' is not a valid value of the atomic type " .
                "'xs:boolean'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><section " .
                "id=\"section1\" advanced=\"string\"><group id=\"group1\"><label>Label</label><field " .
                "id=\"field_id\"/></group><group id=\"group2\"><label>Label_One</label></group></section>" .
                "</system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'advanced_attribute_with_invalid_value' => [
        '<?xml version="1.0"?><config><system><section id="section1" advanced="string">' .
        '<group id="group1"><label>Label</label><field id="field_id" ></field></group>' .
        '<group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'section', attribute 'advanced': 'string' is not a valid value of the atomic type " .
                "'xs:boolean'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system><section " .
                "id=\"section1\" advanced=\"string\"><group id=\"group1\"><label>Label</label>" .
                "<field id=\"field_id\"/></group><group id=\"group2\"><label>Label_One</label></group></section>" .
                "</system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'options_node_without_any_options' => [
        '<?xml version="1.0"?><config><system><section id="section1" advanced="false">' .
        '<group id="group1"><label>Label</label><field id="field_id"><options />' .
        '</field></group><group id="group2"><label>Label_One</label></group></section></system></config>',
        [
            [
                "Element 'options': Missing child element(s). Expected is ( option ).\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section id=\"section1\" advanced=\"false\"><group " .
                "id=\"group1\"><label>Label</label><field id=\"field_id\"><options/></field></group><group " .
                "id=\"group2\"><label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'system_node_without_allowed_elements' => [
        '<?xml version="1.0"?><config><system/></config>',
        [
            [
                "Element 'system': Missing child element(s). Expected is one of ( tab, section ).\nLine: 1\n" .
                "The xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system/></config>\n2:\n",
                false,
            ],
        ],
    ],
    'config_node_without_allowed_elements' => [
        '<?xml version="1.0"?><config></config>',
        [
            [
                "Element 'config': Missing child element(s). Expected is ( system ).\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config/>\n2:\n",
                false,
            ],
        ],
    ],
    'config_without_required_attributes' => [
        '<?xml version="1.0"?><config><system><section><group>' .
        '<label>Label</label><attribute/><field><depends><field/></depends><options><option/></options></field>' .
        '</group><group id="group2"><label>Label_One' .
        '</label></group></section><tab><label>Label</label></tab></system>' .
        '</config>',
        [
            [
                "Element 'section': The attribute 'id' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/>" .
                "<field><depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'group': The attribute 'id' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/>" .
                "<field><depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'attribute': The attribute 'type' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/><field>" .
                "<depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'field': The attribute 'id' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/><field>" .
                "<depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'field': The attribute 'id' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/><field>" .
                "<depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'option': The attribute 'label' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/><field>" .
                "<depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
            [
                "Element 'tab': The attribute 'id' is required but missing.\nLine: 1\nThe xml was: \n" .
                "0:<?xml version=\"1.0\"?>\n1:<config><system><section><group><label>Label</label><attribute/>" .
                "<field><depends><field/></depends><options><option/></options></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section><tab><label>Label</label></tab></system></config>\n2:\n",
                false,
            ],
        ],
    ],
    'attribute_type_is_unique' => [
        '<?xml version="1.0"?><config><system><section id="name"><group id="name">' .
        '<label>Label</label><field id="name"><attribute type="one"/><attribute type="one"/></field>' .
        '</group><group id="group2"><label>Label_One</label></group></section></system>' .
        '</config>',
        [
            [
                "Element 'attribute': Duplicate key-sequence ['one'] in unique identity-constraint " .
                "'uniqueAttributeType'.\nLine: 1\nThe xml was: \n0:<?xml version=\"1.0\"?>\n1:<config><system>" .
                "<section id=\"name\"><group id=\"name\"><label>Label</label><field id=\"name\">" .
                "<attribute type=\"one\"/><attribute type=\"one\"/></field></group><group id=\"group2\">" .
                "<label>Label_One</label></group></section></system></config>\n2:\n",
                false,
            ],
        ],
    ]
];
