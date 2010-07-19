<?php

// *************************************************************************************
// Load the OpenEMR Libraries
// *************************************************************************************
require_once("../../globals.php");
require_once("../../../custom/code_types.inc.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/formatting.inc.php");

// *************************************************************************************
// function bucks 
// *************************************************************************************
function bucks($amount) {
  if ($amount) {
    $amount = oeFormatMoney($amount);
    return $amount;
  }
  return '';
}

?>

<head>
<script type="text/javascript" src="/library/extjs-3.2.1/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/library/extjs-3.2.1/ext-all.js"></script>
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/resources/css/ext-all.css">
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/examples/form/forms.css">
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/examples/examples.css">
  <link rel="stylesheet" type="text/css" href="/interface/themes/style_newui.css" >
<script type="text/javascript">

Ext.onReady(function(){
					 
Ext.QuickTips.init();

Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

// *************************************************************************************
// Data for Search combobox
// *************************************************************************************
var Search_list = [
	[0, 'All'],
	<?php
	foreach ($code_types as $key => $value) { $buff .= "[" . $value['id'] . ", '" . $key . "']," . chr(13); }
	echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
	?>
];
var search_data = new Ext.data.ArrayStore({ // create the data store
	id: 'search_id',
	fields: [ {name: 'id'}, {name: 'value'}	]
});
search_data.loadData(Search_list);

// *************************************************************************************
// Data for Codes DataGrid
// *************************************************************************************
var code_list = [
<?php
$res = sqlStatement("SELECT
						*
					FROM
						codes
					ORDER BY 
						code_type, 
						code,
						code_text");
for ($i = 0; $row = sqlFetchArray($res); $i++) $all[$i] = $row;
foreach($all as $iter) {
	// 1. Codes Data
	//-----------------
	$buff .= "['" . $iter["code"] . "','" . $iter["modifier"] . "','" . ($iter["active"] ? xl('Yes') : xl('No')) . "','" . $iter['code_text'] . "',";
	// 2. Related Data
	//-----------------
	if (related_codes_are_used()) {
		$arel = explode(';', $iter['related_code']);
		foreach ($arel as $tmp) {
			list($reltype, $relcode) = explode(':', $tmp);
			$reltype = $code_types[$reltype]['id'];
			$relrow = sqlQuery("SELECT
							   		code_text
								FROM
									codes
								WHERE 
									code_type = '$reltype' AND code = '$relcode' LIMIT 1");
			$buff .= "'" . $relcode . ' ' . trim($relrow['code_text']) . "',";
		}
	}
	// 3. Price Data
	//-----------------
	$pres = sqlStatement("SELECT
						 		p.pr_price 
							FROM
								list_options AS lo LEFT OUTER JOIN prices AS p ON p.pr_id = '" . $iter['id'] . "' AND p.pr_selector = '' AND p.pr_level = lo.option_id 
							WHERE
								list_id = 'pricelevel'
							ORDER BY
								lo.seq");
	while ($prow = sqlFetchArray($pres)) {
		$buff .= "'" . bucks($prow['pr_price']) . "',";
	}
	$buff = substr($buff, 0, -1);
	$buff = $buff . "],".chr(13);
}
echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
?>
];
var store = new Ext.data.ArrayStore({ // create the data store
	fields: [
		{name: 'code'},
		{name: 'mod'},
		{name: 'act'},
		{name: 'description'},
		// *************************************************************************************
		// Related Code Columns
		// *************************************************************************************
		<?php if (related_codes_are_used()) {
				$arel = explode(';', $iter['related_code']);
				foreach ($arel as $tmp) {
					list($reltype, $relcode) = explode(':', $tmp);
					$reltype = $code_types[$reltype]['id'];
					$relrow = sqlQuery("SELECT
									   		code_text
									   FROM
									   		codes
										WHERE 
											code_type = '$reltype' AND code = '$relcode'
										LIMIT 1");
					$buff .= "{name: '" . $relcode . " " . trim($relrow['code_text']) . "'}," . chr(13);
				}
		}
		?>
		// *************************************************************************************
		// Price Columns
		// *************************************************************************************
		<?php
			$pres = sqlStatement("SELECT
								 	title
								FROM
									list_options 
								 WHERE
								 	list_id = 'pricelevel'
								ORDER BY seq");
			while ($prow = sqlFetchArray($pres)) {
				$buff .= "{name: '" . xl_list_label($prow['title']) . "'},".chr(13);
			}
			echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
		?>
	]
});
// load the data
store.loadData(code_list);

// *************************************************************************************
// Data for Types Field ComboBox
// *************************************************************************************
var typeData_list = [
	<?php
		foreach ($code_types as $key => $value) { $buff .= " [ " . $value['id'] . ", '" . $key . "' ]," . chr(13); }
		echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
	?> 
];
var typeData = new Ext.data.ArrayStore({ // create the data store
	id: 'types_id',
	fields: [ 'id', 'value' ]
});
typeData.loadData(typeData_list);

// *************************************************************************************
// Data for Category Field ComboBox
// FIX ME! 
// I don't know where to get this data.
// *************************************************************************************
var catData_list = [
<?php
	$buff .= " [ 0 , 'Unassigned' ]," .chr(13);
	echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
?> 
];

// *************************************************************************************
// Service Add Window
// *************************************************************************************
var winAdd = new  Ext.Window({
	width:400,
	autoHeight: true,
	modal: true,
	resizable: false,
	autoScroll:true,
	title:	'Add a new service',
	closeAction: 'hide',
	items: [{
		xtype: 'form',
		bodyStyle: 'padding: 5px',
		formBind: true,
		flame: false,
		standardSubmit: true,
		items: [
			{ xtype: 'combo', autoWidth: true, name: 'code_type', fieldLabel: '<?php xl('Type','e'); ?>', editable: false, triggerAction: 'all', mode: 'local', store: typeData, valueField: 'id', hiddenName: 'types_id', displayField: 'value'},
			{ xtype: 'textfield', name: 'code', fieldLabel: 'Code', value: '<?php echo $code ?>' },
			{ xtype: 'textfield', name: 'modifier', fieldLabel: 'Modifier', value: '<?php echo $modifier ?>' },
			{ xtype: 'textfield', name: 'code_text', fieldLabel: '<?php xl('Description','e'); ?>', value: '<?php echo $code_text ?>' },
			{ xtype: 'combo', autoWidth: true, name: 'code_type', fieldLabel: '<?php xl('Category','e'); ?>', editable: false, triggerAction: 'all', mode: 'local', store: catData_list, valueField: 'id', hiddenName: 'list_id', displayField: 'value', value: 0},
			// *************************************************************************************
			// Fees Fieldset
			// *************************************************************************************
			{ 
				xtype:'fieldset', 
				title: '<?php xl('Fees','e'); ?>',
				autoHeight:true,
				items :[
						<?php
							$pres = sqlStatement("SELECT 
							 	lo.option_id,
								lo.title,
								p.pr_price
							FROM 
								list_options AS lo LEFT OUTER JOIN prices AS p ON
								p.pr_id = '$code_id' AND p.pr_selector = '' AND p.pr_level = lo.option_id
							WHERE
								list_id = 'pricelevel' ORDER BY lo.seq");
							for ($i = 0; $prow = sqlFetchArray($pres); ++$i) {
								$buff .= "{ xtype: 'textfield', name: 'fees[" . $prow['option_id'] . "]', fieldLabel: '" . xl_list_label($prow['title']) . "', value: '" . $prow['pr_price'] . "'}," . chr(13);
							}
							echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
						?>
						]
			},
			// *************************************************************************************
			// Tax Fieldset
			// *************************************************************************************
			<?php if ($taxline) { ?>
			{
				xtype:'fieldset', 
				title: '<?php xl('Taxes','e'); ?>',
				autoHeight:true,
				items :[
						<?php
							$taxline = '';
							$pres = sqlStatement("SELECT 
												 	option_id,
													title
												FROM
													list_options
												WHERE
													list_id = 'taxrate'
												ORDER BY seq");
							while ($prow = sqlFetchArray($pres)) {
								$buff .= "{ xtype: 'checkbox', fieldLabel: '" . $prow['title'] . "', name: 'taxrate[" . $prow['option_id'] . "]', value: '1', checked: ";
								if (strpos(":$taxrates", $prow['option_id']) !== false){ $buff .= "true"; } else { $buff .= "false"; }
								$buff .= "}," . chr(13);
							}
							echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
						?>
						]
			},
			<?php } ?>
			{ xtype: 'checkbox', name: 'active', fieldLabel: 'Active', value: '', checked: <?php if (!empty($active)){echo 'true';}else{echo'false';} ?> }
		]
	}],
	bbar:[{
		text:'Save',
		iconCls: 'save',
		handler: function() { form.getForm().submit(); }
	},{
		text:'Close',
		iconCls: 'delete',
		handler: function(){ winAdd.hide(); }
	}]
});

// *************************************************************************************
// Application itself (viewport browser)
// *************************************************************************************
var viewport = new Ext.Viewport({
    layout:'fit',
	renderTo: document.body,
	items:[{
		// *************************************************************************************
		// DataGrid - Codes
		// *************************************************************************************
		xtype: 'grid',
		store: store,
		deferRowRender: false,
		title: 'Services',
        stripeRows: true,
        autoExpandColumn: 'description',
		frame: true,
        columns: [
			{header: '<?php xl('Code','e'); ?>', width: 75, sortable: true, dataIndex: 'code'},
            {header: '<?php xl('Mod','e'); ?>', width: 75, sortable: true, dataIndex: 'mod'},
            {header: '<?php xl('Act','e'); ?>', width: 75, sortable: true, dataIndex: 'act'},
			{id:'description', header: '<?php xl('Description','e'); ?>', width: 175, sortable: true, dataIndex: 'description'},
			// *************************************************************************************
			// Related Code Columns (GRID)
			// *************************************************************************************
			<?php if (related_codes_are_used()) {
					$arel = explode(';', $iter['related_code']);
					foreach ($arel as $tmp) {
						list($reltype, $relcode) = explode(':', $tmp);
						$reltype = $code_types[$reltype]['id'];
						$relrow = sqlQuery("SELECT
										   		code_text
										   FROM
										   		codes
											WHERE 
												code_type = '$reltype' AND code = '$relcode'
											LIMIT 1");
						$buff .= "{header: '" . xl('Related','e') . "', width: 75, sortable: true, dataIndex: 'related'}," . chr(13);
					}
			}
			?>
			// *************************************************************************************
			// Price Columns
			// *************************************************************************************
			<?php
				$pres = sqlStatement("SELECT
									 	title
									FROM
										list_options 
									 WHERE
									 	list_id = 'pricelevel'
									ORDER BY seq");
				while ($prow = sqlFetchArray($pres)) {
					$buff .= "{header: '" . xl_list_label($prow['title']) . "', width: 75, sortable: true, dataIndex: '" . xl_list_label($prow['title']) . "'},".chr(13);
				}
				echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.
			?>
        ],
		// *************************************************************************************
		// Grid Menu
		// *************************************************************************************
		tbar: [{
			xtype:'button',
			text: 'Add New Service',
			iconCls: 'addService',
			handler: function(){ winAdd.show(); }
		},{
			xtype:'button',
			text: 'Edit Service',
			iconCls: 'edit',
			disabled: true
		},{
			xtype:'button',
			text: 'Delete Service',
			iconCls: 'delete',
			disabled: true
		},'-',{
			xtype: 'combo',
			name: 'filter',
			width: 70,
			fieldLabel:'Search',
			editable: false,
			triggerAction: 'all',
			mode: 'local',
			store: search_data,
			valueField: 'id',
			hiddenName: 'search_id',
			displayField: 'value',
			forceSelection: true,
			value: 0
		},{
			xtype: 'textfield',
			name: 'search',
			emptyText:'Search terms...',
			value: '<?php echo $search ?>'
		},{
			xtype:'button',
			text: 'Search',
			iconCls: 'searchData',
		}]
	}]
});

});
</script>
</head>
<body class="ext-gecko ext-gecko2 x-border-layout-ct">
</body>