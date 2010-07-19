<?php

// *************************************************************************************
//SANITIZE ALL ESCAPES
// *************************************************************************************
$sanitize_all_escapes=true;

// *************************************************************************************
//STOP FAKE REGISTER GLOBALS
// *************************************************************************************
$fake_register_globals=false;


// *************************************************************************************
// Load the OpenEMR Libraries
// *************************************************************************************
include_once("../../globals.php");
include_once("$srcdir/sql.inc");
include_once("$srcdir/options.inc.php");

if (isset($_POST['mode'])) {
    
	// *************************************************************************************
	// Add new record
	// *************************************************************************************
    if ($_POST['mode'] == "add" ) {
        $sql = "REPLACE INTO immunizations set 
                      id = ?,
                      administered_date = if(?,?,NULL),  
                      immunization_id = ?,
                      manufacturer = ?,
                      lot_number = ?,
                      administered_by_id = if(?,?,NULL),
                      administered_by = if(?,?,NULL),
                      education_date = if(?,?,NULL), 
                      vis_date = if(?,?,NULL), 
                      note   = ?,
                      patient_id   = ?,
                      created_by = ?,
                      updated_by = ?,
                      create_date = now() ";
	$sqlBindArray = array(
             trim($_POST['id']),
		     trim($_POST['administered_date']), trim($_POST['administered_date']),
		     trim($_POST['immunization_id']),
		     trim($_POST['manufacturer']),
		     trim($_POST['lot_number']),
		     trim($_POST['administered_by_id']), trim($_POST['administered_by_id']),
		     trim($_POST['administered_by']), trim($_POST['administered_by']),
		     trim($_POST['education_date']), trim($_POST['education_date']),
		     trim($_POST['vis_date']), trim($_POST['vis_date']),
		     trim($_POST['note']),
		     $pid,
		     $_SESSION['authId'],
		     $_SESSION['authId']
		     );
        sqlStatement($sql,$sqlBindArray);
        $administered_date=$education_date=date('Y-m-d');
        $immunization_id=$manufacturer=$lot_number=$administered_by_id=$note=$id="";
        $administered_by=$vis_date="";
    }
	// *************************************************************************************
	// Delete a record
	// *************************************************************************************
    elseif ($_POST['mode'] == "delete" ) { // Need to be fixed, the GRID it's not calling the form for deletion.
        // log the event
        newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "Immunization id ".$_POST['id']." deleted from pid ".$pid);
        // delete the immunization
        $sql="DELETE FROM immunizations WHERE id =? LIMIT 1";
        sqlStatement($sql, array($_POST['id']));
    }
	// *************************************************************************************
	// Edit the record
	// *************************************************************************************
    elseif ($_POST['mode'] == "edit" ) { // Need to be fixed, the GRID it's not calling the form for edition.
        $sql = "select * from immunizations where id = ?";
        $results = sqlQ($sql, array($_POST['id']));
        while ($row = sqlFetchArray($results)) {
            $administered_date = $row['administered_date'];
            $immunization_id = $row['immunization_id'];
            $manufacturer = $row['manufacturer'];
            $lot_number = $row['lot_number'];
            $administered_by_id = ($row['administered_by_id'] ? $row['administered_by_id'] : 0);
            $administered_by = $row['administered_by'];
            $education_date = $row['education_date'];
            $vis_date = $row['vis_date'];
            $note = $row['note'];
        }
	//set id for page
	$id = $_POST['id'];
    }
}

// set the default sort method for the list of past immunizations
//$sortby = $_POST['sortby'];
//if (!$sortby) { $sortby = 'vacc'; }
// ^^ Not needed.

// set the default value of 'administered_by'
if (!$administered_by && !$administered_by_id) { 
    $stmt = "select concat(lname,', ',fname) as full_name ".
            " from users where ".
            " id=?";
    $row = sqlQuery($stmt, array($_SESSION['authId']));
    $administered_by = $row['full_name'];
}


// *************************************************************************************
// Sensha Ext JS Start
// New Gui Framework
// *************************************************************************************
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

