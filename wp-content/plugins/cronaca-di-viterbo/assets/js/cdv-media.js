/**
 * Cronaca di Viterbo - Media Scripts
 * Video Stories & Gallerie Foto
 * @since 2.0.0
 */

(function($) {
	'use strict';

	/* ========================================
	   VIDEO STORIES
	======================================== */

	const CdvVideo = {
		init() {
			this.bindEvents();
			this.initVideoPlayers();
			this.initStoriesViewer();
			this.initEmbedDetection();
		},

		bindEvents() {
			// Auto-detect platform on URL input (admin)
			$(document).on('input', '#cdv_video_url', function() {
				const url = $(this).val();
				CdvVideo.detectPlatform(url);
			});

			// Play/Pause su overlay click (solo per video diretti)
			$(document).on('click', '.cdv-play-btn', function(e) {
				e.preventDefault();
				const $card = $(this).closest('.cdv-video-card');
				const $video = $card.find('video')[0];
				
				if ($video) {
					if ($video.paused) {
						$video.play();
						$(this).find('.dashicons').removeClass('dashicons-controls-play').addClass('dashicons-controls-pause');
					} else {
						$video.pause();
						$(this).find('.dashicons').removeClass('dashicons-controls-pause').addClass('dashicons-controls-play');
					}
				}
			});

			// Like button
			$(document).on('click', '.cdv-like-btn', function(e) {
				e.preventDefault();
				const $btn = $(this);
				const videoId = $btn.data('video-id');
				
				if ($btn.hasClass('liked')) {
					return; // Already liked
				}

				$.ajax({
					url: cdvData.ajaxUrl,
					type: 'POST',
					data: {
						action: 'cdv_like_video',
						nonce: cdvData.nonce,
						video_id: videoId
					},
					success(response) {
						if (response.success) {
							$btn.addClass('liked');
							const $count = $btn.find('.cdv-likes-count');
							const current = parseInt($count.text().replace(/[^0-9]/g, '')) || 0;
							$count.text((current + 1).toLocaleString());
						}
					}
				});
			});

			// Track video views
			$(document).on('play', '.cdv-video-player', function() {
				const $video = $(this);
				const videoId = $video.closest('[data-video-id]').data('video-id');
				
				if (!$video.data('view-tracked')) {
					$video.data('view-tracked', true);
					
					$.ajax({
						url: cdvData.ajaxUrl,
						type: 'POST',
						data: {
							action: 'cdv_track_video_view',
							nonce: cdvData.nonce,
							video_id: videoId
						}
					});
				}
			});
		},

		detectPlatform(url) {
			if (!url) return;

			let platform = 'Sconosciuta';
			const urlLower = url.toLowerCase();

			if (urlLower.includes('instagram.com') || urlLower.includes('instagr.am')) {
				platform = 'Instagram';
			} else if (urlLower.includes('youtube.com') || urlLower.includes('youtu.be')) {
				platform = 'YouTube';
			} else if (urlLower.includes('tiktok.com')) {
				platform = 'TikTok';
			} else if (urlLower.includes('vimeo.com')) {
				platform = 'Vimeo';
			} else if (urlLower.includes('facebook.com') || urlLower.includes('fb.watch')) {
				platform = 'Facebook';
			} else if (urlLower.includes('twitter.com') || urlLower.includes('x.com')) {
				platform = 'Twitter/X';
			}

			$('#cdv-detected-platform').text(platform);
			$('#cdv_video_platform').val(platform.toLowerCase());
			$('#cdv_video_type').val(platform !== 'Sconosciuta' ? 'embed' : 'upload');

			// Show/hide detection box
			if (platform !== 'Sconosciuta') {
				$('.cdv-auto-detect').slideDown();
			} else {
				$('.cdv-auto-detect').slideUp();
			}
		},

		initEmbedDetection() {
			// Trigger detection on page load if URL exists
			const initialUrl = $('#cdv_video_url').val();
			if (initialUrl) {
				this.detectPlatform(initialUrl);
			}
		},

		initVideoPlayers() {
			// Auto-pause quando esce dal viewport (solo per video diretti, non embed)
			if ('IntersectionObserver' in window) {
				const observer = new IntersectionObserver((entries) => {
					entries.forEach(entry => {
						const video = entry.target;
						if (!entry.isIntersecting && !video.paused) {
							video.pause();
						}
					});
				}, { threshold: 0.5 });

				$('.cdv-video-player').each(function() {
					observer.observe(this);
				});
			}
		},

		initStoriesViewer() {
			const $storiesViewer = $('.cdv-stories-viewer');
			if ($storiesViewer.length === 0) return;

			let currentIndex = 0;
			const $stories = $storiesViewer.find('.cdv-story-item');
			const totalStories = $stories.length;

			if (totalStories === 0) return;

			// Show first story
			$stories.eq(0).show();
			this.playStory(0);

			// Navigation
			$('.cdv-stories-prev').on('click', () => {
				if (currentIndex > 0) {
					this.switchStory(currentIndex, currentIndex - 1);
					currentIndex--;
				}
			});

			$('.cdv-stories-next').on('click', () => {
				if (currentIndex < totalStories - 1) {
					this.switchStory(currentIndex, currentIndex + 1);
					currentIndex++;
				}
			});

			// Keyboard navigation
			$(document).on('keydown', (e) => {
				if (e.key === 'ArrowLeft' && currentIndex > 0) {
					this.switchStory(currentIndex, currentIndex - 1);
					currentIndex--;
				} else if (e.key === 'ArrowRight' && currentIndex < totalStories - 1) {
					this.switchStory(currentIndex, currentIndex + 1);
					currentIndex++;
				}
			});
		},

		switchStory(fromIndex, toIndex) {
			const $stories = $('.cdv-story-item');
			const $from = $stories.eq(fromIndex);
			const $to = $stories.eq(toIndex);

			// Pause current video
			const currentVideo = $from.find('video')[0];
			if (currentVideo) {
				currentVideo.pause();
				currentVideo.currentTime = 0;
			}

			// Hide current, show next
			$from.hide();
			$to.show();

			// Play next video
			this.playStory(toIndex);
		},

		playStory(index) {
			const $story = $('.cdv-story-item').eq(index);
			const video = $story.find('video')[0];
			const $progressBar = $story.find('.cdv-progress-bar');

			if (video) {
				video.play();

				// Animate progress bar
				const duration = video.duration * 1000;
				$progressBar.css('transition', `width ${duration}ms linear`);
				setTimeout(() => {
					$progressBar.css('width', '100%');
				}, 50);

				// Auto-advance on video end
				$(video).on('ended', () => {
					const currentIndex = parseInt($story.index());
					const totalStories = $('.cdv-story-item').length;
					
					if (currentIndex < totalStories - 1) {
						this.switchStory(currentIndex, currentIndex + 1);
					}
				});
			}
		}
	};

	/* ========================================
	   GALLERIE FOTO
	======================================== */

	const CdvGallery = {
		init() {
			this.initLightbox();
			this.initMasonry();
		},

		initLightbox() {
			// Simple lightbox (può essere sostituito con libreria esterna come GLightbox)
			$(document).on('click', '.cdv-photo-link[data-lightbox]', function(e) {
				const lightboxEnabled = $(this).closest('.cdv-galleria-photos').data('lightbox');
				
				if (!lightboxEnabled) {
					return true;
				}

				e.preventDefault();
				
				const src = $(this).attr('href');
				const caption = $(this).data('title') || '';
				
				CdvGallery.openLightbox(src, caption);
			});

			// Close lightbox
			$(document).on('click', '.cdv-lightbox-overlay', function(e) {
				if (e.target === this) {
					CdvGallery.closeLightbox();
				}
			});

			$(document).on('click', '.cdv-lightbox-close', function() {
				CdvGallery.closeLightbox();
			});

			// Keyboard navigation
			$(document).on('keydown', function(e) {
				if ($('.cdv-lightbox-overlay').length > 0) {
					if (e.key === 'Escape') {
						CdvGallery.closeLightbox();
					}
				}
			});
		},

		openLightbox(src, caption) {
			const $lightbox = $(`
				<div class="cdv-lightbox-overlay">
					<div class="cdv-lightbox-container">
						<button class="cdv-lightbox-close">×</button>
						<img src="${src}" alt="${caption}">
						${caption ? `<div class="cdv-lightbox-caption">${caption}</div>` : ''}
					</div>
				</div>
			`);

			$('body').append($lightbox);
			
			setTimeout(() => {
				$lightbox.addClass('active');
			}, 10);

			$('body').css('overflow', 'hidden');
		},

		closeLightbox() {
			const $lightbox = $('.cdv-lightbox-overlay');
			
			$lightbox.removeClass('active');
			
			setTimeout(() => {
				$lightbox.remove();
				$('body').css('overflow', '');
			}, 300);
		},

		initMasonry() {
			// Simple column balancing per layout masonry
			$('.cdv-layout-masonry').each(function() {
				const $masonry = $(this);
				const $items = $masonry.find('.cdv-photo-item');
				
				$items.each(function() {
					$(this).css('break-inside', 'avoid');
				});
			});
		}
	};

	/* ========================================
	   MEDIA UPLOADER (ADMIN)
	======================================== */

	const CdvMediaUploader = {
		init() {
			if (!wp || !wp.media) return;

			// Video uploader
			$(document).on('click', '.cdv-upload-video-btn', function(e) {
				e.preventDefault();
				
				const $btn = $(this);
				const mediaUploader = wp.media({
					title: 'Seleziona Video',
					button: { text: 'Usa questo video' },
					library: { type: 'video' },
					multiple: false
				});

				mediaUploader.on('select', function() {
					const attachment = mediaUploader.state().get('selection').first().toJSON();
					$('#cdv_video_url').val(attachment.url);
					$('#cdv_video_type').val('upload');
					
					// Show preview
					const $preview = $('.cdv-video-preview');
					if ($preview.length === 0) {
						$btn.closest('.cdv-video-meta-box').append(`
							<div class="cdv-video-preview">
								<p><strong>Anteprima:</strong></p>
								<video src="${attachment.url}" controls style="max-width: 100%;"></video>
							</div>
						`);
					} else {
						$preview.find('video').attr('src', attachment.url);
					}
				});

				mediaUploader.open();
			});

			// Gallery photos uploader
			$(document).on('click', '.cdv-add-gallery-photos', function(e) {
				e.preventDefault();
				
				const $input = $('#cdv_gallery_photos');
				const currentIds = $input.val() ? $input.val().split(',') : [];

				const mediaUploader = wp.media({
					title: 'Seleziona Foto',
					button: { text: 'Aggiungi foto selezionate' },
					library: { type: 'image' },
					multiple: true
				});

				// Pre-select existing images
				mediaUploader.on('open', function() {
					const selection = mediaUploader.state().get('selection');
					currentIds.forEach(id => {
						const attachment = wp.media.attachment(id);
						attachment.fetch();
						selection.add(attachment);
					});
				});

				mediaUploader.on('select', function() {
					const attachments = mediaUploader.state().get('selection').toJSON();
					const photoIds = attachments.map(att => att.id);
					
					$input.val(photoIds.join(','));
					
					// Update preview
					CdvMediaUploader.updateGalleryPreview(attachments);
					$('.cdv-photo-count').text(`Foto selezionate: ${photoIds.length}`);
				});

				mediaUploader.open();
			});

			// Remove photo from gallery
			$(document).on('click', '.cdv-remove-photo', function(e) {
				e.preventDefault();
				
				const $item = $(this).closest('.cdv-gallery-item');
				const photoId = $item.data('id');
				const $input = $('#cdv_gallery_photos');
				const currentIds = $input.val().split(',').filter(id => id != photoId);
				
				$input.val(currentIds.join(','));
				$item.remove();
				
				$('.cdv-photo-count').text(`Foto selezionate: ${currentIds.length}`);
			});
		},

		updateGalleryPreview(attachments) {
			const $preview = $('.cdv-gallery-preview');
			const $list = $preview.find('.cdv-gallery-list');
			
			if ($list.length === 0) {
				$preview.html('<ul class="cdv-gallery-list"></ul>');
			}

			const $listEl = $preview.find('.cdv-gallery-list');
			$listEl.empty();

			attachments.forEach(att => {
				const caption = att.caption || '';
				const thumbUrl = att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
				
				$listEl.append(`
					<li class="cdv-gallery-item" data-id="${att.id}">
						<img src="${thumbUrl}" alt="">
						<div class="cdv-gallery-item-actions">
							<button type="button" class="cdv-remove-photo" title="Rimuovi">
								<span class="dashicons dashicons-no-alt"></span>
							</button>
						</div>
						${caption ? `<div class="cdv-gallery-item-caption">${caption}</div>` : ''}
					</li>
				`);
			});
		}
	};

	/* ========================================
	   INIT
	======================================== */

	$(document).ready(function() {
		CdvVideo.init();
		CdvGallery.init();
		
		// Admin only
		if ($('body').hasClass('wp-admin')) {
			CdvMediaUploader.init();
		}
	});

	// Expose globally
	window.CdvVideo = CdvVideo;
	window.CdvGallery = CdvGallery;

})(jQuery);
