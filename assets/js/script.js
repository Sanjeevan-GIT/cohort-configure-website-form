jQuery(document).ready(function($) {
    'use strict';

    let selections = {};

    // Load saved selections from localStorage if available.
    if (localStorage.getItem('cwf_selections')) {
        selections = JSON.parse(localStorage.getItem('cwf_selections'));
    }

    // Newsletter always shown on fresh page load.
    localStorage.removeItem('cwf_newsletter_closed');

    // Tab content mapping for text-box.
    const tabContent = {
        size: {
            heading: "You're in control.",
            subheading: "Choose the components you need."
        },
        ecommerce: { heading: "", subheading: "" },
        logo: { heading: "", subheading: "" },
        copy: { heading: "", subheading: "" },
        budget: { heading: "", subheading: "" },
        summary: {
            heading: "You're ready.",
            subheading: ""
        }
    };
	
	// Preload all tab images for faster switching (after page load)
const imagesToPreload = ['size', 'ecommerce', 'logo', 'copy', 'budget', 'summary'];
setTimeout(() => {
    imagesToPreload.forEach(tab => {
        const img = new Image();
        img.src = cwf_ajax.plugin_url + 'assets/images/' + tab + '.jpg';
    });
}, 1000); // Slight delay to not compete with critical resources

    const requiredSections = ['size', 'ecommerce', 'logo', 'copy', 'budget'];
    const sectionNames = {
        size: 'Size',
        ecommerce: 'Ecommerce',
        logo: 'Logo Design',
        copy: 'Copy Writing',
        budget: 'Budget'
    };

    // Validate selections.
    function validateSelections() {
        return requiredSections.filter(section => !selections[section]);
    }

    // Display validation message.
    function showValidationMessage(missingSections) {
        const $message = $('.cwf-validation-message');
        const $list = $('#cwf-missing-sections-list');

        $list.empty();

        missingSections.forEach(section => {
            const listItem = $('<li></li>')
                .text(sectionNames[section])
                .attr('data-tab', section)
                .click(() => {
                    $(`.cwf-tab[data-tab="${section}"]`).click();
                });
            $list.append(listItem);
        });

        $message.slideDown(300);

        $('.summary-item').removeClass('missing');
        missingSections.forEach(section => {
            $(`.summary-item[data-tab="${section}"]`).addClass('missing');
        });
    }

    function hideValidationMessage() {
        $('.cwf-validation-message').slideUp(300);
        $('.summary-item').removeClass('missing');
    }

    // Floating icon clicks.
    $('.cwf-floating-icon').click(function() {
        const tab = $(this).data('tab');
        $(`.cwf-tab[data-tab="${tab}"]`).click();
    });

    function updateFloatingIcons(tab) {
        $('.cwf-floating-icon').removeClass('active');
        $(`.cwf-floating-icon[data-tab="${tab}"]`).addClass('active');
    }

    let tabUnderlineReady = false;

    function updateTabUnderline() {
        const $underline = $('.cwf-tab-underline');
        const $activeTab = $('.cwf-tab.active');
        if (!$underline.length || !$activeTab.length || $(window).width() <= 991) return;

        const tabsRect = $('.cwf-tabs')[0].getBoundingClientRect();
        const tabRect = $activeTab[0].getBoundingClientRect();
        $underline.css({
            left: tabRect.left - tabsRect.left,
            width: tabRect.width
        });

        if (!tabUnderlineReady) {
            tabUnderlineReady = true;
            requestAnimationFrame(function() {
                $underline.addClass('ready');
            });
        }
    }

    // Desktop step arrow - get the step to indicate (the tab the user is currently viewing).
    function getCurrentStepForArrow() {
        if ($(window).width() <= 991) return null;
        const activeTab = $('.cwf-tab.active').data('tab');
        if (activeTab === 'summary') return null;
        const steps = ['size', 'ecommerce', 'logo', 'copy', 'budget'];
        return steps.includes(activeTab) ? activeTab : null;
    }

    // Desktop step arrow - position and show/hide.
    function updateDesktopStepArrow() {
        const $arrow = $('.cwf-desktop-step-arrow');
        if (!$arrow.length || $(window).width() <= 991) return;

        const step = getCurrentStepForArrow();
        if (!step) {
            $arrow.removeClass('visible');
            return;
        }

        const stepOrder = ['size', 'ecommerce', 'logo', 'copy', 'budget'];
        const index = stepOrder.indexOf(step);
        if (index === -1) {
            $arrow.removeClass('visible');
            return;
        }

        const iconHeight = 46;
        const gap = 1;
        const arrowHeight = 25;
        const top = index * (iconHeight + gap) + (iconHeight - arrowHeight) / 2;

        $arrow.css('top', top + 'px').addClass('visible');
    }

    // Load tab image with fallback.
    function loadTabImage(tab) {
        const img = $('#cwf-tab-image');
        const imageUrl = cwf_ajax.plugin_url + 'assets/images/' + tab + '.jpg';

        $('.cwf-no-image').remove();

        img.addClass('cwf-image-loading');

        const preloadImg = new Image();

        preloadImg.onload = function() {
            img.attr('src', imageUrl).show();
            setTimeout(() => {
                img.removeClass('cwf-image-loading');
            }, 200);
            $('.cwf-no-image').remove();
        };

        preloadImg.onerror = function() {
            img.hide();
            img.removeClass('cwf-image-loading');
            if (!$('.cwf-no-image').length) {
                img.after('<div class="cwf-no-image" id="cwf-tab-image-placeholder"></div>');
            }
        };

        preloadImg.src = imageUrl;
    }

    // Scroll indicator for mobile.
    function updateScrollIndicator() {
        if ($(window).width() > 991) return;

        const $activeTab = $('.cwf-tab-content.active');
        const $indicator = $('.cwf-scroll-indicator');

        if ($indicator.length === 0) return;

        if ($activeTab.attr('id') === 'summary') {
            $indicator.addClass('summary-tab');
        } else {
            $indicator.removeClass('summary-tab');
        }

        let container = null;
        let hasScroll = false;
        let isAtBottom = false;

        if ($activeTab.attr('id') === 'summary') {
            const $summaryBox = $activeTab.find('.cwf-summary-box');
            if ($summaryBox.length) {
                container = $summaryBox[0];
                hasScroll = container.scrollHeight > container.clientHeight + 2;
                isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 15;
            }
        } else {
            const $optionsContainer = $activeTab.find('.cwf-options');
            if ($optionsContainer.length) {
                container = $optionsContainer[0];
                hasScroll = container.scrollHeight > container.clientHeight + 2;
                isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 15;
            }
        }

        if (container && hasScroll && !isAtBottom) {
            $indicator.addClass('show');
        } else {
            $indicator.removeClass('show');
        }
    }

    function setupScrollListenerForActiveTab() {
        const $activeTab = $('.cwf-tab-content.active');
        $(document).off('scroll', '.cwf-options, .cwf-summary-box', updateScrollIndicator);

        if ($activeTab.attr('id') === 'summary') {
            $activeTab.find('.cwf-summary-box').on('scroll', updateScrollIndicator);
        } else {
            $activeTab.find('.cwf-options').on('scroll', updateScrollIndicator);
        }
    }

    // Tab switching.
    $('.cwf-tab').click(function() {
        const tab = $(this).data('tab');

        $('#cwf-success-message').slideUp(300);

        $('.cwf-tab').removeClass('active');
        $(this).addClass('active');

        const $currentContent = $('.cwf-tab-content.active');
        const $nextContent = $('#' + tab);

        $currentContent.css('opacity', '0');

        setTimeout(() => {
            $currentContent.removeClass('active');
            $nextContent.addClass('active');
            setTimeout(() => {
                $nextContent.css('opacity', '1');
                setTimeout(() => {
                    updateScrollIndicator();
                    setupScrollListenerForActiveTab();
                }, 200);
            }, 50);
        }, 300);

        $('#cwf-tab-image').addClass('cwf-image-loading');

        loadTabImage(tab);

        const $textBox = $('.cwf-text-box');
        $textBox.addClass('cwf-text-box-hidden');

        setTimeout(() => {
            if (tab === 'size') {
                $('#cwf-tab-heading').html('<span class="cwf-text-italic">You\'re</span> in control');
                $('#cwf-tab-subheading').text('Choose the components you need.');
                $textBox.show().removeClass('cwf-text-box-summary');
            } else if (tab === 'summary') {
                $('#cwf-tab-heading').html('You\'re <span class="cwf-text-italic">ready.</span>');
                $('#cwf-tab-subheading').text('');
                $textBox.show().addClass('cwf-text-box-summary');
            } else {
                $textBox.hide();
            }

            setTimeout(() => {
                $textBox.removeClass('cwf-text-box-hidden');
            }, 100);
        }, 200);

        updateFloatingIcons(tab);
        updateDesktopStepArrow();
        updateTabUnderline();

        if (tab === 'summary') {
            updateSummary();
            const missing = validateSelections();
            if (missing.length) {
                showValidationMessage(missing);
            } else {
                hideValidationMessage();
            }
        }
    });

    // Option click.
    $('.cwf-options li').click(function() {
        const value = $(this).data('value');
        const field = $(this).parent().data('field');

        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            delete selections[field];
        } else {
            $(this).siblings().removeClass('selected');
            $(this).addClass('selected');
            selections[field] = value;
        }

        localStorage.setItem('cwf_selections', JSON.stringify(selections));

        // Update selected value displays.
        const displayMap = {
            size: '#selected-size-value',
            ecommerce: '#selected-ecommerce-value',
            logo: '#selected-logo-value',
            copy: '#selected-copy-value',
            budget: '#selected-budget-value'
        };
        if (displayMap[field]) {
            $(displayMap[field]).text(selections[field] || 'Please select');
        }

        updateDesktopStepArrow();
        setTimeout(updateScrollIndicator, 100);
    });

    // Next button.
    $('.cwf-next').click(function() {
        const next = $(this).data('next');
        $(`.cwf-tab[data-tab="${next}"]`).click();
    });

    // Update summary.
    function updateSummary() {
        $('#sum-size').text(selections.size || 'Not selected');
        $('#sum-ecommerce').text(selections.ecommerce || 'Not selected');
        $('#sum-logo').text(selections.logo || 'Not selected');
        $('#sum-copy').text(selections.copy || 'Not selected');
        $('#sum-budget').text(selections.budget || 'Not selected');

        const sections = ['size', 'ecommerce', 'logo', 'copy', 'budget'];
        sections.forEach(section => {
            if (selections[section]) {
                $(`.summary-item[data-tab="${section}"]`).addClass('selected');
                $(`#sum-${section}-label`).hide();
            } else {
                $(`.summary-item[data-tab="${section}"]`).removeClass('selected');
                $(`#sum-${section}-label`).show();
            }
        });

        setTimeout(updateScrollIndicator, 200);
    }

    // Summary item click.
    $('.summary-item').click(function() {
        const tab = $(this).data('tab');
        $(`.cwf-tab[data-tab="${tab}"]`).click();
    });

    // Quote submission.
    $('#cwf-submit').click(function(e) {
        e.preventDefault();

        selections.name = $('#cwf-name').val();
        selections.email = $('#cwf-email').val();

        const missingSections = validateSelections();
        const missingFields = [];
        if (!selections.name) missingFields.push('Name');
        if (!selections.email) missingFields.push('Email');

        $('#cwf-success-message').slideUp(300);

        if (missingSections.length > 0 || missingFields.length > 0) {
            const $message = $('.cwf-validation-message');
            const $list = $('#cwf-missing-sections-list');

            $list.empty();

            missingSections.forEach(section => {
                const li = $('<li></li>')
                    .text(sectionNames[section] + ' (not selected)')
                    .attr('data-tab', section)
                    .click(() => $(`.cwf-tab[data-tab="${section}"]`).click());
                $list.append(li);
            });

            missingFields.forEach(field => {
                $list.append($('<li></li>').text(field + ' (required)').css('color', '#d32f2f'));
            });

            if (missingSections.length && missingFields.length) {
                $message.find('p').text('Please complete the following selections and fields:');
            } else if (missingSections.length) {
                $message.find('p').text('Please complete the following selections before submitting:');
            } else if (missingFields.length) {
                $message.find('p').text('Please fill in all required fields:');
            }

            $message.slideDown(300);
            $('.summary-item').removeClass('missing');
            missingSections.forEach(section => $(`.summary-item[data-tab="${section}"]`).addClass('missing'));

            if ($(window).width() <= 991) {
                $('html, body').animate({ scrollTop: $message.offset().top - 100 }, 500);
            }
            return;
        }

        const nonce = $('#cwf_nonce').val();

        $.post(cwf_ajax.ajax_url, {
            action: 'cwf_submit',
            cwf_nonce: nonce,
            size: selections.size,
            ecommerce: selections.ecommerce,
            logo: selections.logo,
            copy: selections.copy,
            budget: selections.budget,
            name: selections.name,
            email: selections.email
        }, function(response) {
            if (response && response.success) {
                $('#cwf-success-message').slideDown(300, function() {
                    if ($(window).width() <= 991) {
                        const msgTop = $('#cwf-success-message').offset().top;
                        $('html, body').animate({ scrollTop: msgTop - 60 }, 500);
                    }
                });

                localStorage.removeItem('cwf_selections');
                selections = {};

                $('.cwf-options li').removeClass('selected');
                $('#cwf-name, #cwf-email').val('');

                $('#selected-size-value, #selected-ecommerce-value, #selected-logo-value, #selected-copy-value, #selected-budget-value').text('Please select');

                $('#sum-size, #sum-ecommerce, #sum-logo, #sum-copy, #sum-budget').text('Not selected');
                $('.summary-item').removeClass('selected');
                $('#sum-size-label, #sum-ecommerce-label, #sum-logo-label, #sum-copy-label, #sum-budget-label').show();

                hideValidationMessage();

                setTimeout(() => $('#cwf-success-message').slideUp(300), 10000);
                updateSummary();
            } else {
                const $msg = $('.cwf-validation-message');
                $msg.find('p').text(response && response.data ? response.data : 'An error occurred. Please try again.');
                $('#cwf-missing-sections-list').empty();
                $msg.slideDown(300);
                if ($(window).width() <= 991) {
                    $('html, body').animate({ scrollTop: $msg.offset().top - 100 }, 500);
                }
            }
        }).fail(() => {
            const $msg = $('.cwf-validation-message');
            $msg.find('p').text('Error sending request. Please try again.');
            $('#cwf-missing-sections-list').empty();
            $msg.slideDown(300);
            if ($(window).width() <= 991) {
                $('html, body').animate({ scrollTop: $msg.offset().top - 100 }, 500);
            }
        });
    });

    // Prevent enter key in name/email from submitting.
    $('#cwf-name, #cwf-email').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#cwf-submit').click();
            return false;
        }
    });

    // Newsletter signup.
    function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    $('.cwf-newsletter-signup').on('click', function() {
        const $button = $(this);
        const type = $button.data('type');
        const $emailInput = type === 'desktop' ? $('#cwf-newsletter-email-desktop') : $('#cwf-newsletter-email-mobile');
        const $messageDiv = type === 'desktop' ? $('#cwf-newsletter-message-desktop') : $('#cwf-newsletter-message-mobile');
        const email = $emailInput.val();
        const nonce = $('#cwf_newsletter_nonce').val();

        $messageDiv.removeClass('success error').hide();

        if (!email || !isValidEmail(email)) {
            $messageDiv.addClass('error').text('Please enter a valid email address').slideDown(300).delay(3000).slideUp(300);
            return;
        }

        $button.prop('disabled', true).text('Sending...');

        $.post(cwf_ajax.ajax_url, {
            action: 'cwf_newsletter_signup',
            newsletter_nonce: nonce,
            email: email
        }, function(response) {
            if (response.success) {
                $messageDiv.addClass('success').text(response.data).slideDown(300);
                $emailInput.val('');
                setTimeout(() => $('.cwf-newsletter-js').slideUp(300), 2000);
                localStorage.setItem('cwf_newsletter_closed', 'true');
            } else {
                $messageDiv.addClass('error').text(response.data).slideDown(300);
            }
            $button.prop('disabled', false).text('Sign Up');
            setTimeout(() => $messageDiv.slideUp(300), 5000);
        }).fail(() => {
            $messageDiv.addClass('error').text('Connection error. Please try again.').slideDown(300);
            $button.prop('disabled', false).text('Sign Up');
            setTimeout(() => $messageDiv.slideUp(300), 5000);
        });
    });

    $('.cwf-newsletter-email').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const type = $(this).is('#cwf-newsletter-email-desktop') ? 'desktop' : 'mobile';
            $(`.cwf-newsletter-signup[data-type="${type}"]`).click();
            return false;
        }
    });

    // Newsletter close.
    $('.cwf-newsletter-js .cwf-newsletter-close').click(function() {
        $(this).closest('.cwf-newsletter-js').slideUp(300, function() {
            localStorage.setItem('cwf_newsletter_closed', 'true');
        });
    });

    // Pre-select options from localStorage.
    Object.keys(selections).forEach(field => {
        const value = selections[field];
        $(`.cwf-options[data-field="${field}"] li[data-value="${value}"]`).addClass('selected');
        const displayMap = {
            size: '#selected-size-value',
            ecommerce: '#selected-ecommerce-value',
            logo: '#selected-logo-value',
            copy: '#selected-copy-value',
            budget: '#selected-budget-value'
        };
        if (displayMap[field]) {
            $(displayMap[field]).text(value);
        }
    });

    // Initial image load.
    const currentTab = $('.cwf-tab.active').data('tab');
    loadTabImage(currentTab);

    if (currentTab === 'size') {
        $('#cwf-tab-heading').html('<span class="cwf-text-italic">You\'re</span> in control');
        $('#cwf-tab-subheading').text('Choose the components you need.');
        $('.cwf-text-box').show().removeClass('cwf-text-box-summary');
    } else if (currentTab === 'summary') {
        $('#cwf-tab-heading').html('You\'re <span class="cwf-text-italic">ready.</span>');
        $('#cwf-tab-subheading').text('');
        $('.cwf-text-box').show().addClass('cwf-text-box-summary');
    } else {
        $('.cwf-text-box').hide();
    }

    if (currentTab === 'summary') {
        updateSummary();
        const missing = validateSelections();
        if (missing.length) showValidationMessage(missing);
    }

    updateDesktopStepArrow();
    updateTabUnderline();
    $('.summary-item').css('cursor', 'pointer');

    $('#cwf-name, #cwf-email').on('input', function() {
        const missingSections = validateSelections();
        const name = $('#cwf-name').val();
        const email = $('#cwf-email').val();
        if (missingSections.length === 0 && name && email) {
            hideValidationMessage();
        }
    });

    $(window).on('resize', function() {
        updateDesktopStepArrow();
        updateTabUnderline();
    });

    if ($(window).width() <= 991) {
        setTimeout(() => {
            updateScrollIndicator();
            setupScrollListenerForActiveTab();
        }, 300);

        $(document).on('tabChanged', () => {
            setTimeout(() => {
                updateScrollIndicator();
                setupScrollListenerForActiveTab();
            }, 200);
        });

        $(window).on('resize', () => {
            setTimeout(updateScrollIndicator, 100);
            updateDesktopStepArrow();
        });
        $(document).on('click', '.cwf-options li, .summary-item', () => setTimeout(updateScrollIndicator, 150));
    }
});