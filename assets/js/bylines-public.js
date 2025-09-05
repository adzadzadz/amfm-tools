(function ($) {
	'use strict';

	$(document).ready(function () {

		// Hash link scroll offset
		const scrollOffset = 120;

		// Handle hash links clicks
		$('a[href*="#"]').on('click', function(e) {
			const href = $(this).attr('href');
			
			// Check if it's a hash link on the same page
			if (href.indexOf('#') === 0) {
				const target = $(href);
				
				if (target.length) {
					e.preventDefault();
					const offsetTop = target.offset().top - scrollOffset;
					
					$('html, body').animate({
						scrollTop: offsetTop
					}, 300);
					
					// Update URL hash
					if (history.pushState) {
						history.pushState(null, null, href);
					} else {
						window.location.hash = href;
					}
				}
			}
		});

		// Handle direct hash navigation (page load with hash)
		if (window.location.hash) {
			setTimeout(function() {
				const target = $(window.location.hash);
				if (target.length) {
					const offsetTop = target.offset().top - scrollOffset;
					$('html, body').animate({
						scrollTop: offsetTop
					}, 300);
				}
			}, 100);
		}

		console.log("AMFM Bylines Public Running");

		// Show LinkedIn icons if staff has LinkedIn URL
		if (typeof amfmLocalize !== 'undefined' && amfmLocalize.has_social_linkedin) {
			$('.amfm-social-icons-linkedin').removeClass('amfm-hidden');
		}

		// Byline link click handlers
		$('.amfm-byline-link-author').on('click', function() {
			if (typeof amfmLocalize !== 'undefined' && amfmLocalize.author_page_url) {
				window.location.href = "//" + amfmLocalize.author_page_url;
			}
		});

		$('.amfm-byline-link-editor').on('click', function() {
			if (typeof amfmLocalize !== 'undefined' && amfmLocalize.editor_page_url) {
				window.location.href = "//" + amfmLocalize.editor_page_url;
			}
		});

		$('.amfm-byline-link-reviewer').on('click', function() {
			if (typeof amfmLocalize !== 'undefined' && amfmLocalize.reviewer_page_url) {
				window.location.href = "//" + amfmLocalize.reviewer_page_url;
			}
		});

		// In the press link
		$('.amfm-byline-col-in-the-press').on('click', function() {
			if (typeof amfmLocalize !== 'undefined' && amfmLocalize.in_the_press_page_url) {
				window.location.href = "//" + amfmLocalize.in_the_press_page_url;
			}
		});

		// Set width to 100% on mobile
		if ($(window).width() <= 768) {
			$('.amfm-byline-col').css('width', '100%');
		}
	});

})(jQuery);