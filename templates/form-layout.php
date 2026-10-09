<?php
if ( ! defined( 'ABSPATH' ) ) exit; // No direct access.
?>

<div class="cwf">
    <div class="cwf-header">
        <div class="cwf-logo">
            <picture>
                <source srcset="<?php echo esc_url( CWF_PLUGIN_URL . 'assets/images/cohort-logo-mobile.svg' ); ?>"
                    media="(max-width: 991px)">
                <img src="<?php echo esc_url( CWF_PLUGIN_URL . 'assets/images/cohort-logo.svg' ); ?>"
                    alt="Cohort Creative">
            </picture>
        </div>
        <!--<div class="cwf-close">✕</div>-->
    </div>

    <div class="cwf-tabs">
        <div class="cwf-tab-underline" aria-hidden="true"></div>
        <!-- Desktop tabs -->
        <div class="cwf-tab active" data-tab="size">Size</div>
        <div class="cwf-tab" data-tab="ecommerce">Ecommerce</div>
        <div class="cwf-tab" data-tab="logo">Logo Design</div>
        <div class="cwf-tab" data-tab="copy">Copy Writing</div>
        <div class="cwf-tab" data-tab="budget">Budget</div>
        <div class="cwf-tab" data-tab="summary">Summary</div>
    </div>

    <div class="cwf-wrapper">
        <div class="cwf-content">
            <!-- LEFT IMAGE with overlay text -->
            <div class="cwf-left">
                <!-- Desktop Floating Icons (hidden on mobile) -->
                <div class="cwf-floating-icons">
                    <!-- Desktop step arrow - guides user to current step (desktop only) -->
                    <div class="cwf-desktop-step-arrow" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="23" height="25" viewBox="0 0 23 25" fill="none">
                            <mask id="mask0_cwf_step_arrow" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="-1" y="0" width="24" height="25">
                                <path d="M22.283 24.718L22.283 9.74021e-07L1.08046e-06 0L0 24.718L22.283 24.718Z" fill="white"/>
                            </mask>
                            <g mask="url(#mask0_cwf_step_arrow)">
                                <path d="M11.1424 9.74021e-07L11.1424 23.393" stroke="#9D4D22" stroke-miterlimit="10"/>
                                <path d="M21.9305 13.223L11.1425 24.011L0.354463 13.223" stroke="#9D4D22" stroke-miterlimit="10"/>
                            </g>
                        </svg>
                    </div>
                    <div class="cwf-floating-icon active" data-tab="size">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/size-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="ecommerce">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/ecommerce-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="logo">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/logo-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="copy">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/copy-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="budget">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/budget-icon.svg' ); ?>
                    </div>
                </div>

                <!-- Dynamic Text Overlay -->
                <div class="cwf-text-box" id="cwf-tab-text-box">
                    <h1 id="cwf-tab-heading">
                        <span class="cwf-text-italic">You're</span> in control
                    </h1>
                    <p class="cwf-instruction" id="cwf-tab-subheading">Choose the components you need.</p>
                </div>

                <!-- Background Image -->
                <img id="cwf-tab-image" src="<?php echo esc_url( CWF_PLUGIN_URL . 'assets/images/size.jpg' ); ?>"
                    alt="Website configuration">

                <!-- NEWSLETTER SECTION - DESKTOP -->
                <div class="cwf-newsletter-js cwf-newsletter">
                    <p>Sign up to our newsletter for a very special welcome gift.</p>
                    <div class="cwf-newsletter-form">
                        <div>
                            <input type="email" placeholder="Enter your email address" id="cwf-newsletter-email-desktop" class="cwf-newsletter-email">
                        </div>
                        <div>
                            <button class="cwf-newsletter-btn cwf-newsletter-signup" id="cwf-newsletter-signup-desktop" data-type="desktop">Sign Up</button>
                        </div>
                        <div class="cwf-newsletter-close">✕</div>
                    </div>
                    <!-- Newsletter message (hidden by default) -->
                    <div class="cwf-newsletter-message" id="cwf-newsletter-message-desktop" style="display: none;"></div>
                </div>
            </div>

            <!-- RIGHT OPTIONS -->
            <div class="cwf-right">
                <!-- Mobile Floating Icons (visible only on mobile) -->
                <div class="cwf-floating-icons--mobile">
                    <div class="cwf-floating-icon active" data-tab="size">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/size-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="ecommerce">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/ecommerce-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="logo">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/logo-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="copy">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/copy-icon.svg' ); ?>
                    </div>
                    <div class="cwf-floating-icon" data-tab="budget">
                        <?php echo file_get_contents( CWF_PLUGIN_DIR . 'assets/icons/budget-icon.svg' ); ?>
                    </div>
                </div>
                
                <!-- Scroll Indicator Arrow for Mobile -->
                <div class="cwf-scroll-indicator">
                    <svg xmlns="http://www.w3.org/2000/svg" width="23" height="25" viewBox="0 0 23 25" fill="none">
                        <mask id="mask0_125_209" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="-1" y="0" width="24" height="25">
                            <path d="M22.283 24.718L22.283 9.74021e-07L1.08046e-06 0L0 24.718L22.283 24.718Z" fill="white"/>
                        </mask>
                        <g mask="url(#mask0_125_209)">
                            <path d="M11.1424 9.74021e-07L11.1424 23.393" stroke="#9D4D22" stroke-miterlimit="10"/>
                            <path d="M21.9305 13.223L11.1425 24.011L0.354463 13.223" stroke="#9D4D22" stroke-miterlimit="10"/>
                        </g>
                    </svg>
                </div>
                
                <?php include 'tabs/size.php'; ?>
                <?php include 'tabs/ecommerce.php'; ?>
                <?php include 'tabs/logo.php'; ?>
                <?php include 'tabs/copy.php'; ?>
                <?php include 'tabs/budget.php'; ?>
                <?php include 'tabs/summary.php'; ?>
            </div>

            <!-- NEWSLETTER SECTION - MOBILE -->
            <div class="cwf-newsletter-js cwf-newsletter--mobile">
                <div class="top-part">
                    <div>
                        <p>Sign up to our newsletter for a very special welcome gift.</p>
                    </div>
                    <div class="cwf-newsletter-close">✕</div>					
                </div>
                <div class="cwf-newsletter-form">
                    <input type="email" placeholder="Enter your email address" id="cwf-newsletter-email-mobile" class="cwf-newsletter-email">
                    <div>
                        <button class="cwf-newsletter-btn cwf-newsletter-signup" id="cwf-newsletter-signup-mobile" data-type="mobile">Sign Up</button>
                    </div>
                </div>
                <!-- Newsletter message (hidden by default) -->
                <div class="cwf-newsletter-message" id="cwf-newsletter-message-mobile" style="display: none;"></div>
            </div>
            
            <!-- Newsletter nonce field (placed once at the end) -->
            <?php wp_nonce_field('cwf_newsletter_action', 'cwf_newsletter_nonce'); ?>
        </div>
    </div>
</div>