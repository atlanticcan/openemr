<?php

// *************************************************************************************
// Load the OpenEMR Libraries
// *************************************************************************************
include_once("../globals.php");
include_once("$srcdir/sql.inc");
include_once("$srcdir/options.inc.php");

?>

<head>
<script type="text/javascript" src="/library/extjs-3.2.1/adapter/ext/ext-base.js"></script>
<script type="text/javascript" src="/library/extjs-3.2.1/ext-all.js"></script>
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/resources/css/ext-all.css">
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/examples/form/forms.css">
  <link rel="stylesheet" type="text/css" href="/library/extjs-3.2.1/examples/examples.css">
<script type="text/javascript">

Ext.onReady(function(){
					 
Ext.QuickTips.init();

Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

var types_list = [
        ['3m Co',71.72,0.02,0.03]
    ];


    // create the data store
var store = new Ext.data.ArrayStore({
	fields: [
		{name: 'name'},
		{name: 'order'},
		{name: 'code'},
		{name: 'description'},
	]
});

store.loadData(types_list);

var viewport = new Ext.Viewport({
    layout:'fit',
	renderTo: document.body,
    anchorSize: {width:'100%', height:'100%'},
	items:[{
		xtype: 'grid',
		store: store,
		deferRowRender: false,
        columns: [
            {id:'name',header: '<?php xl('Name','e') ?>', width: 160, sortable: true, dataIndex: 'name'},
            {header: '<?php xl('Order','e') ?>', width: 75, sortable: true, dataIndex: 'order'},
            {header: '<?php xl('Code','e') ?>', width: 75, sortable: true, dataIndex: 'code'},
            {header: '<?php xl('Description','e') ?>', width: 75, sortable: true, dataIndex: 'description'}
        ],
        stripeRows: true,
        autoExpandColumn: 'name',
        title: '<?php xl('Types of Orders and Results','e') ?>',
		frame: true
	}]
});


});
</script>
</head>
<body class="ext-gecko ext-gecko2 x-border-layout-ct">
</body>