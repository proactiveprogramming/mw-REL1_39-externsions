(function( $ ) {

	function failed(){
		alert( "Failed doing last operation, check internet connection!" );
	};

	function success(data){
		if ( typeof( data.result ) != 'undefined' ) {
			if ( typeof( data.result.notification ) != 'undefined' ) {
				$.each( data.result.notification, function( k, v ) {
					mw.notify( v );
				} );
			}

			if ( typeof( data.result.campaign_html ) != "undefined" ) {
				$( '#camp_list' ).html( data.result.campaign_html );
			} else {
				refreshPage();
			}
			if ( typeof( data.result.adsets_html ) != "undefined" ) {
				$( '#ad_sets_list' ).html( data.result.adsets_html );
			}
			if ( typeof( data.result.ads_html ) != "undefined" ) {
				$( '#ads_list' ).html( data.result.ads_html );
			}
			if ( typeof( data.result.targeting_html ) != "undefined" ) {
				$( '#ad_target_list' ).html( data.result.targeting_html );
			}
			if ( typeof( data.result.stats_html ) != "undefined" ) {
				$( '#stats_list' ).html( data.result.stats_html );
			}
			$( 'sortable' ).tablesorter();

			if (typeof(data.result.success) != 'undefined') {
				mw.notify(data.result.success);
				$('.temp-data').remove();
			} else if ( typeof( data.result.error ) != 'undefined' ) {
				mw.notify("Error: " + data.result.error.info );
			} else {
				alert("ERROR " + data.result.failed);
			}
		}

		if ( typeof ( data.error ) != 'undefined' ) {
			if ( typeof( data.error.info ) != 'undefined' ) {
				alert( "ERROR : " + data.error.info );
			}
		}
		initBind();
	};

	function initBind() {
		$( '.api_action').unbind().on("click", function(){
			_this = $(this);
			$.ajax({
			  url:wgScriptPath + '/api.php',
			  type:"POST",
			  data: _this.data(),
			  success: function ( data ) {
				success(data);
				return false;
			  }
			});
			return false;
		});
		$('.ad_edit').unbind().click( function() {
			_this = $( this );
			var data = { 'action': 'banner_ads', 'ba_action': 'get_adsets', 'format': 'json' };
			$.get(wgScriptPath + '/api.php', data, function( data ) {
				formContent = $.parseHTML( 
					'<form id="create_ad_form">' +
						'<input name="action" value="banner_ads" type="hidden"/>' +
						'<input name="ba_action" value="create_ad" type="hidden"/>' +
						'<input name="ad_id" value="'+ _this.data( 'id' ) +'" type="hidden"/>' +
						'<input name="format" value="json" type="hidden"/>' +
						'<div class="container-fluid">' +
							'<div style="text-align:left;">' +
								'<div>Ad Name: <br><input type="text" name="name" value="'+ _this.data( 'name' ) +'"></div>' +
								'<div>Campaign: <br><select name="adset_id" id="adset_id"></select></div>' +
								'<div>Ad Type: <select name="ad_type" id="ad_type"></select></div>' +
								'<div>Upload Ad Image: <br><input type="file" name="ad_img"></div>' +
								'<div>Ad Click URL (URL should start with http:// or https://): <br><input type="text" name="ad_url" value="'+ _this.data( 'ad_url' ) +'"></div>' +
							'</div>' +
						'</div>' +
					'</form>'
				);
				$.each( data.result.adsets, function( k, v ) {
					if ( k == _this.data( 'adset_id' ) ) {
						$( formContent ).find( '#adset_id' ).append( '<option value="'+ k +'" selected="selected">'+ v + '</option>' );
					} else {
						$( formContent ).find( '#adset_id' ).append( '<option value="'+ k +'">'+ v + '</option>' );
					}
				} );

				$.each( { 0: "Mobile Top", 1: "Mobile Bottom Sticky", 2: "Mobile Splash" }, function( k, v ) {
					if ( k == _this.data( 'ad_type' ) ) {
						$( formContent ).find( '#ad_type' ).append( '<option value="'+ k +'" selected="selected">'+ v + '</option>' );
					} else {
						$( formContent ).find( '#ad_type' ).append( '<option value="'+ k +'">'+ v + '</option>' );
					}
				} );

				form_id = "create_ad_form";
				form_name = "Edit Ad";
				$.confirm({
					theme: 'modern',
					columnClass: 'large',
					title: form_name,
					content: function() {
						content = formContent;
						return content[0].outerHTML;
					},
					type: 'orange',
					typeAnimated: true,
					buttons: {
						confirm: {
							text: 'Save',
							btnClass: 'btn-orange',
							action: function () {
								mw.notify( "Processing..." );
								var formData = new FormData($( '#' + form_id )[0]);
								$.ajax({  
									type: "POST",  
									url: wgScriptPath + '/api.php',
									data: formData,
									async: false,
									cache: false,
									contentType: false,
									processData: false,
									success: function(data) {
										success(data);
									}
								});
							}
						},
						cancel: function () {
						}
					}
				});
			});
		});
		$('.camp_edit').unbind().click( function() {
			_this = $( this );
			formContent = $.parseHTML( 
				'<form id="create_campaign">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_camp" type="hidden"/>' +
					'<input name="camp_id" value="'+ _this.data('id') +'" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Campaign Name: <br><input type="text" name="name" value="'+ _this.data('name') +'"></div>' +
							'<div>Start Date: (Format 23/04/20)<br><input type="text" name="start_date" value="'+ _this.data('start_date') +'"></div>' +
							'<div>End Date: (Format 23/04/20)<br><input type="text" name="end_date" value="'+ _this.data('end_date') +'"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Edit Campaign", "create_campaign", formContent );
		});

	}

	function refreshPage() {
		$.post(
			wgScriptPath + '/api.php',
			{ 'action': 'banner_ads', 'ba_action': 'fetch_ad_display', 'format': 'json' },
			function(data) {
				success(data);
			}
		).fail(failed);
	};

	function showForm( form_name, form_id, html ) {
		$.confirm({
			theme: 'modern',
			columnClass: 'large',
			title: form_name,
			content: function() {
				content = html;
				return content[0].outerHTML;
			},
			type: 'orange',
			typeAnimated: true,
			buttons: {
				confirm: {
					text: 'Create',
					btnClass: 'btn-orange',
					action: function () {
						mw.notify( "Processing..." );
						$.post(
							wgScriptPath + '/api.php',
							$( '#' + form_id ).serialize(),
							function(data) {
								success(data);
							}
						).fail(failed);
					}
				},
				cancel: function () {
				}
			}
		});
	};

	$(document).ready(function () { //jquery
		$('#tabs a').click(function (e) {
			e.preventDefault();
			$(this).tab('show');
			tab = $(this).attr('href');
		});

		$('#create_camp').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_campaign">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_camp" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Campaign Name: <br><input type="text" name="name"></div>' +
							'<div>Start Date: (Format 23/04/20)<br><input type="text" name="start_date"></div>' +
							'<div>End Date: (Format 23/04/20)<br><input type="text" name="end_date"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Create Campaign", "create_campaign", formContent );
		});


		$('#create_adset').click( function() {
			formContent = $.parseHTML( 
				'<form id="create_adset_form">' +
					'<input name="action" value="banner_ads" type="hidden"/>' +
					'<input name="ba_action" value="create_adset" type="hidden"/>' +
					'<input name="format" value="json" type="hidden"/>' +
					'<div class="container-fluid">' +
						'<div style="text-align:left;">' +
							'<div>Name: <br><input type="text" name="name"></div>' +
						'</div>' +
					'</div>' +
				'</form>'
			);
			showForm( "Create Adset", "create_adset_form", formContent );
		});

		$('#create_ad').click( function() {
			var data = { 'action': 'banner_ads', 'ba_action': 'get_adsets', 'format': 'json' };
			$.get(wgScriptPath + '/api.php', data, function( data ) {
				formContent = $.parseHTML( 
					'<form id="create_ad_form">' +
						'<input name="action" value="banner_ads" type="hidden"/>' +
						'<input name="ba_action" value="create_ad" type="hidden"/>' +
						'<input name="format" value="json" type="hidden"/>' +
						'<div class="container-fluid">' +
							'<div style="text-align:left;">' +
								'<div>Ad Name: <br><input type="text" name="name"></div>' +
								'<div>Campaign: <br><select name="adset_id" id="adset_id"></select></div>' +
								'<div>Ad Type: <select name="ad_type"><option value="0">Mobile Top</option><option value="1">Mobile Bottom Sticky</option><option value="2">Mobile Splash</option></select></div>' +
								'<div>Upload Ad Image: <br><input type="file" name="ad_img"></div>' +
								'<div>Ad Click URL (URL should start with http:// or https://): <br><input type="text" name="ad_url"></div>' +
							'</div>' +
						'</div>' +
					'</form>'
				);
				$.each( data.result.adsets, function( k, v ) {
					$( formContent ).find( '#adset_id' ).append( '<option value="'+ k +'">'+ v + '</option>' );
				} );

				form_id = "create_ad_form";
				form_name = "Create Ad";
				$.confirm({
					theme: 'modern',
					columnClass: 'large',
					title: form_name,
					content: function() {
						content = formContent;
						return content[0].outerHTML;
					},
					type: 'orange',
					typeAnimated: true,
					buttons: {
						confirm: {
							text: 'Create',
							btnClass: 'btn-orange',
							action: function () {
								mw.notify( "Processing..." );
								var formData = new FormData($( '#' + form_id )[0]);
								$.ajax({  
									type: "POST",  
									url: wgScriptPath + '/api.php',
									data: formData,
									async: false,
									cache: false,
									contentType: false,
									processData: false,
									success: function(data) {
										success(data);
									}
								});
							}
						},
						cancel: function () {
						}
					}
				});
			});
		});

		$('#add_target').click( function() {
			var data = { 'action': 'banner_ads', 'ba_action': 'get_campaigns', 'format': 'json' };
			$.get(wgScriptPath + '/api.php', data, function( data ){
				formContent = $.parseHTML( 
					'<form id="add_new_target">' +
						'<input name="action" value="banner_ads" type="hidden"/>' +
						'<input name="ba_action" value="add_target" type="hidden"/>' +
						'<input name="format" value="json" type="hidden"/>' +
						'<div class="container-fluid">' +
							'<div style="text-align:left;">' +
								'<div>Campaign: <br><select name="camp_id" id="camp_id"></select></div>' +
								'<div>Page Name: <br><input type="text" name="title"></div>' +
							'</div>' +
						'</div>' +
					'</form>'
				);
				$.each( data.result.campaigns, function( k, v ) {
					$( formContent ).find( '#camp_id' ).append( '<option value="'+ k +'">'+ v + '</option>' );
				} );
				showForm( "Add Target", "add_new_target", formContent );
			});
		});

		refreshPage();
		var data = { 'action': 'banner_ads', 'ba_action': 'get_campaigns', 'format': 'json' };
		$.get(wgScriptPath + '/api.php', data, function( data ){
			$.each( data.result.campaigns, function( k, v ) {
				$( '#camp_selector' ).parent().find('ul').append( '<li><a class="show_stats" data-camp_id="'+ k +'">'+ v + '</a></li>' );
			} );

			$( '.show_stats' ).click( function() {
				var data = { 'action': 'banner_ads', 'ba_action': 'fetch_stats_display', 'camp_id': $( this ).data( 'camp_id' ), 'format': 'json' };
				$.get( wgScriptPath + '/api.php', data, success );
			});
		});

	});

} )( jQuery );
