/**
 * Show Plugin Menu Items on Activation - Admin Pointers.
 *
 * Licensed under the GPLv2+ license.
 */

window.SPMIOAPointerTips = {};
( function ( window, $, app, SPMIOANewMenuItemPointers ) {

	// Constructor.
	app.init = function() {
		if ( ! app.meetsRequirements() ) {
			return;
		}

		app.bindEvents();
	};

	// Do we meet the requirements?
	app.meetsRequirements = function() {
		return Boolean( SPMIOANewMenuItemPointers );
	};

	// Bind events.
	app.bindEvents = function() {
		$( window ).on( 'load', app.displayAdminPointers );
	};

	// Display the admin pointers.
	app.displayAdminPointers = function() {

		for ( var index in SPMIOANewMenuItemPointers ) {

			var pointer  = SPMIOANewMenuItemPointers[ index ];
			var menuItem = $( pointer.target );

			app.displayAdminPointer( pointer, menuItem );
			app.hidePointerOnMenuItemMouseover( menuItem );
		}
	};

	// Display a single admin pointer.
	app.displayAdminPointer = function( pointer, menuItem ) {
		menuItem.pointer( pointer.options ).pointer( 'open' );
	};

	// Hide a pointer when the user mouses over its associated menu item.
	app.hidePointerOnMenuItemMouseover = function( menuItem ) {
		menuItem.mouseenter( function() {
			menuItem.pointer( 'close' );
		} );
	};

	// Engage!
	$( app.init );

} )( window, jQuery, window.SPMIOAPointerTips, window.SPMIOANewMenuItemPointers );
