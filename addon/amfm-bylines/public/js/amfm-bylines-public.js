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

		// Hide columns if no data
		// if (amfmLocalize.author === '') {
		// 	$('#amfm-byline-col-author').hide();
		// }
		// if (amfmLocalize.editor === '') {
		// 	$('#amfm-byline-col-editor').hide();
		// }
		// if (amfmLocalize.reviewedBy === '') {
		// 	$('#amfm-byline-col-reviewer').hide();
		// 	$('#amfm-byline-col-editor').css('border-bottom', 'none !important');
		// }

		console.log("AMFM Running");

		if (amfmLocalize.has_social_linkedin) {
			$('.amfm-social-icons-linkedin').removeClass('amfm-hidden');
		}

		// if (amfmLocalize.author == '1') {
		// 	$('#amfm-byline-col-author').css('display', 'flex');
		// }
		// if (amfmLocalize.editor == '1') {
		// 	$('#amfm-byline-col-editor').css('display', 'flex');

		// 	// let editor_border = border_left.clone();
		// 	// $('#amfm-byline-col-editor').append(editor_border);
		// }
		// if (amfmLocalize.reviewedBy == '1' && amfmLocalize.editor == '1') {
		// 	$('#amfm-byline-col-reviewer').css('display', 'flex');
		// 	$('#amfm-byline-col-editor').css('border-bottom', 'none !important');

		// 	// let reviewer_border = border_left.clone();
		// 	// $('#amfm-byline-col-reviewer').append(reviewer_border);
		// }

		// console.log(amfmLocalize.author_page_url)
		// console.log(amfmLocalize.editor_page_url)
		// console.log(amfmLocalize.reviewer_page_url)

		$('.amfm-byline-link-author').on('click', function() {
			window.location.href =  "//" + amfmLocalize.author_page_url;
		});

		$('.amfm-byline-link-editor').on('click', function() {
			window.location.href =  "//" + amfmLocalize.editor_page_url;
		});

		$('.amfm-byline-link-reviewer').on('click', function() {
			window.location.href =  "//" + amfmLocalize.reviewer_page_url;
		});

		// add in-the-press link
		$('.amfm-byline-col-in-the-press').on('click', function() {
			window.location.href =  "//" + amfmLocalize.in_the_press_page_url;
		});

		// Adjust widths to fit 100% total
		// if ($(window).width() > 768) {
		// 	var visibleCols = $('.amfm-byline-col:visible').length;
		// 	if (visibleCols > 0) {
		// 		var newWidth = 100 / visibleCols + '%';
		// 		$('.amfm-byline-col').css('width', newWidth);
		// 	}
		// } else {
		// 	$('.amfm-byline-col').css('width', '100%');
		// }

		// Set width to 100% on mobile width
		if ($(window).width() <= 768) {
			$('.amfm-byline-col').css('width', '100%');
		}
	});

})(jQuery);