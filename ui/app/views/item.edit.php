<?php declare(strict_types = 0);
/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * @var CView $this
 * @var array $data
 */

$dir = '/../../include/views/js/';
$scripts = [
	$this->readJsFile('item.preprocessing.js.php', $data, $dir),
	$this->readJsFile('itemtest.js.php', $data + ['hostid' => $data['host']['hostid']], $dir)
];
$item = $data['item'];
$value_types = [
	ITEM_VALUE_TYPE_UINT64 => _('Numeric (unsigned)'),
	ITEM_VALUE_TYPE_FLOAT => _('Numeric (float)'),
	ITEM_VALUE_TYPE_STR => _('Character'),
	ITEM_VALUE_TYPE_LOG => _('Log'),
	ITEM_VALUE_TYPE_TEXT => _('Text'),
	ITEM_VALUE_TYPE_BINARY => _('Binary')
];
$type_with_key_select = [
	ITEM_TYPE_ZABBIX, ITEM_TYPE_ZABBIX_ACTIVE, ITEM_TYPE_SIMPLE, ITEM_TYPE_INTERNAL, ITEM_TYPE_DB_MONITOR,
	ITEM_TYPE_SNMPTRAP, ITEM_TYPE_JMX, ITEM_TYPE_IPMI
];
$form = (new CForm('post'))
	->setName('itemForm')
	->setId('item-form')
	->addItem((new CVar(CCsrfTokenHelper::CSRF_TOKEN_NAME, CCsrfTokenHelper::get('item')))->removeId())
	->addItem(getMessages())
	->addVar('context', $item['context'])
	->addVar('hostid', $data['host']['hostid'])
	->addVar('itemid', $item['itemid'] ? $item['itemid'] : null)
	->addVar('templateid', $item['itemid'] ? $item['templateid'] : null);

// Enable form submitting on Enter.
$form->addItem((new CSubmitButton())->addClass(ZBX_STYLE_FORM_SUBMIT_HIDDEN));

if (!$item['delay_flex']) {
	$item['delay_flex'] = [['delay' => '', 'period' => '', 'type' => ITEM_DELAY_FLEXIBLE]];
}

if (!$item['parameters']) {
	$item['parameters'] = [['name' => '', 'value' => '']];
}

if (!$item['query_fields']) {
	$item['query_fields'] = [['name' => '', 'value' => '']];
}

if (!$item['headers']) {
	$item['headers'] = [['name' => '', 'value' => '']];
}

if (!$item['tags']) {
	$item['tags'] = [['tag' => '', 'value' => '']];
}

if ($item['itemid']) {
	$buttons = [
		[
			'title' => _('Update'),
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'item_edit_form.update()'
		],
		[
			'title' => _('Clone'),
			'class' => ZBX_STYLE_BTN_ALT,
			'keepOpen' => true,
			'isSubmit' => false,
			'action' => 'item_edit_form.clone()'
		]
	];

	if ($data['host']['status'] != HOST_STATUS_TEMPLATE) {
		$buttons[] = [
			'title' => _('Execute now'),
			'class' => implode(' ', [ZBX_STYLE_BTN_ALT, 'js-execute-item']),
			'keepOpen' => true,
			'isSubmit' => false,
			'action' => 'item_edit_form.execute();'
		];
	}

	$buttons[] = [
		'title' => _('Test'),
		'class' => implode(' ', [ZBX_STYLE_BTN_ALT, 'js-test-item']),
		'keepOpen' => true,
		'isSubmit' => false,
		'action' => 'item_edit_form.test();'
	];

	if ($data['host']['status'] == HOST_STATUS_MONITORED || $data['host']['status'] == HOST_STATUS_NOT_MONITORED) {
		$confirmation = ($data['config']['compression_status'])
			? []
			: ['confirmation' => _('History clearing can take a long time. Continue?')];
		$buttons[] = $confirmation + [
			'title' => _('Clear history and trends'),
			'class' => ZBX_STYLE_BTN_ALT,
			'keepOpen' => true,
			'isSubmit' => false,
			'action' => 'item_edit_form.clear();'
		];
	}

	$buttons[] = [
		'title' => _('Delete'),
		'confirmation' => _('Delete selected item?'),
		'class' => ZBX_STYLE_BTN_ALT,
		'keepOpen' => true,
		'isSubmit' => false,
		'enabled' => !$item['templateid'],
		'action' => 'item_edit_form.delete();'
	];
}
else {
	$buttons = [
		[
			'title' => _('Add'),
			'keepOpen' => true,
			'isSubmit' => true,
			'action' => 'item_edit_form.create();'
		],
		[
			'title' => _('Test'),
			'class' => implode(' ', [ZBX_STYLE_BTN_ALT, 'js-test-item']),
			'keepOpen' => true,
			'isSubmit' => false,
			'action' => 'item_edit_form.test();'
		]
	];
}

