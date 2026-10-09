<?php if ( ! defined( 'ABSPATH' ) ) exit; // No direct access. ?>

<div class="cwf-tab-content" id="summary">
    <div class="cwf-tab-main">
        <div class="cwf-tab-top">
            <div class="cwf-selection-header">
                <span class="cwf-section-title">Summary</span>
                <span class="cwf-selected-value">Receive a quote</span>
            </div>

     

            <div class="cwf-summary-box">
				       <!-- Validation Message Area -->
            <div class="cwf-validation-message" id="cwf-validation-message" style="display: none;">
                <p>Please complete the following sections before submitting:</p>
                <ul id="cwf-missing-sections-list"></ul>
            </div>

            
				<ul class="cwf-options">
					<li class="summary-item" data-tab="size">
                        <div class="cwf-option-row">                        
                            <div class="cwf-option-title">
                                <span class="section-label" id="sum-size-label">Size:</span>
                                <span id="sum-size">Not selected</span>
                            </div>
                        </div>
					</li>
					<li class="summary-item" data-tab="ecommerce">
                        <div class="cwf-option-row">                        
                            <div class="cwf-option-title">
                                <span class="section-label" id="sum-ecommerce-label">Ecommerce:</span>
                                <span id="sum-ecommerce">Not selected</span>
                            </div>
                        </div>
					</li>
					<li class="summary-item" data-tab="logo">
                        <div class="cwf-option-row">                        
                            <div class="cwf-option-title">
                                <span class="section-label" id="sum-logo-label">Logo Design:</span>
                                <span id="sum-logo">Not selected</span>
                            </div>
                        </div>
					</li>
					<li class="summary-item" data-tab="copy">
                        <div class="cwf-option-row">                        
                            <div class="cwf-option-title">
                                <span class="section-label" id="sum-copy-label">Copy Writing:</span>
                                <span id="sum-copy">Not selected</span>
                            </div>
                        </div>
                    </li>				
					<li class="summary-item" data-tab="budget">
						<div class="cwf-option-row">                        
                            <div class="cwf-option-title">
                                <span class="section-label" id="sum-budget-label">Budget:</span>
                                <span id="sum-budget">Not selected</span>
                            </div>
                        </div>
					</li>
				</ul>                
          
			     <div class="cwf-tab-bottom">
					 <!-- Success Message Area -->
            <div class="cwf-success-message" id="cwf-success-message" style="display: none;">
                <p><span class="cwf-option-plus-success"></span> Quote request sent successfully!</p>
                <p class="cwf-success-subtext">We'll be in touch soon. Check your email for confirmation.</p>
            </div>
			<div class="cwf-quote-form">			
			
			 <h4>To receive a quote, please complete the form below.</h4>
			<div class="cwf-quote-form-inputs">
                <input type="text" id="cwf-name" placeholder="Name">
                <input type="email" id="cwf-email" placeholder="Email">
                <!-- Add nonce field -->
                <?php wp_nonce_field('cwf_quote_submit', 'cwf_nonce'); ?>
			</div>	
				<div class="quote-submit-btn">
					<button id="cwf-submit">Submit</button>
				</div>
              </div>
			</div>
        </div>
           
        </div>      
   
    </div>
</div>