// *************************************************************************************
// The Grid's Data
// The data, can be declared before the actual field is rendered.
// *************************************************************************************
var list_Data = [
<?php
        $sql = "SELECT
					i1.id,
					i1.immunization_id,
					i1.administered_date,
					i1.manufacturer,
					i1.lot_number,
					ifnull(concat(u.lname,', ',u.fname),'Other') as administered_by,
					i1.education_date,
					i1.note
				FROM
					immunizations i1 left join users u on i1.administered_by_id = u.id
				WHERE patient_id = ?";
        //if ($sortby == "vacc") { $sql .= " i1.immunization_id, i1.administered_date DESC"; }
        //else { $sql .= " administered_date desc"; }
		// ^^ Not needed Sensha Ext JS working for us now.
		
        $result = sqlStatement($sql, array($pid) );
        while ($row = sqlFetchArray($result)) {
            if ($row["id"] == $id) { 
                //echo "<tr class='immrow text selected' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }
            else {
                //echo "<tr class='immrow text' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }
			$buff .= "['" . generate_display_field(array('data_type'=>'1','list_id'=>'immunizations'), $row['immunization_id']) . "'," .
				 "'" . htmlspecialchars( $row["administered_date"], ENT_NOQUOTES) . "'," .
				 "'" . htmlspecialchars( $row["manufacturer"], ENT_NOQUOTES) . "'," .
				 "'" . htmlspecialchars( $row["lot_number"], ENT_NOQUOTES) . "'," .
				 "'" . htmlspecialchars( $row["administered_by"], ENT_NOQUOTES) . "'," .
				 "'" . htmlspecialchars( $row["education_date"], ENT_NOQUOTES) . "'," . 
				 "'" . htmlspecialchars( $row["note"], ENT_NOQUOTES) . "'],". chr(13);
        }
		echo substr($buff, 0, -2); $buff=NULL; // Delete the last comma and clear the buff.

?>
];

var store = new Ext.data.ArrayStore({
		fields: [
			{name: 'vaccine'},
			{name: 'date', type: 'date'},
			{name: 'manufacturer'},
			{name: 'lotnumber'},
			{name: 'administered'},
			{name: 'edudate', type: 'date'},
			{name: 'note'}
		]
});

store.loadData(list_Data);

// *************************************************************************************
// Create the TAB Panel
// Keep in mind that the TAB panel it's a container and every tab (item) has to contain 
// the window itself like (Grid's, Forms, Charts, ect).
// *************************************************************************************
var viewport = new Ext.Viewport({
    layout:'fit',
	renderTo: document.body,
	items:[{
		xtype: 'tabpanel',		   
		activeTab: 0,
		width: '100%',
		height: 380,
		frame: false,
		border: false,
		items: [
			// *************************************************************************************
			// The Form Immunizations
			// *************************************************************************************
			{ 
				title: '<?php echo htmlspecialchars( xl('Immunizations'), ENT_NOQUOTES); ?>',
				xtype: 'form',
				labelWidth: 300,
				frame: true,
				url: 'immunizations.php',
				bodyStyle: 'padding: 5px',
				formBind: true,
				defaults: { width: 290 },
				buttonAlign: 'left',
				standardSubmit: true,
				items: [
					{ xtype: 'textfield', hidden: true, name: 'mode', value: 'add' },
					{ xtype: 'textfield', hidden: true, name: 'id', value: '<?php echo htmlspecialchars( $id, ENT_QUOTES); ?>' },
					{ xtype: 'textfield', hidden: true, name: 'pid', value: '<?php echo htmlspecialchars( $pid, ENT_QUOTES); ?>' },
					new Ext.form.ComboBox({ name: 'immunization_id', fieldLabel: '<?php echo htmlspecialchars( xl('Immunization'), ENT_NOQUOTES); ?>', editable: false, triggerAction: 'all', mode: 'local',
						store: new Ext.data.ArrayStore({
							id: 'immunization_id',
							fields: [ 'option_id', 'title' ],
							data: [
							<?php
        			        	$sql = "SELECT 
											* 
                    					FROM 
											list_options
										WHERE
											list_id = 'immunizations' 
										ORDER BY
											seq, title";
			                $result = sqlStatement($sql);
    	    		        while($row = sqlFetchArray($result)){
								$buff .= " [ " . htmlspecialchars( $row{'option_id'}, ENT_QUOTES) . ", '" . htmlspecialchars( $row{'title'}, ENT_NOQUOTES) . "' ],";
								//echo (isset($administered_by_id) && $administered_by_id != "" ? $administered_by_id : $_SESSION['authId']) == $row{'id'} ? ' selected>' : '>';
        			        }
							echo substr($buff, 0, -1); $buff=NULL; // Delete the last comma and clear the buff.
							?> 
							]
						}),
					valueField: 'option_id',
					hiddenName: 'immunization_id',
					displayField: 'title'
				}),
				{ xtype: 'datefield', name: 'administered_date', fieldLabel: '<?php echo htmlspecialchars( xl('Date Administered'), ENT_NOQUOTES); ?>', value: '<?php echo $administered_date ? htmlspecialchars( $administered_date, ENT_QUOTES) : date('Y-m-d'); ?>' },
				{ xtype: 'textfield', name: 'manufacturer', fieldLabel: '<?php echo htmlspecialchars( xl('Immunization Manufacturer'), ENT_NOQUOTES); ?>', value: '<?php echo htmlspecialchars( $manufacturer, ENT_QUOTES); ?>' },
				{ xtype: 'textfield', name: 'lot_number', fieldLabel: '<?php echo htmlspecialchars( xl('Immunization Lot Number'), ENT_NOQUOTES); ?>', value: '<?php echo htmlspecialchars( $lot_number, ENT_QUOTES); ?>' },
				{ xtype: 'textfield', name: 'administered_by', fieldLabel: '<?php echo htmlspecialchars( xl('Name and Title of Immunization Administrator'), ENT_NOQUOTES); ?>', value: '<?php echo htmlspecialchars( $administered_by, ENT_QUOTES); ?>' },
				new Ext.form.ComboBox({ name: 'administered_by_id', fieldLabel: 'or choose', editable: false, triggerAction: 'all', mode: 'local',
					store: new Ext.data.ArrayStore({
					id: 'administered_by_id',
						fields: [ 'id', 'full_name' ],
						data: [
						<?php
                			$sql = "SELECT 
										id, 
										concat(lname,', ',fname) as full_name 
        		            		FROM 
										users
									WHERE
										username != '' 
									ORDER BY
										concat(lname,', ',fname)";

                		$result = sqlStatement($sql);
		                while($row = sqlFetchArray($result)){
							$buff .= " [ " . htmlspecialchars( $row{'id'}, ENT_QUOTES) . ", '" . htmlspecialchars( $row{'full_name'}, ENT_NOQUOTES) . "' ],";
							//echo (isset($administered_by_id) && $administered_by_id != "" ? $administered_by_id : $_SESSION['authId']) == $row{'id'} ? ' selected>' : '>';
		                }
						echo substr($buff, 0, -1); $buff=NULL; // Delete the last comma and clear the buff.
		              ?> 
					  ]
					}),
					valueField: 'id',
					hiddenName: 'administered_by_id',
					displayField: 'full_name'
				}),

				{ xtype: 'datefield', name: 'education_date', fieldLabel: '<?php echo htmlspecialchars( xl('Date Immunization Information Statements Given'), ENT_NOQUOTES); ?>', value: '<?php echo $education_date? htmlspecialchars( $education_date, ENT_QUOTES) : date('Y-m-d'); ?>' }, 
				{ xtype: 'datefield', id: 'vis', name: 'vis_date', fieldLabel: '<?php echo htmlspecialchars( xl('Date of VIS Statement'), ENT_NOQUOTES); ?>', value: '<?php echo $vis_date ? htmlspecialchars( $vis_date, ENT_QUOTES) : date('Y-m-d'); ?>' },
				{ xtype: 'textarea', name: 'note', fieldLabel: '<?php echo htmlspecialchars( xl('Notes'), ENT_NOQUOTES); ?>', value: '<?php echo htmlspecialchars( $note, ENT_NOQUOTES); ?>' },
			],
			buttons: [ // Need to be finished, the PDF and HTML are not ready.
				new Ext.Button({ type: 'submit', name: 'save', text: '<?php echo htmlspecialchars( xl('Save Immunization'), ENT_QUOTES); ?>', handler: function() { form.getForm().submit(); } }),
				new Ext.Button({ name: 'print', text: '<?php echo htmlspecialchars( xl('Print Record') . xl('PDF','',' (',')'), ENT_QUOTES); ?>', handler: function(){ } }), 
				new Ext.Button({ name: 'printHtml', text: '<?php echo htmlspecialchars( xl('Print Record') . xl('HTML','',' (',')'), ENT_QUOTES); ?>' }), 
				new Ext.Button({ type: 'reset', name: 'clear', text: '<?php echo htmlspecialchars( xl('Clear'), ENT_QUOTES); ?>', handler: function() { form.getForm().reset(); } })
			]

		},
		// *************************************************************************************
		// Render the GridPanel
		// *************************************************************************************
		{ 
			title: 'Patient Immunizations List', 
			xtype: 'grid', 
			store: store,
			stripeRows: true,
			frame: true,
			columns: [
				{ width: 200, id: 'vaccine', header: '<?php echo htmlspecialchars( xl('Vaccine'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'vaccine' },
				{ header: '<?php echo htmlspecialchars( xl('Date'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'date' },
				{ header: '<?php echo htmlspecialchars( xl('Manufacturer'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'manufacturer' },
				{ header: '<?php echo htmlspecialchars( xl('Lot Number'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'lotnumber' }, 
				{ header: '<?php echo htmlspecialchars( xl('Administered By'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'administered' },
				{ header: '<?php echo htmlspecialchars( xl('Education Date'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'edudate' },
				{ header: '<?php echo htmlspecialchars( xl('Note'), ENT_NOQUOTES); ?>', sortable: true, dataIndex: 'note' }
			]}
	]

}]
});

});
</script>
</head>
<body class=" ext-gecko ext-gecko2 x-border-layout-ct">
</body>
