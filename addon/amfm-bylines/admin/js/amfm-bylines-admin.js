(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	// Open and close drawer
	$(document).ready(function () {
		
		$("#amfm-create-staff").on('click', function () {
			// clicking this id redirects to the staff cpt create new page
			window.location.href = "/wp-admin/post-new.php?post_type=staff";
		});

		$('.amfm-card').on('click', function () {

			// check if card has class amfm-card-staff-item, if so, redirect using the data-url attribute
			if ($(this).hasClass('amfm-card-staff-item')) {
				var url = $(this).data('url');
				window.location.href = url;
				return;
			}

			// check if clicked card has id "amfm-create-card", if so, open drawer with empty fields
			if ($(this).attr('id') === 'amfm-create-card') {
				$('#amfmDrawer .drawer-title').text('Create New Byline');
				$('#amfmDrawer').addClass('open');
				// reset form fields
				$('#amfm-bylines-form')[0].reset();
				return;
			}

			var cardTitle = $(this).find('.card-title').text();
			// var cardImage = $(this).find('.card-img-top').attr('src');
			// var cardBody = $(this).find('.card-body').html();

			$('#amfmDrawer .drawer-title').text(cardTitle);
			// $('#amfmDrawer .drawer-body').html('<img src="' + cardImage + '" class="img-fluid mb-3">' + cardBody);
			$('#amfmDrawer').addClass('open');
		});

		$('#closeDrawer').on('click', function () {
			$('#amfmDrawer').removeClass('open');
		});

		// Close drawer when clicking outside of it
		$(document).on('click', function (event) {
			if (!$(event.target).closest('#amfmDrawer, .amfm-card, .media-modal').length) {
				$('#amfmDrawer').removeClass('open');
			}
		});
	});

	// Adjust drawer height to account for admin bar
	$(document).ready(function () {
		var adminBar = $('#wpadminbar');
		var drawer = $('#amfmDrawer');

		if (adminBar.length) {
			var adminBarHeight = adminBar.outerHeight();
			drawer.css({
				'top': adminBarHeight + 'px',
				'height': 'calc(100% - ' + adminBarHeight + 'px)'
			});
		}
	});

	// Media handler
	$(document).ready(function ($) {
		$('#upload_image_button').on('click', function (e) {
			e.preventDefault();
			var mediaFrame;

			// If the media frame already exists, reopen it.
			if (mediaFrame) {
				mediaFrame.open();
				return;
			}

			// Create a new media frame
			mediaFrame = wp.media({
				title: 'Select or Upload Media',
				button: {
					text: 'Use this media'
				},
				multiple: false
			});

			// When an image is selected, run a callback.
			mediaFrame.on('select', function () {
				var attachment = mediaFrame.state().get('selection').first().toJSON();
				$('#image_url').val(attachment.url); // Set the image URL to the input field
			});

			// Finally, open the modal
			mediaFrame.open();
		});
	});

	// Page Selector
	$(document).ready(function($) {
		function fetchPages() {
			$.ajax({
				url: amfmLocalize.ajax_url,
				method: 'GET',
				data: {
					action: 'fetch_pages',
					nonce: amfmLocalize.pageSelectorNonce
				},
				success: function(response) {
					if (response.success) {
						var pages = response.data;
						var $pageSelector = $('#page_selector');
						$pageSelector.empty();
						$pageSelector.append('<option value="">' + 'Select a page' + '</option>');
						$.each(pages, function(index, page) {
							console.log(page);
							$pageSelector.append('<option value="' + page.link + '">' + page.title + '</option>');
						});
					}
				}
			});
		}

		// fetchPages();

		$('#page_selector').on('change', function() {
			var selectedUrl = $(this).val();
			$('#page_url').val(selectedUrl);
		});
	});

	// Ajax CRUD
	$(document).ready(function($) {
		$('#amfm-bylines-form').on('submit', function(e) {
			e.preventDefault();

			var formData = $(this).serialize();
			var name = $('#name').val().trim();

			if (name === '') {
				var message = $('<div class="flash-message error"></div>').text('Name is required.');
				$('#flash-message-container').html(message);
				setTimeout(function() {
					message.fadeOut(function() {
						$(this).remove();
					});
				}, 5000);
				return;
			}

			$.ajax({
				url: amfmLocalize.ajax_url,
				method: 'POST',
				data: {
					action: 'save_amfm_bylines',
					nonce: amfmLocalize.saveBylineNonce,
					form_data: formData
				},
				success: function(response) {
					var message = $('<div class="flash-message"></div>');
					if (response.success) {
						message.text('Data saved successfully!').addClass('success');
						$('#amfm-bylines-form')[0].reset(); // Clear the form
						
						// Temporary solution to reload data
						location.reload(); // Reload the bylines list
					} else {
						message.text('There was an error saving the data.').addClass('error');
					}
					$('#flash-message-container').html(message);

					$('#amfmDrawer').removeClass('open');
					
					setTimeout(function() {
						message.fadeOut(function() {
							$(this).remove();
						});
					}, 5000);
				},
				error: function(jqXHR) {
					var message = $('<div class="flash-message error"></div>');
					if (jqXHR.status === 403) {
						message.text('Unauthorized request. Please refresh the page and try again.');
					} else {
						message.text('There was an error processing the request.');
					}
					$('#flash-message-container').html(message);
					setTimeout(function() {
						message.fadeOut(function() {
							$(this).remove();
						});
						$('#amfmDrawer').removeClass('open');
					}, 5000);
				}
			});
		});
	});

	$(document).ready(function () {
		$('.amfm-card-item').on('click', function () {
			var cardData = $(this).data();
			$('#name').val(cardData.name);
			$('#image_url').val(cardData.image);
			$('#description').val(cardData.description);
			$('#byline_id').val(cardData.id); // Add this line to set the byline ID
			if (cardData.data) {
				console.log(cardData.data);
				var otherData = cardData.data;
				$('#page_url').val(otherData.page_url);
				$('#honorificSuffix').val(otherData.honorificSuffix);
				$('#jobTitle').val(otherData.jobTitle);
				$('#credentialType').val(otherData.hasCredential['@type']);
				$('#credentialName').val(otherData.hasCredential['name']);
				$('#worksForType').val(otherData.worksFor['@type']);
				$('#worksForName').val(otherData.worksFor['name']);				
			}
			$('#authorTag').val(cardData.authorTag);
			$('#editorTag').val(cardData.editorTag);
			$('#reviewedByTag').val(cardData.reviewedByTag);
			$('#amfmDrawer').addClass('open');
			$('#amfm-bylines-form button[type="reset"]').hide(); // Hide reset button
			$('#amfm-bylines-form button[type="submit"]').text('Save'); // Change submit button text to "Save"
			$('#amfm-remove-byline').show();
		});

		$('#amfm-create-card').on('click', function () {
			$('#amfm-bylines-form')[0].reset(); // Reset all form inputs
			$('#byline_id').val(''); // Clear the byline_id field
			$('#amfm-bylines-form button[type="reset"]').show(); // Show reset button
			$('#amfm-bylines-form button[type="submit"]').text('Submit'); // Change submit button text to "Submit"
			$('#amfm-remove-byline').hide();
		});

	});

	$(document).ready(function () {
		$('#amfm-remove-byline').on('click', function () {
			var bylineId = $('#byline_id').val();

			console.log("STARTS");

			console.log(amfmLocalize);

			console.log(bylineId); 

			if (bylineId && confirm('Are you sure you want to delete this byline?')) {
				$.ajax({
					url: amfmLocalize.ajax_url,
					method: 'POST',
					data: {
						action: 'remove_amfm_byline',
						nonce: amfmLocalize.removeBylineNonce,
						id: bylineId
					},
					success: function (response) {
						if (response.success) {
							// console.log(response);
							location.reload();
						} else {
							alert('There was an error deleting the byline.');
						}
					},
					error: function (jqXHR) {
						if (jqXHR.status === 403) {
							alert('Unauthorized request. Please refresh the page and try again.');
						} else {
							alert('There was an error processing the request.');
						}
					}
				});
			}
		});
	});

	$(document).ready(function() {
		$('#functionToggle').change(function() {
			var isActive = $(this).is(':checked');
			
			$.ajax({
				url: ajaxurl, // WordPress AJAX URL
				type: 'POST',
				data: {
					action: 'toggle_function',
					is_active: isActive
				},
				success: function(response) {
					alert('Function ' + (isActive ? 'activated' : 'deactivated') + ' successfully.');
				},
				error: function(xhr, status, error) {
					alert('An error occurred: ' + error);
				}
			});
		});
	});
	
})(jQuery);