
<?	// Navigation links
	foreach($nav_pages as $url => $page){
		echo anchor('/dashboard'.$url, $page['name'],array('class'=>'button button-nav '.$page['active']));
	} 
?>
<hr/>

<? /* Generate QR Code Form */ ?>
<div id="actions-template" class="hidden">
	<span class="qr-wrap">
		<input type="text" id="qr-table-number" placeholder="Table Number"/>
		<input type="text" id="qr-server-name" placeholder="Server Name"/>
		<a href="javascript:qr_code()" class="button button-nav">Generate QR Code</a>
	</span>
</div>

<!--h1>Coming Soon</h1-->

<!--a href="javascript:send_text_offer_popup()" class="send-text-offer primary button float-right" style="margin:1px 0px">Send Text Offer</a-->
<table id="customer-table">
	<thead>
		<tr>
			<th>ID</td>
			<th class="datetime">Date</th>
			<th class="patron-name">Table Number</th>
			<th class="phone">Server Name</th>
			<th class="total-visits">Comments</th>
			<!--th class="actions">Action</th-->
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>

<script>var user_id=<?=$user['id']?>;</script>

<?/*
<div id="actions-template" class="hidden">
	<a href="javascript:void(0)" class="visit-details button blue">Visit Details</a>
</div>
*/?>
<div id="qr-print" style="height:300px;width:300px;padding-left:100px;text-align:center;-webkit-transform: rotate(-90deg);-moz-transform: rotate(-90deg);filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=3);">
<h1>TableQuick</h1>
<h3>Scan and connect<br/>with our manager</h3>
<img id="qr-print-img"/><br/>
<span id="qr-print-table-number"></span><br/>
<span id="qr-print-server-name"></span>
</div>