$tabsid = 'items-tab';
$tabs = (new CTabView(['id' => $tabsid]))
	->setSelected(0)
	->addTab('item-tab', _('Item'),
		new CPartial('item.edit.item.tab', [
			'can_edit_source_timeouts' => $data['can_edit_source_timeouts'],
			'config' => $data['config'],
			'form_name' => $form->getName(),
			'host' => $data['host'],
			'inventory_fields' => $data['inventory_fields'],
			'item' => $item,
			'source' => 'item',
			'types' => $data['types'],
			'value_types' => $value_types,
			'type_with_key_select' => $type_with_key_select
		])
	)
	->addTab('tags-tab', _('Tags'),
		new CPartial('configuration.tags.tab', [
			'readonly' => $item['discovered'],
			'show_inherited_tags' => $item['show_inherited_tags'],
			'source' => 'item',
			'tabs_id' => $tabsid,
			'tags' => $item['tags'],
			'tags_tab_id' => 'tags-tab'
		]),
		TAB_INDICATOR_TAGS
	)
	->addTab('processing-tab', _('Preprocessing'),
		new CPartial('item.edit.preprocessing.tab', [
			'item' => $item,
			'preprocessing' => $item['preprocessing'],
			'preprocessing_types' => $data['preprocessing_types'],
			'readonly' => $item['templated'] || $item['discovered'],
			'value_types' => $value_types
		]),
		TAB_INDICATOR_PREPROCESSING
	);

$form
	->addItem($tabs)
	->addItem((new CScriptTag('item_edit_form.init('.json_encode([
			'actions' => [
				'form' => 'item.edit',
				'update' => 'item.update',
				'create' => 'item.create',
				'delete' => 'item.delete'
			],
			'field_switches' => CItemData::fieldSwitchingConfiguration(['is_discovery_rule' => false]),
			'form_data' => $item,
			'host' => $data['host'],
			'inherited_timeouts' => $data['inherited_timeouts'],
			'interface_types' => $data['interface_types'],
			'readonly' => $item['templated'] || $item['discovered'],
			'source' => 'item',
			'testable_item_types' => $data['testable_item_types'],
			'token' => [CCsrfTokenHelper::CSRF_TOKEN_NAME => CCsrfTokenHelper::get('item')],
			'type_with_key_select' => $type_with_key_select,
			'value_type_keys' => $data['value_type_keys']
		]).');'))->setOnDocumentReady()
	);
$output = [
	'header' => $data['item']['itemid'] ? _('Item') : _('New item'),
	'doc_url' => CDocHelper::getUrl(CDocHelper::DATA_COLLECTION_ITEM_EDIT),
	'body' => $form->toString().implode('', $scripts),
	'buttons' => $buttons,
	'script_inline' => getPagePostJs().$this->readJsFile('item.edit.js.php')
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
	CProfiler::getInstance()->stop();
	$output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);
